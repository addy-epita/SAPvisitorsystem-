<?php
/**
 * API endpoint to verify QR token
 * GET endpoint with token parameter
 * Returns visitor details if valid, error if already checked out or invalid
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, ['message' => 'Method not allowed'], 405);
}

// Get token from query parameter
$token = $_GET['token'] ?? '';

if (empty($token)) {
    jsonResponse(false, ['message' => 'Token parameter is required'], 400);
}

// Sanitize token
$token = sanitize($token);

try {
    $db = getDB();

    // Find visitor by QR token
    $stmt = $db->prepare("
        SELECT
            id,
            first_name,
            last_name,
            company,
            reason,
            host_email,
            host_name,
            visitor_email,
            arrival_time,
            expected_duration,
            departure_time,
            status,
            qr_token,
            TIMESTAMPDIFF(MINUTE, arrival_time, NOW()) as current_duration_minutes
        FROM visitors
        WHERE qr_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $visitor = $stmt->fetch();

    if (!$visitor) {
        jsonResponse(false, [
            'valid' => false,
            'message' => 'Invalid QR code. Visitor not found.'
        ], 404);
    }

    // Check if already checked out
    if ($visitor['status'] === 'checked_out') {
        jsonResponse(false, [
            'valid' => false,
            'already_checked_out' => true,
            'checkout_time' => $visitor['departure_time'],
            'message' => 'This visit has already been checked out on ' . $visitor['departure_time']
        ], 400);
    }

    // Check if unconfirmed (end of day escalation)
    if ($visitor['status'] === 'unconfirmed') {
        jsonResponse(false, [
            'valid' => false,
            'unconfirmed' => true,
            'message' => 'This visit was marked as unconfirmed. Please contact reception.'
        ], 400);
    }

    // Calculate expected departure time
    $expectedDeparture = date('Y-m-d H:i:s', strtotime($visitor['arrival_time'] . ' + ' . $visitor['expected_duration'] . ' minutes'));

    // Return visitor details
    jsonResponse(true, [
        'valid' => true,
        'visitor' => [
            'id' => $visitor['id'],
            'first_name' => $visitor['first_name'],
            'last_name' => $visitor['last_name'],
            'full_name' => $visitor['first_name'] . ' ' . $visitor['last_name'],
            'company' => $visitor['company'],
            'reason' => $visitor['reason'],
            'host_email' => $visitor['host_email'],
            'host_name' => $visitor['host_name'],
            'arrival_time' => $visitor['arrival_time'],
            'expected_duration_minutes' => $visitor['expected_duration'],
            'expected_departure_time' => $expectedDeparture,
            'current_duration_minutes' => $visitor['current_duration_minutes'],
            'qr_token' => $visitor['qr_token']
        ],
        'message' => 'QR code is valid'
    ]);

} catch (Exception $e) {
    error_log("QR verification error: " . $e->getMessage());
    jsonResponse(false, ['message' => 'Error verifying QR code. Please try again.'], 500);
}
