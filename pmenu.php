<?php
if(!isset($_SESSION["provider_utype"]) || $_SESSION["provider_utype"] != "Provider") {
    header("location:Signin.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}

// Get provider wallet balance
$wallet_balance = 0;
$wallet_query = "SELECT sum(wallet) FROM service_request WHERE provider_id = ?";
$wallet_stmt = $con->prepare($wallet_query);
$wallet_stmt->bind_param("i", $_SESSION['provider_id']);
$wallet_stmt->execute();
$wallet_stmt->bind_result($wallet_balance);
$wallet_stmt->fetch();
$wallet_stmt->close();

// Count unread verification requests for notification badge
$unread_verification_count = 0;
if ($con) {
    $provider_id = $_SESSION['provider_id'];
    $count_query = "SELECT COUNT(*) as unread_count FROM verification_request 
                   WHERE provider_id = ? AND read_status = 0";
    $count_stmt = $con->prepare($count_query);
    $count_stmt->bind_param("i", $provider_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    
    if ($count_result) {
        $count_row = $count_result->fetch_assoc();
        $unread_verification_count = $count_row['unread_count'];
    }
}

// Count unread service requests for active requests badge
$unread_service_count = 0;
$query_count = "SELECT COUNT(*) as unread_count FROM service_request WHERE provider_id = ? AND read_status = 0 AND status IN (0,1)";
$stmt_count = $con->prepare($query_count);
$stmt_count->bind_param("i", $provider_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
if ($result_count) {
    $row_count = $result_count->fetch_assoc();
    $unread_service_count = $row_count['unread_count'];
}

// Get provider's average rating
$average_rating = 0;
$query_rating = "SELECT average_rating FROM provider WHERE pid = ?";
$stmt_rating = $con->prepare($query_rating);
$stmt_rating->bind_param("i", $provider_id);
$stmt_rating->execute();
$stmt_rating->bind_result($average_rating);
$stmt_rating->fetch();
$stmt_rating->close();

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
        $new_filename = "provider_" . $_SESSION['provider_id'] . "_" . time() . "." . $imageFileType;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            // Update database with new filename
            $update_query = "UPDATE provider SET photo = ? WHERE pid = ?";
            $update_stmt = $con->prepare($update_query);
            $update_stmt->bind_param("si", $new_filename, $_SESSION['provider_id']);
            $update_stmt->execute();
            
            if ($update_stmt->affected_rows > 0) {
                $_SESSION['provider_img'] = $new_filename;
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

// Calculate star ratings
$full_stars = floor($average_rating);
$has_half_star = ($average_rating - $full_stars) >= 0.5;
$empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
?>

<!-- HTML Section -->
<section id="search" class="alt">
    <center>
        <form method="post" enctype="multipart/form-data" id="profile_pic_form" style="position: relative;">
            <img src='uploads/<?php echo $_SESSION["provider_img"]; ?>' width="150px" height="150px" style="border-radius: 75px;border:1px solid white;">
            <br>
            <h2><?php echo $_SESSION["provider_name"]; ?></h2>
            <a href="#" onclick="document.getElementById('profile_pic_input').click(); return false;" style="font-size: 0.8em; margin-top: 5px; display: inline-block;">
                <i class="fas fa-camera"></i> Edit Profile Picture
            </a>
            <input type="file" name="profile_pic" id="profile_pic_input" style="display: none;" onchange="document.getElementById('profile_pic_form').submit();">
            <input type="hidden" name="update_profile_pic" value="1">
        </form>
        
       
        
        <!-- Star Rating Display -->
        <div class="star-rating" style="margin: 5px 0 5px 0;">
            <?php
            // Display full stars
            for ($i = 0; $i < $full_stars; $i++) {
                echo '<span class="fa fa-star checked" style="color: gold;"></span>';
            }
            
            // Display half star if needed
            if ($has_half_star) {
                echo '<span class="fa fa-star-half-alt checked" style="color: gold;"></span>';
            }
            
            // Display empty stars
            for ($i = 0; $i < $empty_stars; $i++) {
                echo '<span class="fa fa-star" style="color: #ccc;"></span>';
            }
            
            // Display rating number
            echo '<span style="margin-left: 5px; font-size: 0.9em;">(' . number_format($average_rating, 2) . ')</span>';
            ?>
        </div>
        
        <!-- Wallet Balance -->
        <div style="margin: 10px 0; font-size: 0.9em;">
            <h4><strong><i class="fas fa-wallet" style="color: #17a2b8;"></i> Wallet: Rs.</strong> <?php echo number_format($wallet_balance, 2); ?></h4>
        </div>
    </center>
</section>

<!-- Menu -->
<nav id="menu">
    <header class="major">
        <h2>Menu</h2>
    </header>
    <ul>
        <li>
            <a href="Mservices.php" style="position: relative; display: inline-block;">Verify Services
                <?php if ($unread_verification_count > 0): ?>
                    <span class="unread-badge" style="margin-left: 5px; display: inline-flex;color:white; align-items: center; justify-content: center;">
                        <?php echo $unread_verification_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="view_request.php" style="position: relative; display: inline-block;">Active Requests
                <?php if ($unread_service_count > 0): ?>
                    <span class="unread-badge" style="margin-left: 5px; display: inline-flex;color:white; align-items: center; justify-content: center;">
                        <?php echo $unread_service_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>
        <li><a href="View_report.php">Request History</a></li>
        <li><a href="Provider_Cpass.php">My Account</a></li>
        <li><a href="contactusp.php">Contact Us</a></li>
        <li><a href="Signout.php">Signout</a></li>
    </ul>
</nav>

<!-- Section -->
<section>
    <header>
        <h2>Get in touch</h2>
    </header>
    <ul class="contact">
        <li class="icon solid fa-envelope"><a href="mailto:manishc021338@nec.edu.np">manishc021338@nec.edu.np</a></li>
        <li class="icon solid fa-phone">+977 9763617636</li>
        <li class="icon solid fa-envelope"><a href="mailto:luthanc021336@nec.edu.np">luthanc021336@nec.edu.np</a></li>
        <li class="icon solid fa-phone">+977 9861526806</li>
        <li class="icon solid fa-envelope"><a href="mailto:manjilb021342@nec.edu.np">manjilb021340@nec.edu.np</a></li>
        <li class="icon solid fa-phone">+977 9804040377</li>
        <li class="icon solid fa-home">Nepal Engineering College,<br />Changunarayan,<br />Bhaktapur</li>
    </ul>
</section>

<!-- Footer -->
<footer id="footer">
    <p class="copyright">&copy; homeservice. All rights reserved. Design By: <a href="mailto:manishc021338@nec.edu.np">Manish Chapagain,</a>
    <a href="mailto:luthanc021336@nec.edu.np">Luthan Hang Chongbang,</a>
    <a href="mailto:manjilb021340@nec.edu.np">Manjil Bhandari,</a>.</p>
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