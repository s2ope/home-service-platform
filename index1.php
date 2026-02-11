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
                    <a href="index.php" class="logo"><strong>Household</strong> Services</a>
                </header>

                <!-- Banner -->
                <section>
                    <header class="major">
                        <h2>Household Services</h2>
                    </header>

                    <?php
                    // Database connection details
                    $hostname = "localhost";
                    $uname = "root";
                    $pw = "";
                    $db = "homeservice";

                    // Create connection
                    $conn = new mysqli($hostname, $uname, $pw, $db);

                    // Check connection
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    // Query to fetch data from the 'services' table
                    $sql = "SELECT * FROM services";
                    $result = $conn->query($sql);

                    // Check if there are results and display them
                    if ($result->num_rows > 0) {
                        echo '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">'; // Start grid container
                        while ($row = $result->fetch_assoc()):
                    ?>

                    <div style="border: 1px solid #ddd; padding: 10px; text-align: center; border-radius: 5px;">
                        <h3><?php echo $row['sname']; ?></h3>
                        <img src="<?php echo $row['img']; ?>" width="200px" height="200px" alt="Service Image" />
                        <p>
                            <a href="view_provider.php?type=elect" class="button">View All</a>
                        </p>
                    </div>

                    <?php 
                        endwhile;
                        echo '</div>'; // End grid container
                    } else {
                        echo "<p>No services available.</p>";
                    }
                    $conn->close();
                    ?>

                </section>

            </div>
        </div>

        <!-- Sidebar -->
        <div id="sidebar">
            <div class="inner">

                <!-- Menu -->
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
