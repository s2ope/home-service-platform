<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}


$_SESSION['consumer_id'] = $consumer_id; // for demo

$message = "";


if($_SERVER["REQUEST_METHOD"] == "POST") {

    $consumer_id = $_SESSION['consumer_id'] ?? null;
    $service_id = $_POST['service_id'] ?? null;
    $req_date = $_POST['req_date'] ?? null;
    $req_time = $_POST['req_time'] ?? null;
    $user_lat = $_POST['user_lat'] ?? $_SESSION['user_lat'] ?? null;
    $user_lng = $_POST['user_lng'] ?? $_SESSION['user_lng'] ?? null;

echo "Consumer ID: " . ($_SESSION['consumer_id'] ?? 'empty') . "<br>";
// echo "Service ID: " . ($_POST['service_id'] ?? 'empty') . "<br>";
echo "Date: " . ($_POST['req_date'] ?? 'empty') . "<br>";
echo "Time: " . ($_POST['req_time'] ?? 'empty') . "<br>";
echo "POST lat: " . ($_POST['user_lat'] ?? 'empty') . "<br>";
echo "POST lng: " . ($_POST['user_lng'] ?? 'empty') . "<br>";


$user_lat = $_SESSION['user_lat'] ?? '';
$user_lng = $_SESSION['user_lng'] ?? '';

    if(!$consumer_id || !$req_date || !$req_time || !$user_lat || !$user_lng){
        die("All fields are required, including location.");
    }

    // Find nearest provider
    $query = "
    SELECT pid,
    (6371 * ACOS(
      COS(RADIANS($user_lat)) *
      COS(RADIANS(latitude)) *
      COS(RADIANS(longitude) - RADIANS($user_lng)) +
      SIN(RADIANS($user_lat)) *
      SIN(RADIANS(latitude))
    )) AS distance
    FROM provider
    ORDER BY distance ASC
    LIMIT 1
    ";

    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);

    if(isset($consumer_id)){
    $insert = "
    INSERT INTO service_request 
(consumer_id, provider_id, service_id, req_date, req_time, status, work_status, payment_status, charge, user_lat, user_lng) 
VALUES 
('$consumer_id', 9, 102, '$req_date', '$req_time', 'Pending', 'Pending', 'Pending', 0.00, '$user_lat', '$user_lng');

    ";
    mysqli_query($con, $insert) or die(mysqli_error($con));
    echo "Booking successful. Provider assigned!";
}
$result = mysqli_query($con, $query) or die(mysqli_error($con));
$row = mysqli_fetch_assoc($result);

if($row && isset($row['provider_id'])){
    $provider_id = $row['provider_id'];
} else {
    die("No provider found nearby!");
}

}

// ------------------- Messages -------------------
$msg = '';
$msg_color = 'green';
if (isset($_SESSION['success_message'])) {
    $msg = $_SESSION['success_message'];
    $msg_color = 'green';
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $msg = $_SESSION['error_message'];
    $msg_color = $_SESSION['error_color'];
    unset($_SESSION['error_message'], $_SESSION['error_color']);
}

// ------------------- GET PARAMS -------------------
$sname = isset($_GET['sname']) ? $_GET['sname'] : '';
$pid = isset($_GET['pid']) ? $_GET['pid'] : '';
$sid = '';
$provider_phone = '';

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

