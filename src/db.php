<?php
session_start();
$host = 'db';
$user = 'reguser';
$pass = 'regpass';
$dbname = 'registration';

// Helper function to generate tiny placeholder
function generatePlaceholder($width = 48, $height = 48)
{
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 $width $height'%3E%3Crect width='100%25' height='100%25' fill='%23f8f9fa'/%3E%3Cpath d='M24 20c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6zm0 4c4 0 12 2 12 6v2H12v-2c0-4 8-6 12-6z' fill='%23dee2e6'/%3E%3C/svg%3E";
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
