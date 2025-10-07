<?php 
session_start();
include_once('db_config.php');
if (isset($_POST['submit']))
{
	// variable to hold display HTML
	$ticket_info = '';
	$pnr = mysqli_real_escape_string($conn, $_POST['pnr']);
	// fetch ticket and related passenger
	$sql = "SELECT t.PNR, t.t_status, t.t_fare, t.p_id, p.p_fname, p.p_lname, p.p_age, p.p_contact, p.email, p.t_no
			FROM tickets t
			LEFT JOIN passengers p ON t.p_id = p.p_id
			WHERE t.PNR = '$pnr'";
	$result = mysqli_query($conn, $sql);
	$row = mysqli_fetch_assoc($result);
	if (!$row) {
		$ticket_info = "<div style='color:red; text-align:center;'>PNR not found</div>";
	} else {
		// fetch train details for this ticket's train number (ticket.t_no)
		$train_html = '';
		if (!empty($row['t_no'])) {
			$tno = intval($row['t_no']);
			$tres = mysqli_query($conn, "SELECT t_name, t_source, t_destination FROM trains WHERE t_no={$tno}");
			$trow = mysqli_fetch_assoc($tres);
			if ($trow) {
				$train_html = "<div><strong>Train:</strong> " . htmlspecialchars($trow['t_name']) . " (" . htmlspecialchars($tno) . ")</div>";
				$train_html .= "<div><strong>From:</strong> " . htmlspecialchars($trow['t_source']) . "</div>";
				$train_html .= "<div><strong>To:</strong> " . htmlspecialchars($trow['t_destination']) . "</div>";
			}
		}

		// styled ticket card
		$ticket_info = "<div class='ticket-card'>";
		$ticket_info .= "<div class='ticket-header'><div class='left'><strong>PNR: " . htmlspecialchars($row['PNR']) . "</strong></div><div class='right'><strong>Status: " . htmlspecialchars($row['t_status']) . "</strong></div></div>";
		$ticket_info .= "<div class='ticket-body'><div class='col'><h4>Passenger</h4>";
		$ticket_info .= "<div><strong>Name:</strong> " . htmlspecialchars($row['p_fname'] . ' ' . $row['p_lname']) . "</div>";
		$ticket_info .= "<div><strong>Age:</strong> " . htmlspecialchars($row['p_age']) . "</div>";
		$ticket_info .= "<div><strong>Contact:</strong> " . htmlspecialchars($row['p_contact']) . "</div>";
		$ticket_info .= "<div><strong>Email:</strong> " . htmlspecialchars($row['email']) . "</div></div>";
		$ticket_info .= "<div class='col'><h4>Journey</h4>";
		if ($train_html) {
			$ticket_info .= $train_html;
		} else {
			$ticket_info .= "<div>Train details not available</div>";
		}
		$ticket_info .= "<div style='margin-top:8px;'><strong>Fare:</strong> Rs. " . htmlspecialchars($row['t_fare']) . "</div>";
		$ticket_info .= "</div></div>";
		$ticket_info .= "<div class='ticket-footer'><button onclick=\"window.print()\" class='button'>Print</button> ";
		$ticket_info .= "<a href='eticket.php?pnr=" . htmlspecialchars($row['PNR']) . "' class='button'>Open E-Ticket</a></div>";
		$ticket_info .= "</div>";
	}
}
if (isset($_POST['cancel']))
{
$pnr=$_POST['pnr'];
$sql = "DELETE FROM tickets WHERE PNR=$pnr;";
if(mysqli_query($conn, $sql))
	echo "<script type='text/javascript'>alert('Your ticket has been cancelled');</script>";
	else echo "<script type='text/javascript'>alert('Cancellation failed');</script>";	
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>PNR Status</title>
	<LINK REL="STYLESHEET" HREF="STYLE.CSS">
	<style type="text/css">
		#pnr	{
			font-size: 20px;
			background-color: white;
			width: 500px;
			height: 300px;
			margin: auto;
			border-radius: 25px;
			border: 2px solid blue; 
			margin: auto;
  			position: absolute;
  			left: 0; 
  			right: 0;
  			padding-top: 40px;
  			padding-bottom:20px;
  			margin-top: 130px;
 
		}
		html { 
		  background: url(img/bg7.jpg) no-repeat center center fixed; 
		  -webkit-background-size: cover;
		  -moz-background-size: cover;
		  -o-background-size: cover;
		  background-size: cover;
		}
		#pnrtext	{
			padding-top: 20px;
		}

		/* ticket card styles */
		.ticket-card{
			background: linear-gradient(#ffffff,#f9fbff);
			width:540px;
			margin:20px auto;
			border-radius:10px;
			box-shadow:0 4px 10px rgba(0,0,0,0.15);
			overflow:hidden;
			border:1px solid #e0e6f0;
		}
		.ticket-header{display:flex;justify-content:space-between;padding:12px 16px;background:#eef3ff;border-bottom:1px solid #e6ecff}
		.ticket-body{display:flex;padding:16px;}
		.ticket-body .col{flex:1;padding:0 10px}
		.ticket-body h4{margin-top:0}
		.ticket-footer{padding:12px 16px;text-align:right;border-top:1px dashed #e6ecff;background:#fafcff}
		.ticket-footer .button{margin-left:8px}

		@media print{.ticket-footer .button{display:none} body{background:#fff}}
	</style>
</head>
<body>
<?php
include("header.php"); ?>
<center>
	<div id="pnr">Check your PNR status here:<br/><br/>
	<form method="post" name="pnrstatus" action="pnrstatus.php">
	<div id="pnrtext"><input type="text" name="pnr" size="30" maxlength="20" placeholder="Enter PNR here"></div>
	<br/><br/>
	<input type="submit" name="submit" value="Check here!" class="button" id="submit"><br/><br/>
	<?php  
		if(isset($_SESSION['user_info'])){
			echo '<form action="pnrstatus.php" method="post"><input type="submit" class="button" value="Cancel your ticket!" name="cancel" id="cancel"/></form>';
		}
		else
			echo '<A HREF="register.php">Login/Register</A>';
		?>
	</form>

	<?php
		// show ticket info if set
		if (!empty($ticket_info)) echo $ticket_info;
	?>
	</div>
</center>
</body>
</html>