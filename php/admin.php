<?php 
session_start();
include "connect.php";

// Add date
if (isset($_POST['add_date'])) {
    $newDate = $_POST['date'];
    $stmt = $connect->prepare("INSERT INTO `unavailable_office` (date) VALUES (?)");
    if ($stmt) {
        $stmt->bind_param("s", $newDate);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Error in SQL statement: " . $connect->error;
    }
}

// Delete date
if (isset($_POST['delete_date'])) {
    $deleteId = $_POST['delete_id'];
    $stmt = $connect->prepare("DELETE FROM unavailable_office WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
}

// Fetch all unavailable dates
$result = $connect->query("SELECT * FROM unavailable_office ORDER BY date ASC");

if (isset($_POST['update_status'])) {
    // Update for document requests
    if (isset($_POST['id']) && isset($_POST['status'])) {
        $id = mysqli_real_escape_string($connect, $_POST['id']);
        $status = mysqli_real_escape_string($connect, $_POST['status']);
        mysqli_query($connect, "UPDATE docurequests_db SET status='$status' WHERE id='$id'");
    }

    // Update for facility reservations
    if (isset($_POST['facil_id']) && isset($_POST['status'])) {
        $facil_id = mysqli_real_escape_string($connect, $_POST['facil_id']);
        $status = mysqli_real_escape_string($connect, $_POST['status']);
        mysqli_query($connect, "UPDATE facilreserve_db SET status='$status' WHERE id='$facil_id'");
    }
}

if (isset($facil_id) && isset($status)) {
    if (mysqli_query($connect, "UPDATE facilreserve_db SET status='$status' WHERE facil_id='$facil_id'")) {
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . mysqli_error($connect);
    }
}

if(isset($_POST['add_document'])) {
    $nameDocu = mysqli_real_escape_string($connect, $_POST['edit_name']);
    $des = mysqli_real_escape_string($connect, $_POST['edit_description']);
    $fee = mysqli_real_escape_string($connect, $_POST['edit_fee']);
    mysqli_query($connect, "INSERT INTO documents (name, description, fee) VALUES ('$nameDocu', '$des', '$fee')");
}

if (isset($_POST['delete_document']) && isset($_POST['delete_document_id'])) {
    $id = intval($_POST['delete_document_id']);
    mysqli_query($connect, "DELETE FROM documents WHERE id = $id");
}

if(isset($_POST['add_facility'])) {
    $facility = mysqli_real_escape_string($connect, $_POST['name']);
    mysqli_query($connect, "INSERT INTO facilities (name) VALUES ('$facility')");
}

if (isset($_POST['delete_facility']) && isset($_POST['delete_facility_id'])) {
    $id = intval($_POST['delete_facility_id']);
    mysqli_query($connect, "DELETE FROM facilities WHERE id = $id");
}

// Add unavailability
if (isset($_POST['add_unavailability'])) {
    $facility_id = mysqli_real_escape_string($connect, $_POST['facility_id']);
    $unavailable_date = mysqli_real_escape_string($connect, $_POST['unavailable_date']);
    $start_time = mysqli_real_escape_string($connect, $_POST['unavailable_startTime']);
    $end_time = mysqli_real_escape_string($connect, $_POST['unavailable_endTime']);

    mysqli_query($connect, "
        INSERT INTO facility_unavailability (facility_id, unavailable_date, unavailable_startTime, unavailable_endTime) 
        VALUES ('$facility_id', '$unavailable_date', '$start_time', '$end_time')
    ");
    echo "<p style='color: green;'>Unavailability added successfully!</p>";
}

// Delete unavailability
if (isset($_POST['delete_unavailability'])) {
    $facility_id = mysqli_real_escape_string($connect, $_POST['facility_id']);
    $unavailable_date = mysqli_real_escape_string($connect, $_POST['unavailable_date']);
    $start_time = mysqli_real_escape_string($connect, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($connect, $_POST['end_time']);
    
    mysqli_query($connect, "
        DELETE FROM facility_unavailability 
        WHERE facility_id='$facility_id' 
          AND unavailable_date='$unavailable_date' 
          AND unavailable_startTime='$start_time' 
          AND unavailable_endTime='$end_time'
    ");
    echo "<p style='color: red;'>Unavailability deleted successfully!</p>";
}

if (isset($_POST['submit_announcement'])) {
    $text = trim($_POST['announce_text']);
    $image = $_FILES['announce_image'];

    // Handle text-only announcement
    if (!empty($text)) {
        $stmt = $connect->prepare("INSERT INTO announcements (type, content, created_at) VALUES ('text', ?, NOW())");
        $stmt->bind_param("s", $text);
        $stmt->execute();
        $stmt->close();
    }

    // Handle image upload
    if (!empty($image['name'])) {
        $uploadDir = "uploads/"; // Make sure this directory exists and is writable
        $imageName = uniqid() . "_" . basename($image['name']);
        $targetPath = $uploadDir . $imageName;

        if (move_uploaded_file($image['tmp_name'], $targetPath)) {
            $stmt = $connect->prepare("INSERT INTO announcements (type, content, created_at) VALUES ('image', ?, NOW())");
            $stmt->bind_param("s", $targetPath);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "<p style='color:red;'>Image upload failed.</p>";
        }
    }
}

// Delete announcement
if (isset($_POST['delete_announcement'])) {
    $deleteId = $_POST['delete_id'];
    $stmt = $connect->prepare("DELETE FROM announcements WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Error deleting announcement: " . $connect->error;
    }
}

// Add Requirement with document type selection
if(isset($_POST['add_requirement'])) {
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $description = mysqli_real_escape_string($connect, $_POST['description']);
    $order = intval($_POST['order']);
    $document_id = intval($_POST['document_id']);
    
    // First verify the document exists
    $doc_check = mysqli_query($connect, "SELECT id FROM documents WHERE id = $document_id");
    if(mysqli_num_rows($doc_check) > 0) {
        $query = "INSERT INTO filing_requirements 
                 (name, description, requirement_order, document_id) 
                 VALUES ('$name', '$description', $order, $document_id)";
        
        if(mysqli_query($connect, $query)) {
            $_SESSION['success'] = "Requirement added successfully!";
        } else {
            $_SESSION['error'] = "Error adding requirement: " . mysqli_error($connect);
        }
    } else {
        $_SESSION['error'] = "Invalid document selected";
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Delete Requirement
if(isset($_POST['delete_requirement'])) {
    $id = intval($_POST['delete_id']);
    mysqli_query($connect, "DELETE FROM filing_requirements WHERE id = $id");
}

// Search functionality
$searchTerm = '';
$documentRequestsQuery = "
    SELECT dr.id, c.name AS client_name, d.name AS document_name, status
    FROM docurequests_db dr
    JOIN clients c ON dr.client_id = c.id
    JOIN documents d ON dr.document_id = d.id
";

$facilityReservationsQuery = "
    SELECT fr.facil_id, c.name AS client_name, f.name AS facility_name, reservation_date, status
    FROM facilreserve_db fr
    JOIN clients c ON fr.client_id = c.id
    JOIN facilities f ON fr.facility_id = f.id
";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = mysqli_real_escape_string($connect, $_GET['search']);
    $documentRequestsQuery .= " WHERE c.name LIKE '%$searchTerm%' OR d.name LIKE '%$searchTerm%' OR status LIKE '%$searchTerm%'";
    $facilityReservationsQuery .= " WHERE c.name LIKE '%$searchTerm%' OR f.name LIKE '%$searchTerm%' OR status LIKE '%$searchTerm%' OR reservation_date LIKE '%$searchTerm%'";
}

// Add this to your existing PHP code at the top of admin.php
if (isset($_POST['respond_to_feedback'])) {
    $feedbackId = intval($_POST['feedback_id']);
    $response = mysqli_real_escape_string($connect, $_POST['response']);
    $status = mysqli_real_escape_string($connect, $_POST['status']);
    
    $stmt = $connect->prepare("UPDATE feedback SET response = ?, status = ?, responded_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $response, $status, $feedbackId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $_SESSION['feedback_message'] = "Response submitted successfully!";
    } else {
        $_SESSION['feedback_message'] = "Error submitting response: " . $connect->error;
    }
    $stmt->close();
    
    header("Location: ".$_SERVER['PHP_SELF']."#feedback-section");
    exit();
}

// Edit document
if(isset($_POST['edit_document'])) {
    $id = intval($_POST['edit_document_id']);
    $nameDocu = mysqli_real_escape_string($connect, $_POST['edit_name']);
    $des = mysqli_real_escape_string($connect, $_POST['edit_description']);
    $fee = mysqli_real_escape_string($connect, $_POST['edit_fee']);
    
    $stmt = $connect->prepare("UPDATE documents SET name=?, description=?, fee=? WHERE id=?");
    $stmt->bind_param("ssdi", $nameDocu, $des, $fee, $id);
    $stmt->execute();
    
    // Refresh the page to show updated data
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Count feedbacks for dashboard card
$feedbackCount = mysqli_query($connect, "SELECT COUNT(*) as count FROM feedback");
$feedbackCount = mysqli_fetch_assoc($feedbackCount)['count'];

$pendingFeedbackCount = mysqli_query($connect, "SELECT COUNT(*) as count FROM feedback WHERE status = 'Pending'");
$pendingFeedbackCount = mysqli_fetch_assoc($pendingFeedbackCount)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Barangay Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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

    .overlay {
      display: none;
      position: fixed;
      z-index: 999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
    }
    
    .overlay-content {
      background-color: white;
      margin: 5% auto;
      padding: 25px;
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      position: relative;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      max-height: 90vh;
      overflow-y: auto;
    }
    
    .close-btn {
      position: absolute;
      top: 15px;
      right: 20px;
      font-size: 24px;
      font-weight: bold;
      color: #6b7280;
      cursor: pointer;
      transition: color 0.2s;
    }
    
    .close-btn:hover {
      color: #1f2937;
    }
    
    .card {
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .table-container {
      max-height: 400px;
      overflow-y: auto;
    }
    
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: #a1a1a1;
    }
    
    .status-pending {
      background-color: #fef3c7;
      color: #92400e;
    }
    
    .status-approved {
      background-color: #d1fae5;
      color: #065f46;
    }
    
    .status-denied {
      background-color: #fee2e2;
      color: #991b1b;
    }
    
    .status-cancelled {
      background-color: #e5e7eb;
      color: #4b5563;
    }

    .document-section {
      background-color: #f8fafc;
      border-radius: 0.5rem;
      padding: 1.5rem;
      margin-bottom: 2rem;
      border-left: 4px solid #3b82f6;
    }
    .document-title {
      color: #1e40af;
      font-weight: 600;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
    }
    .document-fee {
      background-color: #e0f2fe;
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
      font-size: 0.875rem;
      margin-left: 0.5rem;
    }
    .requirement-item {
      display: flex;
      margin-bottom: 0.75rem;
      padding: 0.75rem;
      background-color: white;
      border-radius: 0.375rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .requirement-order {
      background-color: #3b82f6;
      color: white;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 1rem;
      flex-shrink: 0;
    }

    .status-completed {
      background-color: #d1fae5;
      color: #065f46;
    }

    /* Expanded row styling */
    .expanded-row-details {
        display: none;
        background-color: #f9fafb;
        transition: all 0.3s ease;
    }
    
    .expanded-row-details.active {
        display: table-row;
        animation: fadeIn 0.3s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        padding: 1rem;
    }
    
    .detail-item {
        padding: 0.5rem;
    }
    
    .detail-label {
        font-weight: 600;
        color: #4b5563;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .detail-value {
        color: #1f2937;
        font-size: 0.9375rem;
    }
    
    .expandable-row {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .expandable-row:hover {
        background-color: #f3f4f6 !important;
    }
    
    .expand-icon {
        transition: transform 0.2s;
        margin-left: 0.5rem;
    }
    
    .expanded-row-details.active + .expandable-row .expand-icon {
        transform: rotate(180deg);
    }

    .status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-resolved {
    background-color: #d1fae5;
    color: #065f46;
}

.status-reviewed {
    background-color: #e0f2fe;
    color: #075985;
}
  </style>
</head>
<body class="bg-gray-50 font-sans">

  <!-- Header -->
  <header class="bg-gradient-to-r text-white p-4 shadow-lg" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);">
    <div class="container mx-auto flex justify-between items-center">
      <h1 class="text-2xl md:text-3xl font-bold flex items-center">
        <i class="fas fa-tachometer-alt mr-3"></i> Barangay Admin Dashboard
      </h1>
    </div>
  </header>

  <!-- Search Bar -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <form method="GET" class="flex items-center">
        <div class="relative flex-grow">
            <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                   class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500" 
                   placeholder="Search requests and reservations...">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
        <button type="submit" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center">
            <i class="fas fa-search mr-2"></i> Search
        </button>
        <?php if (!empty($searchTerm)): ?>
            <a href="?" class="ml-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition flex items-center">
                <i class="fas fa-times mr-2"></i> Clear
            </a>
        <?php endif; ?>
    </form>
</div>

  <main class="container mx-auto p-4 md:p-6">
    <!-- Dashboard Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <?php
    // Count document requests
    $docRequests = mysqli_query($connect, "SELECT COUNT(*) as count FROM docurequests_db");
    $docCount = mysqli_fetch_assoc($docRequests)['count'];
    
    // Count facility reservations
    $facilRequests = mysqli_query($connect, "SELECT COUNT(*) as count FROM facilreserve_db");
    $facilCount = mysqli_fetch_assoc($facilRequests)['count'];
    
    // Count pending document requests
    $pendingDocs = mysqli_query($connect, "SELECT COUNT(*) as count FROM docurequests_db WHERE status = 'Pending'");
    $pendingDocCount = mysqli_fetch_assoc($pendingDocs)['count'];
    
    // Count pending facility reservations
    $pendingFacil = mysqli_query($connect, "SELECT COUNT(*) as count FROM facilreserve_db WHERE status = 'Pending'");
    $pendingFacilCount = mysqli_fetch_assoc($pendingFacil)['count'];
    
    // Count completed document requests
    $completedDocs = mysqli_query($connect, "SELECT COUNT(*) as count FROM docurequests_db WHERE status = 'Completed'");
    $completedDocCount = mysqli_fetch_assoc($completedDocs)['count'];
    
    // Count completed facility reservations
    $completedFacil = mysqli_query($connect, "SELECT COUNT(*) as count FROM facilreserve_db WHERE status = 'Completed'");
    $completedFacilCount = mysqli_fetch_assoc($completedFacil)['count'];
    ?>
      
      <div class="bg-white rounded-xl shadow-md p-6 card">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-indigo-100 text-indigo-800 mr-4">
            <i class="fas fa-file-alt text-xl"></i>
          </div>
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Document Requests</h3>
            <p class="text-2xl font-bold"><?php echo $docCount; ?></p>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-md p-6 card">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-green-100 text-green-800 mr-4">
            <i class="fas fa-building text-xl"></i>
          </div>
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Facility Reservations</h3>
            <p class="text-2xl font-bold"><?php echo $facilCount; ?></p>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-md p-6 card">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-yellow-100 text-yellow-800 mr-4">
            <i class="fas fa-clock text-xl"></i>
          </div>
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Pending Documents</h3>
            <p class="text-2xl font-bold"><?php echo $pendingDocCount; ?></p>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-md p-6 card">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-blue-100 text-blue-800 mr-4">
            <i class="fas fa-calendar-check text-xl"></i>
          </div>
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Pending Reservations</h3>
            <p class="text-2xl font-bold"><?php echo $pendingFacilCount; ?></p>
          </div>
        </div>
      </div>

    <div class="bg-white rounded-xl shadow-md p-6 card">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-teal-100 text-teal-800 mr-4">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <h3 class="text-gray-500 text-sm font-medium">Completed Documents</h3>
                <p class="text-2xl font-bold"><?php echo $completedDocCount; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-md p-6 card">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-teal-100 text-teal-800 mr-4">
          <i class="fas fa-check-double text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Completed Reservations</h3>
          <p class="text-2xl font-bold"><?php echo $completedFacilCount; ?></p>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6 card">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-orange-100 text-orange-800 mr-4">
            <i class="fas fa-comments text-xl"></i>
        </div>
        <div>
            <h3 class="text-gray-500 text-sm font-medium">Feedback Messages</h3>
            <p class="text-2xl font-bold"><?php echo $feedbackCount; ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 card">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-amber-100 text-amber-800 mr-4">
            <i class="fas fa-clock text-xl"></i>
        </div>
        <div>
            <h3 class="text-gray-500 text-sm font-medium">Pending Feedback</h3>
            <p class="text-2xl font-bold"><?php echo $pendingFeedbackCount; ?></p>
        </div>
      </div>
  </div>
</div>

    <!-- Document Requests Section -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6 border-b">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
          <i class="fas fa-file-alt text-indigo-600 mr-2"></i> Document Requests
        </h2>
        <button id="openDocumentOverlay" class="mt-2 md:mt-0 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center text-sm">
          <i class="fas fa-plus mr-2"></i> Add Document
        </button>
      </div>
      
      <div class="table-container">
        <table class="w-full text-sm">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="p-3 text-left">ID</th>
              <th class="p-3 text-left">Client</th>
              <th class="p-3 text-left">Document</th>
              <th class="p-3 text-left">Status</th>
              <th class="p-3 text-left">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php
            $result = mysqli_query($connect, $documentRequestsQuery);
            while($row = mysqli_fetch_assoc($result)) {
                $statusClass = strtolower($row['status']) . ' status-' . strtolower($row['status']);
                echo "<tr class='hover:bg-gray-50'>";
                echo "<td class='p-3'>".$row['id']."</td>";
                echo "<td class='p-3'>".htmlspecialchars($row['client_name'])."</td>";
                echo "<td class='p-3'>".htmlspecialchars($row['document_name'])."</td>";
                echo "<td class='p-3'><span class='px-2 py-1 rounded-full text-xs $statusClass'>".htmlspecialchars($row['status'])."</span></td>";
                echo "<td class='p-3'>
                  <form method='post' class='flex gap-2 items-center'>
                    <input type='hidden' name='id' value='".$row['id']."'>
                    <select name='status' class='border rounded p-1 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500'>
                      <option ".($row['status'] == 'Pending' ? 'selected' : '').">Pending</option>
                      <option ".($row['status'] == 'Approved' ? 'selected' : '').">Approved</option>
                      <option ".($row['status'] == 'Denied' ? 'selected' : '').">Denied</option>
                      <option ".($row['status'] == 'Completed' ? 'selected' : '').">Completed</option>
                    </select>
                    <button name='update_status' class='bg-indigo-600 text-white px-3 py-1 rounded text-sm hover:bg-indigo-700 transition flex items-center'>
                      <i class='fas fa-save mr-1'></i> Save
                    </button>
                  </form>
                </td>";
                echo "</tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Facility Reservations Section -->
<div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6 border-b">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
            <i class="fas fa-building text-green-600 mr-2"></i> Facility Reservations
        </h2>
        <button id="openFacilityOverlay" class="mt-2 md:mt-0 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center text-sm">
            <i class="fas fa-plus mr-2"></i> Add Facility
        </button>
    </div>
    
    <div class="table-container">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Client</th>
                    <th class="p-3 text-left">Facility</th>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
    <?php
    $result = mysqli_query($connect, $facilityReservationsQuery);
    while($row = mysqli_fetch_assoc($result)) {
        $statusClass = strtolower($row['status']) . ' status-' . strtolower($row['status']);
        echo "<tr class='expandable-row hover:bg-gray-50' onclick='toggleRow(this)'>";
        echo "<td class='p-3'>".$row['facil_id']."</td>";
        echo "<td class='p-3'>".htmlspecialchars($row['client_name'])."</td>";
        echo "<td class='p-3'>".htmlspecialchars($row['facility_name'])."</td>";
        echo "<td class='p-3'>".$row['reservation_date']."</td>";
        echo "<td class='p-3 flex items-center justify-between'>
                <span class='px-2 py-1 rounded-full text-xs $statusClass'>".htmlspecialchars($row['status'])."</span>
                <i class='fas fa-chevron-down expand-icon text-gray-400'></i>
              </td>";
        echo "</tr>";
        
        // Expanded details row
        echo "<tr class='expanded-row-details'>";
        echo "<td colspan='5' class='p-0'>";
        echo "<div class='details-grid'>";
        
        // Get more details for this reservation
        $detailsQuery = mysqli_query($connect, "
            SELECT fr.*, c.*, f.* 
            FROM facilreserve_db fr
            JOIN clients c ON fr.client_id = c.id
            JOIN facilities f ON fr.facility_id = f.id
            WHERE fr.facil_id = ".$row['facil_id']
        );
        
        if($details = mysqli_fetch_assoc($detailsQuery)) {
            echo "<div class='detail-item'>";
            echo "<div class='detail-label'>Client Name</div>";
            echo "<div class='detail-value'>".htmlspecialchars($details['name'])."</div>";
            echo "</div>";
            
            echo "<div class='detail-item'>";
            echo "<div class='detail-label'>Contact Number</div>";
            echo "<div class='detail-value'>".($details['contact_number'] ?? 'N/A')."</div>";
            echo "</div>";
            
            echo "<div class='detail-item'>";
            echo "<div class='detail-label'>Reservation Date</div>";
            echo "<div class='detail-value'>".$details['reservation_date']."</div>";
            echo "</div>";
            
            echo "<div class='detail-item'>";
            echo "<div class='detail-label'>Start Time</div>";
            echo "<div class='detail-value'>".($details['startTime'] ?? 'N/A')."</div>";
            echo "</div>";
            
            echo "<div class='detail-item'>";
            echo "<div class='detail-label'>End Time</div>";
            echo "<div class='detail-value'>".($details['endTime'] ?? 'N/A')."</div>";
            echo "</div>";
            
            echo "<div class='detail-item'>";
            echo "<div class='detail-label'>Purpose</div>";
            echo "<div class='detail-value'>".($details['purpose'] ?? 'N/A')."</div>";
            echo "</div>";
            
            echo "<div class='detail-item'>";
            echo "<div class='detail-label'>Status</div>";
            echo "<div class='detail-value'><span class='px-2 py-1 rounded-full text-xs $statusClass'>".htmlspecialchars($details['status'])."</span></div>";
            echo "</div>";
        }
        
        // Action form
        echo "<div class='detail-item col-span-2 md:col-span-3'>";
        echo "<form method='post' class='flex flex-col sm:flex-row gap-2 items-start sm:items-center'>";
        echo "<input type='hidden' name='facil_id' value='".$row['facil_id']."'>";
        echo "<select name='status' class='border rounded p-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 flex-grow'>";
        echo "<option ".($row['status'] == 'Pending' ? 'selected' : '').">Pending</option>";
        echo "<option ".($row['status'] == 'Approved' ? 'selected' : '').">Approved</option>";
        echo "<option ".($row['status'] == 'Cancelled' ? 'selected' : '').">Cancelled</option>";
        echo "<option ".($row['status'] == 'Completed' ? 'selected' : '').">Completed</option>";
        echo "</select>";
        echo "<button name='update_status' class='bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 transition flex items-center w-full sm:w-auto justify-center'>";
        echo "<i class='fas fa-save mr-1'></i> Update Status";
        echo "</button>";
        echo "</form>";
        echo "</div>";
        echo "</div>"; // Close details-grid
        echo "</td>";
        echo "</tr>";
    }
    ?>
</tbody>
        </table>
    </div>
</div>
    <!-- Announcements Section -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6 border-b">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
          <i class="fas fa-bullhorn text-red-600 mr-2"></i> Announcements
        </h2>
        <button id="openAnnouncementOverlay" class="mt-2 md:mt-0 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center text-sm">
          <i class="fas fa-plus mr-2"></i> Add Announcement
        </button>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
        <?php
        $announcements = mysqli_query($connect, "SELECT * FROM announcements ORDER BY created_at DESC");
        while ($ann = mysqli_fetch_assoc($announcements)) {
            echo "<div class='border border-gray-200 rounded-lg p-4 bg-white shadow-sm card'>";
            if ($ann['type'] === 'text') {
                echo "<p class='text-gray-800 max-w-full max-h-60 overflow-auto break-words whitespace-pre-wrap'>" . nl2br(htmlspecialchars($ann['content'])) . "</p>";
            } else {
                echo "<img src='" . htmlspecialchars($ann['content']) . "' class='rounded-lg w-full max-h-60 object-contain border mt-2' alt='Announcement'>";
            }

            echo "<small class='text-gray-500 block mt-2'><i class='far fa-clock mr-1'></i>" . htmlspecialchars($ann['created_at']) . "</small>";

            echo "<form method='POST' class='mt-3 text-right'>
                    <input type='hidden' name='delete_id' value='" . htmlspecialchars($ann['id']) . "'>
                    <button type='submit' name='delete_announcement'
                        class='bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600 transition flex items-center ml-auto'>
                        <i class='fas fa-trash mr-1'></i> Delete
                    </button>
                </form>";
            echo "</div>";
        }
        ?>
      </div>
    </div>

    <!-- Unavailability Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Office Unavailability -->
      <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6 border-b">
          <h2 class="text-xl font-semibold text-gray-800 flex items-center">
            <i class="fas fa-calendar-times text-purple-600 mr-2"></i> Unavailable Office Dates
          </h2>
          <button id="openOverlay" class="mt-2 md:mt-0 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center text-sm">
            <i class="fas fa-plus mr-2"></i> Add Date
          </button>
        </div>
        
        <div class="table-container">
          <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
              <tr>
                <th class="p-3 text-left">ID</th>
                <th class="p-3 text-left">Date</th>
                <th class="p-3 text-left">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php
                  $dates = $connect->query("SELECT * FROM unavailable_office ORDER BY date ASC");
                  while ($row = mysqli_fetch_assoc($dates)) {
                      echo "<tr class='hover:bg-gray-50'>";
                      echo "<td class='p-3'>".$row['id']."</td>";
                      echo "<td class='p-3'>".$row['date']."</td>";
                      echo "<td class='p-3'>
                              <form method='POST' class='flex justify-end'>
                                  <input type='hidden' name='delete_id' value='".$row['id']."'>
                                  <button name='delete_date' class='bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600 transition flex items-center'>
                                    <i class='fas fa-trash mr-1'></i> Delete
                                  </button>
                              </form>
                            </td>";
                      echo "</tr>";
                  }
              ?>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Facility Unavailability -->
      <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6 border-b">
          <h2 class="text-xl font-semibold text-gray-800 flex items-center">
            <i class="fas fa-clock text-blue-600 mr-2"></i> Unavailable Facility Times
          </h2>
          <button id="openFacilityUnavailabilityOverlay" class="mt-2 md:mt-0 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center text-sm">
            <i class="fas fa-plus mr-2"></i> Add Unavailability
          </button>
        </div>
        
        <div class="table-container">
          <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
              <tr>
                <th class="p-3 text-left">Facility</th>
                <th class="p-3 text-left">Date</th>
                <th class="p-3 text-left">Time</th>
                <th class="p-3 text-left">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php
                  $unavailabilities = mysqli_query($connect, "
                  SELECT facility_unavailability.*, facilities.name AS facility_name
                  FROM facility_unavailability
                  JOIN facilities ON facility_unavailability.facility_id = facilities.id
                  ORDER BY unavailable_date DESC
                  ");
                  while($unavail = mysqli_fetch_assoc($unavailabilities)) {
                      echo "<tr class='hover:bg-gray-50'>";
                      echo "<td class='p-3'>".htmlspecialchars($unavail['facility_name'])."</td>";
                      echo "<td class='p-3'>".htmlspecialchars($unavail['unavailable_date'])."</td>";
                      echo "<td class='p-3'>".htmlspecialchars($unavail['unavailable_startTime'])." - ".htmlspecialchars($unavail['unavailable_endTime'])."</td>";
                      echo "<td class='p-3'>
                              <form method='post' class='flex justify-end'>
                              <input type='hidden' name='facility_id' value='".$unavail['facility_id']."'>
                              <input type='hidden' name='unavailable_date' value='".$unavail['unavailable_date']."'>
                              <input type='hidden' name='start_time' value='".$unavail['unavailable_startTime']."'>
                              <input type='hidden' name='end_time' value='".$unavail['unavailable_endTime']."'>
                              <button type='submit' name='delete_unavailability' class='bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600 transition flex items-center'>
                                <i class='fas fa-trash mr-1'></i> Delete
                              </button>
                          </form></td>";
                      echo "</tr>";
                  }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Requirements Management Section -->
  <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6 border-b">
      <h2 class="text-xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-file-contract text-blue-600 mr-2"></i> Document Requirements
      </h2>
    </div>
    
    <div class="p-6">
      <?php
      // Get all documents with their requirements
      $documents = mysqli_query($connect, 
          "SELECT d.id, d.name, d.description, d.fee
           FROM documents d
           ORDER BY d.name ASC");
      
      while($doc = mysqli_fetch_assoc($documents)) {
          echo '<div class="document-section mb-6">
                  <div class="flex justify-between items-center mb-4">
                    <h3 class="document-title">
                      <i class="fas fa-file-alt mr-2"></i> '.htmlspecialchars($doc['name']).'
                      <span class="document-fee">₱'.number_format($doc['fee'], 2).'</span>
                    </h3>
                    <button onclick="openAddRequirementModal('.$doc['id'].')" class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 transition flex items-center">
                      <i class="fas fa-plus mr-1"></i> Add Requirement
                    </button>
                  </div>
                  <p class="text-sm text-gray-600 mb-4">'.nl2br(htmlspecialchars($doc['description'])).'</p>';
          
          // Get requirements for this document
          $requirements = mysqli_query($connect, 
            "SELECT * FROM filing_requirements 
            WHERE document_id = ".$doc['id']." 
            ORDER BY requirement_order ASC");
          
          if(mysqli_num_rows($requirements) > 0) {
              echo '<div class="space-y-3">';
              while($req = mysqli_fetch_assoc($requirements)) {
                  echo '<div class="requirement-item flex items-start">
                          <div class="requirement-order mt-1">'.$req['requirement_order'].'</div>
                          <div class="flex-1 ml-3">
                            <h4 class="font-medium">'.htmlspecialchars($req['name']).'</h4>
                            <p class="text-sm text-gray-600">'.nl2br(htmlspecialchars($req['description'])).'</p>
                          </div>
                          <div class="flex space-x-2">
                            <form method="POST" class="inline">
                              <input type="hidden" name="delete_id" value="'.$req['id'].'">
                              <button type="submit" name="delete_requirement" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                              </button>
                            </form>
                          </div>
                        </div>';
              }
              echo '</div>';
          } else {
              echo '<p class="text-gray-500 italic">No requirements set yet for this document</p>';
          }
          
          echo '</div>'; // Close document-section
      }
      ?>
    </div>
  </div>

  <!-- Add Requirement Modal (Specific to Document) -->
  <div id="addRequirementOverlay" class="overlay">
    <div class="overlay-content">
      <span id="closeAddRequirementOverlay" class="close-btn">&times;</span>
      <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-plus-circle text-blue-600 mr-2"></i> 
        <span id="addRequirementTitle">Add New Requirement</span>
      </h2>
      
      <form method="POST" id="addRequirementForm">
        <input type="hidden" id="selectedDocumentId" name="document_id">
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Requirement Name</label>
          <input type="text" name="name" class="w-full border border-gray-300 p-2 rounded-lg" required>
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea name="description" rows="3" class="w-full border border-gray-300 p-2 rounded-lg"></textarea>
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
          <input type="number" name="order" class="w-full border border-gray-300 p-2 rounded-lg" required>
        </div>
        
        <button type="submit" name="add_requirement" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
          Add Requirement
        </button>
      </form>
    </div>
  </div>

  <!-- Overlays -->
 <!-- Document Overlay -->
<div id="documentOverlay" class="overlay">
    <div class="overlay-content">
        <span id="closeDocumentOverlay" class="close-btn">&times;</span>
        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-file-alt text-indigo-600 mr-2"></i> 
            <span id="documentModalTitle">Add New Document</span>
        </h2>

        <!-- Add/Edit Form -->
        <form method="POST" enctype="multipart/form-data" class="space-y-4 mb-6" id="documentForm">
            <input type="hidden" id="edit_document_id" name="edit_document_id" value="">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Document Name</label>
                <input type="text" name="edit_name" id="edit_name" 
                       class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500" 
                       required placeholder="e.g., Barangay Clearance">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="edit_description" id="edit_description" 
                          class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500" 
                          required></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fee (₱)</label>
                <input type="number" name="edit_fee" id="edit_fee" 
                       class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500" 
                       required placeholder="e.g., 100" step="0.01" min="0">
            </div>
            
            <div class="flex gap-2">
                <button type="submit" name="add_document" id="addDocumentBtn" 
                        class="flex-1 bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Add Document
                </button>
                <button type="submit" name="edit_document" id="editDocumentBtn" 
                        class="flex-1 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition flex items-center justify-center" 
                        style="display: none;">
                    <i class="fas fa-save mr-2"></i> Update Document
                </button>
                <button type="button" id="cancelEditBtn" 
                        class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 transition flex items-center justify-center" 
                        style="display: none;">
                    <i class="fas fa-times mr-2"></i> Cancel
                </button>
            </div>
        </form>

        <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
            <i class="fas fa-list-ul mr-2"></i> Existing Documents
        </h3>
        <ul class="space-y-2 text-sm max-h-64 overflow-auto">
            <?php
            $docs = mysqli_query($connect, "SELECT * FROM documents ORDER BY id DESC");
            while ($doc = mysqli_fetch_assoc($docs)) {
                echo "<li class='flex justify-between items-center bg-gray-50 px-4 py-3 rounded-lg border border-gray-200'>";
                echo "<div class='flex flex-col'>
                        <span class='font-medium text-gray-800'>".htmlspecialchars($doc['name'])."</span>
                        <span class='text-gray-600 text-xs'>₱".number_format($doc['fee'], 2)."</span>
                      </div>";
                echo "<div class='flex gap-2'>
                        <button onclick='editDocument(".$doc['id'].", \"".htmlspecialchars(addslashes($doc['name']))."\", \"".htmlspecialchars(addslashes($doc['description']))."\", \"".$doc['fee']."\")' 
                                class='bg-blue-500 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-600 transition flex items-center'>
                            <i class='fas fa-edit mr-1'></i> Edit
                        </button>
                        <form method='POST' class='inline'>
                            <input type='hidden' name='delete_document_id' value='".htmlspecialchars($doc['id'])."'>
                            <button type='submit' name='delete_document' class='bg-red-500 text-white px-3 py-1 rounded-lg text-sm hover:bg-red-600 transition flex items-center'>
                                <i class='fas fa-trash mr-1'></i> Delete
                            </button>
                        </form>
                      </div>";
                echo "</li>";
            }
            ?>
        </ul>
    </div>
</div>
  <!-- Facility Overlay -->
  <div id="facilityOverlay" class="overlay">
    <div class="overlay-content">
      <span id="closeFacilityOverlay" class="close-btn">&times;</span>
      <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-building text-green-600 mr-2"></i> Add New Facility
      </h2>

      <form method="POST" enctype="multipart/form-data" class="space-y-4 mb-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Facility Name</label>
          <input type="text" name="name" class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500" required placeholder="e.g., Barangay Gymnasium">
        </div>
        <button type="submit" name="add_facility" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition flex items-center justify-center">
          <i class="fas fa-save mr-2"></i> Add Facility
        </button>
      </form>

      <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
        <i class="fas fa-list-ul mr-2"></i> Existing Facilities
      </h3>
      <ul class="space-y-2 text-sm max-h-64 overflow-auto">
        <?php
        $facil = mysqli_query($connect, "SELECT * FROM facilities ORDER BY id DESC");
        while ($faci = mysqli_fetch_assoc($facil)) {
            echo "<li class='flex justify-between items-center bg-gray-50 px-4 py-3 rounded-lg border border-gray-200'>";
            echo "<div class='flex flex-col'>
                    <span class='font-medium text-gray-800'>".htmlspecialchars($faci['name'])."</span>
                  </div>";
            echo "<form method='POST'>
                    <input type='hidden' name='delete_facility_id' value='".htmlspecialchars($faci['id'])."'>
                    <button type='submit' name='delete_facility' class='bg-red-500 text-white px-3 py-1 rounded-lg text-sm hover:bg-red-600 transition flex items-center'>
                      <i class='fas fa-trash mr-1'></i> Delete
                    </button>
                  </form>";
            echo "</li>";
        }
        ?>
      </ul>
    </div>
  </div>

  <!-- Announcement Overlay -->
  <div id="announcementOverlay" class="overlay">
    <div class="overlay-content">
      <span id="closeAnnouncementOverlay" class="close-btn">&times;</span>
      <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-bullhorn text-red-600 mr-2"></i> Post an Announcement
      </h2>
    
      <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Text Message</label>
          <textarea name="announce_text" rows="4"
              class="w-full border border-gray-300 p-3 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-500"
              placeholder="Type your message..."></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Upload Image</label>
          <div class="flex items-center justify-center w-full">
            <label class="flex flex-col w-full border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-50">
              <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4">
                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                <p class="text-sm text-gray-500">Click to upload or drag and drop</p>
              </div>
              <input type="file" name="announce_image" accept="image/*" class="hidden">
            </label>
          </div>
        </div>
        <button type="submit" name="submit_announcement"
            class="w-full bg-red-600 text-white py-3 rounded-lg hover:bg-red-700 transition flex items-center justify-center">
            <i class="fas fa-paper-plane mr-2"></i> Post Announcement
        </button>
      </form>
    </div>
  </div>

  <!-- Office Unavailability Overlay -->
  <div id="overlay" class="overlay">
    <div class="overlay-content">
      <span id="closeOverlay" class="close-btn">&times;</span>
      <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-calendar-times text-purple-600 mr-2"></i> Add Unavailable Date
      </h2>

      <form method="POST">
        <label class="block text-sm font-medium text-gray-700 mb-1">Select Date:</label>
        <input type="date" name="date" class="border border-gray-300 p-2 rounded-lg w-full focus:ring-2 focus:ring-purple-200 focus:border-purple-500 text-sm" required>
        <button type="submit" name="add_date"
            class="mt-4 w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition flex items-center justify-center">
            <i class="fas fa-calendar-plus mr-2"></i> Add Date
        </button>
      </form>
    </div>
  </div>
  <!-- Feedback Management Section -->
<div id="feedback-section" class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6 border-b">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
            <i class="fas fa-comments text-orange-600 mr-2"></i> Feedback Management
        </h2>
        <div class="mt-2 md:mt-0 flex items-center">
            <form method="GET" class="flex items-center mr-2">
                <div class="relative">
                    <input type="text" name="feedback_search" value="<?php echo isset($_GET['feedback_search']) ? htmlspecialchars($_GET['feedback_search']) : ''; ?>" 
                           class="pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-200 focus:border-orange-500 text-sm" 
                           placeholder="Search feedback...">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
                <button type="submit" class="ml-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition flex items-center text-sm">
                    Search
                </button>
            </form>
            <select id="feedbackFilter" class="border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-200 focus:border-orange-500">
                <option value="all">All Feedback</option>
                <option value="pending">Pending</option>
                <option value="responded">Responded</option>
            </select>
        </div>
    </div>
    
    <div class="table-container">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Client</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Subject</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php
                $feedbackQuery = "SELECT * FROM feedback";
                
                if (isset($_GET['feedback_search']) && !empty($_GET['feedback_search'])) {
                    $searchTerm = mysqli_real_escape_string($connect, $_GET['feedback_search']);
                    $feedbackQuery .= " WHERE client_name LIKE '%$searchTerm%' OR subject LIKE '%$searchTerm%' OR message LIKE '%$searchTerm%'";
                }
                
                $feedbackQuery .= " ORDER BY created_at DESC";
                
                $feedbacks = mysqli_query($connect, $feedbackQuery);
                
                while($feedback = mysqli_fetch_assoc($feedbacks)) {
                    $statusClass = strtolower($feedback['status']) . ' status-' . strtolower($feedback['status']);
                    echo "<tr class='hover:bg-gray-50 feedback-row' data-status='".strtolower($feedback['status'])."'>";
                    echo "<td class='p-3'>".$feedback['id']."</td>";
                    echo "<td class='p-3'>".htmlspecialchars($feedback['client_name'])."</td>";
                    echo "<td class='p-3'>".htmlspecialchars($feedback['type'])."</td>";
                    echo "<td class='p-3'>".htmlspecialchars($feedback['subject'])."</td>";
                    echo "<td class='p-3'><span class='px-2 py-1 rounded-full text-xs $statusClass'>".htmlspecialchars($feedback['status'])."</span></td>";
                    echo "<td class='p-3'>".date('M d, Y', strtotime($feedback['created_at']))."</td>";
                    echo "<td class='p-3'>
                            <button onclick='openFeedbackModal(".$feedback['id'].")' class='bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition flex items-center'>
                                <i class='fas fa-eye mr-1'></i> View
                            </button>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Feedback Modal Overlay -->
<div id="feedbackModal" class="overlay">
    <div class="overlay-content" style="max-width: 700px;">
        <span class="close-btn" onclick="closeFeedbackModal()">&times;</span>
        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-comment-dots text-orange-600 mr-2"></i> 
            <span id="feedbackModalTitle">Feedback Details</span>
        </h2>
        
        <div id="feedbackModalContent" class="space-y-4">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

  <!-- Facility Unavailability Overlay -->
  <div id="facilityUnavailabilityOverlay" class="overlay">
    <div class="overlay-content">
      <span id="closeFacilityUnavailabilityOverlay" class="close-btn">&times;</span>
      <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-clock text-blue-600 mr-2"></i> Add Facility Unavailable Time
      </h2>

      <?php $facilities = mysqli_query($connect, "SELECT * FROM facilities ORDER BY name ASC"); ?>

      <form method="POST">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Select Facility:</label>
          <select name="facility_id" class="border border-gray-300 p-2 rounded-lg w-full focus:ring-2 focus:ring-blue-200 focus:border-blue-500 text-sm" required>
            <option value="">-- Select Facility --</option>
            <?php while($facility = mysqli_fetch_assoc($facilities)) { ?>
              <option value="<?php echo $facility['id']; ?>">
                <?php echo htmlspecialchars($facility['name']); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Select Date:</label>
          <input type="date" name="unavailable_date" class="border border-gray-300 p-2 rounded-lg w-full focus:ring-2 focus:ring-blue-200 focus:border-blue-500 text-sm" required>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Time:</label>
            <input type="time" name="unavailable_startTime" class="border border-gray-300 p-2 rounded-lg w-full focus:ring-2 focus:ring-blue-200 focus:border-blue-500 text-sm" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Time:</label>
            <input type="time" name="unavailable_endTime" class="border border-gray-300 p-2 rounded-lg w-full focus:ring-2 focus:ring-blue-200 focus:border-blue-500 text-sm" required>
          </div>
        </div>

        <button type="submit" name="add_unavailability"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition flex items-center justify-center">
            <i class="fas fa-plus-circle mr-2"></i> Add Unavailable Time
        </button>
      </form>
    </div>
  </div>

  <script>
    // Overlay toggle functions
    function setupOverlay(overlayId, openBtnId, closeBtnId) {
      const overlay = document.getElementById(overlayId);
      const openBtn = document.getElementById(openBtnId);
      const closeBtn = document.getElementById(closeBtnId);
      
      openBtn.onclick = () => overlay.style.display = "block";
      closeBtn.onclick = () => overlay.style.display = "none";
      
      window.onclick = (event) => {
        if (event.target === overlay) {
          overlay.style.display = "none";
        }
      };
    }
    
    // Setup all overlays
    setupOverlay("overlay", "openOverlay", "closeOverlay");
    setupOverlay("facilityUnavailabilityOverlay", "openFacilityUnavailabilityOverlay", "closeFacilityUnavailabilityOverlay");
    setupOverlay("announcementOverlay", "openAnnouncementOverlay", "closeAnnouncementOverlay");
    setupOverlay("documentOverlay", "openDocumentOverlay", "closeDocumentOverlay");
    setupOverlay("facilityOverlay", "openFacilityOverlay", "closeFacilityOverlay");
    
   // Function to open add requirement modal for specific document
    function openAddRequirementModal(documentId) {
        const docSelect = document.getElementById('selectedDocumentId');
        const docTitle = document.querySelector(`.document-section h3[data-document-id="${documentId}"]`);
        
        docSelect.value = documentId;
        if(docTitle) {
            document.getElementById('addRequirementTitle').textContent = 
                `Add Requirement for ${docTitle.textContent.trim()}`;
        }
        
        document.getElementById('addRequirementOverlay').style.display = 'block';
    }

    // Function to open edit requirement modal
    function openEditRequirementModal(requirement) {
        document.getElementById('editRequirementId').value = requirement.id;
        document.getElementById('editRequirementName').value = requirement.name;
        document.getElementById('editRequirementDescription').value = requirement.description;
        document.getElementById('editRequirementOrder').value = requirement.requirement_order;
        document.getElementById('editRequirementActive').checked = requirement.is_active == 1;
        
        // Set the document dropdown
        const docSelect = document.getElementById('editRequirementForm').querySelector('select[name="document_id"]');
        if(docSelect) {
            docSelect.value = requirement.document_id;
        }
        
        document.getElementById('editRequirementOverlay').style.display = 'block';
    }

    // Close modals
    document.getElementById('closeAddRequirementOverlay').onclick = function() {
        document.getElementById('addRequirementOverlay').style.display = 'none';
    };
    
    document.getElementById('closeEditRequirementOverlay').onclick = function() {
        document.getElementById('editRequirementOverlay').style.display = 'none';
    };

    // Close when clicking outside
    window.onclick = function(event) {
        if(event.target.classList.contains('overlay')) {
            document.getElementById('addRequirementOverlay').style.display = 'none';
            document.getElementById('editRequirementOverlay').style.display = 'none';
        }
    };

    // Toggle row expansion with better animation
    function toggleRow(row) {
        const detailsRow = row.nextElementSibling;
        const isActive = detailsRow.classList.contains('active');
        
        // Close all other open rows first
        if (!isActive) {
            document.querySelectorAll('.expanded-row-details.active').forEach(activeRow => {
                activeRow.classList.remove('active');
                // Reset the chevron icon for all rows
                document.querySelectorAll('.expand-icon').forEach(icon => {
                    icon.classList.remove('transform', 'rotate-180');
                });
            });
        }
        
        // Toggle the clicked row
        detailsRow.classList.toggle('active');
        
        // Toggle the chevron icon
        const chevron = row.querySelector('.expand-icon');
        if (detailsRow.classList.contains('active')) {
            chevron.classList.add('transform', 'rotate-180');
        } else {
            chevron.classList.remove('transform', 'rotate-180');
        }
    }
    
    // Close expanded rows when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.expandable-row') && !event.target.closest('.expanded-row-details')) {
            document.querySelectorAll('.expanded-row-details.active').forEach(row => {
                row.classList.remove('active');
            });
            // Reset all chevron icons
            document.querySelectorAll('.expand-icon').forEach(icon => {
                icon.classList.remove('transform', 'rotate-180');
            });
        }
    });

    // Add this to your existing JavaScript
function openFeedbackModal(feedbackId) {
    fetch(`get_feedback.php?id=${feedbackId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const feedback = data.feedback;
                
                let responseForm = '';
                if (feedback.status === 'Pending') {
                    responseForm = `
                        <form id="feedbackResponseForm" method="POST" class="mt-6 space-y-4">
                            <input type="hidden" name="feedback_id" value="${feedback.id}">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Response</label>
                                <textarea name="response" rows="4" class="w-full border border-gray-300 p-3 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500" required></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="w-full border border-gray-300 p-2 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                                    <option value="Pending">Pending</option>
                                    <option value="Resolved">Resolved</option>
                                    <option value="Reviewed">Reviewed</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="respond_to_feedback" class="w-full bg-orange-600 text-white py-2 rounded-lg hover:bg-orange-700 transition flex items-center justify-center">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Response
                            </button>
                        </form>
                    `;
                } else if (feedback.response) {
                    responseForm = `
                        <div class="bg-gray-50 p-4 rounded-lg mt-6">
                            <h4 class="font-medium text-gray-800 mb-2">Admin Response</h4>
                            <p class="text-gray-700">${feedback.response}</p>
                            <p class="text-sm text-gray-500 mt-2">Responded on: ${feedback.responded_at}</p>
                        </div>
                    `;
                }
                
                document.getElementById('feedbackModalContent').innerHTML = `
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Client Name</p>
                                <p class="font-medium">${feedback.client_name}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Feedback Type</p>
                                <p class="font-medium">${feedback.type}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Status</p>
                                <p class="font-medium"><span class="px-2 py-1 rounded-full text-xs status-${feedback.status.toLowerCase()}">${feedback.status}</span></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Date Submitted</p>
                                <p class="font-medium">${feedback.created_at}</p>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-sm text-gray-500">Subject</p>
                            <p class="font-medium">${feedback.subject}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-500">Message</p>
                            <div class="bg-white p-3 rounded border border-gray-200 mt-1">
                                ${feedback.message}
                            </div>
                        </div>
                        
                        ${responseForm}
                    </div>
                `;
                
                document.getElementById('feedbackModal').style.display = "block";
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading feedback details.');
        });
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').style.display = "none";
}

// Feedback filter functionality
document.getElementById('feedbackFilter').addEventListener('change', function() {
    const filterValue = this.value;
    const rows = document.querySelectorAll('.feedback-row');
    
    rows.forEach(row => {
        if (filterValue === 'all' || row.dataset.status === filterValue) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Function to edit a document
function editDocument(id, name, description, fee) {
    // Set the form to edit mode
    document.getElementById('documentModalTitle').textContent = 'Edit Document';
    document.getElementById('edit_document_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_fee').value = fee;
    
    // Show/hide appropriate buttons
    document.getElementById('addDocumentBtn').style.display = 'none';
    document.getElementById('editDocumentBtn').style.display = 'block';
    document.getElementById('cancelEditBtn').style.display = 'block';
    
    // Open the overlay
    document.getElementById('documentOverlay').style.display = 'block';
}

// Function to cancel edit and reset form
document.getElementById('cancelEditBtn').onclick = function() {
    resetDocumentForm();
    document.getElementById('documentOverlay').style.display = 'none';
};

function resetDocumentForm() {
    document.getElementById('documentModalTitle').textContent = 'Add New Document';
    document.getElementById('documentForm').reset();
    document.getElementById('edit_document_id').value = '';
    
    // Show/hide appropriate buttons
    document.getElementById('addDocumentBtn').style.display = 'block';
    document.getElementById('editDocumentBtn').style.display = 'none';
    document.getElementById('cancelEditBtn').style.display = 'none';
}

// Reset form when opening overlay for adding
document.getElementById('openDocumentOverlay').onclick = function() {
    resetDocumentForm();
    document.getElementById('documentOverlay').style.display = 'block';
};

// Set the filter to match URL parameter if present
<?php if (isset($_GET['feedback_status'])): ?>
document.getElementById('feedbackFilter').value = '<?php echo htmlspecialchars($_GET['feedback_status']); ?>';
document.getElementById('feedbackFilter').dispatchEvent(new Event('change'));
<?php endif; ?>
  </script>
</body>
</html>
