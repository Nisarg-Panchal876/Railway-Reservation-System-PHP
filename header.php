<!DOCTYPE html>
<html>
<head>
<title></title>
<link rel="stylesheet" href="s1.css" type="text/css">
<style type="text/css">
    li {
        font-family: sans-serif;
        font-size:18px;
    }
    /* small dropdown styling */
    #dropdown { position: relative; display: inline-block; }
    #user { cursor: pointer; }
    #Logout { position: absolute; top: 100%; left: 0; background: #fff; color: #000; border-radius: 4px; padding: 6px 10px; display: none; z-index: 9999; box-shadow: 0 2px 6px rgba(0,0,0,0.2); }
    #Logout a { color: #000; text-decoration: none; }
    /* No hover fallback: logout will be shown/hidden only when the user clicks the email (no animation) */
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var logoutBox = document.getElementById('Logout');
    var userSpan = document.getElementById('user');
    if (logoutBox) logoutBox.style.display = 'none';
    if (userSpan) {
        // toggle on click
        userSpan.addEventListener('click', function(e){
            e.preventDefault();
            if (logoutBox.style.display === 'none' || logoutBox.style.display === '') logoutBox.style.display = 'block';
            else logoutBox.style.display = 'none';
        });
        // hide when clicking elsewhere
        document.addEventListener('click', function(ev){
            if (!userSpan.contains(ev.target) && !logoutBox.contains(ev.target)) {
                logoutBox.style.display = 'none';
            }
        });
    }
});
</script>
</head>
<body link="white" alink="white" vlink="white">
     <div class="container dark">
        <div class="wrapper">
                    <div class="Menu">
                        <ul id="navmenu">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="pnrstatus.php">PNR Status</a></li>
                            <li><a href="booktkt.php">Book a ticket</a></li>
                            <?php if(isset($_SESSION['user_info'])){ ?>
                                <li><a href="my_tickets.php">My Tickets</a></li>
                                <li>
                                    <div id="dropdown" style="position:relative;">
                                        <span id="user" class="user-email"><?php echo htmlspecialchars($_SESSION['user_info']); ?></span>
                                        <div id="Logout"><a href="logout.php" id="logoutLink">Logout</a></div>
                                    </div>
                                </li>
                            <?php } else { ?>
                                <li><a href="register.php">Login/Register</a></li>
                            <?php } ?>
                        </ul>
                    </div>
        </div>
      </div>
</body>
</html>