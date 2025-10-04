<?php
include 'connect.php';

if (isset($_POST['save'])) {
    $msg = $_POST['message'];
    
    $targetDir = "uploads/"; 
    $fileName = basename($_FILES["image_path"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    
    if (move_uploaded_file($_FILES["image_path"]["tmp_name"], $targetFilePath)) {
        $sql = "INSERT INTO `anonymous_db`(`message`, `image_path`) VALUES ('$msg','$targetFilePath')";
        $result = mysqli_query($connect, $sql);

        if ($result) {
            echo "<script>alert('Feedback and Complaint submitted successfully!');</script>";
            header("Location: feed.php");
            exit();
        } else {
            echo "<script>alert('Error submitting feedback and complaint.');</script>";
        }
    } else {
        echo "<script>alert('Error uploading image. Please try again.');</script>";
    }
}
?>