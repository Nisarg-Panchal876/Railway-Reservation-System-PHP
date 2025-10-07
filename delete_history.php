<?php
session_start();
include_once('db_config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Invalid request');window.location='my_tickets.php';</script>";
    exit();
}

if (!isset($_SESSION['user_info'])) {
    echo "<script>alert('Please login');window.location='login.php';</script>";
    exit();
}

$email = mysqli_real_escape_string($conn, $_SESSION['user_info']);
// find passenger id
$p_res = mysqli_query($conn, "SELECT p_id FROM passengers WHERE email='$email'");
$prow = mysqli_fetch_assoc($p_res);
if (!$prow) {
    echo "<script>alert('User not found');window.location='my_tickets.php';</script>";
    exit();
}
$p_id = $prow['p_id'];

// delete all tickets for this passenger
$del = mysqli_prepare($conn, 'DELETE FROM tickets WHERE p_id = ?');
mysqli_stmt_bind_param($del, 'i', $p_id);
$ok = mysqli_stmt_execute($del);
if ($ok) {
    echo "<script>alert('All your tickets have been removed from history');window.location='my_tickets.php';</script>";
} else {
    echo "<script>alert('Failed to clear history');window.location='my_tickets.php';</script>";
}

?>
