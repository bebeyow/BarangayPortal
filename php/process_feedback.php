<?php
session_start();
include 'connect.php';

// Check if user is authenticated
if (!isset($_SESSION['client_authenticated']) || $_SESSION['client_authenticated'] !== true) {
    header("Location: mainDashboard.php");
    exit();
}

$client_id = $_SESSION['clientId'];
$client_name = $_SESSION['clientName'];

// Initialize variables
$error = '';
$success = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['type'], $_POST['subject'], $_POST['message'])) {
    $type = trim($_POST['type']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate inputs
    if (empty($subject) || empty($message)) {
        $error = "Subject and message are required.";
    } else {
        // Insert feedback into database
        $stmt = $connect->prepare("INSERT INTO feedback (client_id, client_name, type, subject, message, status, created_at) 
                                  VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
        $stmt->bind_param("issss", $client_id, $client_name, $type, $subject, $message);
        
        if ($stmt->execute()) {
            $success = "Your " . ($type === 'complaint' ? 'complaint' : 'feedback') . " has been submitted successfully!";
        } else {
            $error = "Error submitting your feedback. Please try again.";
        }
        $stmt->close();
    }
}

// Redirect back to dashboard with status message
if (!empty($success)) {
    $_SESSION['success_message'] = $success;
} elseif (!empty($error)) {
    $_SESSION['error_message'] = $error;
}

header("Location: mainDashboard.php");
exit();
?>