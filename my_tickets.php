<?php
session_start();
include_once('db_config.php');
if (!isset($_SESSION['user_info'])) {
    echo "<script>alert('Please login to view your tickets');window.location='login.php';</script>";
    exit();
}
$email = mysqli_real_escape_string($conn, $_SESSION['user_info']);
// fetch passenger id
$p_res = mysqli_query($conn, "SELECT p_id FROM passengers WHERE email='$email'");
$p = mysqli_fetch_assoc($p_res);
if (!$p) {
    echo "<script>alert('User not found');window.location='index.php';</script>"; exit();
}
$p_id = $p['p_id'];

// fetch tickets — but be defensive: some installs may not have tickets.t_no column yet.
$has_t_no = false;
$colRes = mysqli_query($conn, "SHOW COLUMNS FROM tickets LIKE 't_no'");
if ($colRes && mysqli_num_rows($colRes) > 0) {
    $has_t_no = true;
}

if ($has_t_no) {
    // tickets table stores t_no per-ticket
    $sql = "SELECT t.PNR, t.t_status, t.t_fare, t.p_id, p.p_fname, p.p_lname, p.p_contact, p.email, t.t_no, t.t_date,
    tr.t_name AS train_name, tr.t_source AS train_source, tr.t_destination AS train_destination
    FROM tickets t
    LEFT JOIN passengers p ON t.p_id = p.p_id
    LEFT JOIN trains tr ON t.t_no = tr.t_no
    WHERE t.p_id = $p_id
    ORDER BY t.PNR DESC";
} else {
    // fallback: use passenger.t_no (older installs)
    $sql = "SELECT t.PNR, t.t_status, t.t_fare, t.p_id, p.p_fname, p.p_lname, p.p_contact, p.email, p.t_no AS t_no, t.t_date,
    tr.t_name AS train_name, tr.t_source AS train_source, tr.t_destination AS train_destination
    FROM tickets t
    LEFT JOIN passengers p ON t.p_id = p.p_id
    LEFT JOIN trains tr ON p.t_no = tr.t_no
    WHERE t.p_id = $p_id
    ORDER BY t.PNR DESC";
}

// mark past-date, non-paid tickets for this user as Expired in DB
$update_sql = "UPDATE tickets SET t_status='Expired' WHERE p_id=$p_id AND t_date IS NOT NULL AND t_date < CURDATE() AND t_status NOT IN ('Paid','Cancelled','Expired')";
mysqli_query($conn, $update_sql);
$res = mysqli_query($conn, $sql);

?>
<!DOCTYPE html>
<html>
<head>
    <title>My Tickets</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; }
        .list { max-width:1000px; margin:30px auto; background:#fff;padding:24px;border-radius:8px; }
        table { width:100%; border-collapse:collapse; font-size:16px }
        th,td { padding:12px 14px; border-bottom:1px solid #f0f0f0 }
        th { background:#fbfcfe; text-align:left; font-size:15px }
        .status-paid{color:green;font-weight:700}
        .status-unpaid{color:orange;font-weight:700}
        .status-expired{color:#d93025;font-weight:700}
        .action-links a{ margin-right:10px; text-decoration:none; color:#1e73be; font-weight:600 }
        .action-links a.print-link{ color:#d35400 }
    </style>
</head>
<body>
<?php include('header.php'); ?>
<div class="list">
    <h2>My Tickets</h2>
    <?php if(isset($_SESSION['user_info'])) { ?>
    <div style="text-align:right;margin-bottom:12px;">
        <form method="POST" action="delete_history.php" onsubmit="return confirm('This will permanently remove all tickets from your history. Continue?');" style="display:inline-block;margin:0;">
            <button type="submit" style="background:#c0392b;color:#fff;padding:8px 12px;border:none;border-radius:6px;cursor:pointer;font-weight:700;">Clear History</button>
        </form>
    </div>
    <?php } ?>
    <table>
            <tr><th>PNR</th><th>Passenger</th><th>Date</th><th>From - To</th><th>Fare</th><th>Status</th><th>Action</th></tr>
            <?php while ($row = mysqli_fetch_assoc($res)) {
            // train info from joined trains table (if available)
            $train_source = !empty($row['train_source']) ? $row['train_source'] : null;
            $train_destination = !empty($row['train_destination']) ? $row['train_destination'] : null;
            $train_name = !empty($row['train_name']) ? $row['train_name'] : null;
            // date and expired check
            $date = !empty($row['t_date']) ? $row['t_date'] : 'N/A';
            $expired = false;
            if ($date !== 'N/A') {
                $expired = (strtotime($date) < strtotime(date('Y-m-d')));
            }
            // determine status class and label
            if ($expired && strtolower($row['t_status']) !== 'paid') {
                $statusLabel = 'Expired';
                $statusClass = 'status-expired';
            } else {
                $statusLabel = $row['t_status'];
                $statusClass = strtolower($row['t_status']) === 'paid' ? 'status-paid' : 'status-unpaid';
            }
                echo '<tr>';
                echo '<td>'.htmlspecialchars($row['PNR']).'</td>';
                echo '<td>'.htmlspecialchars($row['p_fname'].' '.$row['p_lname']).'</td>';
                echo '<td>'.htmlspecialchars($date).'</td>';
                echo '<td>'.($train_source?htmlspecialchars($train_source.' - '.$train_destination):'N/A').'</td>';
                echo '<td>Rs. '.htmlspecialchars($row['t_fare']).'</td>';
                echo '<td class="'.$statusClass.'">'.htmlspecialchars($statusLabel).'</td>';
                // build action links
                $viewLink = '<a href="eticket.php?pnr='.htmlspecialchars($row['PNR']).'">View</a>';
                $printLink = ' <a class="print-link" href="eticket.php?pnr='.htmlspecialchars($row['PNR']).'&print=1" target="_blank">Print</a>';
                $actions = $viewLink . $printLink;
                // show delete button for tickets that are not Paid
                if (strtolower($row['t_status']) !== 'paid') {
                    $actions .= ' <form method="POST" action="delete_ticket.php" style="display:inline-block;margin:0;padding:0;">'
                              . '<input type="hidden" name="pnr" value="'.htmlspecialchars($row['PNR']).'">'
                              . '<button type="submit" onclick="return confirm(\'Are you sure you want to delete this ticket?\')" '
                              . 'style="background:none;border:none;color:#c0392b;font-weight:700;cursor:pointer;padding:0;margin-left:8px;">Delete</button></form>';
                }
                echo '<td class="action-links">'.$actions.'</td>';
                echo '</tr>';
        } ?>
    </table>
</div>
</body>
</html>