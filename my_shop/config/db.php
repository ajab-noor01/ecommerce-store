<?php
$host = "localhost";
$user = "root";
$password = ""; // Agar aapne XAMPP me password rakha hai toh wo likhein, warna khali chorein
$dbname = "ecommerce_db";

// Connection banana
$conn = mysqli_connect($host, $user, $password, $dbname);

// Connection check karna
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>