<?php
// get_survey_responses.php - Fetch survey responses for a booking (AJAX API)
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$booking_id = sanitize($_GET['booking_id'] ?? '');
$customer_id = $_SESSION['customer_id'];

if (empty($booking_id)) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT s.question_id, s.response, q.question_type 
                           FROM surveys s 
                           JOIN questions q ON s.question_id = q.id 
                           WHERE s.booking_id = ? AND s.customer_id = ?");
    $stmt->execute([$booking_id, $customer_id]);
    $responses = $stmt->fetchAll();

    if (empty($responses)) {
        echo json_encode(['success' => false, 'message' => 'No feedback found for this booking.']);
    } else {
        echo json_encode(['success' => true, 'responses' => $responses]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
