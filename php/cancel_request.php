<?php
session_start();
include "connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_POST['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'No request ID provided']);
    exit;
}

$requestId = intval($_POST['request_id']);
$userId = intval($_SESSION['user_id']);

// Verify the reservation belongs to the user
$checkQuery = $connect->prepare("SELECT d.id FROM docurequests_db r 
                               JOIN users u ON r.email = u.email 
                               WHERE r.id = ? AND u.user_id = ?");
$checkQuery->bind_param("ii", $reservationId, $userId);
$checkQuery->execute();
$checkResult = $checkQuery->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Reservation not found or not authorized']);
    exit;
}

// Delete the reservation
$deleteQuery = $connect->prepare("DELETE FROM reservations WHERE id = ?");
$deleteQuery->bind_param("i", $reservationId);

if ($deleteQuery->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}