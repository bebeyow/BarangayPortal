<?php
session_start();
include 'connect.php';

// Add this to your existing PHP code where you fetch other data
$feedbacks = [];
if (isset($_SESSION['clientId'])) {
    $client_id = $_SESSION['clientId'];
    $stmt = $connect->prepare("SELECT * FROM feedback WHERE client_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle Feedback cancellation
if (isset($_POST['delete_feedback'])) {
    $feedback_id = $_POST['id'];
    $stmt = $connect->prepare("DELETE FROM feedback WHERE id = ? AND client_id = ?");
    $stmt->bind_param("ii", $feedback_id, $_SESSION['clientId']);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Feedback cancelled successfully";
    } else {
        $_SESSION['error_message'] = "Error cancelling feedback request";
    }
    $stmt->close();
    header("Location: feedback_responses.php");
    exit();
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
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: -1;">
        <img src="logo1.png" style="opacity: 0.130; position: absolute; top: 50%; left: 63%; transform: translate(-50%, -50%); width: 50%; pointer-events: none;">
    </div>

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
                <i class="fa-solid fa-comments"></i>
                <span>Feedback Responses</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="mainDashboard.php" class="nav-link">
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

    <!-- Feedback Section -->
<div class="section">
    <h2 class="section-title">
        <i class="fas fa-comments"></i>
        Your Feedback & Responses
    </h2>
    
    <?php if (!empty($feedbacks)): ?>
        <?php foreach ($feedbacks as $feedback): ?>
            <div class="request-item">
                <div class="request-header">
                    <h3 class="request-title"><?= htmlspecialchars($feedback['subject']) ?></h3>
                    <span class="request-status status-<?= strtolower($feedback['status']) ?>">
                        <?= htmlspecialchars($feedback['status']) ?>
                    </span>
                </div>
                <p class="request-details">
                    <strong>Type:</strong> <?= ucfirst(htmlspecialchars($feedback['type'])) ?>
                    <br>
                    <strong>Submitted:</strong> <?= date('M d, Y h:i A', strtotime($feedback['created_at'])) ?>
                </p>
                
                <div class="feedback-message">
                    <p><strong>Your Message:</strong></p>
                    <div class="message-content"><?= nl2br(htmlspecialchars($feedback['message'])) ?></div>
                </div>
                
                <?php if (!empty($feedback['response'])): ?>
                    <div class="admin-response" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                        <p><strong>Admin Response:</strong></p>
                        <div class="response-content"><?= nl2br(htmlspecialchars($feedback['response'])) ?></div>
                        <p class="response-date" style="font-size: 12px; color: #666; margin-top: 5px;">
                            Responded on: <?= date('M d, Y h:i A', strtotime($feedback['responded_at'])) ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="no-response" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                        <p style="font-style: italic; color: #666;">No response yet from the admin.</p>
                    </div>
                <?php endif; ?>
                <div class="request-actions">
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?= $feedback['id'] ?>">
                        <button type="submit" name="delete_feedback" class="btn-cancel1">Cancel Sending Feedback</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-comment-slash"></i>
            <p>You haven't submitted any feedback yet.</p>
        </div>
    <?php endif; ?>
</div>
</div>

</body>
</html>
