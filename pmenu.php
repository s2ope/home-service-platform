<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure provider is logged in
if (!isset($_SESSION["provider_utype"]) || $_SESSION["provider_utype"] != "Provider") {
    header("location:Signin.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "homeservice");
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

$provider_id = $_SESSION['provider_id'];

// ==================== WALLET ====================
$wallet_balance = 0.00;
$wallet_query = "SELECT SUM(wallet) FROM service_request WHERE provider_id = ?";
$wallet_stmt = $con->prepare($wallet_query);
if ($wallet_stmt) {
    $wallet_stmt->bind_param("i", $provider_id);
    $wallet_stmt->execute();
    $wallet_stmt->bind_result($wallet_balance);
    $wallet_stmt->fetch();
    $wallet_stmt->close();
    $wallet_balance = $wallet_balance ?? 0.00; // ensure not null
} else {
    $wallet_balance = 0.00;
}


// ==================== NOTIFICATIONS ====================
// Unread verification requests
$unread_verification_count = 0;
$count_query = "SELECT COUNT(*) as unread_count FROM verification_request WHERE provider_id = ? AND read_status = 0";
$count_stmt = $con->prepare($count_query);
if ($count_stmt) {
    $count_stmt->bind_param("i", $provider_id);
    $count_stmt->execute();
    $result = $count_stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $unread_verification_count = $row['unread_count'] ?? 0;
    }
    $count_stmt->close();
}

// Unread active service requests
$unread_service_count = 0;
$service_count_query = "SELECT COUNT(*) as unread_count FROM service_request WHERE provider_id = ? AND read_status = 0 AND status IN (0,1)";
$service_count_stmt = $con->prepare($service_count_query);
if ($service_count_stmt) {
    $service_count_stmt->bind_param("i", $provider_id);
    $service_count_stmt->execute();
    $result = $service_count_stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $unread_service_count = $row['unread_count'] ?? 0;
    }
    $service_count_stmt->close();
}

// ==================== AVERAGE RATING ====================
$average_rating = 0.0;
$rating_query = "SELECT average_rating FROM provider WHERE pid = ?";
$rating_stmt = $con->prepare($rating_query);
if ($rating_stmt) {
    $rating_stmt->bind_param("i", $provider_id);
    $rating_stmt->execute();
    $rating_stmt->bind_result($average_rating);
    $rating_stmt->fetch();
    $rating_stmt->close();
}
$average_rating = $average_rating ?? 0.0;

// ==================== PROFILE PICTURE UPDATE ====================
if (isset($_POST['update_profile_pic'])) {
    $target_dir = "uploads/";
    $file = $_FILES['profile_pic'];
    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $uploadOk = 1;

    // Validate image
    if (getimagesize($file['tmp_name']) === false) {
        $_SESSION['flash_message'] = "File is not an image.";
        $uploadOk = 0;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['flash_message'] = "File is too large (max 5MB).";
        $uploadOk = 0;
    }

    if (!in_array($imageFileType, ['jpg','jpeg','png','gif'])) {
        $_SESSION['flash_message'] = "Only JPG, JPEG, PNG & GIF allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk) {
        $new_filename = "provider_" . $provider_id . "_" . time() . "." . $imageFileType;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $update_query = "UPDATE provider SET photo = ? WHERE pid = ?";
            $stmt = $con->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param("si", $new_filename, $provider_id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['provider_img'] = $new_filename;
                $_SESSION['flash_message'] = "Profile picture updated successfully.";
            }
        } else {
            $_SESSION['flash_message'] = "Error uploading file.";
        }
    }

    echo '<script>window.location.href = "' . $_SERVER['PHP_SELF'] . '";</script>';
    exit();
}

// Calculate stars
$full_stars = floor($average_rating);
$has_half_star = ($average_rating - $full_stars) >= 0.5;
$empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);

?>

<!-- ==================== HTML ==================== -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Provider Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .star { font-size: 20px; color: #ccc; }
        .star.checked { color: gold; }
        .unread-badge { display:inline-block; background:red; color:white; border-radius:50%; width:20px; height:20px; text-align:center; line-height:20px; font-size:12px; animation:pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
    </style>
</head>
<body>

<section id="profile" style="text-align:center; margin-top:20px;">
    <form method="post" enctype="multipart/form-data" id="profile_pic_form">
        <img src="uploads/<?php echo $_SESSION['provider_img']; ?>" width="150" height="150" style="border-radius:50%; border:1px solid #ccc;"><br>
        <h2><?php echo $_SESSION['provider_name']; ?></h2>
        <a href="#" onclick="document.getElementById('profile_pic_input').click(); return false;"><i class="fas fa-camera"></i> Edit Profile Picture</a>
        <input type="file" name="profile_pic" id="profile_pic_input" style="display:none;" onchange="document.getElementById('profile_pic_form').submit();">
        <input type="hidden" name="update_profile_pic" value="1">
    </form>

    <!-- Star Rating -->
    <div style="margin:10px;">
        <?php
        for ($i=0;$i<$full_stars;$i++) echo '<span class="star checked">&#9733;</span>';
        if ($has_half_star) echo '<span class="star checked">&#9733;</span>'; // using same star
        for ($i=0;$i<$empty_stars;$i++) echo '<span class="star">&#9733;</span>';
        echo ' <span>('.number_format((float)$average_rating,2).')</span>';
        ?>
    </div>

    <!-- Wallet -->
    <div style="margin:10px; font-size:1em;">
        <strong><i class="fas fa-wallet" style="color:#17a2b8;"></i> Wallet: Rs.</strong> <?php echo number_format((float)$wallet_balance,2); ?>
    </div>
</section>

<!-- ==================== MENU ==================== -->
<nav id="menu">
    <header class="major">
	<h2>Menu</h2>
</header>

    <ul>
        <li><a href="Mservices.php">Verify Services <?php if($unread_verification_count>0) echo '<span class="unread-badge">'.$unread_verification_count.'</span>'; ?></a></li>
        <li><a href="view_request.php">Active Requests <?php if($unread_service_count>0) echo '<span class="unread-badge">'.$unread_service_count.'</span>'; ?></a></li>
        <li><a href="provider_map.php">View Map</a></li>
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
                                                <li class="icon solid fa-envelope"><a href="mailto:shoujanyashakya57@pmc.edu.np">shoujanyashakya57@pmc.edu.np</a></li>
												<li class="icon solid fa-phone">+977 9820000000</li>
												<li class="icon solid fa-envelope"><a href="mailto:sandeshkandel52@pmc.edu.np">sandeshkandel52@pmc.edu.np</a></li>
												<li class="icon solid fa-phone">+977 9801000000</li>
												<li class="icon solid fa-home">PMC,<br />Patandhoka,<br />Lalitpur</li>
    </ul>
</section>

<!-- Footer -->
<footer id="footer">
    <p class="copyright">&copy; homeservice. All rights reserved. 
	<a href="mailto:shoujanyashakya57@pmc.edu.np">shoujanyashakya,</a>
	<a href="mailto:sandeshkandel52@pmc.edu.np">sandeshkandel,</a>.</p>
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
</body>
</html>

