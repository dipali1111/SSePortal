<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sseportal_db"; // Make sure this matches your database name

// 1. Initialize mysqli object
$conn = mysqli_init();

// 2. Set connection timeout option BEFORE connecting
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

// 3. Establish the connection
if (!$conn->real_connect($servername, $username, $password, $dbname)) {
    die("Connection failed: " . mysqli_connect_error());
}
?>