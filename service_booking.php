<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
session_start();
$con = mysqli_connect("localhost", "root", "", "homeservice");
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
$provider_phone = '';

$selected_lat = $_GET['lat'] ?? '';
$selected_lng = $_GET['lng'] ?? '';


// ------------------- VALIDATE PROVIDER -------------------
if ($pid != '') {
    $check_provider = $con->prepare("SELECT pid, phnno FROM provider WHERE pid = ?");
    $check_provider->bind_param("i", $pid);
    $check_provider->execute();
    $provider_result = $check_provider->get_result();
    if ($provider_result->num_rows == 0) {
        $msg = "Invalid provider selected. Please try again.";
        $msg_color = 'red';
        $pid = '';
    } else {
        $provider_data = $provider_result->fetch_assoc();
        $provider_phone = $provider_data['phnno'];
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
                $user_lat = $_POST['user_lat'] ?? null;
$user_lng = $_POST['user_lng'] ?? null;


              $query3 = "INSERT INTO service_request 
(consumer_id, provider_id, service_id, req_date, req_time, payment_status, work_status, status, read_status_c, read_status, msgc, msgp, user_lat, user_lng) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt3 = $con->prepare($query3);
$stmt3->bind_param("iiissiiiisssdd", 
    $cid, 
    $pid, 
    $sid, 
    $req_date, 
    $req_time, 
    $payment_status, 
    $work_status, 
    $status, 
    $read_status_c, 
    $read_status, 
    $msgc, 
    $msgp,
    $user_lat,
    $user_lng
);

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

// ------------------- GENERATE TIME SLOTS -------------------
$slots = [];
$booked = [];
$startHour = 9; // 9 AM
$endHour = 18; // 6 PM

if ($pid != '' && $sid != '') {
    for ($h = $startHour; $h < $endHour; $h++) {
        $slots[] = sprintf('%02d:00', $h); // 09:00, 10:00, etc.
    }

    if (isset($_POST['req_date'])) {
        $selected_date = $_POST['req_date'];
        $result = $con->query("SELECT req_time FROM service_request WHERE provider_id=$pid AND req_date='$selected_date' AND status=1");
        while ($row = $result->fetch_assoc()) {
            $booked[] = $row['req_time'];
        }
    }
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

                                                  <label>Select Time Slot</label>
                            <select name="req_time" required>
                                <?php
                                foreach ($slots as $slot) {
                                    $disabled = in_array($slot, $booked) ? 'disabled' : '';
                                    $display = date('h:i A', strtotime($slot)) . " - " . date('h:i A', strtotime($slot . ' +1 hour'));
                                    echo "<option value='$slot' $disabled>$display</option>";
                                }
                                ?>
                            </select>
                            <br><br><br>
                            <label>Service Duration</label>
                            <input type="number" name="service_duration" value="60" readonly /> <!-- Fixed 1-hour slot -->

                            <!-- Optional Call Provider -->
                            <a href="tel:+<?php echo $provider_phone; ?>" class="call-btn">Call Provider</a>

                                <input type="hidden" name="pname" value="<?php echo htmlspecialchars(isset($_GET["pname"]) ? $_GET["pname"] : ""); ?>" />
                                <input type="hidden" name="pemailid" value="<?php echo htmlspecialchars(isset($_GET["email"]) ? $_GET["email"] : ""); ?>" />
                                <input type="hidden" name="pphnno" value="<?php echo htmlspecialchars(isset($_GET["phnno"]) ? $_GET["phnno"] : ""); ?>" />
                                <input type="hidden" name="sid" value="<?php echo htmlspecialchars($sid); ?>" />
                                <input type="hidden" name="stype" value="<?php echo htmlspecialchars(isset($_GET["sname"]) ? $_GET["sname"] : ""); ?>" />
                                <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>" />
                                <input type="hidden" name="sname" value="<?php echo htmlspecialchars($sname); ?>" />
<input type="hidden" id="user_lat" name="user_lat" value="<?php echo $selected_lat ?? ''; ?>">
<input type="hidden" id="user_lng" name="user_lng" value="<?php echo $selected_lng ?? ''; ?>">

Location:
<input type="text" id="location_display" value="<?php 
    if(!empty($selected_lat) && !empty($selected_lng)){
        echo $selected_lat . ", " . $selected_lng;
    }
?>" readonly>

<br><br>

<button type="button" name="sbbtn" class="primary" onclick="openMap()">Select Location</button>




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
<script>
function openMap(){
    window.open("select_location.php", "Select Location", "width=600,height=500");
}
</script>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/browser.min.js"></script>
<script src="assets/js/breakpoints.min.js"></script>
<script src="assets/js/util.js"></script>
<script src="assets/js/main.js"></script>

</body>
</html>