<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// DB Connection
$con = new mysqli("localhost", "root", "", "homeservice");
if ($con->connect_errno > 0) {
    die("Connection failed: " . $con->connect_error);
}

// Get GET parameters
$sname = $_GET['sname'] ?? '';
$pid = $_GET['pid'] ?? '';
$selected_lat = $_GET['lat'] ?? '';
$selected_lng = $_GET['lng'] ?? '';

// Initialize messages
$msg = $_SESSION['success_message'] ?? $_SESSION['error_message'] ?? '';
$msg_color = isset($_SESSION['success_message']) && $_SESSION['success_message'] ? 'green' : ($_SESSION['error_color'] ?? 'red');
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['error_color']);

// Validate provider
$provider_phone = '';
if ($pid) {
    $stmt = $con->prepare("SELECT pid, phnno FROM provider WHERE pid=?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows == 0) {
        $msg = "Invalid provider selected.";
        $msg_color = 'red';
        $pid = '';
    } else {
        $provider_phone = $res->fetch_assoc()['phnno'];
    }
}

// Get service ID
$sid = '';
if ($sname && $pid) {
    $stmt = $con->prepare("SELECT sid FROM services WHERE sname=?");
    $stmt->bind_param("s", $sname);
    $stmt->execute();
    $res = $stmt->get_result();
    $sid = $res->num_rows ? $res->fetch_assoc()['sid'] : '';
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['consumer_id'])) {
    $cid = $_SESSION['consumer_id'];
    $req_date = $_POST['req_date'];
    $slot_id = $_POST['slot_id'];
    $pid = $_POST['pid'];
    $sid = $_POST['sid'];

    // Get the selected time from hidden input
    $req_time = $_POST["slot_time_$slot_id"];
    $req_time = date('H:i:s', strtotime($req_time));

    $payment_status = 0;
    $work_status = 0;
    $status = 0;
    $read_status = 0;
    $read_status_c = 0;
    $msgc = "Your request has been sent to the provider.";
    $msgp = "You have a new request";
    $user_lat = $_POST['user_lat'] ?? 0;
    $user_lng = $_POST['user_lng'] ?? 0;

    // Check if provider already booked at this time
    $stmt = $con->prepare("SELECT * FROM service_request WHERE provider_id=? AND req_date=? AND req_time=? AND status=1 AND (work_status=0 OR payment_status=0)");
    $stmt->bind_param("iss", $pid, $req_date, $req_time);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "Provider is already booked at this time.";
        $_SESSION['error_color'] = 'red';
        header("Location: service_booking.php?pid=$pid&sname=$sname");
        exit();
    }

    // Insert booking
    $stmt = $con->prepare("INSERT INTO service_request 
        (consumer_id, provider_id, service_id, req_date, req_time, payment_status, work_status, status, read_status_c, read_status, msgc, msgp, user_lat, user_lng) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissiiiisssdd", $cid, $pid, $sid, $req_date, $req_time, $payment_status, $work_status, $status, $read_status_c, $read_status, $msgc, $msgp, $user_lat, $user_lng);

    if ($stmt->execute()) {
        // Mark slot as booked
        $con->query("UPDATE provider_schedule_day SET status=1 WHERE id=$slot_id");
        $_SESSION['success_message'] = "Service booked successfully!";
        header("Location: service_booking.php?pid=$pid&sname=$sname");
        exit();
    } else {
        $_SESSION['error_message'] = "Booking failed: " . $stmt->error;
        $_SESSION['error_color'] = 'red';
        header("Location: service_booking.php?pid=$pid&sname=$sname");
        exit();
    }
}

