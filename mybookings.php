<?php  // Start a session for customer
    session_start();
if(!isset($_SESSION["consumer_utype"]) || $_SESSION["consumer_utype"] != "Consumer") {
    header("Location: Signin.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "gharsewa");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

$id = $_SESSION["consumer_id"];

// Query to fetch service requests with payment_status = 0
$query = "SELECT provider.fname, provider.mname, provider.lname, provider.address, provider.city, provider.state, provider.country, 
            provider.phnno,
            service_request.req_date AS fdate, service_request.req_time,
            service_request.status AS status, service_request.work_status,
            service_request.payment_status, service_request.srid, services.sname
          FROM service_request
          JOIN provider ON provider.pid = service_request.provider_id
          JOIN consumer ON consumer.cid = service_request.consumer_id
          JOIN services ON services.sid = service_request.service_id
          WHERE service_request.consumer_id = ? 
          AND service_request.payment_status = 0 -- Filter for pending payment
          ORDER BY service_request.req_date DESC, service_request.req_time DESC";

// Prepare and execute the query
$stmt = $con->prepare($query);
$stmt->bind_param("s", $id);
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

    <style>
        table.dataTable {
            border-collapse: collapse;
            border: 1px solid #ddd;
            width: 100%;
        }

        table.dataTable th, table.dataTable td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        table.dataTable th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            font-size: 0.875em;
            font-weight: bold;
            border-radius: 12px;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #fff;
        }

        .badge-success {
            background-color: #28a745;
            color: #fff;
        }

        .badge-danger {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body class="is-preload">

    <div id="wrapper">
        <div id="main">
            <div class="inner">

                <header id="header">
                    <a href="welcome.php" class="logo"><strong>GharSewa</strong></a>
                </header>

                <section>
                    <h2 id="elements">Active Bookings</h2>
                    <hr class="major" />

                    <div class="row gtr-200">
                        <div class="col-12 col-12-medium">
                            <div class="table-wrapper">
                                <?php
                                if ($stmt->num_rows > 0) {
                                    $stmt->bind_result($fname, $mname, $lname, $address, $city, $state, $country, $phnno, $fdate, $rtime, $status, $workstatus, $payment_status, $srid, $sname);
                                    echo '<table id="myTable" class="display">
                                          <thead>
                                              <tr>
                                                  <th>Provider Name</th>
                                                  <th>Service Name</th>
                                                  <th>Address</th>
                                                  <th>Phone Number</th>
                                                  <th>Request Date</th>
                                                  <th>Request Time</th>
                                                  <th>Status</th>
                                                  <th>Work Status</th>
                                                  
                                                  <th>Action</th>
                                              </tr>
                                          </thead>
                                          <tbody>';
                                    while ($stmt->fetch()) {
                                        // Determine the status labels and badge classes
                                        $status_label = $status == 0 ? "Pending" : ($status == 1 ? "Accepted" : "Rejected");
                                        $status_class = $status == 0 ? "badge-warning" : ($status == 1 ? "badge-success" : "badge-danger");

                                        $workstatus_label = $workstatus == 0 ? "Pending" : "Completed";
                                        $workstatus_class = $workstatus == 0 ? "badge-warning" : "badge-success";

                                        $payment_label = $payment_status == 0 ? "Pending" : "Paid";
                                        $payment_class = $payment_status == 0 ? "badge-warning" : "badge-success";

                                        $action_button = ($status == 1 && $workstatus == 1 && $payment_status == 0) 
                                            ? "<button class='action-btn primary'>Pay</button>" 
                                            : "<button class='action-btn primary' disabled>Pay</button>";

                                        echo '<tr>
                                                <td>' . $fname . ' ' . $mname . ' ' . $lname . '</td>
                                                <td>' . $sname . '</td>
                                                <td>' . $address . ',<br>' . $city . ', ' . $state . ', ' . $country . '</td>
                                                <td>' . $phnno . '</td>
                                                <td>' . $fdate . '</td>
                                                <td>' . $rtime . '</td>
                                                <td><span class="badge ' . $status_class . '">' . $status_label . '</span></td>
                                                <td><span class="badge ' . $workstatus_class . '">' . $workstatus_label . '</span></td>
                                                
                                                <td>' . $action_button . '</td>
                                              </tr>';
                                    }
                                    echo '</tbody>
                                          </table>';
                                } else {
                                    echo "<h3 style='color:red'>No Active Bookings Found</h3>";
                                }
                                ?>
                            </div>
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
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/browser.min.js"></script>
    <script src="assets/js/breakpoints.min.js"></script>
    <script src="assets/js/util.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function () {
            $('#myTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true
            });
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$con->close();
?>
