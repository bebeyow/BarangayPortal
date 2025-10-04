<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['client_authenticated']) || $_SESSION['client_authenticated'] !== true) {
    header("Location: mainDashboard.php");
    exit;
}
$client_id = $_SESSION['clientId'];
// Fetch available documents
$documents = [];
$stmt = $connect->prepare("SELECT * FROM documents");
$stmt->execute();
$result = $stmt->get_result();
while ($document = $result->fetch_assoc()) {
    $documents[] = $document;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['document_id'])) {
    $document_id = $_POST['document_id'];

    // Insert the document request into the database
    $stmt = $connect->prepare("INSERT INTO docurequests_db (client_id, document_id, status) VALUES (?, ?, 'Pending')");
    if ($stmt === false) {
        die('MySQL prepare error: ' . $connect->error);
    }

    $stmt->bind_param("ii", $client_id, $document_id);

    // Execute the insert query
    if ($stmt->execute()) {
        header("Location: mainDashboard.php"); // Redirect after success
        exit;
    } else {
        echo "Error processing request: " . $stmt->error;  // Display error details if query fails
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css" integrity="sha512-kJlvECunwXftkPwyvHbclArO8wszgBGisiLeuDFwNM8ws+wKIw0sv1os3ClWZOcrEB2eRXULYUsm8OVRGJKwGA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Cinzel:wght@400..900&family=Special+Gothic+Expanded+One&family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        header {
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            background: #006aff;
            padding: 10px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        header h1 {
            font-family: "Cinzel", serif;
            font-size: 25px;
            color: white;
            font-family: "Cinzel", serif;
  font-optical-sizing: auto;
  font-weight: <weight>;
  font-style: normal;
        }

        .back {
            position: fixed;
            top: 20px;
            left: 20px;
            cursor: pointer;
            z-index: 1000;
        }

        .back a {
            color: white;
            text-decoration: none;
            font-size: 24px;
        }

        form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            width: 400px;
            display: flex;
            flex-direction: column;
            text-align: center;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        select, button {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
        }
        button {
            background-color: #368398;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        button:hover {
            background-color: #2a6c7b;
        }
    </style>
</head>
<body>

<header>
    <h1>Barangay Portal</h1>
    <div class="back">
        <a href="mainDashboard.php"><i class="ri-arrow-left-fill"></i></a>
    </div>
</header>

<form method="POST">
    <h2>Request a Document</h2>
    <select name="document_id" required>
        <option value="">-- Select Document --</option>
        <?php foreach ($documents as $doc): ?>
            <option value="<?= htmlspecialchars($doc['id']) ?>"><?= htmlspecialchars($doc['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Submit Request</button>
</form>

</body>
</html>