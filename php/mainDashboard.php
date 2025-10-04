<?php
session_start();
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'], $_POST['password'])) {
    $name = trim($_POST['name']);
    $password = $_POST['password'];

    $stmt = $connect->prepare("SELECT * FROM clients WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['client_authenticated'] = true;
            $_SESSION['clientId'] = $user['id'];
            $_SESSION['clientName'] = $name;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $connect->prepare("INSERT INTO clients (name, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $hashed_password);
        if ($stmt->execute()) {
            $_SESSION['client_authenticated'] = true;
            $_SESSION['clientId'] = $connect->insert_id;
            $_SESSION['clientName'] = $name;
        } else {
            $error = "Signup failed.";
        }
    }
}

$authenticated = isset($_SESSION['client_authenticated']) && $_SESSION['client_authenticated'] === true;
$client_name = $_SESSION['clientName'] ?? '';

// Fetch the bookings and requests for the authenticated client
$reserves = [];
$requests = [];

if (isset($_SESSION['clientId'])) {
    $client_id = $_SESSION['clientId'];}

    // Fetch client bookings
 /*   $stmt = $connect->prepare("SELECT * FROM bookings WHERE client_id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($booking = $result->fetch_assoc()) {
        $bookings[] = $booking;
    }*/
    $stmt = $connect->prepare("
    SELECT fr.*, f.name AS facility_name 
    FROM facilreserve_db fr
    JOIN facilities f ON fr.facility_id = f.id
    WHERE fr.client_id = ?
");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$reserves = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch client document requests
    $stmt = $connect->prepare("
    SELECT dr.*, d.name AS document_name, fee
    FROM docurequests_db dr
    JOIN documents d ON dr.document_id = d.id
    WHERE dr.client_id = ?
");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (isset($_POST['delete_request'])) {
    $deleteId = $_POST['delete_id'];
    $stmt = $connect->prepare("DELETE FROM docurequests_db WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Error deleting Document: " . $connect->error;
    }
}

$documents = [];
$stmt = $connect->prepare("SELECT * FROM documents");
$stmt->execute();
$result = $stmt->get_result();
while ($document = $result->fetch_assoc()) {
    $documents[] = $document;
}

$facilities = [];
$stmt = $connect->prepare("SELECT * FROM facilities");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $facilities[] = $row;
}

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

// Handle document request cancellation
if (isset($_POST['delete_request'])) {
    $request_id = $_POST['request_id'];
    $stmt = $connect->prepare("DELETE FROM docurequests_db WHERE id = ? AND client_id = ?");
    $stmt->bind_param("ii", $request_id, $_SESSION['clientId']);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Document request cancelled successfully";
    } else {
        $_SESSION['error_message'] = "Error cancelling document request";
    }
    $stmt->close();
    header("Location: mainDashboard.php");
    exit();
}

