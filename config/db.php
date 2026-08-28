<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "godaddy_db";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Set UTF-8 encoding
mysqli_set_charset($conn, "utf8mb4");

// Set PHP timezone
date_default_timezone_set("Asia/Kolkata");