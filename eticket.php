<?php
session_start();
include_once('db_config.php');

if (!isset($_GET['pnr'])) {
    echo "<script type='text/javascript'>alert('PNR missing');window.location='index.php';</script>";
    exit();
}
$pnr = mysqli_real_escape_string($conn, $_GET['pnr']);

// fetch ticket
$sql = "SELECT * FROM tickets WHERE PNR='$pnr'";
$res = mysqli_query($conn, $sql);
$ticket = mysqli_fetch_assoc($res);
if (!$ticket) {
    echo "<script type='text/javascript'>alert('Ticket not found');window.location='index.php';</script>";
    exit();
}

// fetch passenger
$p_res = mysqli_query($conn, "SELECT * FROM passengers WHERE p_id={$ticket['p_id']}");
$passenger = mysqli_fetch_assoc($p_res);

// enforce access control: only the logged-in user who booked the ticket can view
if (!isset($_SESSION['user_info']) || $_SESSION['user_info'] !== $passenger['email']) {
    echo "<script type='text/javascript'>alert('You are not authorized to view this ticket');window.location='index.php';</script>";
    exit();
}

// fetch train details from the ticket's t_no (each ticket stores its own train)
$train = null;
if (!empty($ticket['t_no'])) {
    $tno = intval($ticket['t_no']);
    $t_res = mysqli_query($conn, "SELECT * FROM trains WHERE t_no={$tno}");
    if ($t_res) $train = mysqli_fetch_assoc($t_res);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>E-Ticket - IRCTC Style</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f4f4f4; font-family: Arial, Helvetica, sans-serif; }
        .ticket {
            width: 800px;
            margin: 40px auto;
            background: #fff;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 6px;
        }
        .header { display:flex; align-items: center; }
        .logo { width:100px; }
        .title { flex:1; text-align:center; }
        .title h2 { margin:0; }
        .details { margin-top:20px; display:flex; }
        .col { flex:1; padding:10px; }
        .row { margin-bottom:8px; }
        .print-btn { text-align:right; margin-top:10px; }
        @media print {
            .print-btn { display:none; }
        }
    </style>