// ------------------- GET SERVICE ID -------------------
if ($sname != '' && $pid != '') {
    $stmt = $con->prepare("SELECT sid FROM services WHERE sname = ?");
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

// ------------------- HANDLE FORM SUBMISSION -------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION["consumer_id"])) {
    $sid = intval($_POST['sid']);
    $req_date = $_POST['req_date'];
    $req_time = $_POST['req_time'];
    $pid = intval($_POST['pid']);
    $cid = intval($_SESSION["consumer_id"]);

    // Check if provider exists
    $check_provider = $con->prepare("SELECT pid FROM provider WHERE pid = ?");
    $check_provider->bind_param("i", $pid);
    $check_provider->execute();
    $provider_result = $check_provider->get_result();
    if ($provider_result->num_rows == 0) {
        $_SESSION['error_message'] = "Invalid provider selected. Please try again.";
        $_SESSION['error_color'] = 'red';
        header("location: service_booking.php?pid=$pid&sname=$sname");
        exit();
    }

    // Check if consumer already has pending request for same service/date
    $stmt1 = $con->prepare("SELECT * FROM service_request WHERE consumer_id = ? AND service_id = ? AND req_date = ? AND status = 0");
    $stmt1->bind_param("iis", $cid, $sid, $req_date);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    if ($result1->num_rows > 0) {
        $_SESSION['error_message'] = "You already have a pending request for this service on the selected date.";
        $_SESSION['error_color'] = 'red';
        header("location: service_booking.php?pid=$pid&sname=$sname");
        exit();
    }

    // Check if provider is already booked for this slot
    $stmt2 = $con->prepare("SELECT * FROM service_request WHERE provider_id = ? AND req_date = ? AND req_time = ? AND status = 1 AND (work_status = 0 OR payment_status = 0)");
    $stmt2->bind_param("iss", $pid, $req_date, $req_time);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($result2->num_rows > 0) {
        $_SESSION['error_message'] = "Provider is already booked at the requested time.";
        $_SESSION['error_color'] = 'red';
        header("location: service_booking.php?pid=$pid&sname=$sname");
        exit();
    }

    // Insert booking
    $service_duration = 60; // 1-hour slot
    $payment_status = 0;
    $work_status = 0;
    $status = 0;
    $read_status_c = 0;
    $read_status = 0;
    $msgc = "Your Request has been sent to the provider. The provider will respond ASAP";
    $msgp = "You have a new request";

    // CORRECT VARIABLE NAMES HERE
    $stmt3 = $con->prepare("INSERT INTO service_request 
        (consumer_id, provider_id, service_id, req_date, req_time, service_duration, payment_status, work_status, status, read_status_c, read_status, msgc, msgp) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt3->bind_param('iiissiiiissss', 
        $cid, $pid, $sid, $req_date, $req_time,
        $service_duration, $payment_status, $work_status, $status,
        $read_status_c, $read_status, $msgc, $msgp
    );

    if ($stmt3->execute()) {
        $_SESSION['success_message'] = "Service booked successfully!";
        header("location: service_booking.php?pid=$pid&sname=$sname");
        exit();
    } else {
        $_SESSION['error_message'] = "Error: " . $stmt3->error;
        $_SESSION['error_color'] = 'red';
        header("location: service_booking.php?pid=$pid&sname=$sname");
        exit();
    }
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
            <h2>Service Booking</h2>
            <hr class="major" />
            <div class="row gtr-200">
                <div class="col-6 col-12-medium">
                    <form method="post" action="">
                        <?php if (!empty($msg)): ?>
                            <label><h3 style="color:<?php echo $msg_color; ?>"><?php echo $msg; ?></h3></label>
                        <?php endif; ?>

                        <?php if ($pid == ''): ?>
                            <h3 style="color:red">No valid provider selected. Please go back and try again.</h3>
                        <?php else: ?>
                            <label>Requested Date</label>
                            <input type="date" name="req_date" id="req_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['req_date']) ? $_POST['req_date'] : ''; ?>" required />

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

                            <label>Service Duration</label>
                            <input type="number" name="service_duration" value="60" readonly /> <!-- Fixed 1-hour slot -->

                            <!-- Optional Call Provider -->
                            <a href="tel:+<?php echo $provider_phone; ?>" class="call-btn">Call Provider</a>

                            <input type="hidden" name="sid" value="<?php echo htmlspecialchars($sid); ?>" />
                            <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>" />
                            <input type="hidden" name="sname" value="<?php echo htmlspecialchars($sname); ?>" />

                            <div><strong>Note:</strong> Total Cost will be determined after the provider assesses your request.</div>
<!-- <h2>Book Service</h2> -->
<?php if($message) echo "<p>$message</p>"; ?>

<form method="POST">
    <!-- Service ID: <input type="number" name="service_id" required><br><br>
    Date: <input type="date" name="req_date" required><br><br>
    Time: <input type="time" name="req_time" required><br><br> -->

    Location: 
   <input type="text" name="user_lat" value="<?php echo $_SESSION['user_lat'] ?? ''; ?>" readonly>
<input type="text" name="user_lng" value="<?php echo $_SESSION['user_lng'] ?? ''; ?>" readonly>

    <a href="select_location.php" target="_blank">Select Location</a>

    <br><br>
    <!-- <button type="submit">Book Service</button>
</form> -->
                            <input type="submit" name="sbbtn" value="Book Service" class="primary" />
                        <?php endif; ?>
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

 

</body>
</html>
