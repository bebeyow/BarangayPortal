<?php
include "connect.php";

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No feedback ID provided']);
    exit;
}

$feedbackId = intval($_GET['id']);
$query = "SELECT * FROM feedback WHERE id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $feedbackId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $feedback = $result->fetch_assoc();
    echo json_encode([
        'success' => true, 
        'feedback' => [
            'id' => $feedback['id'],
            'client_name' => htmlspecialchars($feedback['client_name']),
            'type' => htmlspecialchars($feedback['type']),
            'subject' => htmlspecialchars($feedback['subject']),
            'message' => nl2br(htmlspecialchars($feedback['message'])),
            'response' => $feedback['response'] ? nl2br(htmlspecialchars($feedback['response'])) : null,
            'status' => htmlspecialchars($feedback['status']),
            'created_at' => date('M d, Y h:i A', strtotime($feedback['created_at'])),
            'responded_at' => $feedback['responded_at'] ? date('M d, Y h:i A', strtotime($feedback['responded_at'])) : null
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Feedback not found']);
}

$stmt->close();
?>