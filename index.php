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
                <a href="index.php" class="logo"><strong>Ghar Sewa</strong></a>
            </header>

            <!-- Services -->
            <header class="major">
                <h2>Our Services</h2>
            </header>

            <?php
            // Database connection
            $conn = new mysqli("localhost", "root", "", "homeservice");

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sql = "SELECT sid, sname, icon FROM services";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                echo '<div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:20px;">';

                while ($row = $result->fetch_assoc()) {
                    ?>
                    <div style="border:1px solid black; padding:10px; text-align:center; border-radius:5px;">
                        <h3><?php echo htmlspecialchars($row['sname']); ?></h3>

                        <img 
                            src="<?php echo htmlspecialchars($row['icon']); ?>" 
                            width="200" 
                            height="200" 
                            alt="Service Image"
                            style="object-fit:cover;"
                        >

                        <p>
                            <a href="view_provider1.php?id=<?php echo $row['sid']; ?>" class="button">
                                View All
                            </a>
                        </p>
                    </div>
                    <?php
                }

                echo '</div>';
            } else {
                echo "<p>No services available.</p>";
            }

            $conn->close();
            ?>

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
