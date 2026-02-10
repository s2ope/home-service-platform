<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
session_start();
$con = mysqli_connect("localhost", "root", "", "gharsewa");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

// Check for success message in session
if (isset($_SESSION['success_message'])) {
    $msg = $_SESSION['success_message'];
    $msg_color = 'green';
    unset($_SESSION['success_message']); // Clear the message after displaying
} else {
    $msg = '';
    $msg_color = 'green';
}

$sname = isset($_GET['sname']) ? $_GET['sname'] : '';
$pid = isset($_GET['pid']) ? $_GET['pid'] : '';
$sid = '';

if ($pid != '') {
    $check_provider = $con->prepare("SELECT pid FROM provider WHERE pid = ?");
    $check_provider->bind_param("i", $pid);
    $check_provider->execute();
    $provider_result = $check_provider->get_result();

    if ($provider_result->num_rows == 0) {
        $msg = "Invalid provider selected. Please try again.";
        $msg_color = 'red';
        $pid = '';
    }
}

if ($sname != '' && $pid != '') {
    $query = "SELECT sid FROM services WHERE sname = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $sname);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $res = $result->fetch_assoc();
        $sid = $res['sid'];
    } else {
        $msg = "Service not found.";
        $msg_color = 'red';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION["consumer_id"])) {
    $pname = $_POST['pname'];
    $pemailid = $_POST['pemailid'];
    $pphnno = $_POST['pphnno'];
    $sid = $_POST['sid'];
    $stype = $_POST['stype'];
    $req_date = $_POST['req_date'];
    $req_time = $_POST['req_time'];
    $pid = $_POST['pid'];
    $cid = $_SESSION["consumer_id"];

    $check_provider = $con->prepare("SELECT pid FROM provider WHERE pid = ?");
    $check_provider->bind_param("i", $pid);
    $check_provider->execute();
    $provider_result = $check_provider->get_result();

    if ($provider_result->num_rows == 0) {
        $_SESSION['error_message'] = "Invalid provider selected. Please try again.";
        $_SESSION['error_color'] = 'red';
        header("location: service_booking.php?pid=" . $pid . "&sname=" . $sname);
        exit();
    } else {
        $query1 = "SELECT * FROM service_request WHERE consumer_id = ? AND service_id = ? AND req_date = ? AND status = 0";
        $stmt1 = $con->prepare($query1);
        $stmt1->bind_param("iis", $cid, $sid, $req_date);
        $stmt1->execute();
        $result1 = $stmt1->get_result();

        if ($result1->num_rows > 0) {
            $_SESSION['error_message'] = "You already have a pending request for this service on the selected date.";
            $_SESSION['error_color'] = 'red';
            header("location: service_booking.php?pid=" . $pid . "&sname=" . $sname);
            exit();
        } else {
            $query2 = "SELECT * FROM service_request WHERE provider_id = ? AND req_date = ? AND req_time = ? AND status = 1 AND (work_status = 0 OR payment_status = 0)";
            $stmt2 = $con->prepare($query2);
            $stmt2->bind_param("iss", $pid, $req_date, $req_time);
            $stmt2->execute();
            $result2 = $stmt2->get_result();

            if ($result2->num_rows > 0) {
                $_SESSION['error_message'] = "Provider is already booked at the requested time.";
                $_SESSION['error_color'] = 'red';
                header("location: service_booking.php?pid=" . $pid . "&sname=" . $sname);
                exit();
            } else {
                $payment_status = 0;
                $work_status = 0;
                $status = 0;
                $read_status = 0;
                $read_status_c = 0;
                $msgc = "Your Request has been sent to the provider. The provider will respond ASAP";
                $msgp = "You have a new request";

                $query3 = "INSERT INTO service_request (consumer_id, provider_id, service_id, req_date, req_time, payment_status, work_status, status, read_status_c, read_status, msgc, msgp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt3 = $con->prepare($query3);
                $stmt3->bind_param("iiissiiiisss", $cid, $pid, $sid, $req_date, $req_time, $payment_status, $work_status, $status, $read_status_c, $read_status, $msgc, $msgp);

                if ($stmt3->execute()) {
                    $_SESSION['success_message'] = "Service booked successfully!";
                    header("location: service_booking.php?pid=" . $pid . "&sname=" . $sname);
                    exit();
                } else {
                    $_SESSION['error_message'] = "Error: " . $stmt3->error;
                    $_SESSION['error_color'] = 'red';
                    header("location: service_booking.php?pid=" . $pid . "&sname=" . $sname);
                    exit();
                }
            }
        }
    }
}

// Check for error message in session
if (isset($_SESSION['error_message'])) {
    $msg = $_SESSION['error_message'];
    $msg_color = $_SESSION['error_color'];
    unset($_SESSION['error_message']);
    unset($_SESSION['error_color']);
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
                <a href="index.html" class="logo"><strong>Ghar Sewa</strong></a>
            </header>

            <br>
            <h2 id="elements">Service Booking</h2>
            <hr class="major" />
            <div class="row gtr-200">
                <div class="col-6 col-12-medium">
                    <form method="post" action="service_booking.php?pid=<?php echo htmlspecialchars($pid); ?>&sname=<?php echo htmlspecialchars($sname); ?>">
                        <div class="row gtr-uniform">
                            <div class="col-12">
                                <?php if (!empty($msg)): ?>
                                    <label><h3 style="color:<?php echo $msg_color; ?>"><?php echo $msg; ?></h3></label>
                                <?php endif; ?>
                            </div>

                            <?php if ($pid == ''): ?>
                                <div class="col-12">
                                    <h3 style="color:red">No valid provider selected. Please go back and try again.</h3>
                                </div>
                            <?php else: ?>
                                <div class="col-12">
                                    <label>Requested Date</label>
                                    <input type="date" name="req_date" id="req_date" min="<?php echo date('Y-m-d'); ?>" required />
                                </div>

                                <div class="col-12">
                                    <label>Requested Time</label>
                                    <select name="req_time" id="req_time" required>
                                        <option value="Morning(Before Noon)">Morning(Before Noon)</option>
                                        <option value="Afternoon">Afternoon</option>
                                    </select>
                                </div>

                                <input type="hidden" name="pname" value="<?php echo htmlspecialchars(isset($_GET["pname"]) ? $_GET["pname"] : ""); ?>" />
                                <input type="hidden" name="pemailid" value="<?php echo htmlspecialchars(isset($_GET["email"]) ? $_GET["email"] : ""); ?>" />
                                <input type="hidden" name="pphnno" value="<?php echo htmlspecialchars(isset($_GET["phnno"]) ? $_GET["phnno"] : ""); ?>" />
                                <input type="hidden" name="sid" value="<?php echo htmlspecialchars($sid); ?>" />
                                <input type="hidden" name="stype" value="<?php echo htmlspecialchars(isset($_GET["sname"]) ? $_GET["sname"] : ""); ?>" />
                                <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>" />
                                <input type="hidden" name="sname" value="<?php echo htmlspecialchars($sname); ?>" />

                                <div class="col-12">
                                    <div><strong>Note:</strong> Total Cost will be determined after the provider assesses your request.</div>
                                </div>

                                <div class="col-12">
                                    <ul class="actions">
                                        <li><input type="submit" name="sbbtn" value="Book Service" class="primary" /></li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
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