</head>
<body>
<?php include('header.php'); ?>
<div class="ticket">
    <div class="header">
        <img class="logo" src="img/logo.png" alt="Railway Logo">
        <div class="title">
            <h2>Indian Railways - E-Ticket</h2>
            <div>PNR: <?php echo htmlspecialchars($ticket['PNR']); ?></div>
        </div>
        <div style="text-align:right;">
            <strong>Status:</strong> <span id="ticketStatus"><?php echo htmlspecialchars($ticket['t_status']); ?></span><br>
            <strong>Fare:</strong> Rs. <?php echo htmlspecialchars($ticket['t_fare']); ?>
        </div>
    </div>

    <div class="details">
        <div class="col">
            <h3>Passenger Details</h3>
            <div class="row"><strong>Name:</strong> <?php echo htmlspecialchars($passenger['p_fname'] . ' ' . $passenger['p_lname']); ?></div>
            <div class="row"><strong>Age:</strong> <?php echo htmlspecialchars($passenger['p_age']); ?></div>
            <div class="row"><strong>Contact:</strong> <?php echo htmlspecialchars($passenger['p_contact']); ?></div>
            <div class="row"><strong>Email:</strong> <?php echo htmlspecialchars($passenger['email']); ?></div>
        </div>
        <div class="col">
            <h3>Journey Details</h3>
            <div class="row"><strong>Train:</strong> <?php echo $train ? htmlspecialchars($train['t_name']) : 'N/A'; ?></div>
            <div class="row"><strong>Train No:</strong> <?php echo $train ? htmlspecialchars($train['t_no']) : 'N/A'; ?></div>
            <div class="row"><strong>From:</strong> <?php echo $train ? htmlspecialchars($train['t_source']) : 'N/A'; ?></div>
            <div class="row"><strong>To:</strong> <?php echo $train ? htmlspecialchars($train['t_destination']) : 'N/A'; ?></div>
            <div class="row"><strong>Seat/Coach:</strong> Not Allocated</div>
        </div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:20px; margin-top:18px; align-items:flex-start;">
        <div class="payment-box" style="background:#f7fbff;border:1px solid #e6f0ff;padding:12px;border-radius:8px;width:260px;text-align:center;">
            <div style="font-weight:700;margin-bottom:8px;">Pay only via GPay</div>
            <div id="payArea">
                <button id="showQrBtn" style="margin-top:10px;padding:8px 12px;border-radius:6px;border:none;background:#1e73be;color:#fff;cursor:pointer;">Pay via GPay</button>
            </div>
            <div id="qrArea" style="display:none;margin-top:8px;">
                <img src="img/image.png" alt="QR Code" id="qrImg" style="width:140px;height:140px;object-fit:contain;border:1px solid #ddd;padding:6px;background:#fff;border-radius:6px;"/>
                <div style="margin-top:8px;font-size:13px;color:#333;">Scan to pay</div>
                <button id="confirmQrBtn" style="margin-top:10px;padding:8px 12px;border-radius:6px;border:none;background:#2fa84f;color:#fff;cursor:pointer;">Confirm QR Scanned</button>
                <div id="countdown" style="margin-top:8px;font-size:13px;color:#d35400;display:none;">Confirming in <span id="sec">10</span>s...</div>
                <div id="payMsg" style="margin-top:8px;font-size:13px;color:#2a7f3a;display:none;">Payment confirmed</div>
            </div>
        </div>
    </div>

    <div style="margin-top:20px;">
        <strong>Important Details:</strong>
        <ul>
            <li>Please carry a valid government-issued photo ID while traveling.</li>
            <li>This is a computerized ticket. No signature required.</li>
        </ul>
    </div>

    <div class="print-btn">
        <button onclick="window.print()">Print E-Ticket</button>
        <a href="pnrstatus.php" class="button">Back to PNR Status</a>
    </div>
</div>
<script>
    var pnr = '<?php echo addslashes($ticket['PNR']); ?>';
    document.addEventListener('DOMContentLoaded', function(){
        var showBtn = document.getElementById('showQrBtn');
        var payArea = document.getElementById('payArea');
        var qrArea = document.getElementById('qrArea');
        var confirmBtn = document.getElementById('confirmQrBtn');
        var countdownEl = document.getElementById('countdown');
        var secSpan = document.getElementById('sec');
        var payMsg = document.getElementById('payMsg');

        if (showBtn) showBtn.addEventListener('click', function(){
            if (payArea) payArea.style.display = 'none';
            if (qrArea) qrArea.style.display = 'block';
        });

        if (confirmBtn) confirmBtn.addEventListener('click', function(){
            // start countdown and submit a normal POST when done
            var sec = 10;
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Confirming...';
            if (countdownEl) countdownEl.style.display = 'block';
            if (secSpan) secSpan.textContent = sec;
            var timer = setInterval(function(){
                sec--;
                if (secSpan) secSpan.textContent = sec;
                if (sec <= 0) {
                    clearInterval(timer);
                    // create and submit a POST form to pay_ticket.php
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'pay_ticket.php';
                    var inp = document.createElement('input'); inp.type = 'hidden'; inp.name = 'pnr'; inp.value = pnr; form.appendChild(inp);
                    // redirect back to this page after payment
                    var ret = document.createElement('input'); ret.type = 'hidden'; ret.name = 'return_to'; ret.value = window.location.pathname + window.location.search; form.appendChild(ret);
                    document.body.appendChild(form);
                    form.submit();
                }
            }, 1000);
        });
    });

    // auto print support if ?print=1
    (function(){
        var params = new URLSearchParams(window.location.search);
        if (params.get('print') === '1') {
            setTimeout(function(){ window.print(); }, 400);
        }
    })();
</script>
</body>
</html>
