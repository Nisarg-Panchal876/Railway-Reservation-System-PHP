<?php
session_start();
include_once('db_config.php');
session_start();
// detect if request expects JSON (AJAX) or not
$acceptsJson = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

$pnr = isset($_POST['pnr']) ? trim($_POST['pnr']) : '';
if ($pnr === '') {
    if ($acceptsJson) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'msg'=>'PNR missing']); }
    else { echo "<script>alert('PNR missing');window.location='index.php';</script>"; }
    exit();
}

// fetch ticket and passenger to verify ownership
$ticketRes = mysqli_query($conn, "SELECT * FROM tickets WHERE PNR='".mysqli_real_escape_string($conn,$pnr)."'");
$ticket = mysqli_fetch_assoc($ticketRes);
if (!$ticket) {
    if ($acceptsJson) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'msg'=>'Ticket not found']); }
    else { echo "<script>alert('Ticket not found');window.location='index.php';</script>"; }
    exit();
}

// verify session user owns this ticket
if (!isset($_SESSION['user_info'])) {
    if ($acceptsJson) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'msg'=>'Not logged in']); }
    else { echo "<script>alert('Please login');window.location='login.php';</script>"; }
    exit();
}
$passRes = mysqli_query($conn, "SELECT * FROM passengers WHERE p_id={$ticket['p_id']}");
$pass = mysqli_fetch_assoc($passRes);
if (!$pass || $_SESSION['user_info'] !== $pass['email']) {
    if ($acceptsJson) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'msg'=>'Not authorized']); }
    else { echo "<script>alert('Not authorized');window.location='index.php';</script>"; }
    exit();
}

// update ticket status to Paid
$upd = mysqli_prepare($conn, 'UPDATE tickets SET t_status = ? WHERE PNR = ?');
$newstatus = 'Paid';
mysqli_stmt_bind_param($upd, 'ss', $newstatus, $pnr);
$ok = mysqli_stmt_execute($upd);
// respond according to request type
if ($ok) {
    if ($acceptsJson) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>true,'msg'=>'Payment recorded','status'=>$newstatus]); }
    else {
        // if a return_to param is provided, redirect back there; otherwise to eticket
        $ret = isset($_POST['return_to']) ? $_POST['return_to'] : 'eticket.php?pnr='.urlencode($pnr);
        echo "<script>alert('Payment recorded');window.location='".htmlspecialchars($ret, ENT_QUOTES, 'UTF-8')."';</script>";
    }
} else {
    if ($acceptsJson) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'msg'=>'Failed to update status']); }
    else { echo "<script>alert('Failed to update status');window.location='eticket.php?pnr=".urlencode($pnr)."';</script>"; }
}
