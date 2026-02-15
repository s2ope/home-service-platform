<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// DB connection
$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}

// 1️⃣ Provider session check
if (isset($_SESSION['provider_id']) && !empty($_SESSION['provider_id'])) {
    $provider_id = $_SESSION['provider_id'];
    $provider_name = $_SESSION['provider_name'] ?? '';
} else {
    echo "No provider logged in!";
    exit();
}

// 2️⃣ Sorting option
$sort_order = 'ASC';
if (isset($_GET['sort_by']) && in_array(strtolower($_GET['sort_by']), ['asc', 'desc'])) {
    $sort_order = strtoupper($_GET['sort_by']);
}

// 3️⃣ Fetch bookings with consumer info using JOIN
$query = "
    SELECT sr.srid, sr.user_lat, sr.user_lng, sr.req_date, sr.req_time, sr.status, sr.work_status, sr.payment_status,
           c.fname, c.lname, c.phnno
    FROM service_request sr
    JOIN consumer c ON sr.consumer_id = c.cid
    WHERE sr.provider_id = '$provider_id'
      AND sr.user_lat IS NOT NULL
      AND sr.user_lng IS NOT NULL
    ORDER BY sr.req_date $sort_order, sr.req_time $sort_order
";

$result = mysqli_query($con, $query);

$locations = [];
$today = date('Y-m-d');

// 4️⃣ Prepare locations for map and marker color
while ($row = mysqli_fetch_assoc($result)) {
    $booking_date = date('Y-m-d', strtotime($row['req_date']));
    if (strtolower($row['payment_status']) == '1') {
        $row['color'] = 'green'; // Completed / Paid
    } elseif ($booking_date == $today) {
        $row['color'] = 'red'; // Today
    } else {
        $row['color'] = 'blue'; // Future
    }
    $locations[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Provider Map</title>
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <style>
        /* ---------------- Map and Table ---------------- */
        #main #map {
            width: 100%;
            height: 500px;
            margin-top: 20px;
        }

        #main table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        #main table th, #main table td {
            padding: 8px;
            border: 1px solid #ccc;
            text-align: left;
        }

        /* ---------------- Legend ---------------- */
        .legend {
            padding: 10px;
            background: #fff;
            border: 1px solid #ccc;
            display: inline-block;
            margin-top: 10px;
            font-size: 14px;
        }

        .legend-color {
            display: inline-block;
            width: 15px;
            height: 15px;
            margin-right: 5px;
            vertical-align: middle;
        }
    </style>
</head>
<body class="is-preload">
<div id="wrapper">
    <!-- Main content -->
    <div id="main">
        <div class="inner">
            <header id="header">
                <a href="provider_map.php" class="logo"><strong>Ghar Sewa</strong></a>
            </header>

            <h2 id="elements">View map, <?php echo htmlspecialchars($provider_name); ?></h2>
            <hr class="minor" />

            <div class="row gtr-200">
                <div class="col-12 col-12-medium">

                    <!-- Sorting Form -->
                    <form method="GET" action="">
                        <label for="sort_by">Sort Bookings By Date:</label>
                        <select name="sort_by" id="sort_by" onchange="this.form.submit()">
                            <option value="asc" <?php if($sort_order=='ASC') echo 'selected'; ?>>Earliest First</option>
                            <option value="desc" <?php if($sort_order=='DESC') echo 'selected'; ?>>Latest First</option>
                        </select>
                    </form>

                    <br>

                    <!-- Bookings Table with consumer info -->
                    <table>
                        <thead>
                            <tr>
                                <th>SRID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Phone No</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Work Status</th>
                                <th>Payment Status</th>
                                <th>Marker Color</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($locations as $loc): ?>
                            <tr>
                                <td><?php echo $loc['srid']; ?></td>
                                <td><?php echo $loc['fname']; ?></td>
                                <td><?php echo $loc['lname']; ?></td>
                                <td><?php echo $loc['phnno']; ?></td>
                                <td><?php echo $loc['req_date']; ?></td>
                                <td><?php echo $loc['req_time']; ?></td>
                                <td><?php echo $loc['status']; ?></td>
                                <td><?php echo $loc['work_status']; ?></td>
                                <td><?php echo $loc['payment_status']; ?></td>
                                <td><?php echo ucfirst($loc['color']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Map -->
                    <div id="map"></div>

                    <!-- Legend -->
                    <div class="legend">
                        <b>Marker Colors:</b><br>
                        <span class="legend-color" style="background:red;"></span> Today<br>
                        <span class="legend-color" style="background:blue;"></span> Future<br>
                        <span class="legend-color" style="background:green;"></span> Completed
                    </div>

                </div> <!-- End of column -->
            </div> <!-- End of row -->
        </div> <!-- End of inner -->
    </div> <!-- End of main -->

    <!-- Sidebar -->
    <div id="sidebar">
        <div class="inner">
            <?php include "pmenu.php"; ?>
        </div>
    </div>
</div>

<script>
var map = L.map('map').setView([27.7172, 85.3240], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

var locations = <?php echo json_encode($locations); ?>;

// Colored marker icons
var icons = {
    red: new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34], shadowSize: [41,41]
    }),
    blue: new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34], shadowSize: [41,41]
    }),
    green: new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34], shadowSize: [41,41]
    })
};

// Add markers
locations.forEach(function(loc){
    var lat = parseFloat(loc.user_lat);
    var lng = parseFloat(loc.user_lng);
    if(!isNaN(lat) && !isNaN(lng)){
        var color = (loc.color && icons[loc.color]) ? loc.color : 'blue';
        var marker = L.marker([lat, lng], {icon: icons[color]}).addTo(map);
        marker.bindPopup(
            "<b>Service Request ID:</b> " + loc.srid +
            "<br><b>Name:</b> " + loc.fname + " " + loc.lname +
            "<br><b>Date:</b> " + loc.req_date +
            "<br><b>Time:</b> " + loc.req_time +
            "<br><b>Status:</b> " + (loc.status || 'Pending')
        );
    }
});
</script>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/browser.min.js"></script>
<script src="assets/js/breakpoints.min.js"></script>
<script src="assets/js/util.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
