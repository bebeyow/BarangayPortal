<?php
session_start();
include 'connect.php'; // your database connection file

// Fetch client details (Assuming client is logged in and session holds client_id)
$client_id = isset($_SESSION['session_username']) ? $_SESSION['session_username'] : 'Guest';
$bookings = [];
$document_requests = [];

// Fetch facility reservations for the client
if ($client_name) {
    $query = "SELECT * FROM facilreserve_db WHERE client_name = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("s", $client_name);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();

    // Fetch document requests for the client
    $query = "SELECT * FROM docurequests_db WHERE client_name = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("s", $client_name);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $document_requests[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Bookings & Document Requests</title>
    <style>
        .container {
            padding: 20px;
        }
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
        }
        .requestBox {
            padding: 20px;
        }
        .booking-entry, .request-entry {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
        }
        .summary {
            font-weight: bold;
        }
        .details {
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Your Bookings & Document Requests</h2>

    <!-- Facility Reservations Section -->
    <div class="requestBox">
        <h3 class="section-title">Your Facility Reservations</h3>

        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-entry">
                    <div class="summary">
                        <p><strong>Facility:</strong> <?= htmlspecialchars($booking['facilities']) ?></p>
                        <p><strong>Date Reserved:</strong> <?= htmlspecialchars($booking['datePick']) ?></p>
                        <p><strong>Reservation Status:</strong> <?= htmlspecialchars($booking['status']) ?></p>
                    </div>
                    <div class="details">
                        <?php if ($booking['status'] === 'Pending'): ?>
                            <p>Your reservation is pending approval.</p>
                        <?php elseif ($booking['status'] === 'Approved'): ?>
                            <p>Your reservation is confirmed!</p>
                        <?php else: ?>
                            <p>Your reservation was rejected.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You have no reservations at the moment.</p>
        <?php endif; ?>
    </div>

    <!-- Document Requests Section -->
    <div class="requestBox">
        <h3 class="section-title">Your Document Requests</h3>

        <?php if (!empty($document_requests)): ?>
            <?php foreach ($document_requests as $request): ?>
                <div class="request-entry">
                    <div class="summary">
                        <p><strong>Document Type:</strong> <?= htmlspecialchars($request['documents']) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($request['status']) ?></p>
                    </div>
                    <div class="details">
                        <?php if ($request['status'] === 'Pending'): ?>
                            <p>Your document request is still being processed.</p>
                        <?php elseif ($request['status'] === 'Approved'): ?>
                            <p>Your document request has been approved!</p>
                        <?php else: ?>
                            <p>Your document request has been rejected.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You have no document requests at the moment.</p>
        <?php endif; ?>
    </div>

    <!-- Form to Request Documents -->
    <div class="requestBox">
        <h3 class="section-title">Request a Document</h3>

        <form method="POST" action="request_document.php">
            <label for="document_type">Select Document Type:</label>
            <select name="document_type" id="document_type" required>
                <option value="Invoice">Invoice</option>
                <option value="Receipt">Receipt</option>
                <option value="Certificate">Certificate</option>
            </select>
            <br><br>
            <button type="submit" name="submit_request">Submit Document Request</button>
        </form>
    </div>
</div>

</body>
</html>