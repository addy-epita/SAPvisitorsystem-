<?php
/**
 * API endpoint to process check-in
 * Receives POST data, validates inputs, generates QR token, saves to database
 */

require_once __DIR__ . '/../includes/db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, ['message' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(false, ['message' => 'Invalid JSON input'], 400);
}

// Validate required fields
$required = ['first_name', 'last_name', 'company', 'reason', 'host_email'];
$missing = [];

foreach ($required as $field) {
    if (empty($input[$field])) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    jsonResponse(false, ['message' => 'Missing required fields: ' . implode(', ', $missing)], 400);
}

// Sanitize inputs
$firstName = sanitize($input['first_name']);
$lastName = sanitize($input['last_name']);
$company = sanitize($input['company']);
$reason = sanitize($input['reason']);
$hostEmail = filter_var($input['host_email'], FILTER_SANITIZE_EMAIL);
$visitorEmail = !empty($input['visitor_email']) ? filter_var($input['visitor_email'], FILTER_SANITIZE_EMAIL) : null;
$expectedDuration = intval($input['expected_duration'] ?? 180);
$hostName = !empty($input['host_name']) ? sanitize($input['host_name']) : null;

// Validate email
if (!filter_var($hostEmail, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, ['message' => 'Invalid host email address'], 400);
}

if ($visitorEmail && !filter_var($visitorEmail, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, ['message' => 'Invalid visitor email address'], 400);
}

// Validate expected duration (allow 1-480 minutes / 8 hours)
if ($expectedDuration < 1 || $expectedDuration > 480) {
    $expectedDuration = 180; // Default to 3 hours
}

try {
    $db = getDB();

    // Generate unique QR token
    $qrToken = generateQRToken();

    // Ensure token is unique
    $attempts = 0;
    $maxAttempts = 5;

    while ($attempts < $maxAttempts) {
        $stmt = $db->prepare("SELECT id FROM visitors WHERE qr_token = ?");
        $stmt->execute([$qrToken]);
        if (!$stmt->fetch()) {
            break;
        }
        $qrToken = generateQRToken();
        $attempts++;
    }

    if ($attempts >= $maxAttempts) {
        throw new Exception('Failed to generate unique QR token');
    }

    // Insert visitor record
    $stmt = $db->prepare("
        INSERT INTO visitors (
            first_name, last_name, company, reason, host_email, host_name,
            visitor_email, arrival_time, expected_duration, status,
            checkin_method, qr_token, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'checked_in', 'kiosk', ?, NOW())
    ");

    $stmt->execute([
        $firstName,
        $lastName,
        $company,
        $reason,
        $hostEmail,
        $hostName,
        $visitorEmail,
        $expectedDuration,
        $qrToken
    ]);

    $visitorId = $db->lastInsertId();

    // Build QR code URL
    $qrUrl = SITE_URL . '/checkout.php?token=' . urlencode($qrToken);

    // Log the check-in
    logAudit('checkin', $visitorId, "Visitor checked in: $firstName $lastName from $company");

    // Return success response
    jsonResponse(true, [
        'visitor_id' => $visitorId,
        'qr_token' => $qrToken,
        'qr_url' => $qrUrl,
        'message' => 'Check-in successful'
    ]);

} catch (Exception $e) {
    error_log("Check-in error: " . $e->getMessage());
    jsonResponse(false, ['message' => 'Error processing check-in. Please try again.'], 500);
}