// Fetch provider schedule
$groupedData = [];
if ($pid) {
    $sql = "SELECT * FROM provider_schedule_day WHERE provider_id=$pid ORDER BY day, slots ASC";
    $result = $con->query($sql);
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $daysOfWeek = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

    // Sort by day
    usort($data, fn($a,$b) => array_search($a['day'],$daysOfWeek) <=> array_search($b['day'],$daysOfWeek));

    foreach ($data as $item) {
        $day = $item['day'];
        $groupedData[$day][] = [
            'id' => $item['id'],
            'slot' => date('H:i A', strtotime($item['slots'])),
            'status' => $item['status']
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Service Booking</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <style>
        /* Slot button basic style */
.slot-button {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 5px;
    color: grey;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}

/* Hover effect */
.slot-button:hover {
    opacity: 0.8;
}

/* Active / Clicked effect */
.slot-label input[type="radio"]:checked + .slot-button {
    box-shadow: 0 0 0 3px #00000050; /* Highlight selected slot */
    transform: scale(0.95);          /* Slight press effect */
    border: 2px solid #000;          /* Optional border for selected */
}

/* Optional: on mouse click (active) */
.slot-button:active {
    transform: scale(0.95);
    opacity: 0.9;
}

    </style>
</head>
<body>
<body class="is-preload">

<div id="wrapper">
    <div id="main">
        <div class="inner">
            <header id="header">
                <a href="welcome.php" class="logo"><strong>Ghar Sewa</strong></a>
            </header>

            <br>
            <h2 id="elements">Service Booking</h2>
            <hr class="major" />
            <div class="row gtr-200">
                <div class="col-6 col-12-medium">

<form method="post" action="service_booking.php?pid=<?php echo htmlspecialchars($pid); ?>&sname=<?php echo htmlspecialchars($sname); ?>">
    <div class="row gtr-uniform">

        <!-- Message -->
        <?php if (!empty($msg)): ?>
        <div class="col-12">
            <h3 style="color:<?php echo $msg_color; ?>"><?php echo $msg; ?></h3>
        </div>
        <?php endif; ?>

        <?php if ($pid == ''): ?>
        <div class="col-12">
            <h3 style="color:red">No valid provider selected. Please go back and try again.</h3>
        </div>
        <?php else: ?>

        <!-- Requested Date -->
        <div class="col-12">
            <label>Requested Date</label>
            <input type="date" name="req_date" id="req_date" min="<?php echo date('Y-m-d'); ?>" required />
        </div>

        <!-- Time Slots -->
        <div class="col-12">
            <?php if (!empty($groupedData)) { ?>
            <table class="table" style="margin-top:10px;">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time Slots (Required)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($groupedData as $day => $slots) { ?>
                    <tr class="slot-row" data-day="<?php echo $day; ?>">
                        <td><?php echo htmlspecialchars($day); ?></td>
                        <td>
                            <?php foreach ($slots as $slot) {
                                $color = ($slot['status']==0) ? "green" : (($slot['status']==1) ? "yellow" : "red");
                            ?>
                                <?php if ($slot['status']==0) { ?>
                                    <label class="slot-label" style="margin-right:5px;">
                                        <input type="radio" name="slot_id" value="<?php echo $slot['id']; ?>" required>
                                        <input type="hidden" name="slot_time_<?php echo $slot['id']; ?>" value="<?php echo $slot['slot']; ?>">
                                        <span class="slot-button" style="background:<?php echo $color; ?>;"><?php echo $slot['slot']; ?></span>
                                    </label>
                                <?php } else { ?>
                                    <span class="slot-button" style="background:<?php echo $color; ?>; cursor:not-allowed; margin-right:5px;">
                                        <?php echo $slot['slot']; ?>
                                    </span>
                                <?php } ?>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <?php } else { ?>
                <h3 style="color:red;">No Available Slots</h3>
            <?php } ?>
        </div>

        <!-- Service Duration -->
        <div class="col-12">
            <label>Service Duration (Minutes)</label>
            <input type="number" name="service_duration" value="60" class="primary" readonly />
        </div>

        <!-- Call Provider -->
        <div class="col-12">
            <a href="tel:+<?php echo $provider_phone; ?>" class="call-btn">Call Provider for custom duration</a>
        </div>

        <!-- Location Display -->
        <div class="col-12">
            <label>Location (Optional)</label>
            <input type="text" id="location_display" placeholder="Click 'Select Location'" value="<?php echo $selected_lat . ', ' . $selected_lng; ?>" readonly required />
        </div>

        <!-- Select Location Button -->
        <div class="col-12">
            <ul class="actions">
                <li>
                    <input type="button" class="primary" onclick="openMap()" value="Select Location" />
                </li>
            </ul>
        </div>

        <!-- Hidden Inputs -->
        <input type="hidden" name="user_lat" id="user_lat" value="<?php echo $selected_lat; ?>">
        <input type="hidden" name="user_lng" id="user_lng" value="<?php echo $selected_lng; ?>">
        <input type="hidden" name="sid" value="<?php echo htmlspecialchars($sid); ?>">
        <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>">

        <!-- Note -->
        <div class="col-12">
            <strong>Note:</strong> Total Cost will be determined after the provider assesses your request.
        </div>

        <!-- Submit Button -->
        <div class="col-12">
            <ul class="actions">
                <li>
                    <button type="submit" name="sbbtn" class="primary">Book Service</button>
                </li>
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
function openMap() {
    window.open("select_location.php", "Select Location", "width=600,height=500");
}

// Filter slots by selected date
document.getElementById('req_date').addEventListener('change', function(){
    const dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const day = dayNames[new Date(this.value).getDay()];
    document.querySelectorAll('.slot-row').forEach(row=>{
        row.style.display = row.dataset.day===day ? '' : 'none';
        row.querySelectorAll('input[type=radio]').forEach(r=>r.disabled = row.dataset.day!==day);
    });
});
</script>


<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/browser.min.js"></script>
<script src="assets/js/breakpoints.min.js"></script>
<script src="assets/js/util.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
