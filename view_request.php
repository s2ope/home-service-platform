<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if the user is a provider
if (!isset($_SESSION["provider_utype"])) {
    header("location:Signin.php");
    exit();
}

// Initialize message variable
$msg = '';

// Display flash messages if they exist
if (isset($_SESSION['flash_message'])) {
    $msg = "<div class='alert-message'>
                <i class='fas fa-check-circle'></i> " . $_SESSION['flash_message'] . "
            </div>";
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// Handle bill update message
if (isset($_GET['bill_update']) && $_GET['bill_update'] == "success") {
    $_SESSION['flash_message'] = "Bill updated successfully.";
    $_SESSION['flash_type'] = "success";
    header("Location: view_request.php");
    exit();
}

if (isset($_POST["btn"])) {
    $con = mysqli_connect("localhost", "root", "", "homeservice");
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }

    $status = ($_POST["btn"] == "Accept") ? 1 : 2;
    $srid = $_POST['srid'];

    if ($status == 1) {
        // First get the date/time of the request being accepted
        $query = "SELECT req_date, req_time FROM service_request WHERE srid = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $srid);
        $stmt->execute();
        $stmt->bind_result($req_date, $req_time);
        $stmt->fetch();
        $stmt->close();

        // Check for conflicts
        $conflict_query = "SELECT COUNT(*) FROM service_request 
                          WHERE provider_id = ? 
                          AND req_date = ? 
                          AND req_time = ? 
                          AND srid != ?
                          AND status = 1 
                          AND work_status != 1";
        
        $stmt = $con->prepare($conflict_query);
        $stmt->bind_param("issi", $_SESSION['provider_id'], $req_date, $req_time, $srid);
        $stmt->execute();
        $stmt->bind_result($conflict_count);
        $stmt->fetch();
        $stmt->close();

        if ($conflict_count > 0) {
            $_SESSION['flash_message'] = "You already have an active request at this date/time. Please choose another time or reject this request.";
            $_SESSION['flash_type'] = "error";
            $con->close();
            header("Location: view_request.php");
            exit();
        }
    }

    // Update both status and mark as read
    if ($status == 2) {
        $query = "UPDATE service_request SET status = ?, work_status = 2, payment_status = 2, read_status = 1, read_status_c = 0, last_modified = CURRENT_TIMESTAMP, msgc = 'Your request has been rejected.' WHERE srid = ?";
    } else {
        $query = "UPDATE service_request SET status = ?, read_status = 1, read_status_c = 0, last_modified = CURRENT_TIMESTAMP, msgc = 'Your request has been accepted.' WHERE srid = ?";
    }

    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $status, $srid);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $_SESSION['flash_message'] = "Request has been " . $_POST["btn"] . "ed.";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Request could not be " . $_POST["btn"] . "ed.";
        $_SESSION['flash_type'] = "error";
    }
    
    $stmt->close();
    $con->close();
    
    header("Location: view_request.php");
    exit();
}

if (isset($_POST['work_done'])) {
    $srid = $_POST['srid'];
    $con = mysqli_connect("localhost", "root", "", "homeservice");
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }

    $notification_msg = "The work for your service request has been completed. Please wait until the bill is updated by the provider.";

    $query = "UPDATE service_request SET work_status = 1, msgc = ?, read_status_c = 0, last_modified = CURRENT_TIMESTAMP WHERE srid = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("si", $notification_msg, $srid);
    $stmt->execute();

    $_SESSION['flash_message'] = "Work status updated to completed.";
    $_SESSION['flash_type'] = "success";
    
    $stmt->close();
    $con->close();
    
    header("Location: view_request.php");
    exit();
}

if (isset($_POST['mark_as_read'])) {
    $srid = $_POST['srid'];
    $con = mysqli_connect("localhost", "root", "", "homeservice");
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }

    $query = "UPDATE service_request SET read_status = 1, last_modified = CURRENT_TIMESTAMP WHERE srid = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $srid);
    $stmt->execute();

    $_SESSION['flash_message'] = "Request marked as read.";
    $_SESSION['flash_type'] = "success";
    
    $stmt->close();
    $con->close();
    
    header("Location: view_request.php");
    exit();
}

