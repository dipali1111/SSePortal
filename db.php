<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sseportal_db"; // Make sure this matches your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Enable connection timeout handling
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>