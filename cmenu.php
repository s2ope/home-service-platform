<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
ob_start();


$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}

// Get the consumer ID from session
$consumer_id = $_SESSION["consumer_id"];

// Query to count the number of unread requests (read_status = 0)
$query_count = "SELECT COUNT(*) FROM service_request WHERE consumer_id = ? AND read_status_c = 0 and status in(0,1)";
$stmt_count = $con->prepare($query_count);
$stmt_count->bind_param("i", $consumer_id);
$stmt_count->execute();
$stmt_count->bind_result($unread_count);
$stmt_count->fetch();
$stmt_count->close();

// Handle profile picture update
if (isset($_POST['update_profile_pic'])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
    if($check !== false) {
        $uploadOk = 1;
    } else {
        $_SESSION['flash_message'] = "File is not an image.";
        $_SESSION['flash_type'] = "error";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["profile_pic"]["size"] > 5000000) {
        $_SESSION['flash_message'] = "Sorry, your file is too large (max 5MB).";
        $_SESSION['flash_type'] = "error";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" ) {
        $_SESSION['flash_message'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $_SESSION['flash_type'] = "error";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 1) {
        // Generate unique filename
        $new_filename = "consumer_" . $_SESSION['consumer_id'] . "_" . time() . "." . $imageFileType;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            // Update database with new filename
            $update_query = "UPDATE consumer SET photo = ? WHERE cid = ?";
            $update_stmt = $con->prepare($update_query);
            $update_stmt->bind_param("si", $new_filename, $_SESSION['consumer_id']);
            $update_stmt->execute();
            
            if ($update_stmt->affected_rows > 0) {
                $_SESSION['consumer_img'] = $new_filename;
                $_SESSION['flash_message'] = "Profile picture updated successfully.";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Failed to update profile picture in database.";
                $_SESSION['flash_type'] = "error";
            }
            $update_stmt->close();
        } else {
            $_SESSION['flash_message'] = "Sorry, there was an error uploading your file.";
            $_SESSION['flash_type'] = "error";
        }
    }
    echo '<script>window.location.href = "' . $_SERVER['PHP_SELF'] . '";</script>';
    exit();
}

// Close the database connection
$con->close();
?>

<!-- Search -->
<section id="search" class="alt">
    <center>
        <form method="post" enctype="multipart/form-data" id="profile_pic_form" style="position: relative;">
            <img src='uploads/<?php echo $_SESSION["consumer_img"]; ?>' width="150px" height="150px" style="border-radius: 75px;border:1px solid white;">
            <br>
            <a href="#" onclick="document.getElementById('profile_pic_input').click(); return false;" style="font-size: 0.8em; margin-top: 5px; display: inline-block;">
                <i class="fas fa-camera"></i> Edit Profile Picture
            </a>
            <input type="file" name="profile_pic" id="profile_pic_input" style="display: none;" onchange="document.getElementById('profile_pic_form').submit();">
            <input type="hidden" name="update_profile_pic" value="1">
        </form>
        
        <h4><?php echo $_SESSION["consumer_name"]; ?></h4>
    </center>
</section>

<!-- Menu -->
<nav id="menu">
    <header class="major">
        <h2>Menu</h2>
    </header>
    <ul>
        <li><a href="welcome.php">Homepage</a></li>
        <li>
            <a href="mybookings1.php" style="position: relative; display: inline-block;">My Bookings
                <?php if ($unread_count > 0): ?>
                    <span class="unread-badge" style="margin-left: 5px; display: inline-flex;color:white; align-items: center; justify-content: center;">
                        <?php echo $unread_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>
        <li><a href="myservices.php">Booking History</a></li>
        <li><a href="Consumer_Cpass.php">My Account</a></li>
				<li><a href="contactusc.php">Contact Us</a></li>
        <li><a href="Signout.php">Signout</a></li>
    </ul>
</nav>

<!-- Section -->
<section>
    <header class="major">
        <h2>Get in touch</h2>
    </header>
    <ul class="contact">
        <li class="icon solid fa-envelope"><a href="mailto:shoujanya57@pmc.edu.np">shoujanya57@pmc.edu.np</a></li>
        <li class="icon solid fa-phone">+977 9700000000</li>
        <li class="icon solid fa-envelope"><a href="mailto:sandesh52@pmc.edu.np">sandesh52@pmc.edu.np</a></li>
        <li class="icon solid fa-phone">+977 98000000</li>
        <li class="icon solid fa-home">PMC,<br />Patandhoka,<br />Lalitpur</li>
    </ul>
</section>

<!-- Footer -->
<footer id="footer">
    <p class="copyright">&copy; homeservice. All rights reserved. 
    </p>
</footer>

<style>
    .unread-badge {
        display: inline-block;
        background-color: red;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        text-align: center;
        line-height: 20px;
        font-size: 12px;
        margin-left: 5px;
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
</style>