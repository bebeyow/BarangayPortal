<?php 
session_start();
include "connect.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator</title>
</head>
<body>
    <form action="" method="POST">
        <input type="text" name="user" placeholder="Username"><br>
        <input type="password" name="pass" placeholder="Password"><br>
        <button type="submit" name="btn_login">Login Admin</button>
    </form>
</body>
</html>

<?php 
if(isset($_POST['btn_login'])) {
    $valid_user = 'ADMIN';
    $valid_pass = 'adminmabuhay143';

    $user = $_POST['user'];
    $pass = $_POST['pass'];

    if($user === $valid_user && $pass === $valid_pass) {
        $_SESSION['session_username'] = $user;
        header('location: admin.php');
        exit();
    } else {
        echo 'Incorrect Username and Password.';
    }
}
?>