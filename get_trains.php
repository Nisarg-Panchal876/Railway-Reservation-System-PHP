<?php
header('Content-Type: application/json; charset=utf-8');
include_once('db_config.php');

$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';
if ($from === '' || $to === '') {
    echo json_encode([]);
    exit();
}

// Use prepared statement and case-insensitive match
$sql = "SELECT t_no, t_name, t_source, t_destination FROM trains WHERE LOWER(t_source) = LOWER(?) AND LOWER(t_destination) = LOWER(?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ss', $from, $to);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$trains = [];
while ($row = mysqli_fetch_assoc($res)) {
    $trains[] = $row;
}

echo json_encode($trains);
