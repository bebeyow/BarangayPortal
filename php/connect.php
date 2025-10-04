<?php 
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'baranggayportal_db';
$connect = mysqli_connect($host, $user, $pass, $db);
    if (!$connect) {
        die("Database connection failed!" .mysqli_connect_error());
    } 
?>