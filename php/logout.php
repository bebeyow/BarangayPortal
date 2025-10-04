<?php 
session_start();

session_unset();
session_destroy();

header('location: dashboard.php');
exit();
?>