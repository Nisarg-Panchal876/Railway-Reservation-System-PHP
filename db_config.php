<?php

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'railway';
$DB_PORT = 3306;

$conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if (!$conn) {
    // Friendly JS alert for the browser and stop execution
    echo "<script type='text/javascript'>alert('Database connection failed: " . mysqli_connect_error() . "');</script>";
    die('Could not connect: ' . mysqli_connect_error());
}
?>
