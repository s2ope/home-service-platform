<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
// Start a generic session to handle messages
session_start();

$con = mysqli_connect("localhost", "root", "", "gharsewa");
if (mysqli_connect_errno()) {
    echo "Connection failed: " . mysqli_connect_error();
    exit();
}

// Initialize message variable from session if it exists
$msg = isset($_SESSION['login_msg']) ? $_SESSION['login_msg'] : '';
unset($_SESSION['login_msg']); // Clear the message after displaying

if (isset($_POST["sbbtn"])) {
    $emailid = $_POST["emailid"];
    $pass = $_POST["pass"];

    // Check if the email is "admin@gmail.com"
    if ($emailid == "admin@gmail.com") {
        // Query to fetch admin's password from the users table
        $query = "SELECT password FROM users WHERE email=?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $emailid);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($stored_pass);
            $stmt->fetch();

            // Match the provided password with the stored password
            if ($pass == $stored_pass) {
                // Start admin-specific session
                session_name('admin_session');
                session_start();
                $_SESSION['emailid'] = $emailid;
                $_SESSION['utype'] = "Admin";
                $_SESSION['name'] = "Admin";
                $_SESSION['id'] = 3;

                header("Location: view_arequest.php");
                exit();
            } else {
                $_SESSION['login_msg'] = "Invalid Password.";
            }
        } else {
            $_SESSION['login_msg'] = "Admin account not found.";
        }
        $stmt->close();
    } else {
        // Handle other user types (Consumer or Provider)
        $query = "SELECT user_type, password FROM users WHERE email=?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $emailid);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_type, $stored_pass);
            $stmt->fetch();

            if ($pass == $stored_pass) {
                if ($user_type == 1) { // Consumer
                    // Consumer: Fetch data from the 'consumer' table
                    $user_query = "SELECT cid, fname, mname, lname, photo, phnno FROM consumer WHERE email=?";
                    $user_stmt = $con->prepare($user_query);
                    $user_stmt->bind_param("s", $emailid);
                    $user_stmt->execute();
                    $user_stmt->store_result();

                    if ($user_stmt->num_rows > 0) {
                        $user_stmt->bind_result($id, $fname, $mname, $lname, $img, $mno);
                        $user_stmt->fetch();

                        // Start consumer-specific session
                        session_name('consumer_session');
                        session_start();
                        $_SESSION['consumer_id'] = $id;
                        $_SESSION["consumer_name"] = $fname . " " . $mname . " " . $lname;
                        $_SESSION["consumer_img"] = $img;
                        $_SESSION["consumer_mno"] = $mno;
                        $_SESSION["consumer_emailid"] = $emailid;
                        $_SESSION["consumer_utype"] = "Consumer";

                        header("Location: Welcome.php");
                        exit();
                    } else {
                        $_SESSION['login_msg'] = "Consumer data not found...";
                    }
                    $user_stmt->close();
                } elseif ($user_type == 2) { // Provider
                    // Provider: Fetch data from the 'provider' table
                    $user_query = "SELECT pid, fname, mname, lname, photo, phnno FROM provider WHERE email=?";
                    $user_stmt = $con->prepare($user_query);
                    $user_stmt->bind_param("s", $emailid);
                    $user_stmt->execute();
                    $user_stmt->store_result();

                    if ($user_stmt->num_rows > 0) {
                        $user_stmt->bind_result($id, $fname, $mname, $lname, $img, $mno);
                        $user_stmt->fetch();

                        // Start provider-specific session
                        session_name('provider_session');
                        session_start();
                        $_SESSION['provider_id'] = $id;
                        $_SESSION["provider_name"] = $fname . " " . $mname . " " . $lname;
                        $_SESSION["provider_img"] = $img;
                        $_SESSION["provider_mno"] = $mno;
                        $_SESSION["provider_emailid"] = $emailid;
                        $_SESSION["provider_utype"] = "Provider";

                        header("Location: Mservices.php");
                        exit();
                    } else {
                        $_SESSION['login_msg'] = "Provider data not found...";
                    }
                    $user_stmt->close();
                } else {
                    $_SESSION['login_msg'] = "Unknown user type...";
                }
            } else {
                $_SESSION['login_msg'] = "Invalid Email or Password...";
            }
        } else {
            $_SESSION['login_msg'] = "Invalid Email or Password...";
        }
        $stmt->close();
    }
    $con->close();

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE HTML>
<html>

<head>
    <title>Online Household Service Portal</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
</head>

<body class="is-preload">

    <div id="wrapper">

        <div id="main">
            <div class="inner">

                <header id="header">
                    <a href="index.php" class="logo"><strong>Ghar Sewa</strong></a>
                </header>

                    <h2 id="elements">Sign In</h2>
                    <hr class="minor" />
                    <div class="row gtr-200">
                        <div class="col-6 col-12-medium">
                            <form method="post" action="signin.php" name="f1">
                                <div class="row gtr-uniform">
                                    <?php if (!empty($msg)) : ?>
                                        <div class="col-12">
                                            <h3 style="color: <?php echo (strpos($msg, 'Invalid') === false && strpos($msg, 'not found') === false) ? 'green' : 'red'; ?>"><?php echo htmlspecialchars($msg); ?></h3>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-12">
                                        <label>Email Id :</label>
                                        <input type="email" name="emailid" id="emailid" value="" required="" placeholder="Email id" />
                                    </div>
                                    <div class="col-12">
                                        <label>Password</label>
                                        <input type="password" name="pass" id="pass" value="" required="" placeholder="Password (At least 8 Characters)" />
                                        <br>
                                        <input type="checkbox" id="ch1" name="ch1" onclick="showpass()">
                                        <label for="ch1">Show Password</label>
                                    </div>
                                    <div class="col-12">
                                        <ul class="actions">
                                            <li><input type="submit" name="sbbtn" value="Login" class="primary" /></li>
                                            <li><input type="reset" value="Reset" /></li>
                                        </ul>
                                    </div>
                                    <div class="col-12">
                                        New User?&nbsp;&nbsp;<a href="SignUp.php">Sign Up Now!</a>
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
                <?php include "menu.php"; ?>
            </div>
        </div>

    </div>
    <script type="text/javascript">
        function showpass() {
            var x = document.getElementById("pass");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/browser.min.js"></script>
    <script src="assets/js/breakpoints.min.js"></script>
    <script src="assets/js/util.js"></script>
    <script src="assets/js/main.js"></script>

</body>

</html>