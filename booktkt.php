<?php
session_start();
include_once('db_config.php');

// handle booking POST (user clicked "Book")
if (isset($_POST['book_submit'])) {
	$selected_tno = isset($_POST['trains']) ? intval($_POST['trains']) : 0;
	if ($selected_tno <= 0) {
		echo "<script>alert	('Please select a train before booking');</script>";
	} else {
		// fetch train details
		$tstmt = mysqli_prepare($conn, 'SELECT t_no, t_name FROM trains WHERE t_no = ?');
		mysqli_stmt_bind_param($tstmt, 'i', $selected_tno);
		mysqli_stmt_execute($tstmt);
		$tres = mysqli_stmt_get_result($tstmt);
		$train = mysqli_fetch_assoc($tres);

		$email = isset($_SESSION['user_info']) ? $_SESSION['user_info'] : '';
		$p_res = mysqli_prepare($conn, 'SELECT p_id FROM passengers WHERE email = ?');
		mysqli_stmt_bind_param($p_res, 's', $email);
		mysqli_stmt_execute($p_res);
		$pres = mysqli_stmt_get_result($p_res);
		$p_row = mysqli_fetch_assoc($pres);

		if (!$p_row || !$train) {
			echo "<script>alert('Booking failed: invalid user or train');</script>";
		} else {
			$p_id = $p_row['p_id'];
			// generate unique PNR
			do {
				$pnr = strval(mt_rand(1000000000, 1999999999));
				$chk = mysqli_query($conn, "SELECT PNR FROM tickets WHERE PNR='$pnr'");
			} while ($chk && mysqli_num_rows($chk) > 0);

				$fare = 500; // placeholder fare
				// ensure tickets table has a t_date column to store travel date
				$colRes = mysqli_query($conn, "SHOW COLUMNS FROM tickets LIKE 't_date'");
				if (!$colRes || mysqli_num_rows($colRes) == 0) {
					// try to add the column
					mysqli_query($conn, "ALTER TABLE tickets ADD COLUMN t_date DATE NULL");
				}

			// ensure tickets table has a t_no column to store train number per-ticket
			$colRes2 = mysqli_query($conn, "SHOW COLUMNS FROM tickets LIKE 't_no'");
			if (!$colRes2 || mysqli_num_rows($colRes2) == 0) {
				// try to add the column
				mysqli_query($conn, "ALTER TABLE tickets ADD COLUMN t_no INT NULL");
			}
				// use selected date from form if provided
				$travel_date = null;
				if (!empty($_POST['date'])) {
					$d = $_POST['date'];
					// basic validation YYYY-MM-DD
					if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) $travel_date = $d;
				}

				// re-check whether t_no exists (ALTER may have failed if no permission)
				$colResCheck = mysqli_query($conn, "SHOW COLUMNS FROM tickets LIKE 't_no'");
				$has_tno = ($colResCheck && mysqli_num_rows($colResCheck) > 0);

				if ($travel_date) {
					if ($has_tno) {
						$ins = mysqli_prepare($conn, 'INSERT INTO tickets (PNR, t_status, t_fare, p_id, t_date, t_no) VALUES (?, ?, ?, ?, ?, ?)');
						$status = 'Unpaid';
						// types: pnr(s), status(s), fare(i), p_id(i), t_date(s), t_no(i)
						mysqli_stmt_bind_param($ins, 'ssiisi', $pnr, $status, $fare, $p_id, $travel_date, $selected_tno);
					} else {
						$ins = mysqli_prepare($conn, 'INSERT INTO tickets (PNR, t_status, t_fare, p_id, t_date) VALUES (?, ?, ?, ?, ?)');
						$status = 'Unpaid';
						// types: pnr(s), status(s), fare(i), p_id(i), t_date(s)
						mysqli_stmt_bind_param($ins, 'ssiis', $pnr, $status, $fare, $p_id, $travel_date);
					}
				} else {
					if ($has_tno) {
						$ins = mysqli_prepare($conn, 'INSERT INTO tickets (PNR, t_status, t_fare, p_id, t_no) VALUES (?, ?, ?, ?, ?)');
						$status = 'Unpaid';
						mysqli_stmt_bind_param($ins, 'ssiii', $pnr, $status, $fare, $p_id, $selected_tno);
					} else {
						$ins = mysqli_prepare($conn, 'INSERT INTO tickets (PNR, t_status, t_fare, p_id) VALUES (?, ?, ?, ?)');
						$status = 'Unpaid';
						mysqli_stmt_bind_param($ins, 'ssii', $pnr, $status, $fare, $p_id);
					}
				}
			if (mysqli_stmt_execute($ins)) {
				// update passenger's t_no field (optional)
				$u = mysqli_prepare($conn, 'UPDATE passengers SET t_no = ? WHERE p_id = ?');
				mysqli_stmt_bind_param($u, 'ii', $selected_tno, $p_id);
				mysqli_stmt_execute($u);
				header('Location: eticket.php?pnr=' . $pnr);
				exit();
			} else {
				echo "<script>alert('Booking failed');</script>";
			}
		}
	}
}

