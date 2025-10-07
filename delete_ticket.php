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

$pnr = isset($_POST['pnr']) ? mysqli_real_escape_string($conn, $_POST['pnr']) : '';
if ($pnr === '') {
    echo "<script>alert('PNR missing');window.location='my_tickets.php';</script>";
    exit();
}


$res = mysqli_query($conn, "SELECT t.p_id, p.email FROM tickets t LEFT JOIN passengers p ON t.p_id = p.p_id WHERE t.PNR='$pnr'");
$row = mysqli_fetch_assoc($res);
if (!$row) {
    echo "<script>alert('Ticket not found');window.location='my_tickets.php';</script>";
    exit();
}
if ($_SESSION['user_info'] !== $row['email']) {
    echo "<script>alert('Not authorized to delete this ticket');window.location='my_tickets.php';</script>";
    exit();
}


$del = mysqli_prepare($conn, 'DELETE FROM tickets WHERE PNR = ?');
mysqli_stmt_bind_param($del, 's', $pnr);
$ok = mysqli_stmt_execute($del);
if ($ok) {
    echo "<script>alert('Ticket deleted');window.location='my_tickets.php';</script>";
} else {
    echo "<script>alert('Failed to delete ticket');window.location='my_tickets.php';</script>";
}

?>
