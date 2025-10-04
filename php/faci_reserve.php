<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['client_authenticated']) || $_SESSION['client_authenticated'] !== true) {
    header("Location: mainDashboard.php");
    exit;
}

$client_id = $_SESSION['clientId'];

// Fetch facility details
$facilities = [];
$stmt = $connect->prepare("SELECT * FROM facilities");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $facilities[] = $row;
}

// Fetch all unavailable dates per facility
$facility_unavailable = [];
$date_stmt = $connect->prepare("SELECT facility_id, unavailable_date, unavailable_startTime, unavailable_endTime FROM facility_unavailability");
$date_stmt->execute();
$date_result = $date_stmt->get_result();
while ($row = $date_result->fetch_assoc()) {
    $facility_id = $row['facility_id'];
    $date = $row['unavailable_date'];
    $start_time = $row['unavailable_startTime'];
    $end_time = $row['unavailable_endTime'];

    if (!isset($facility_unavailable[$facility_id])) {
        $facility_unavailable[$facility_id] = [];
    }

    if (!isset($facility_unavailable[$facility_id][$date])) {
        $facility_unavailable[$facility_id][$date] = [];
    }

    $facility_unavailable[$facility_id][$date][] = [
        'start' => $start_time,
        'end' => $end_time
    ];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['facility_id']) && isset($_POST['reservation_date'])) {
    $facility_id = $_POST['facility_id'];
    $reservation_date = $_POST['reservation_date'];

    // Insert the facility reservation into the database
    $stmt = $connect->prepare("INSERT INTO facilreserve_db (client_id, facility_id, reservation_date, startTime, endTime status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    if ($stmt === false) {
        die('MySQL prepare error: ' . $connect->error);
    }

    $start_time = $_POST['startTime'];
    $end_time = $_POST['endTime'];

    $stmt->bind_param("iisss", $client_id, $facility_id, $reservation_date, $start_time, $end_time);

    if ($stmt->execute()) {
        header("Location: mainDashboard.php"); // Redirect after success
        exit;
    } else {
        echo "Error processing request: " . $stmt->error;
    }
}
?>