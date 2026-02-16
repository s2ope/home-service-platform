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


// -----------------------------
// Handle booking when a slot is clicked
// -----------------------------
if (isset($_POST['book_slot']) && $consumer_id) {

    $cid = $consumer_id;
    $pid = intval($_POST['provider_id']);
    $slot_id = intval($_POST['slot_id']);
    $req_date = $_POST['req_date'];
    $req_time = date('H:i:s', strtotime($_POST['slot_time'])); // convert to H:i:s format

    $sid = $service_id ?? 1; // replace with current service ID
    $payment_status = 0;
    $work_status = 0;
    $status = 0;
    $read_status_c = 0;
    $read_status = 0;
    $msgc = '';
    $msgp = '';
    $user_lat = 0;
    $user_lng = 0;

    // Check slot is still available
    $check_sql = "SELECT status FROM provider_schedule_day WHERE id=$slot_id AND provider_id=$pid";
    $res = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($res);

    if ($row && $row['status'] == 0) {
        // Insert booking into service_request
        $stmt3 = $conn->prepare("INSERT INTO service_request 
            (consumer_id, provider_id, service_id, req_date, req_time, payment_status, work_status, status, read_status_c, read_status, msgc, msgp, user_lat, user_lng) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt3->bind_param("iiissiiiisssdd", 
            $cid, $pid, $sid, $req_date, $req_time,
            $payment_status, $work_status, $status,
            $read_status_c, $read_status, $msgc, $msgp,
            $user_lat, $user_lng
        );

        if ($stmt3->execute()) {
            // Update slot status to packed (1)
            mysqli_query($conn, "UPDATE provider_schedule_day SET status=1 WHERE id=$slot_id");

            echo "<p style='color:green;'>Slot booked successfully for $req_date at $req_time!</p>";
        } else {
            echo "<p style='color:red;'>Booking failed!</p>";
        }
    } else {
        echo "<p style='color:red;'>Slot already booked or unavailable!</p>";
    }
}

$data = [];

// Fetch schedule for that provider
$sql = "SELECT * 
        FROM provider_schedule_day 
        WHERE provider_id = $pid 
        ORDER BY day, slots ASC";

$result = mysqli_query($con, $sql);

$data = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}







$groupedData = [];

if (!empty($data)) {

    $daysOfWeek = [
        'Sunday','Monday','Tuesday',
        'Wednesday','Thursday','Friday','Saturday'
    ];

    // Sort by correct day order
    usort($data, function($a, $b) use ($daysOfWeek) {
        return array_search($a['day'], $daysOfWeek)
             <=> array_search($b['day'], $daysOfWeek);
    });

    foreach ($data as $item) {

        $day = $item['day'];

        if (!isset($groupedData[$day])) {
            $groupedData[$day] = [];
        }

        $groupedData[$day][] = [
            'id'     => $item['id'],
            'slot'   => date('g:i A', strtotime($item['slots'])),
            'status' => $item['status']
        ];
    }
}
// ------------------- GENERATE TIME SLOTS -------------------


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
                        <th>Time Slots</th>
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