$search_results = [];
// handle search for trains via server-side POST (non-AJAX)
if (isset($_POST['search_trains'])) {
	$from = isset($_POST['from']) ? trim($_POST['from']) : '';
	$to = isset($_POST['to']) ? trim($_POST['to']) : '';
	if ($from && $to) {
		$stmt = mysqli_prepare($conn, "SELECT t_no, t_name, t_source, t_destination FROM trains WHERE LOWER(t_source)=LOWER(?) AND LOWER(t_destination)=LOWER(?)");
		mysqli_stmt_bind_param($stmt, 'ss', $from, $to);
		mysqli_stmt_execute($stmt);
		$gres = mysqli_stmt_get_result($stmt);
		while ($r = mysqli_fetch_assoc($gres)) $search_results[] = $r;
	}
}

// fetch station list to populate datalist
$stations = [];
$sres = mysqli_query($conn, "SELECT DISTINCT s_name FROM station ORDER BY s_name");
if ($sres) {
	while ($srow = mysqli_fetch_assoc($sres)) $stations[] = $srow['s_name'];
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Book Ticket</title>
	<link rel="stylesheet" href="style.css">
	<style>
		body { background: url(img/bg7.jpg) no-repeat center center fixed; background-size: cover; }
		.book-wrap { max-width:900px; margin:40px auto; background: #fff; padding:30px 40px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.1); }
		.row { display:flex; gap:16px; margin-bottom:14px; }
		.col { flex:1; }
		label{ display:block; font-weight:600; margin-bottom:6px; }
		input[type=text], input[type=date], select { width:100%; padding:10px 12px; border:1px solid #ccc; border-radius:6px; }
		.actions { display:flex; gap:12px; margin-top:12px; }
		.search-btn, .book-btn { background:#ff8c3b; color:#fff; padding:10px 18px; border:none; border-radius:6px; cursor:pointer; }
		.search-btn:hover, .book-btn:hover { opacity:0.95 }
		#trains { padding:8px; }
		#trains option { padding:8px }
		.results-note { margin-top:8px; color:#555 }
	</style>
	<!-- Using server-side search (no AJAX). Submit the Search button to search trains on the server. -->
</head>
<body>
<?php include('header.php'); ?>
<div class="book-wrap">
	<h1 style="text-align:center; color:#264167;">BOOK TICKET</h1>
	<form method="post" id="searchForm">
		<div class="row">
			<div class="col">
				<label for="from">From</label>
				<input list="stations" id="from" name="from" type="text" placeholder="From">
			</div>
			<div class="col">
				<label for="date">Date</label>
				<input id="date" name="date" type="date" value="<?php echo date('Y-m-d'); ?>">
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label for="to">To</label>
				<input list="stations" id="to" name="to" type="text" placeholder="To">
			</div>
			<div class="col">
				<label for="class">Class</label>
				<select id="class" name="class">
					<option value="GEN">GENERAL</option>
					<option value="SL">SLEEPER</option>
					<option value="3A">3AC</option>
					<option value="2A">2AC</option>
					<option value="1A">1AC</option>
				</select>
			</div>
		</div>

		<div style="margin-top:8px;">
			<datalist id="stations">
				<?php foreach($stations as $s) echo '<option value="'.htmlspecialchars($s).'">'; ?>
			</datalist>
		</div>

		<div class="row">
			<div class="col">
				<label for="trains">Available Trains</label>
				<select id="trains" name="trains">
					<option selected disabled>Search trains using From & To</option>
				</select>
				<div class="results-note"></div>
			</div>
		</div>

		<div class="actions">
			<button type="submit" name="search_trains" class="search-btn">Search</button>
			<button type="submit" name="book_submit" class="book-btn">Book Selected Train</button>
		</div>
	</form>
</div>
<script>
// If search results were returned by server, populate the select client-side (simple JS, no AJAX)
<?php if (!empty($search_results)) { ?>
document.addEventListener('DOMContentLoaded', function(){
	var sel = document.getElementById('trains');
	sel.innerHTML = '';
	var opt = document.createElement('option'); opt.text = '-- Select a train --'; opt.disabled = true; opt.selected = true; sel.add(opt);
	<?php foreach ($search_results as $t) { $label = htmlspecialchars($t['t_name'].' — '.$t['t_source'].' → '.$t['t_destination'].' (No: '.$t['t_no'].')'); ?>
	var o = document.createElement('option'); o.value = '<?php echo $t['t_no']; ?>'; o.text = '<?php echo $label; ?>'; sel.add(o);
	<?php } ?>
	var note = document.querySelector('.results-note'); if (note) note.textContent = '<?php echo count($search_results); ?> train(s) found';
});
<?php } ?>
</script>
</body>
</html>