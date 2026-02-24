<?php
/**
 * API endpoint to process checkout
 * Accepts qr_token or visitor_id, verifies visitor is checked in, updates departure_time and status
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, ['message' => 'Method not allowed'], 405);
}

// Get input - support both JSON and form data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$input = [];

if (strpos($contentType, 'application/json') !== false) {
    // JSON input
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    // Form data input
    $input = $_POST;
}

if (empty($input)) {
    jsonResponse(false, ['message' => 'Invalid input data'], 400);
}

try {
    $db = getDB();
    $visitor = null;
    $checkoutMethod = sanitize($input['method'] ?? 'qr_rescan');

    // Check if QR token is provided
    if (!empty($input['qr_token'])) {
        $qrToken = sanitize($input['qr_token']);

        $stmt = $db->prepare("
            SELECT id, first_name, last_name, company, status, qr_token
            FROM visitors
            WHERE qr_token = ?
            LIMIT 1
        ");
        $stmt->execute([$qrToken]);
        $visitor = $stmt->fetch();

        if (!$visitor) {
            jsonResponse(false, ['message' => 'Invalid QR code. Visitor not found.'], 404);
        }
    }
    // Check if manual entry (name + company) is provided
    elseif (!empty($input['first_name']) && !empty($input['last_name']) && !empty($input['company'])) {
        $firstName = sanitize($input['first_name']);
        $lastName = sanitize($input['last_name']);
        $company = sanitize($input['company']);

        // Find active visit by name and company (checked in today)
        $stmt = $db->prepare("
            SELECT id, first_name, last_name, company, status, qr_token
            FROM visitors
            WHERE first_name = ?
            AND last_name = ?
            AND company = ?
            AND status = 'checked_in'
            AND DATE(arrival_time) = CURDATE()
            ORDER BY arrival_time DESC
            LIMIT 1
        ");
        $stmt->execute([$firstName, $lastName, $company]);
        $visitor = $stmt->fetch();

        if (!$visitor) {
            jsonResponse(false, [
                'message' => 'No active visit found for this person today. Please check your details or contact reception.'
            ], 404);
        }
    }
    // Check if visitor_id is provided
    elseif (!empty($input['visitor_id'])) {
        $visitorId = intval($input['visitor_id']);

        $stmt = $db->prepare("
            SELECT id, first_name, last_name, company, status, qr_token
            FROM visitors
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$visitorId]);
        $visitor = $stmt->fetch();

        if (!$visitor) {
            jsonResponse(false, ['message' => 'Visitor not found.'], 404);
        }
    }
    else {
        jsonResponse(false, ['message' => 'Please provide QR token, visitor ID, or name and company details.'], 400);
    }

    // Check if visitor is already checked out
    if ($visitor['status'] === 'checked_out') {
        jsonResponse(false, [
            'message' => 'This visit has already been checked out.',
            'already_checked_out' => true
        ], 400);
    }

    // Update visitor record with checkout
    $stmt = $db->prepare("
        UPDATE visitors
        SET status = 'checked_out',
            departure_time = NOW(),
            checkout_method = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$checkoutMethod, $visitor['id']]);

    // Log the checkout
    logAudit('checkout', $visitor['id'], "Visitor checked out: {$visitor['first_name']} {$visitor['last_name']} via $checkoutMethod");

    // Calculate visit duration
    $stmt = $db->prepare("
        SELECT
            TIMESTAMPDIFF(MINUTE, arrival_time, departure_time) as duration_minutes
        FROM visitors
        WHERE id = ?
    ");
    $stmt->execute([$visitor['id']]);
    $duration = $stmt->fetch();

    // Check if request is from form or AJAX/API
    $isFormRequest = strpos($contentType, 'application/json') === false;

    if ($isFormRequest) {
        // Redirect to confirmation page for form submissions
        header("Location: ../confirmation.php?type=checkout&token=" . urlencode($visitor['qr_token'] ?? ''));
        exit;
    } else {
        // Return JSON response for API requests
        jsonResponse(true, [
            'visitor_id' => $visitor['id'],
            'name' => $visitor['first_name'] . ' ' . $visitor['last_name'],
            'company' => $visitor['company'],
            'duration_minutes' => $duration['duration_minutes'] ?? 0,
            'message' => 'Checkout successful. Thank you for visiting!'
        ]);
    }

} catch (Exception $e) {
    error_log("Checkout error: " . $e->getMessage());

    // Check if request is from form or AJAX/API
    $isFormRequest = strpos($contentType, 'application/json') === false;

    if ($isFormRequest) {
        // Show error page for form submissions
        header("Location: ../checkout.php?error=1&message=" . urlencode($e->getMessage()));
        exit;
    } else {
        jsonResponse(false, ['message' => 'Error processing checkout. Please try again.'], 500);
    }
}
