<?php
session_start();
include 'connect.php';

// Function to get requirements with document info
function getRequirements() {
    global $connect;
    $sql = "SELECT fr.*, d.name AS document_name 
            FROM filing_requirements fr
            JOIN documents d ON fr.document_id = d.id
            WHERE fr.is_active = 1 
            ORDER BY d.name, fr.requirement_order";
    $result = $connect->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get all requirements
$requirements = getRequirements();

// Get all documents for admin form
$documents = [];
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    $documents_result = $connect->query("SELECT * FROM documents ORDER BY name");
    $documents = $documents_result->fetch_all(MYSQLI_ASSOC);
}

// Check if user is admin
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Handle form submission for admin updates
if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_requirement'])) {
        // Add new requirement
        $name = $connect->real_escape_string($_POST['name']);
        $description = $connect->real_escape_string($_POST['description']);
        $order = (int)$_POST['order'];
        $document_id = (int)$_POST['document_id'];
        
        $sql = "INSERT INTO filing_requirements (name, description, requirement_order, document_id) 
                VALUES ('$name', '$description', $order, $document_id)";
        if ($connect->query($sql)) {
            $_SESSION['message'] = "Requirement added successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding requirement: " . $connect->error;
            $_SESSION['message_type'] = "danger";
        }
        
        // Refresh requirements
        header("Location: requirements.php");
        exit();
    } elseif (isset($_POST['update_requirement'])) {
        // Update existing requirement
        $id = (int)$_POST['requirement_id'];
        $name = $connect->real_escape_string($_POST['name']);
        $description = $connect->real_escape_string($_POST['description']);
        $order = (int)$_POST['order'];
        $document_id = (int)$_POST['document_id'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE filing_requirements 
                SET name = '$name', 
                    description = '$description', 
                    requirement_order = $order,
                    document_id = $document_id,
                    is_active = $is_active
                WHERE id = $id";
        if ($connect->query($sql)) {
            $_SESSION['message'] = "Requirement updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating requirement: " . $connect->error;
            $_SESSION['message_type'] = "danger";
        }
        
        // Refresh requirements
        header("Location: requirements.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filing Requirements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
        }

        
        .document-section {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #0d6efd;
        }
        .document-title {
            color: #1e40af;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .requirement-card {
            margin-bottom: 15px;
            border-left: 3px solid #6c757d;
            padding-left: 15px;
        }
        .requirement-order {
            background-color: #0d6efd;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        .admin-controls {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }

        .btn {
            background-color: var(--primary-color);
            text-decoration: none;
            color: white;
        }

        .btn:hover {
            background-color: #2a7285;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Documents Required for Filing</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>
        
        <!-- Group requirements by document -->
        <?php
        $current_document = null;
        foreach ($requirements as $req):
            if ($current_document != $req['document_name']):
                $current_document = $req['document_name'];
                ?>
                <div class="document-section">
                    <h3 class="document-title"><?php echo htmlspecialchars($current_document); ?></h3>
            <?php endif; ?>
            
            <div class="requirement-card">
                <div class="d-flex align-items-start">
                    <span class="requirement-order"><?php echo $req['requirement_order']; ?></span>
                    <div>
                        <h5><?php echo htmlspecialchars($req['name']); ?></h5>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($req['description'])); ?></p>
                    </div>
                </div>
                
                <?php if ($is_admin): ?>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                data-id="<?php echo $req['id']; ?>"
                                data-name="<?php echo htmlspecialchars($req['name']); ?>"
                                data-description="<?php echo htmlspecialchars($req['description']); ?>"
                                data-order="<?php echo $req['requirement_order']; ?>"
                                data-document="<?php echo $req['document_id']; ?>"
                                data-active="<?php echo $req['is_active']; ?>">
                            Edit
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php 
            $next_req = next($requirements);
            if (!$next_req || $next_req['document_name'] != $current_document):
                ?>
                </div> <!-- Close document-section -->
            <?php endif; ?>
        <?php endforeach; ?>
        
        <!-- Admin controls -->
        <?php if ($is_admin): ?>
            <div class="admin-controls mt-5">
                <h3>Admin Panel</h3>
                
                <!-- Add new requirement form -->
                <div class="mb-4">
                    <h5>Add New Requirement</h5>
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Document</label>
                                <select class="form-select" name="document_id" required>
                                    <?php foreach ($documents as $doc): ?>
                                        <option value="<?php echo $doc['id']; ?>">
                                            <?php echo htmlspecialchars($doc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Requirement Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" required></textarea>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Order</label>
                                <input type="number" class="form-control" name="order" required>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" name="add_requirement" class="btn btn-primary">Add</button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Edit requirement form (hidden by default) -->
                <div id="editFormContainer" style="display: none;">
                    <h5>Edit Requirement</h5>
                    <form method="POST">
                        <input type="hidden" id="edit_requirement_id" name="requirement_id">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Document</label>
                                <select class="form-select" id="edit_document" name="document_id" required>
                                    <?php foreach ($documents as $doc): ?>
                                        <option value="<?php echo $doc['id']; ?>">
                                            <?php echo htmlspecialchars($doc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" required></textarea>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Order</label>
                                <input type="number" class="form-control" id="edit_order" name="order" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">Active</label>
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" name="update_requirement" class="btn btn-success">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Proceed to filing button -->
        <div class="text-center mt-4">
            <a href="mainDashboard.php" class="btn btn-lg" style="font-family: 'Urbanist, sans-serif; font-optical-sizing: auto; font-weight: <weight>; font-style: normal;">Proceed to Filing</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($is_admin): ?>
    <script>
        // Handle edit button clicks
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const description = this.getAttribute('data-description');
                const order = this.getAttribute('data-order');
                const documentId = this.getAttribute('data-document');
                const isActive = this.getAttribute('data-active') === '1';
                
                document.getElementById('edit_requirement_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_order').value = order;
                document.getElementById('edit_document').value = documentId;
                document.getElementById('edit_is_active').checked = isActive;
                
                document.getElementById('editFormContainer').style.display = 'block';
                
                // Scroll to the edit form
                document.getElementById('editFormContainer').scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>