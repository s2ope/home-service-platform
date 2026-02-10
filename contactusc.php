<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
session_start();

if(!isset($_SESSION['consumer_id'])) {
    header("Location: Signin.php");
    exit();
}

$msg = '';

if(isset($_POST["sbbtn"])) {
    $con = mysqli_connect("localhost", "root", "", "gharsewa");
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }

    $mm = mysqli_real_escape_string($con, $_POST["msg"]);
    
    $insert_query = "INSERT INTO feedbacks (email, name, message) VALUES ('".$_SESSION['consumer_emailid']."', '".$_SESSION['consumer_name']."', '$mm')";
    if(mysqli_query($con, $insert_query)) {
        $msg = "Thank you for your feedback!";
    } else {
        $msg = "Error submitting feedback. Please try again.";
    }
    mysqli_close($con);
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
                        <a href="index.php" class="logo"><strong>Ghar Sewa</strong> </a>
                    </header>
                    <section>
                        <header class="minor">
                            <h2>Contact Us At:</h2>
                        </header>
                        <div class="row gtr-200">
                            <div class="col-6 col-12-medium">
                                <section>
                                    <ul class="contact">
                                                <li class="icon solid fa-envelope"><a href="mailto:shoujanya57@pmc.edu.np">shoujanya57@pmc.edu.np</a></li>
                                                <li class="icon solid fa-phone">+977 9700000000</li>
                                                <li class="icon solid fa-envelope"><a href="mailto:sandesh52@pmc.edu.np">sandesh52@pmc.edu.np</a></li>
                                                <li class="icon solid fa-phone">+977 98000000</li>
                                                <li class="icon solid fa-home">PMC,<br />Patandhoka,<br />Lalitpur</li>
                                    </ul>
                                </section>
                            </div>
                            <div class="col-6 col-12-medium">
                                <form method="post" action="">
                                    <div class="row gtr-uniform">
                                        <div class="col-12">
                                            <label><h3 style="color:green"><?php echo $msg; ?></h3></label>
                                        </div>
                                        <div class="col-12">
                                            <label>Email Id:</label>
                                            <input type="email" name="emailid" id="emailid" value="<?php echo htmlspecialchars($_SESSION['consumer_emailid']); ?>" readonly required />
                                        </div>
                                        <div class="col-12">
                                            <label>Name:</label>
                                            <input type="text" name="nm" id="nm" value="<?php echo htmlspecialchars($_SESSION['consumer_name']); ?>" readonly required />
                                        </div>
                                        <div class="col-12">
                                            <label>Message:</label>
                                            <textarea name="msg" id="msg" placeholder="Enter Your Message" rows="5" required></textarea>
                                        </div>
                                        <div class="col-12">
                                            <ul class="actions">
                                                <li><input type="submit" name="sbbtn" value="Send" class="primary" /></li>
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
    </body>
</html>