if (isset($_POST['paid_cash'])) {
    $srid = $_POST['srid'];
    $con = mysqli_connect("localhost", "root", "", "homeservice");
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }

    // Get the charge amount first
    $query = "SELECT charge FROM service_request WHERE srid = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $srid);
    $stmt->execute();
    $stmt->bind_result($charge);
    $stmt->fetch();
    $stmt->close();
// var_dump($srid);
// exit;

    if ($charge > 0) {
        // Calculate 10% of charge for wallet deduction
        $wallet_deduction = $charge * 0.10;
        
        // Update payment status and deduct from wallet
        $query = "UPDATE service_request 
                 SET payment_status = 1, 
                     msgc = 'Thank u for choosing us!! Please rate our service.',
                     read_status_c = 0,
                     last_modified = CURRENT_TIMESTAMP
                 WHERE srid = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $srid);
        $stmt->execute();
        
        // Update provider's wallet (deduct 10%)
        $wallet_query = "UPDATE service_request SET wallet =- ? WHERE srid = ?";
        $wallet_stmt = $con->prepare($wallet_query);
        $wallet_stmt->bind_param("di", $wallet_deduction, $srid);
        $wallet_stmt->execute();
        $wallet_stmt->close();
        
        $_SESSION['flash_message'] = "Payment marked as completed in cash. 10% fee deducted from your wallet.";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Cannot process cash payment - charge amount is invalid.";
        $_SESSION['flash_type'] = "error";
    }
    
    // $stmt->close();
    $con->close();
    
    header("Location: view_request.php");
    exit();
}
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <title>Online HouseHold Service Portal</title>
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
         .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.8);
        overflow: auto;
    }

    .modal-content {
        display: block;
        margin: 5% auto;
        max-width: 80%;
        max-height: 80%;
    }

    .close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        transition: 0.3s;
        cursor: pointer;
    }

    .close:hover {
        color: #bbb;
    }

    /* Consumer Image Styles */
    .consumer-image {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #17a2b8;
        cursor: pointer;
        transition: transform 0.3s;
        margin-right: 15px;
        float: left;
    }

    .consumer-image:hover {
        transform: scale(1.05);
    }

    /* Notification System */
    .card-notification {
        padding: 10px 15px;
        margin: -15px -15px 15px -15px;
        border-radius: 10px 10px 0 0;
        display: flex;
        align-items: center;
        gap: 10px;
        background-color: #fff3cd;
        animation: fadeIn 0.5s ease-out;
    }

    .card-notification i {
        color: #FFC107;
        font-size: 18px;
        flex-shrink: 0;
    }

    .card-notification span {
        color: #856404;
        font-size: 14px;
        line-height: 1.4;
        flex-grow: 1;
    }

    .notification-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .notification-btn {
        padding: 4px 10px;
        background-color: #6C757D;
        color: white;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: none;
        cursor: pointer;
        height: 28px;
        line-height: 1;
    }

    .notification-btn.accept {
        background-color: #1cc88a;
    }

    .notification-btn.reject {
        background-color: #e74a3b;
    }

    .alert-message {
        padding: 10px 15px;
        background: #d4edda;
        color: #155724;
        border-radius: 5px;
        margin-bottom: 15px;
        font-size: 13px;
        border-left: 4px solid #28a745;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Existing Styles */
    .request-card {
        width: 90%;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin: 10px auto;
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        position: relative;
        background: white;
        overflow: hidden;
    }

    .request-card:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: #17a2b8;
    }

    .request-card.unread {
        border-left: 4px solid #ffc107;
        background-color: #fff9f9;
    }

    .request-header {
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 8px;
        color: #333;
        display: flex;
        align-items: center;
    }

    .request-content {
        margin-bottom: 8px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        font-size: 13px;
        clear: both;
    }

    .request-content strong {
        color: #666;
    }

    .status-badges {
        display: flex;
        gap: 8px;
        margin: 8px 0;
        flex-wrap: wrap;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        color: white;
        font-size: 12px;
        font-weight: bold;
        display: inline-block;
    }

    .badge-success { background-color: #28a745; }
    .badge-warning { background-color: #ffc107; }
    .badge-danger { background-color: #dc3545; }
    .badge-info { background-color: #17a2b8; }

    .request-notes {
        margin: 8px 0;
        padding: 8px;
        background-color: #f8f9fa;
        border-radius: 4px;
        border-left: 3px solid #6c757d;
        font-size: 13px;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        margin-top: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .action-buttons .btn,
    .action-buttons input[type="submit"],
    .action-buttons a.btn {
        padding: 7px 14px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
        min-width: 100px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: inherit;
        margin: 0;
        line-height: 1;
        box-sizing: border-box;
        height: 32px;
    }

    .btn-pay {
        background-color: #28a745;
        color: white;
    }

    .btn-pay:hover {
        background-color: #218838;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .btn-cash {
        background-color: #17a2b8;
        color: white;
    }

    .btn-cash:hover {
        background-color: #138496;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .btn-mark-read {
        background-color: #17a2b8;
        color: white;
    }

    .btn-mark-read:hover {
        background-color: #138496;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .btn-reject {
        background-color: #dc3545;
        color: white;
    }

    .no-bookings {
        text-align: center;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        margin: 15px auto;
        max-width: 500px;
    }

    /* Search Container Styles */
    .search-container {
        display: flex;
        justify-content: center;
        margin: 20px 0;
        padding: 0 5%;
    }

    .search-box {
        position: relative;
        width: 100%;
        max-width: 600px;
        display: flex;
        align-items: center;
    }

    .search-input {
        width: 100%;
        padding: 10px 15px;
        padding-right: 120px;
        border: 1px solid #ddd;
        border-radius: 25px;
        font-size: 14px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }

    .search-input:focus {
        outline: none;
        border-color: #17a2b8;
        box-shadow: 0 2px 8px rgba(23,162,184,0.2);
    }

    .search-button {
        background: #17a2b8;
        border: none;
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        cursor: pointer;
        flex-shrink: 0;
        margin-left: 10px;
    }

    .search-button:hover {
        background: #138496;
    }

    .action-form {
        display: inline-block;
        margin: 0;
        padding: 0;
    }

    .star-rating {
        display: inline-block;
        margin-left: 5px;
    }

    .star-rating .gold-star {
        color: gold;
        font-size: 18px;
        text-shadow: 0 0 1px rgba(0,0,0,0.3);
        margin-right: 2px;
    }

    .rating-text {
        font-size: 14px;
        margin-left: 5px;
        vertical-align: middle;
        color: #555;
    }

    /* Icon styles for request content */
    .info-with-icon {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-with-icon i {
        color: #17a2b8;
        width: 16px;
        text-align: center;
    }

    .badge-container {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    @media (max-width: 768px) {
        .request-content {
            grid-template-columns: 1fr;
        }
        
        .request-card {
            width: 95%;
            padding: 12px;
        }

        .action-buttons {
            flex-direction: row;
            justify-content: flex-start;
        }
        
        .action-buttons .btn,
        .action-buttons input[type="submit"],
        .action-buttons a.btn {
            min-width: 80px;
            padding: 6px 10px;
            font-size: 11px;
            height: 28px;
        }

        .card-notification {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .notification-actions {
            width: 100%;
            justify-content: flex-end;
        }

        .consumer-image {
            width: 50px;
            height: 50px;
            margin-right: 10px;
        }

        .modal-content {
            max-width: 95%;
            max-height: 95%;
        }

        .close {
            top: 10px;
            right: 20px;
            font-size: 30px;
        }
    }
    /* Image Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.8);
        overflow: auto;
    }

    .modal-content {
        display: block;
        margin: 5% auto;
        max-width: 80%;
        max-height: 80%;
        border-radius: 5px;
    }

    .close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        transition: 0.3s;
        cursor: pointer;
    }

    .close:hover {
        color: #bbb;
    }

    /* Consumer Image Styles */
    .consumer-image-container {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .consumer-image {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #17a2b8;
        cursor: pointer;
        transition: transform 0.3s;
        margin-right: 15px;
    }

    .consumer-image:hover {
        transform: scale(1.05);
    }

    .consumer-name {
        font-weight: bold;
        font-size: 16px;
        color: #333;
    }
    </style>
</head>

<body class="is-preload">
<div id="wrapper">
    <div id="main">
        <div class="inner">
            <header id="header">
                <a href="view_request.php" class="logo"><strong>Ghar Sewa</strong></a>
            </header>
        
            <h2 id="elements">View Requests</h2>
            <hr class="minor" />
            <div class="row gtr-200">
                <!-- Message Display Section -->
                <div class="col-12">
                    <?php echo $msg; ?>
                </div>
                <br>
                <div class="col-12 col-12-medium">
                    <!-- Search Box -->
                    <div class="search-container">
                        <div class="search-box">
                            <input type="text" id="searchInput" class="search-input" placeholder="Search requests by name, service, location, status...">
                            <button class="button primary" onclick="searchRequests()" style="margin-left:10px;">Search</button>
                        </div>
                    </div>
                    
                    <div class="table-wrapper">
                        <form name="f1" method="post" action="view_request.php">
                            <div id="requestsContainer">
                            <?php
                            $con = mysqli_connect("localhost", "root", "", "homeservice");
                            if (mysqli_connect_errno()) {
                                echo mysqli_connect_error();
                                exit();
                            }

                            $provider_id = $_SESSION["provider_id"];
                            $query1 = "
SELECT 
    sr.srid, 
    c.fname, c.mname, c.lname, 
    c.address, c.city, c.state, c.country, 
    sr.req_date, sr.req_time, 
    s.sname, 
    sr.status, sr.work_status, sr.payment_status, 
    sr.charge, sr.read_status, sr.last_modified, sr.msgp,
    p.average_rating,
    c.phnno,
    r.rating AS customer_rating,
    c.photo
FROM service_request sr
JOIN consumer c ON c.cid = sr.consumer_id
JOIN services s ON s.sid = sr.service_id
JOIN provider p ON p.pid = sr.provider_id
LEFT JOIN ratings r ON r.service_id = sr.service_id   -- ✅ FIXED
WHERE sr.provider_id = ?
AND sr.status IN (0, 1)
AND (
    sr.payment_status = 0
    OR (
        sr.payment_status = 1
        AND sr.read_status = 0
    )
)
ORDER BY sr.last_modified DESC
";

                            
                            $stmt1 = $con->prepare($query1);
                            $stmt1->bind_param("i", $provider_id);
                            $stmt1->execute();
                            $stmt1->store_result();
                            
                            if ($stmt1->num_rows > 0) {
                                $stmt1->bind_result($srid, $fname, $mname, $lname, $address, $city, $state, $country, 
                                                   $fdate, $tdate, $sname, $status, $work_status, $payment_status, 
                                                   $charge, $read_status, $last_modified, $msgp, 
                                                   $average_rating, $provider_phone, $customer_rating, $photo);

                                while ($stmt1->fetch()) {
                                    $status_class = $status == 1 ? 'status-success' : ($status == 2 ? 'status-danger' : 'status-warning');
                                    $card_class = $read_status == 0 ? 'request-card unread ' . $status_class : 'request-card ' . $status_class;
                                    $status_text = $status == 1 ? 'Accepted' : ($status == 2 ? 'Rejected' : 'Pending');
                                    $work_status_text = $work_status == 1 ? 'Completed' : ($work_status == 2 ? 'Dismissed' : 'Pending');
                                    $payment_status_text = $payment_status == 1 ? 'Paid' : ($payment_status == 2 ? 'Dismissed' : 'Pending');
                                    ?>
                                    <div class="<?= $card_class ?>" data-search="<?= strtolower("$fname $mname $lname $sname $address $city $state $status_text $work_status_text $payment_status_text $charge $provider_phone") ?>">
                                        <?php if (($read_status == 0 && !empty($msgp)) || ($payment_status == 1 && $read_status == 0)): ?>
                                            <div class="card-notification">
                                                <i class="fas fa-bell"></i>
                                                <span>
                                                    <?php 
                                                    if ($payment_status == 1 && $read_status == 0 && !empty($customer_rating)) {
                                                        echo "Congratulations on completing the service!!";
                                                        if (!empty($customer_rating)) {
                                                            echo " You have been rated: ";
                                                            $wholeStars = floor($customer_rating);
                                                            echo '<span class="star-rating">';
                                                            for ($i = 1; $i <= $wholeStars; $i++) {
                                                                echo '<span class="gold-star">★</span>';
                                                            }
                                                            echo '</span>';
                                                            echo '<span class="rating-text">(' . number_format($customer_rating, 1) . '/5)</span>';
                                                        }
                                                    } else {
                                                        echo $msgp;
                                                    }
                                                    ?>
                                                </span>
                                                <div class="notification-actions">
                                                    <?php if ($status == 0): ?>
                                                        <!-- For new requests, show Accept/Reject buttons -->
                                                        <form method="post" action="view_request.php" class="action-form">
                                                            <input type="hidden" name="srid" value="<?= $srid ?>">
                                                            <button type="submit" name="btn" value="Accept" class=" button primary notification-btn accept">
                                                                <i class="fas fa-check"></i> Accept
                                                            </button>
                                                        </form>
                                                        <form method="post" action="view_request.php" class="action-form">
                                                            <input type="hidden" name="srid" value="<?= $srid ?>">
                                                            <button type="submit" name="btn" value="Reject" class=" button primary notification-btn reject">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <!-- For other notifications, show Mark as Read button -->
                                                        <form method="post" action="view_request.php" class="action-form">
                                                            <input type="hidden" name="srid" value="<?= $srid ?>">
                                                            <button type="submit" name="mark_as_read" class="button primary notification-btn" >
                                                                <i class="fas fa-check-circle"></i> Mark Read
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="consumer-image-container">
                                            <img src="<?= !empty($photo) ? 'uploads/' . htmlspecialchars($photo) : 'https://via.placeholder.com/60?text=User' ?>" 
                                                 alt="Consumer Photo" 
                                                 class="consumer-image" 
                                                 onclick="openModal('<?= !empty($photo) ? 'uploads/' . htmlspecialchars($photo) : 'https://via.placeholder.com/400?text=User' ?>')">
                                            <span class="consumer-name"><?= strtoupper($fname . ' ' . $mname . ' ' . $lname) ?></span>
                                        </div>
                                        
                                        <div class="request-content">
                                            <div class="info-with-icon">
                                                <i class="fas fa-concierge-bell"></i>
                                                <span><strong>Service:</strong> <?= $sname ?></span>
                                            </div>
                                            <div class="info-with-icon">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><strong>Location:</strong> <?= "$address, $city, $state" ?></span>
                                            </div>
                                            <div class="info-with-icon">
                                                <i class="fas fa-phone"></i>
                                                <span><strong>Contact:</strong> <?= $provider_phone ?></span>
                                            </div>
                                            <div class="info-with-icon">
                                                <i class="far fa-calendar-alt"></i>
                                                <span><strong>Request Date:</strong> <?= date('M j, Y', strtotime($fdate)) ?></span>
                                            </div>
                                            <div class="info-with-icon">
                                                <i class="far fa-clock"></i>
                                                <span><strong>Request Time:</strong> <?= $tdate?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="status-badges">
                                            <div class="badge-container">
                                                <i class="fas fa-tasks"></i>
                                                <span style="margin-right:5px; font-size:12px;">Request:</span>
                                                <span class="badge <?= $status == 1 ? 'badge-success' : ($status == 2 ? 'badge-danger' : 'badge-warning') ?>">
                                                    <i class="fas <?= $status == 1 ? 'fa-check-circle' : ($status == 2 ? 'fa-times-circle' : 'fa-clock') ?>"></i>
                                                    <?= $status_text ?>
                                                </span>
                                            </div>
                                            
                                            <div class="badge-container">
                                                <i class="fas fa-hammer"></i>
                                                <span style="margin-right:5px; font-size:12px;">Work:</span>
                                                <span class="badge <?= $work_status == 1 ? 'badge-success' : ($work_status == 2 ? 'badge-danger' : 'badge-warning') ?>">
                                                    <i class="fas <?= $work_status == 1 ? 'fa-check' : ($work_status == 2 ? 'fa-ban' : 'fa-spinner') ?>"></i>
                                                    <?= $work_status_text ?>
                                                </span>
                                            </div>
                                            
                                            <div class="badge-container">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <span style="margin-right:5px; font-size:12px;">Payment:</span>
                                                <span class="badge <?= $payment_status == 1 ? 'badge-success' : ($payment_status == 2 ? 'badge-danger' : 'badge-warning') ?>">
                                                    <i class="fas <?= $payment_status == 1 ? 'fa-check' : ($payment_status == 2 ? 'fa-ban' : 'fa-hourglass-half') ?>"></i>
                                                    <?= $payment_status_text ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <?php if (!($charge == '' && $status == 0)): ?>
                                            <div class="request-notes">
                                                <?php if ($charge == '' && $status != 0): ?>
                                                    <i class="fas fa-info-circle"></i> <strong>Note:</strong> Charge will be estimated after work completion
                                                <?php else: ?>
                                                    <i class="fas fa-rupee-sign"></i> <strong>Charge:</strong> <span style="color: #28a745;">Rs. <?= htmlspecialchars($charge) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="action-buttons">
    <?php if ($status == 1 && $work_status == 0): ?>
        <!-- Work not done yet -->
        <form method="post" action="view_request.php" class="action-form">
            <input type="hidden" name="srid" value="<?= $srid ?>">
            <input type="submit" name="work_done" value="Work Done" class="btn btn-pay">
        </form>

    <?php elseif ($work_status == 1 && $payment_status == 0 && ($charge === null || $charge == 0)): ?>
        <!-- Work done, bill not updated yet -->
        <a href="update_bill.php?srid=<?= $srid ?>" class="btn btn-pay primary" style="display: inline-flex; align-items: center; justify-content: center;">
            <i class="fas fa-file-invoice-dollar" style="margin-right:3px;"></i> Update Bill
        </a>

    <?php elseif ($work_status == 1 && $payment_status == 0 && !empty($charge)): ?>
        <!-- Work done, bill updated, payment pending -->
        <form method="post" action="view_request.php" class="action-form">
            <input type="hidden" name="srid" value="<?= $srid ?>">
            <button type="submit" name="paid_cash" class="btn btn-cash button primary" style="display: inline-flex; align-items: center; justify-content: center;">
                <i class="fas fa-money-bill-wave" style="margin-right:3px;"></i> Paid in Cash
            </button>
        </form>
    <?php endif; ?>
</div>

                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div class="no-bookings">
                                    <h3><i class="fas fa-inbox"></i> No Active Requests Found</h3>
                                    <p>You don\'t have any active service requests at the moment.</p>
                                </div>';
                            }
                            $stmt1->close();
                            $con->close();
                            ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="sidebar">
        <div class="inner">
            <?php include "pmenu.php"; ?>
        </div>
    </div>
</div>

<!-- The Modal -->
<div id="imageModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/browser.min.js"></script>
<script src="assets/js/breakpoints.min.js"></script>
<script src="assets/js/util.js"></script>
<script src="assets/js/main.js"></script>

<script>
// Modal functionality
function openModal(imageSrc) {
    var modal = document.getElementById("imageModal");
    var modalImg = document.getElementById("modalImage");
    modal.style.display = "block";
    modalImg.src = imageSrc;
    
    // Adjust modal image size based on screen size
    if (window.innerWidth <= 768) {
        modalImg.style.maxWidth = "95%";
        modalImg.style.maxHeight = "95%";
    } else {
        modalImg.style.maxWidth = "80%";
        modalImg.style.maxHeight = "80%";
    }
}

function closeModal() {
    document.getElementById("imageModal").style.display = "none";
}

// Close modal when clicking outside the image
window.onclick = function(event) {
    var modal = document.getElementById("imageModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Search functionality
function searchRequests() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.request-card');
    let hasResults = false;
    
    cards.forEach(card => {
        const searchText = card.getAttribute('data-search');
        if (searchText.includes(searchTerm)) {
            card.style.display = 'flex';
            hasResults = true;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show no results message if needed
    const noResultsElement = document.querySelector('.no-results');
    if (!hasResults) {
        if (!noResultsElement) {
            const noResults = document.createElement('div');
            noResults.className = 'no-bookings no-results';
            noResults.innerHTML = '<h3><i class="fas fa-search"></i> No Matching Requests Found</h3><p>Try a different search term.</p>';
            document.getElementById('requestsContainer').appendChild(noResults);
        }
    } else if (noResultsElement) {
        noResultsElement.remove();
    }
}

// Add event listener for Enter key
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchRequests();
    }
});
</script>
</body>
</html>