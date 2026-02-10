<?php
session_start();
if(!isset($_SESSION["utype"]) || $_SESSION["utype"] != "Admin") {
    header("location:Signin.php");
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

<!-- Wrapper -->
<div id="wrapper">

    <!-- Main -->
    <div id="main">
        <div class="inner">

            <!-- Header -->
            <header id="header">
                <a href="view_request.php" class="logo"><strong>Household</strong> Service Portal</a>
            </header>

            <!-- Banner -->
            <section>
                <h2 id="elements">View Request Page</h2>
                <hr class="major" />
                <div class="row gtr-200">
                    <div class="col-12">
                        <label><h3 style="color:green"><?php isset($msg) ? print $msg : print ""; ?></h3></label>
                    </div>

                    <div class="col-12 col-12-medium">
                        <div class="table-wrapper">
                            <form name="f1" method="post" action="view_request.php">
                                <?php
                                $con = mysqli_connect("localhost", "root", "", "gharsewa");
                                if (mysqli_connect_errno() > 0) {
                                    echo mysqli_connect_error();
                                    exit();
                                }

                                $emailid = $_SESSION["emailid"];
                                $query1 = "SELECT sr.srid, c.fname, c.mname, c.lname, c.address, c.city, c.state, c.country, 
                                                  sr.req_date, sr.req_time, s.sname, sr.status, sr.work_status, sr.payment_status, sr.charge
                                           FROM service_request sr
                                           JOIN consumer c ON c.cid = sr.consumer_id
                                           JOIN services s ON s.sid = sr.service_id
                                           WHERE sr.provider_id = (SELECT pid FROM provider WHERE email = ?)
                                           ORDER BY sr.req_date DESC";
                                $stmt1 = $con->prepare($query1);
                                $stmt1->bind_param("s", $emailid);
                                $stmt1->execute();
                                $stmt1->store_result();

                                if ($stmt1->num_rows > 0) {
                                    $stmt1->bind_result($srid, $fname, $mname, $lname, $address, $city, $state, $country, $fdate, $tdate, $sname, $status, $work_status, $payment_status, $charge);
                                    echo '<table class="alt">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Consumer Name</th>
                                                <th>Consumer Contact Details</th>
                                                <th>Request For</th>
                                                <th>Request Date & Time</th>
                                                <th>Status</th>
                                                <th>Work Status</th>
                                                <th>Payment Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                                    while ($stmt1->fetch()) {
                                        echo '<tr>';
                                        echo '<td>';
                                        if ($status == 0) {
                                            echo '<div class="col-3 col-12-small">
                                                      <input type="checkbox" id="chk' . $srid . '" name="chk' . $srid . '" value="' . $srid . '">
                                                      <label for="chk' . $srid . '"></label>
                                                  </div>';
                                        }
                                        echo '</td>';
                                        echo '<td>' . strtoupper($fname . ' ' . $mname . ' ' . $lname) . '</td>
                                              <td>' . $address . ',<br>' . $city . ',<br>' . $state . ',' . $country . '</td>
                                              <td>' . $sname . '</td>
                                              <td>' . $fdate . ' ' . $tdate . '</td>';

                                        echo '<td>';
                                        if ($status == 0) {
                                            echo "Pending";
                                        } elseif ($status == 1) {
                                            echo "Accepted";
                                        } else {
                                            echo "Rejected";
                                        }
                                        echo '</td>';

                                        echo '<td>';
                                        if ($work_status == 1) {
                                            echo "Completed";
                                            // Show bill amount input when work is completed
                                            echo '<form method="post" style="display:inline;">
                                                      <input type="hidden" name="srid" value="' . $srid . '" />
                                                      <label for="bill_amount">Enter Bill Amount:</label>
                                                      <input type="text" name="bill_amount" id="bill_amount" required /><br>
                                                      <input type="submit" value="Submit Bill" class="primary" />
                                                  </form>';
                                        } elseif ($work_status == 2) {
                                            echo "Dismissed"; // Work status is Dismissed for Rejected requests
                                        } else {
                                            echo "Pending";
                                        }
                                        echo '</td>';

                                        echo '<td>';
                                        if ($payment_status == 1) {
                                            echo "Paid";
                                        } elseif ($payment_status == 2) {
                                            echo "Dismissed"; // Payment status is Dismissed for Rejected requests
                                        } else {
                                            echo "Pending";
                                        }
                                        echo '</td>';
																				echo "<td></td>";

                                        echo '</tr>';
                                    }
                                    echo '</tbody>
                                          <tfoot>
                                              <tr>
                                                  <td colspan="9" align="right"><b>No of Requests :</b></td>
                                                  <td><b>' . $stmt1->num_rows . '</b></td>
                                              </tr>
                                              <tr>
                                                  <td colspan="10" align="left">
                                                      <input type="submit" value="Accept" name="btn" class="primary" />
                                                      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                      <input type="submit" value="Reject" name="btn" class="primary" />
                                                  </td>
                                              </tr>
                                          </tfoot>
                                      </table>';
                                } else {
                                    echo "<h3 style='color:red'>No Request Found</h3>";
                                }

                                $stmt1->close();
                                $con->close();
                                ?>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>

    <!-- Sidebar -->
    <div id="sidebar">
        <div class="inner">
            <?php include "amenu.php" ?>
        </div>
    </div>

</div>

<!-- Scripts -->
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/browser.min.js"></script>
<script src="assets/js/breakpoints.min.js"></script>
<script src="assets/js/util.js"></script>
<script src="assets/js/main.js"></script>

</body>
</html>