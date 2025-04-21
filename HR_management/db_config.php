<?php
$servername = "localhost";
$username = "hr_user";  // Changed from 'root'
$password = "StrongPassword123!";  // New password
$database = "hr_management";
$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>