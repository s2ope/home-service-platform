<?php
session_start();
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Online HouseHold Service Portal</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <!-- Add Font Awesome for star icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<body class="is-preload">

    <!-- Wrapper -->
    <div id="wrapper">

        <!-- Main -->
        <div id="main">
            <div class="inner">

                <!-- Header -->
                <header id="header">
                    <a href="welcome.php" class="logo"><strong>Ghar Sewa</strong></a>
                </header>

                <!-- Provider Details Section -->
                
                    <header class="major">
                        <h2>Provider Details</h2>
                    </header>
                    <div class="features">
                        <?php
                        // Database connection
                        $con = mysqli_connect("localhost", "root", "", "gharsewa");
                        if (mysqli_connect_errno()) {
                            echo "Failed to connect to MySQL: " . mysqli_connect_error();
                            exit();
                        }

                        // Check if ID is set
                        if (isset($_GET['id'])) {
                            $sid = intval($_GET['id']); // Sanitize the input

                            // Fetch service name
                            $query1 = "SELECT sname FROM services WHERE sid = ?";
                            $stmt1 = $con->prepare($query1);
                            $stmt1->bind_param("i", $sid);
                            $stmt1->execute();
                            $stmt1->store_result();

                            if ($stmt1->num_rows > 0) {
                                $stmt1->bind_result($service_name);
                                $stmt1->fetch();
                            } else {
                                $service_name = "Unknown Service";
                            }

                            // Fetch providers offering the service with accepted status
                            $query = "SELECT 
                                p.pid, p.fname, p.mname, p.lname, p.dob, p.gender, p.country, p.state, p.city, p.address, 
                                p.phnno, p.email, p.photo, p.average_rating,
                                vr.specification, vr.estd_date, vr.certificate_pic
                            FROM 
                                provider p
                            JOIN 
                                verification_request vr ON p.pid = vr.provider_id
                            WHERE 
                                vr.service_id = ? AND vr.status = 1
                            ORDER BY 
                                p.average_rating DESC";

                            $stmt = $con->prepare($query);
                            $stmt->bind_param("i", $sid);
                            $stmt->execute();
                            $stmt->store_result();

                            // Check if providers were found
                            if ($stmt->num_rows > 0) {
                                $stmt->bind_result($pid, $fname, $mname, $lname, $dob, $gender, $country, $state, $city, $address, 
                                                   $phnno, $email, $photo, $rating, $specification, $estd_date, $certificate_pic);

                                while ($stmt->fetch()) {
                                    // Calculate star ratings
                                    $full_stars = floor($rating);
                                    $has_half_star = ($rating - $full_stars) >= 0.5;
                                    $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
                                    
                                    // Display provider details - maintaining your exact styling
                                    echo '<article>';
                                    echo '<div style="border:solid 1px grey;border-radius:6%;"class="content" style="padding: 10px">';
                                    echo '<h3 align="center">' . strtoupper($fname . ' ' . $mname . ' ' . $lname) . '</h3>';
                                    echo '<center>';
                                    echo '<img src="uploads/' . $photo . '" width="150px" height="150px" style="border-radius: 50%; border:1px solid white;" />';
                                    
                                    // Add star rating display (only new addition)
                                    echo '<div style="margin: 5px 0; text-align: center;">';
                                    for ($i = 0; $i < $full_stars; $i++) {
                                        echo '<span class="fa fa-star" style="color: gold;"></span>';
                                    }
                                    if ($has_half_star) {
                                        echo '<span class="fa fa-star-half-alt" style="color: gold;"></span>';
                                    }
                                    for ($i = 0; $i < $empty_stars; $i++) {
                                        echo '<span class="fa fa-star" style="color: #ccc;"></span>';
                                    }
                                    echo ' <span>(' . number_format($rating, 1) . ')</span>';
                                    echo '</div>';
                                    
                                    echo '</center>';
                                    echo '<p align="center">';
                                    echo '<strong>Specification:</strong> ' . $specification . '<br>';
                                    echo '<strong>Established Date:</strong> ' . $estd_date . '<br>';
                                    echo '<strong>Contact:</strong> ' . $phnno . '<br>';
                                    echo '<strong>Email:</strong> ' . $email . '<br>';
                                    echo '<strong>Address:</strong> ' . $address . ', ' . $city . ', ' . $state . ', ' . $country . '<br>';
                                    echo '</p>';
                                    echo '<p align="center">';
                                    echo '<a href="Signin.php?pid=' . $pid . 
                                        '&pname=' . urlencode($fname . ' ' . $mname . ' ' . $lname) . 
                                        '&sname=' . $service_name . 
                                        '&email=' . urlencode($email) . 
                                        '&phnno=' . urlencode($phnno) . '" class="button">Send Request</a>';
                                    echo '</p>';
                                    echo '<hr>';
                                    echo '</div>';
                                    echo '</article>';
                                }
                            } else {
                                echo "<h3 style='color:red'>No providers found for " . $service_name . ".</h3>";
                            }
                            $stmt->close();
                        } else {
                            echo "<h3 style='color:red'>No service ID specified.</h3>";
                        }

                        $con->close();
                        ?>
                    </div>
                </>

            </div>
        </div>

        <!-- Sidebar -->
        <div id="sidebar">
            <div class="inner">
                <?php include "menu.php"; ?>
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