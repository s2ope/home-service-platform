<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
// session_name('consumer_session');

// Start a session for customer
session_start();

if (!isset($_SESSION["consumer_utype"]) || $_SESSION["consumer_utype"] != "Consumer") {
    header("Location: Signin.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

$msg = ""; // Initialize $msg

if (isset($_POST["sbbtn"])) {
    $emailid = $_SESSION["consumer_emailid"];
    $opass = $_POST["opass"];
    $npass = $_POST["npass"];
    $rnpass = $_POST["rnpass"];
    $utype = $_SESSION["consumer_utype"];

    // Validate passwords
    if (strlen($npass) < 8) {
        $_SESSION['msg'] = "New Password contains Atleast 8 Characters.";
    } elseif ($npass != $rnpass) {
        $_SESSION['msg'] = "Retype Password is not matched.";
    } else {
        // Update the password in the user-specific table
        $query1 = "UPDATE $utype SET password = ? WHERE email = ? AND password = ?";
        $stmt1 = $con->prepare($query1);
        $stmt1->bind_param("sss", $npass, $emailid, $opass);
        $stmt1->execute();

        // Check if the update was successful in the user-specific table
        if ($stmt1->affected_rows > 0) {
            // Update the password in the users table
            $query2 = "UPDATE users SET password = ? WHERE email = ?";
            $stmt2 = $con->prepare($query2);
            $stmt2->bind_param("ss", $npass, $emailid);
            $stmt2->execute();

            if ($stmt2->affected_rows > 0) {
                $_SESSION['msg'] = "Password is successfully changed.";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Failed to update password in users table.";
                $_SESSION['msg_type'] = "error";
            }
        } else {
            $_SESSION['msg'] = "Incorrect Old Password.";
            $_SESSION['msg_type'] = "error";
        }
    }
    $con->close();
    
    // Redirect to prevent form resubmission
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Get message from session if it exists
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    $msg_type = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : "error";
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Online HouseHold Service Portal</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
</head>
<body class="is-preload">

    <div id="wrapper">
        <div id="main">
            <div class="inner">
                <header id="header">
                    <a href="welcome.php" class="logo"><strong>homeservice</strong></a>
                </header>

               
                    <h2 id="elements">Change Password</h2>
                    <hr class="minor" />
                    <div class="row gtr-200">
                        <div class="col-6 col-12-medium">
                            <form name="f1" method="post" action="Consumer_Cpass.php">
                                <div class="row gtr-uniform">
                                    <div class="col-12">
                                        <?php if (!empty($msg)): ?>
                                            <h3 style="color: <?php echo ($msg_type == "success") ? "green" : "red"; ?>"><?php echo htmlspecialchars($msg); ?></h3>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-12">
                                        <label>Old Password</label>
                                        <input type="password" name="opass" id="opass" value="" required="" placeholder="Old Password" /><br>
                                        <input type="checkbox" id="ch1" name="ch1" onclick="showpass(document.f1.opass)">
                                        <label for="ch1">Show Password</label>
                                    </div>

                                    <div class="col-12">
                                        <label>New Password</label>
                                        <input type="password" name="npass" id="npass" value="" required="" placeholder="New Password (Atleast 8 Characters)" /><br>
                                        <input type="checkbox" id="ch2" name="ch2" onclick="showpass(document.f1.npass)">
                                        <label for="ch2">Show Password</label>
                                    </div>

                                    <div class="col-12">
                                        <label>Retype New Password</label>
                                        <input type="password" name="rnpass" id="rnpass" value="" required="" placeholder="Retype New Password" /><br>
                                        <input type="checkbox" id="ch3" name="ch3" onclick="showpass(document.f1.rnpass)">
                                        <label for="ch3">Show Password</label>
                                    </div>

                                    <div class="col-12">
                                        <ul class="actions">
                                            <li><input type="submit" name="sbbtn" value="Change Password" class="primary" /></li>
                                            <li><input type="reset" value="Reset" /></li>
                                        </ul>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div id="sidebar">
            <div class="inner">
                <?php include "cmenu.php"; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/browser.min.js"></script>
    <script src="assets/js/breakpoints.min.js"></script>
    <script src="assets/js/util.js"></script>
    <script src="assets/js/main.js"></script>

    <script type="text/javascript">
        function showpass(x) {
            if (x.type == "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
</body>
</html>