// Handle facility reservation cancellation
if (isset($_POST['delete_reservation'])) {
    $reserve_id = $_POST['reserve_id'];
    $stmt = $connect->prepare("DELETE FROM facilreserve_db WHERE facil_id = ? AND client_id = ?");
    $stmt->bind_param("ii", $reserve_id, $_SESSION['clientId']);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Reservation cancelled successfully";
    } else {
        $_SESSION['error_message'] = "Error cancelling reservation";
    }
    $stmt->close();
    header("Location: mainDashboard.php");
    exit();
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['facility_id'], $_POST['reservation_date'], $_POST['startTime'], $_POST['endTime'])) {
    $facility_id = $_POST['facility_id'];
    $reservation_date = $_POST['reservation_date'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $purpose = $_POST['purpose'];

    // Check for time conflicts with admin-set unavailable times
    $conflictCheck = $connect->prepare("
        SELECT 1 FROM facility_unavailability 
        WHERE facility_id = ? 
        AND unavailable_date = ?
        AND (
            (? < unavailable_endTime AND ? > unavailable_startTime)
        )
    ");
    $conflictCheck->bind_param("isss", $facility_id, $reservation_date, $startTime, $endTime);
    $conflictCheck->execute();
    
    if ($conflictCheck->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "The selected time conflicts with an unavailable time slot set by admin";
        header("Location: mainDashboard.php");
        exit;
    }

    // Proceed with reservation if no conflicts
    $stmt = $connect->prepare("INSERT INTO facilreserve_db 
                             (client_id, facility_id, reservation_date, startTime, endTime, purpose, status) 
                             VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    
    if ($stmt === false) {
        die('MySQL prepare error: ' . $connect->error);
    }

    $stmt->bind_param("iissss", $client_id, $facility_id, $reservation_date, $startTime, $endTime, $purpose);

    if ($stmt->execute()) {
        header("Location: mainDashboard.php");
        exit;
    } else {
        echo "Error processing request: " . $stmt->error;
    }
}

// Add this to your existing PHP code where you fetch other data
$feedbacks = [];
if (isset($_SESSION['clientId'])) {
    $client_id = $_SESSION['clientId'];
    $stmt = $connect->prepare("SELECT * FROM feedback WHERE client_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Client Dashboard</title>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary-color: #368398;
            --secondary-color: #4e9cf9;
            --accent-color: #e74c3c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo-container {
            width: 180px;
            height: 180px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 3px solid var(--secondary-color);
        }

        .user-info {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 20px;
        }

        .user-info h3 {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-info p {
            font-size: 14px;
            opacity: 0.8;
        }

        .nav-menu {
            width: 100%;
            padding: 0 20px;
        }

        .nav-item {
            margin-bottom: 10px;
            list-style: none;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
            font-weight: 500;
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 280px;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .user-badge {
            background-color: white;
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .user-badge i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: white;
        }

        .card-icon.reservation {
            background: linear-gradient(45deg, #4e54c8, #8f94fb);
        }

        .card-icon.document {
            background: linear-gradient(45deg, #11998e, #38ef7d);
        }

        .card-icon.feedback {
            background: linear-gradient(45deg, #f46b45, #eea849);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .card-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }

        .card-action {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .card-action:hover {
            background-color: #2a7285;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 15px;
            width: 100%;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .form-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .form-btn:hover {
            background-color: #2a7285;
        }

        /* Requests Section */
        .section {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .request-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .request-item:hover {
            border-color: var(--primary-color);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .request-title {
            font-weight: 600;
            color: var(--dark-color);
        }

        .request-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .request-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .request-actions {
            text-align: right;
        }

        .btn-cancel1 {
            padding: 8px 15px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-cancel1:hover {
            background-color: #c0392b;
        }

        .btn-cancel {
            padding: 8px 16px;
            background-color: #e74c3c;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        .btn-cancel:hover {
            background-color: #c0392b;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #999;
        }

        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #ddd;
        }

        /* Login Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .login-box {
            background-color: white;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .login-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            color: var(--primary-color);
        }

        .login-input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .login-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
            background-color: #2a7285;
        }

         .time-selection-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .time-select-group {
        flex: 1;
    }
    
    .time-select-container {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .time-select {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }
    
    .time-separator {
        font-weight: bold;
        margin: 0 5px;
    }
    
    .time-label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 500;
        color: var(--dark-color);
    }

    /* Date Picker Styles */
    .date-picker-container {
        position: relative;
        margin-bottom: 20px;
    }
    
    .date-picker-label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 500;
        color: var(--dark-color);
    }
    
    .date-picker-input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        background-color: white;
        cursor: pointer;
        transition: all 0.3s;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="%23368398" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>');
        background-repeat: no-repeat;
        background-position: 15px center;
    }
    
    .date-picker-input:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 2px rgba(54, 131, 152, 0.2);
    }
    
    /* Alert Styles */
    .alert-overlap {
        display: none;
        padding: 10px;
        margin-top: 10px;
        border-radius: 6px;
        background-color: #f8d7da;
        color: #721c24;
        font-size: 13px;
        border: 1px solid #f5c6cb;
    }
    
    /* Flatpickr Overrides */
    .flatpickr-calendar {
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        font-family: 'Poppins', sans-serif;
    }
    
    .flatpickr-day.selected, 
    .flatpickr-day.selected:hover {
        background: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .flatpickr-day.today {
        border-color: var(--primary-color);
    }
    
    .flatpickr-day.today:hover {
        background: var(--primary-color);
        color: white;
    }
    
    .flatpickr-weekdays {
        background: var(--primary-color);
    }
    
    .flatpickr-weekday {
        color: white;
    }

    .alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-success {
    background-color: #dff0d8;
    color: #3c763d;
    border: 1px solid #d6e9c6;
}

.alert-danger {
    background-color: #f2dede;
    color: #a94442;
    border: 1px solid #ebccd1;
}

 .modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: white;
    border-radius: 12px;
    padding: 30px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    animation: fadeIn 0.3s ease;
}

.close-modal {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 24px;
    color: var(--accent-color);
    cursor: pointer;
    background: none;
    border: none;
    z-index: 10;
}

/* Hide all service forms by default */
.service-form {
    display: none;
    width: 100%
}

textarea.form-control {
    min-height: 100px;
}

.feedback-message, .admin-response {
    background-color: #f9f9f9;
    padding: 10px 15px;
    border-radius: 6px;
    margin: 10px 0;
}

.message-content, .response-content {
    white-space: pre-wrap;
    word-wrap: break-word;
}

.no-response {
    text-align: center;
    color: #999;
    font-size: 14px;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-reviewed {
    background-color: #cce5ff;
    color: #004085;
}

.status-resolved {
    background-color: #d4edda;
    color: #155724;
}

/* Sweet Alert Style */
.swal2-popup {
    font-family: 'Poppins', sans-serif !important;
    border-radius: 12px !important;
}

.swal2-title {
    font-size: 22px !important;
    font-weight: 600 !important;
}

.swal2-content {
    font-size: 16px !important;
}

.swal2-confirm {
    background-color: var(--primary-color) !important;
    border: none !important;
    box-shadow: none !important;
    padding: 10px 24px !important;
    border-radius: 6px !important;
    font-weight: 500 !important;
}

.swal2-confirm:hover {
    background-color: #2a7285 !important;
}

.swal2-cancel {
    background-color: #6c757d !important;
    border: none !important;
    box-shadow: none !important;
    padding: 10px 24px !important;
    border-radius: 6px !important;
    font-weight: 500 !important;
}

.swal2-cancel:hover {
    background-color: #5a6268 !important;
}
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: -1;">
        <img src="logo1.png" style="opacity: 0.130; position: absolute; top: 50%; left: 63%; transform: translate(-50%, -50%); width: 50%; pointer-events: none;">
    </div>
<?php if (!$authenticated): ?>
<div id="loginOverlay" class="overlay">
    <div class="login-box">
        <h2 class="login-title">Welcome Ka-Barangay!</h2>
        <form id="loginForm" method="POST">
            <input type="text" name="name" class="login-input" placeholder="Enter Name" required>
            <input type="password" name="password" class="login-input" placeholder="Enter Password" required>
            <button type="submit" class="login-btn">Enter Dashboard</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="sidebar">
    <div class="logo-container">
        <lord-icon
            src="https://cdn.lordicon.com/kdduutaw.json"
            trigger="none"
            colors="primary:#110a5c,secondary:#1b1091"
            style="width:120px;height:120px">
        </lord-icon>
    </div>
    
    <div class="user-info">
        <h3><?php echo isset($client_name) ? htmlspecialchars($client_name) : 'Guest'; ?></h3>
        <p>Client ID: <?php echo isset($_SESSION['clientId']) ? $_SESSION['clientId'] : 'N/A'; ?></p>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="#" class="nav-link active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="requirements.php" class="nav-link">
                <i class="fas fa-file-alt"></i>
                <span>Requirements</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="feedback_responses.php" class="nav-link">
                <i class="fa-solid fa-comments"></i>
                <span>Feedback Responses</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>



<div class="main-content">
    <div class="header">
        <h1 class="page-title">Client Dashboard</h1>
        <div class="user-badge">
            <i class="fas fa-user-circle"></i>
            <span><?php echo isset($client_name) ? htmlspecialchars($client_name) : 'Guest'; ?></span>
        </div>
    </div>
    
    <div class="dashboard-cards">
        <!-- Facility Reservation Card -->
        <div class="card">
            <div class="card-icon reservation">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <h3 class="card-title">Facility Reservation</h3>
            <p class="card-text">Book barangay facilities for your events and gatherings.</p>
            <a href="#" class="card-action">Reserve Now</a>
        
            <div class="service-form">
                <h3 class="form-title">Facility Reservation</h3>
                <form action="" method="POST" id="reservationForm">
                    <div class="form-group">
                        <label class="form-label">Facility</label>
                        <select name="facility_id" class="form-control" id="facilitySelect" required>
                            <option value="">Select Facility</option>
                                <?php foreach ($facilities as $facility): ?>
                                    <option value="<?= htmlspecialchars($facility['id']) ?>"><?= htmlspecialchars($facility['name']) ?></option>
                                <?php endforeach; ?>
                        </select>
                    </div>
        
                    <div class="form-group">
                        <label class="form-label">Reservation Date</label>
                        <input type="date" name="reservation_date" class="form-control" id="reservationDate" required>
                        <div class="alert-overlap" id="dateAlert"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="startTime" class="form-control" id="startTime" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="time" name="endTime" class="form-control" id="endTime" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Purpose</label>
                        <input type="text" name="purpose" class="form-control" required>
                    </div>
                <div class="alert-overlap" id="timeAlert"></div>
                <button type="submit" class="form-btn">Submit Request</button>
            </form>
        </div>
    </div>
        
        <!-- Document Request Card -->
        <div class="card">
        <div class="card-icon document">
            <i class="fas fa-file-alt"></i>
        </div>
        <h3 class="card-title">Document Request</h3>
        <p class="card-text">Request official documents from the barangay office.</p>
        <a href="#" class="card-action">Request Now</a>
        
        <div class="service-form">
                <h3 class="form-title">Document Request</h3>
                <form action="docu_request.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Document Type</label>
                        <select name="document_id" class="form-control" required>
                            <option value="">Select Document</option>
                            <?php foreach ($documents as $doc): ?>
                                <option value="<?= htmlspecialchars($doc['id']) ?>"><?= htmlspecialchars($doc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="form-btn">Submit Request</button>
                </form>
            </div>
        </div>
        
        <!-- Feedback Card -->
    <div class="card">
        <div class="card-icon feedback">
            <i class="fas fa-comment-dots"></i>
        </div>
        <h3 class="card-title">Feedback & Complaints</h3>
        <p class="card-text">Share your feedback or file a complaint with the barangay.</p>
        <a href="#" class="card-action">Provide Feedback</a>
        <div class="service-form">
            <h3 class="form-title">Feedback/Complaint</h3>
                <form action="process_feedback.php" method="POST">
                    <input type="hidden" name="client_id" value="<?= isset($_SESSION['clientId']) ? $_SESSION['clientId'] : '' ?>">
                    <input type="hidden" name="client_name" value="<?= isset($_SESSION['clientName']) ? $_SESSION['clientName'] : '' ?>">
        
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="feedback">Feedback</option>
                            <option value="complaint">Complaint</option>
                            <option value="suggestion">Suggestion</option>
                            <option value="question">Question</option>
                        </select>
                    </div>
        
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                    </div>
        
                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" placeholder="Your message" required rows="5"></textarea>
                    </div>
        
                    <button type="submit" class="form-btn">Submit</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Reservations Section -->
    <div class="section">
        <h2 class="section-title">
            <i class="fas fa-calendar-check"></i>
            Your Facility Reservations
        </h2>
        
        <?php if (!empty($reserves)): ?>
            <?php foreach ($reserves as $reserve): ?>
                <div class="request-item">
                    <div class="request-header">
                        <h3 class="request-title"><?= htmlspecialchars($reserve['facility_name']) ?></h3>
                        <span class="request-status status-<?= strtolower($reserve['status']) ?>">
                            <?= htmlspecialchars($reserve['status']) ?>
                        </span>
                    </div>
                    <p class="request-details">
                        <strong>Date:</strong> <?= htmlspecialchars($reserve['reservation_date']) ?>
                    </p>
                    <p style="text-align: center;">
                        <strong>For confirmation, please contact this account for further evaluation. Thank you!</strong>
                    </p>
                    <p style="text-align: center;">
                        <strong>Email:</strong> jovelyn.adante@csucc.edu.ph
                    </p>
                    <div class="request-actions">
                        <form method="POST" action="">
                            <input type="hidden" name="reserve_id" value="<?= $reserve['facil_id'] ?>">
                            <button type="submit" name="delete_reservation" class="btn-cancel1">Cancel Reservation</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>You have no reservations at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Document Requests Section -->
    <div class="section">
        <h2 class="section-title">
            <i class="fas fa-file-contract"></i>
            Your Document Requests
        </h2>
        
        <?php if (!empty($requests)): ?>
            <?php foreach ($requests as $request): ?>
                <div class="request-item">
                    <div class="request-header">
                        <h3 class="request-title"><?= htmlspecialchars($request['document_name']) ?></h3>
                        <span class="request-status status-<?= strtolower($request['status']) ?>">
                            <?= htmlspecialchars($request['status']) ?>
                        </span>
                    </div>
                    <p class="request-details">
                        <strong>Fee:</strong> ₱<?= htmlspecialchars($request['fee']) ?>
                    </p>
                    <p style="text-align: center;">
                        <strong>Please ensure all requirements are met and submitted before claiming the requested document.</strong>
                    </p>
                    <div class="request-actions">
                        <form method="POST" action="" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;  gap: 10px; margin-top: 20px;">
                            <p style="text-align: left; margin: 0; flex: 1;">To view requirements, click "Requirements" in the sidebar.</p>
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <button type="submit" name="delete_request" class="btn-cancel">Cancel Request</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-file-excel"></i>
                <p>You have no document requests at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Create modal overlay
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    document.body.appendChild(modalOverlay);

    // Close modal function
    const closeModal = function() {
        modalOverlay.style.display = 'none';
        modalOverlay.innerHTML = '';
    };

    // Close modal when clicking outside content
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });

    // Add click handlers to all card action buttons
    document.querySelectorAll('.card-action').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const card = this.closest('.card');
            const form = card.querySelector('.service-form');
            
            if (!form) {
                console.error('No form found in card');
                return;
            }
            
            // Create modal content container
            const modalContent = document.createElement('div');
            modalContent.className = 'modal-content';
            
            // Create close button
            const closeButton = document.createElement('button');
            closeButton.className = 'close-modal';
            closeButton.innerHTML = '&times;';
            closeButton.addEventListener('click', closeModal);
            
            // Clone the form (important to avoid removing it from DOM)
            const formClone = form.cloneNode(true);
            formClone.style.display = 'block'; // Make sure it's visible
            
            // Build modal content
            modalContent.appendChild(closeButton);
            modalContent.appendChild(formClone);
            
            // Show modal
            modalOverlay.innerHTML = '';
            modalOverlay.appendChild(modalContent);
            modalOverlay.style.display = 'flex';
            
            // Reinitialize any needed scripts for the form
            if (formClone.id === 'reservationForm') {
                // Reinitialize flatpickr for the cloned form
                const dateInput = formClone.querySelector('#reservationDate');
                if (dateInput) {
                    flatpickr(dateInput, {
                        minDate: "today",
                        dateFormat: "Y-m-d",
                        disable: [
                            function(date) {
                                return (date.getDay() === 0 || date.getDay() === 6);
                            }
                        ]
                    });
                }
                
                // Reattach validation for time inputs
                const startHour = formClone.querySelector('select[name="start_hour"]');
                const startMinute = formClone.querySelector('select[name="start_minute"]');
                const endHour = formClone.querySelector('select[name="end_hour"]');
                const endMinute = formClone.querySelector('select[name="end_minute"]');
                
                if (startHour && startMinute && endHour && endMinute) {
                    function validateTimes() {
                        const startTime = parseInt(startHour.value) * 60 + parseInt(startMinute.value);
                        const endTime = parseInt(endHour.value) * 60 + parseInt(endMinute.value);
                        
                        if (endTime <= startTime) {
                            alert("End time must be after start time");
                            return false;
                        }
                        
                        if ((endTime - startTime) < 30) {
                            alert("Minimum reservation duration is 30 minutes");
                            return false;
                        }
                        
                        return true;
                    }
                    
                    // Add event listeners for validation
                    startHour.addEventListener('change', validateTimes);
                    startMinute.addEventListener('change', validateTimes);
                    endHour.addEventListener('change', validateTimes);
                    endMinute.addEventListener('change', validateTimes);
                    
                    // Form submission validation
                    formClone.addEventListener('submit', function(e) {
                        if (!validateTimes()) {
                            e.preventDefault();
                        }
                    });
                }
            }
        });
    });

    // Close the login overlay once authenticated
    <?php if ($authenticated): ?>
        document.getElementById('loginOverlay').style.display = 'none';
    <?php endif; ?>

    // Show overlay if user is not authenticated
    <?php if (!$authenticated): ?>
        document.getElementById('loginOverlay').style.display = 'flex';
    <?php endif; ?>
});


document.getElementById('reservationForm').addEventListener('submit', function(e) {
    const facilityId = document.getElementById('facilitySelect').value;
    const date = document.getElementById('reservationDate').value;
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    
    // Convert to Date objects for comparison
    const start = new Date(`${date}T${startTime}`);
    const end = new Date(`${date}T${endTime}`);
    
    // Basic time validation
    if (end <= start) {
        Swal.fire({
            title: 'Invalid Time Selection',
            text: 'End time must be after start time',
            icon: 'error',
            confirmButtonText: 'OK'
        })
        e.preventDefault();
        return false;
    }
    
    // Check against admin-set unavailable times
    const unavailableTimes = <?php echo json_encode($facility_unavailable); ?>;
    
    if (unavailableTimes[facilityId] && unavailableTimes[facilityId][date]) {
        const conflicts = unavailableTimes[facilityId][date].some(slot => {
            const slotStart = new Date(`${date}T${slot.start}`);
            const slotEnd = new Date(`${date}T${slot.end}`);
            
            return (start < slotEnd && end > slotStart);
        });
        
        if (conflicts) {
            Swal.fire({
                title:'Time Conflict',
                text: 'The selected time conflicts with an unavailable time slot set by admin',
                icon: 'error',
                confirmButtonText: 'OK'
            })
            e.preventDefault();
            return false;
        }
    }
    
    return true;
});

// Add event listeners to check for conflicts when times are changed
document.getElementById('facilitySelect').addEventListener('change', checkForConflicts);
document.getElementById('reservationDate').addEventListener('change', checkForConflicts);
document.getElementById('startTime').addEventListener('change', checkForConflicts);
document.getElementById('endTime').addEventListener('change', checkForConflicts);

/*function checkForConflicts() {
    const facilityId = document.getElementById('facilitySelect').value;
    const date = document.getElementById('reservationDate').value;
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    
    if (!facilityId || !date || !startTime || !endTime) return;
    
    const start = new Date(`${date}T${startTime}`);
    const end = new Date(`${date}T${endTime}`);
    
    if (end <= start) {
        document.getElementById('timeAlert').textContent = "End time must be after start time";
        document.getElementById('timeAlert').style.display = 'block';
        return;
    }
    
    const unavailableTimes = <?php echo json_encode($facility_unavailable); ?>;
    
    if (unavailableTimes[facilityId] && unavailableTimes[facilityId][date]) {
        const conflicts = unavailableTimes[facilityId][date].some(slot => {
            const slotStart = new Date(`${date}T${slot.start}`);
            const slotEnd = new Date(`${date}T${slot.end}`);
            
            return (start < slotEnd && end > slotStart);
        });
        
        if (conflicts) {
            document.getElementById('timeAlert').textContent = "The selected time conflicts with an unavailable time slot set by admin";
            document.getElementById('timeAlert').style.display = 'block';
            return;
        }
    }
    
    document.getElementById('timeAlert').style.display = 'none';
}*/
</script>
</body>
</html>
