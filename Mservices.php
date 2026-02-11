<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Determine user type and id
if (isset($_SESSION['consumer_utype']) && $_SESSION['consumer_utype'] == "Consumer") {
    $user_type = "Consumer";
    $user_id = $_SESSION['consumer_id'];
} elseif (isset($_SESSION['provider_utype']) && $_SESSION['provider_utype'] == "Provider") {
    $user_type = "Provider";
    $user_id = $_SESSION['provider_id'];
} else {
    header("Location: Signin.php");
    exit();
}

// PRG Pattern for filter form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["sbbtn"])) {
    $_SESSION['form_data'] = $_POST;
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

$filterMessage = '';
if (!empty($formData)) {
    $filterMessage = '<h3 style="color: green;">Filters applied successfully!</h3>';
}

$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}

// Build query depending on user type
if ($user_type == "Consumer") {
    $query = "SELECT provider.fname, provider.mname, provider.lname, provider.photo, provider.average_rating,
              provider.address, provider.city, provider.state, provider.country, provider.phnno,
              service_request.req_date AS fdate, service_request.req_time,
              service_request.status AS status, service_request.work_status,
              service_request.payment_status, service_request.srid, services.sname AS service_name,
              service_request.charge AS charge
              FROM service_request
              JOIN provider ON provider.pid = service_request.provider_id
              JOIN services ON services.sid = service_request.service_id
              WHERE service_request.consumer_id = ?";
} else { // Provider
    $query = "SELECT consumer.fname, consumer.mname, consumer.lname, consumer.photo, NULL AS average_rating,
              consumer.address, consumer.city, consumer.state, consumer.country, consumer.phnno,
              service_request.req_date AS fdate, service_request.req_time,
              service_request.status AS status, service_request.work_status,
              service_request.payment_status, service_request.srid, services.sname AS service_name,
              service_request.charge AS charge
              FROM service_request
              JOIN consumer ON consumer.cid = service_request.consumer_id
              JOIN services ON services.sid = service_request.service_id
              WHERE service_request.provider_id = ?";
}

$params = [$user_id];
$param_types = "s";

if (!empty($formData)) {
    $frdate = $formData["fdate"] ?? '';
    $todate = $formData["tdate"] ?? '';
    $sts = $formData["rp1"] ?? '';

    if (!empty($frdate) && !empty($todate)) {
        $query .= " AND service_request.req_date BETWEEN ? AND ?";
        $params[] = $frdate;
        $params[] = $todate;
        $param_types .= "ss";
    }

    if ($sts !== "") {
        $query .= " AND service_request.status = ?";
        $params[] = $sts;
        $param_types .= "s";
    }
}

$query .= " ORDER BY service_request.last_modified DESC";

$stmt = $con->prepare($query);

// Bind parameters dynamically
if (count($params) > 1) {
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param($param_types, $user_id);
}

$stmt->execute();
$stmt->store_result();
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Online Household Service Portal</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        /* --- Keep your previous CSS here --- */
    </style>
</head>
<body class="is-preload">

<!-- Image Modal -->
<div id="imageModal" class="image-modal">
    <span class="close-image-modal" onclick="closeImageModal()">&times;</span>
    <div class="image-modal-content">
        <img id="modalImage" class="modal-image">
    </div>
</div>

<div id="wrapper">
    <div id="main">
        <div class="inner">
            <header id="header">
                <a href="welcome.php" class="logo"><strong>homeservice</strong></a>
            </header>

            <h2>My Services</h2>
            <hr class="minor" />

            <?php echo $filterMessage; ?>

            <form method="post" action="myservices.php" class="filter-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label for="fdate">From Date:</label>
                        <input type="date" name="fdate" id="fdate" value="<?= $formData['fdate'] ?? '' ?>">
                    </div>
                    <div>
                        <label for="tdate">To Date:</label>
                        <input type="date" name="tdate" id="tdate" value="<?= $formData['tdate'] ?? '' ?>">
                    </div>
                </div>

                <label>Service Status:</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="rp4" name="rp1" value="" <?= empty($formData['rp1']) ? 'checked' : '' ?>>
                        <label for="rp4" style="display: inline; font-weight: normal;">All Statuses</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="rp1" name="rp1" value="1" <?= ($formData['rp1'] ?? '') == '1' ? 'checked' : '' ?>>
                        <label for="rp1" style="display: inline; font-weight: normal;">Accepted</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="rp2" name="rp1" value="2" <?= ($formData['rp1'] ?? '') == '2' ? 'checked' : '' ?>>
                        <label for="rp2" style="display: inline; font-weight: normal;">Rejected</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="rp3" name="rp1" value="0" <?= ($formData['rp1'] ?? '') == '0' ? 'checked' : '' ?>>
                        <label for="rp3" style="display: inline; font-weight: normal;">Pending</label>
                    </div>
                </div>
                <button type="submit" name="sbbtn" class="button primary">Apply Filters</button>
            </form>

            <hr class="major" />

            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search by any data (name, service, location, etc.)">
                <button onclick="searchServices()" class="button primary">Search</button>
            </div>
            <div id="noResults" class="no-results">No matching results found</div>

            <div class="service-requests" id="serviceRequestsContainer">
                <?php
                if ($stmt->num_rows > 0) {
                    if ($user_type == "Consumer") {
                        $stmt->bind_result($fname, $mname, $lname, $photo, $average_rating, $address, $city, $state, $country, $phnno, $fdate, $rtime, $status, $workstatus, $payment_status, $srid, $service_name, $charge);
                    } else { // Provider
                        $stmt->bind_result($fname, $mname, $lname, $photo, $average_rating, $address, $city, $state, $country, $phnno, $fdate, $rtime, $status, $workstatus, $payment_status, $srid, $service_name, $charge);
                    }

                    while ($stmt->fetch()) {
                        // Keep your existing card rendering logic
                        include "service_card_template.php"; // Optional: Extract card HTML to keep clean
                    }
                } else {
                    echo '<div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 8px;">
                            <h3 style="color:#666">No service requests found</h3>
                            <p>There are no records matching your criteria</p>
                          </div>';
                }
                ?>
            </div>
        </div>
    </div>

    <div id="sidebar">
        <div class="inner">
            <?php
            if ($user_type == "Consumer") include "cmenu.php";
            else include "pmenu.php"; // provider menu
            ?>
        </div>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/browser.min.js"></script>
<script src="assets/js/breakpoints.min.js"></script>
<script src="assets/js/util.js"></script>
<script src="assets/js/main.js"></script>
<script>
// Keep your previous modal + search JS here
</script>

</body>
</html>

<!-- <?php
$stmt->close();
$con->close();
?> -->
