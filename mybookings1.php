<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
// session_name('consumer_session');
session_start();
if (!isset($_SESSION["consumer_utype"])) {
    header("Location: Signin.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

$msg = '';
$msg_color = 'green';
if(isset($_SESSION['rating_msg'])) {
    $msg = $_SESSION['rating_msg'];
    $msg_color = $_SESSION['rating_msg_color'] ?? 'green';
    unset($_SESSION['rating_msg']);
    unset($_SESSION['rating_msg_color']);
}
if(isset($_GET['payment']) && isset($_GET['srid'])) {
    $srid = $_GET['srid'];
    
    if($_GET['payment'] == 'success') {
        // First update payment status and messages
        $update_query = "UPDATE service_request SET payment_status = 1, read_status = 0, msgp = 'Payment Received for Your Service..!!', read_status_c = 0, msgc = 'Thank u for choosing us!! Please rate our service' WHERE srid = ?";
        $stmt = $con->prepare($update_query);
        $stmt->bind_param("s", $srid);
        $stmt->execute();
        
        // Get the charge amount
        $charge_query = "SELECT charge FROM service_request WHERE srid = ?";
        $charge_stmt = $con->prepare($charge_query);
        $charge_stmt->bind_param("s", $srid);
        $charge_stmt->execute();
        $charge_stmt->bind_result($charge);
        $charge_stmt->fetch();
        $charge_stmt->close();
        
        if ($charge > 0) {
            // Calculate 90% of the charge
            $wallet_amount = $charge * 0.9;
            
            // Update the wallet column
            $wallet_query = "UPDATE service_request SET wallet = ? WHERE srid = ?";
            $wallet_stmt = $con->prepare($wallet_query);
            $wallet_stmt->bind_param("ds", $wallet_amount, $srid);
            $wallet_stmt->execute();
            $wallet_stmt->close();
        }
        
        $msg = "Payment completed successfully!";
        $msg_color = 'green';
    } else {
        $msg = "Payment failed. Please try again.";
        $msg_color = 'red';
    }
    
    $_SESSION['payment_msg'] = $msg;
    $_SESSION['payment_msg_color'] = $msg_color;
    
    header("Location: " . strtok($current_url, '?'));
    exit();
}

if(isset($_SESSION['payment_msg'])) {
    $msg = $_SESSION['payment_msg'];
    $msg_color = $_SESSION['payment_msg_color'];
    unset($_SESSION['payment_msg']);
    unset($_SESSION['payment_msg_color']);
}

if(isset($_POST['update_read_status']) && isset($_POST['srid'])) {
    $srid = $_POST['srid'];
    $update_query = "UPDATE service_request SET read_status_c = 1 WHERE srid = ?";
    $stmt = $con->prepare($update_query);
    $stmt->bind_param("s", $srid);
    $stmt->execute();
    $stmt->close();
    
    header("Location: " . $current_url);
    exit();
}

$id = $_SESSION["consumer_id"];

$query = "SELECT provider.fname, provider.mname, provider.lname, provider.photo, provider.average_rating, 
          service_request.srid as srid, provider.address, provider.city, provider.state, provider.country, provider.phnno,
          service_request.req_date AS fdate, service_request.req_time, service_request.status AS status, 
          service_request.work_status, service_request.payment_status, services.sname, service_request.charge as charge,
          service_request.last_modified, service_request.read_status_c, service_request.msgc, service_request.provider_id
          FROM service_request
          JOIN provider ON provider.pid = service_request.provider_id
          JOIN consumer ON consumer.cid = service_request.consumer_id
          JOIN services ON services.sid = service_request.service_id
          WHERE service_request.consumer_id = ? 
          AND (
              (service_request.status IN (0, 1) AND (service_request.payment_status = 0 OR 
               (service_request.payment_status = 1 AND service_request.read_status_c = 0)))
              OR
              (service_request.status = 2 AND service_request.read_status_c = 0)
          )
          ORDER BY service_request.last_modified DESC";

$stmt = $con->prepare($query);
$stmt->bind_param("s", $id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($fname, $mname, $lname,$photo,$average_rating, $srid, $address, $city, $state, $country, $phnno, 
                  $fdate, $rtime, $status, $workstatus, $payment_status, $sname, $charge, $last_modified, $read_status_c, $msgc, $provider_id);
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Online Household Service Portal</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
    /* Rating Modal Styles */
    .provider-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #4e73df;
        cursor: pointer;
        transition: transform 0.3s ease;
        margin-right: 15px;
    }

    .provider-avatar:hover {
        transform: scale(1.1);
    }

    .star-rating {
        display: inline-flex;
        align-items: center;
        margin-top: 5px;
    }

    .star-rating i {
        color: #FFD700;
        font-size: 14px;
        margin-right: 2px;
    }

    .star-rating .far {
        color: #ccc;
    }

    .rating-value {
        font-size: 12px;
        margin-left: 5px;
        color: #fff;
        background: rgba(0,0,0,0.2);
        padding: 2px 6px;
        border-radius: 10px;
    }

    /* Image modal styles */
    .image-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.9);
        overflow: hidden;
    }

    .image-modal-content {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        width: 100vw;
        position: relative;
    }

    .modal-image {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
    }

    .close-image-modal {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        transition: 0.3s;
        cursor: pointer;
        z-index: 10001;
    }

    .close-image-modal:hover {
        color: #bbb;
    }

    .modal-open {
        overflow: hidden;
    }

    .rating-stars {
        text-align: center;
        margin: 20px 0;
        font-size: 0;
    }

    .rating-stars i {
        font-size: 32px;
        cursor: pointer;
        color: #ccc;
        transition: all 0.2s;
        margin: 0 5px;
    }

    .rating-stars i.fas {
        color: #ffc107;
    }

    .rating-stars i:hover {
        transform: scale(1.2);
    }

    .rating-instruction {
        text-align: center;
        color: #555;
        margin-bottom: 10px;
        font-size: 14px;
    }

    #submitRating {
        padding: 10px 25px;
        font-size: 16px;
    }

    #submitRating:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
        padding-top: 40px;
    }

    .modal-content {
        background-color: #fefefe;
        margin: 3% auto;
        padding: 20px;
        border: 1px solid #ddd;
        width: 90%;
        max-width: 500px;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    }

    .close {
        color: #888;
        float: right;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.2s;
    }

    .close:hover {
        color: #333;
    }

    /* Icon-specific styles */
    .card-label i,
    .btn i,
    .notification-btn i {
        margin-right: 8px;
        width: 16px;
        text-align: center;
    }

    .badge i {
        margin-right: 4px;
    }

    .card-title i {
        margin-right: 10px;
        color: #4e73df;
    }

    .service-name i {
        margin-right: 6px;
        color: #6c757d;
        font-size: 0.9em;
    }

    .btn i {
        font-size: 0.9em;
    }

    .card-notification i {
        font-size: 1.2em;
    }

    .search-container {
        margin-bottom: 20px;
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 10px 15px;
        padding-left: 40px;
        border: 1px solid #ddd;
        border-radius: 25px;
        font-size: 14px;
        transition: all 0.2s;
        background-color: #f8f9fa;
    }

    .search-input:focus {
        outline: none;
        border-color: #4CAF50;
        box-shadow: 0 0 8px rgba(76, 175, 80, 0.2);
        background-color: white;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #777;
        font-size: 14px;
    }

    .booking-cards {
        display: flex;
        flex-direction: column;
        gap: 15px;
        padding: 10px 0;
    }

    .booking-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        position: relative;
        display: flex;
        flex-direction: column;
        box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        width: 100%;
        max-width: 800px;
        margin: 0 auto;
    }

    .booking-card:hover {
        transform: scale(1.05);
        transform: translateY(-3px);
        box-shadow: 0 5px 12px rgba(0,0,0,0.12);
        border-color: #4CAF50;
    }

    .booking-card.unread {
        border-left: 4px solid #ffc107;
        background-color: #f8fff8;
    }

    .booking-card.hidden {
        display: none;
    }

    /* Notification System */
    .card-notification {
        padding: 10px 15px;
        margin: -15px -15px 10px -15px;
        border-radius: 10px 10px 0 0;
        display: flex;
        align-items: center;
        gap: 10px;
        background-color: #fff3cd;
        animation: fadeIn 0.5s ease-out;
        flex-wrap: wrap;
    }

    .card-notification i {
        color: #FFC107;
        font-size: 16px;
        flex-shrink: 0;
    }

    .card-notification span {
        color: #856404;
        font-size: 13px;
        line-height: 1.4;
        flex-grow: 1;
        min-width: 200px;
    }

    .notification-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .notification-btn {
        padding: 4px 10px;
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
        white-space: nowrap;
    }

    .notification-btn.rate {
    }

    .notification-btn.mark-read {
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card-header {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .card-title {
        margin: 0;
        color: #333;
        font-size: 1.1em;
        font-weight: 600;
        line-height: 1.3;
    }

    .service-name {
        color: #666;
        font-size: 0.85em;
        font-style: italic;
    }

    .card-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .card-section {
        margin-bottom: 8px;
    }

    .card-label {
        color: #777;
        font-size: 0.75em;
        text-transform: uppercase;
        margin-bottom: 2px;
        letter-spacing: 0.3px;
    }

    .card-value {
        color: #333;
        font-size: 0.9em;
        line-height: 1.4;
        font-weight: 500;
    }

    .status-container {
        display: flex;
        gap: 10px;
        margin: 10px 0;
        flex-wrap: wrap;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        font-size: 0.75em;
        font-weight: bold;
        border-radius: 12px;
        color: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .badge-warning { background-color: #ffc107; color: #000; }
    .badge-success { background-color: #28a745; }
    .badge-danger { background-color: #dc3545; }
    .badge-info { background-color: #17a2b8; }
    .badge-secondary { background-color: #6c757d; }

    .button-container {
        margin-top: 10px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding-top: 10px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 8px 14px;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-size: 0.85em;
        transition: all 0.2s;
        text-align: center;
        height: 34px;
        min-width: 90px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .btn-view {
        background-color: #007bff;
        color: white;
        text-decoration: none;
    }

    .btn-view:hover {
        background-color: #0069d9;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(0,0,0,0.15);
    }

    .btn-pay {
        background-color: #28a745;
        color: white;
    }

    .btn-pay:hover {
        background-color: #218838;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(0,0,0,0.15);
    }

    .btn:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .no-bookings {
        text-align: center;
        padding: 2rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px dashed #e0e0e0;
        margin: 1.5rem 0;
    }

    .no-bookings h3 {
        color: #6c757d;
        margin-bottom: 0.8rem;
        font-size: 1.1em;
    }

    .no-bookings p {
        color: #6c757d;
        font-size: 0.9em;
    }

    .service-details {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.8rem;
    }

    .detail {
        display: flex;
        margin-bottom: 0.6rem;
        font-size: 0.9em;
    }

    .detail strong {
        min-width: 100px;
        color: #555;
    }
    .provider-info {
        flex: 1;
    }

    .amount {
        font-weight: bold;
        color: #28a745;
    }

    .esewa-logo {
        height: 16px;
        margin-left: 6px;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .booking-card {
        animation: fadeIn 0.4s ease-out forwards;
        opacity: 0;
    }

    .booking-card:nth-child(1) { animation-delay: 0.1s; }
    .booking-card:nth-child(2) { animation-delay: 0.2s; }
    .booking-card:nth-child(3) { animation-delay: 0.3s; }
    .booking-card:nth-child(4) { animation-delay: 0.4s; }
    .booking-card:nth-child(5) { animation-delay: 0.5s; }

    @media (max-width: 768px) {
        .card-content {
            grid-template-columns: 1fr;
        }
        
        .modal-content {
            width: 95%;
            margin: 15px auto;
            padding: 15px;
        }
        
        .button-container {
            justify-content: flex-start;
        }
        
        .btn {
            flex-grow: 1;
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
    }

    @media (max-width: 480px) {
        .booking-card {
            padding: 12px;
        }
        
        .button-container {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
        }
        
        .search-input {
            padding: 8px 12px;
            padding-left: 35px;
            font-size: 13px;
        }
        
        .search-icon {
            font-size: 13px;
            left: 10px;
        }
    }
</style>
</head>
<body class="is-preload">
<div id="imageModal" class="image-modal">
        <span class="close-image-modal" onclick="closeImageModal()">&times;</span>
        <div class="image-modal-content">
            <img id="modalImage" class="modal-image">
        </div>
    </div>
    <!-- Service Details Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 style="color: #28a745; margin-bottom: 1.5rem;"><i class="fas fa-info-circle"></i> Service Request Details</h2>
            <div class="service-details">
                <div class="detail"><strong><i class="fas fa-user-tie"></i> Provider:</strong> <span id="modal-provider-name"></span></div>
                <div class="detail"><strong><i class="fas fa-concierge-bell"></i> Service:</strong> <span id="modal-service-name"></span></div>
                <div class="detail"><strong><i class="fas fa-map-marker-alt"></i> Address:</strong> <span id="modal-address"></span></div>
                <div class="detail"><strong><i class="fas fa-phone"></i> Phone:</strong> <span id="modal-phone"></span></div>
                <div class="detail"><strong><i class="far fa-calendar-alt"></i> Date/Time:</strong> <span id="modal-date-time"></span></div>
                <div class="detail"><strong><i class="fas fa-tasks"></i> Status:</strong> <span id="modal-status" class="badge"></span></div>
                <div class="detail"><strong><i class="fas fa-hammer"></i> Work Status:</strong> <span id="modal-work-status" class="badge"></span></div>
                <div class="detail"><strong><i class="fas fa-money-bill-wave"></i> Payment Status:</strong> <span id="modal-payment-status" class="badge"></span></div>
                <div class="detail"><strong><i class="fas fa-money-bill-wave"></i> Amount:</strong> <span id="modal-amount-to-pay" class="amount"></span></div>
            </div>
        </div>
    </div>

    <!-- Rating Modal -->
    <div id="ratingModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRatingModal()">&times;</span> 
            <h2 style="color: #28a745; margin-bottom: 1.5rem;"><i class="fas fa-star"></i> Rate Your Experience</h2>
            <form id="ratingForm" method="post" action="submit_rating.php">
                <input type="hidden" name="srid" id="rating_srid">
                <input type="hidden" name="provider_id" id="rating_provider_id">
                
                <div class="rating-instruction">Select your rating (1-5 stars):</div>
                
                <div class="rating-stars">
                    <i class="far fa-star" data-rating="1"></i>
                    <i class="far fa-star" data-rating="2"></i>
                    <i class="far fa-star" data-rating="3"></i>
                    <i class="far fa-star" data-rating="4"></i>
                    <i class="far fa-star" data-rating="5"></i>
                </div>
                <input type="hidden" name="rating_value" id="rating_value" value="0">
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" id="submitRating" class="btn btn-pay button primary" style="margin-left:140px;" disabled>
                        <i class="fas fa-paper-plane"></i> Submit Rating
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="wrapper">
        <div id="main">
            <div class="inner">
                <header id="header">
                    <a href="welcome.php" class="logo"><strong><i class="fas fa-home"></i> homeservice</strong></a>
                </header>

                <h2 id="elements"><i class="fas fa-calendar-check"></i> Active Bookings</h2>
                
                <hr class="minor" />
                <label><h3 style="color:<?php echo $msg_color ?? 'green' ?>"><?php echo $msg ?? ""; ?></h3></label>
                
                <div class="row gtr-200">
                    <div class="col-12 col-12-medium">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="searchInput" class="search-input" style="margin-left:40px;width:70%;"placeholder="Search bookings..." onkeyup="filterBookings()">
                        </div>
                        
                        <?php if ($stmt->num_rows > 0) : ?>
                            <div class="booking-cards" id="bookingCardsContainer">
                                <?php while ($stmt->fetch()) : 
                                    $status_label = $status == 0 ? "Pending" : ($status == 1 ? "Accepted" : "Rejected");
                                    $status_class = $status == 0 ? "badge-warning" : ($status == 1 ? "badge-success" : "badge-danger");
                                    $paymentstatus_label = $payment_status == 0 ? "Pending" : "Paid";
                                    $paymentstatus_class = $payment_status == 0 ? "badge-warning" : "badge-success";
                                    
                                    $workstatus_label = $workstatus == 0 ? "Pending" : "Completed";
                                    $workstatus_class = $workstatus == 0 ? "badge-warning" : "badge-success";
                                    $pay_disabled = !($status == 1 && $workstatus == 1 && $payment_status == 0 && !empty($charge));
                                    $card_class = $read_status_c == 0 ? 'booking-card unread' : 'booking-card';
                                    $full_name = "$fname $mname $lname";
                                    $full_address = "$address, $city, $state, $country";
                                    $date_time = date('M j, Y', strtotime($fdate)) . ' at ' .$rtime;
                                    $card_data = strtolower(implode(' ', [
                                        $full_name, $sname, $full_address, $phnno, $date_time, 
                                        $status_label, $workstatus_label, $payment_status ? 'Paid' : 'Pending', 
                                        $charge, $msgc
                                    ]));
                                ?>
                                    <div class="<?= $card_class ?>" data-search="<?= htmlspecialchars($card_data) ?>">
                                        <?php if ($read_status_c == 0 && !empty($msgc)): ?>
                                            <div class="card-notification">
                                                <i class="fas fa-bell"></i>
                                                <span><?= htmlspecialchars($msgc) ?></span>
                                                <div class="notification-actions">
                                                    <?php if ($status == 1 && $payment_status == 1): ?>
                                                        <button class="notification-btn rate button primary" onclick="openRatingModal('<?= $srid ?>', '<?= $provider_id ?>')">
                                                            <i class="fas fa-star"></i> Rate Service
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($workstatus!=1):?>
                                                    <form method="post" action="mybookings1.php" class="action-form">
                                                        <input type="hidden" name="srid" value="<?= $srid ?>">
                                                        <input type="hidden" name="update_read_status" value="1">
                                                        <button type="submit" class="notification-btn mark-read button primary ">
                                                            <i class="fas fa-check-circle"></i> Mark Read
                                                        </button>
                                                    </form>
                                                    <?php endif;?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="card-header">
                                            <img src="<?= htmlspecialchars($photo ? 'uploads/' . $photo : 'https://via.placeholder.com/60') ?>" 
                                                 alt="Provider Avatar" class="provider-avatar" 
                                                 onclick="openImageModal('<?= htmlspecialchars($photo ? 'uploads/' . $photo : 'https://via.placeholder.com/150') ?>')">
                                            <div class="provider-info">
                                                <h3 class="card-title"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($full_name) ?></h3>
                                                <div class="service-name"><i class="fas fa-concierge-bell"></i> <?= htmlspecialchars($sname) ?></div>
                                                <?php if ($average_rating !== null): ?>
                                                    <div class="star-rating">
                                                        <?php
                                                        $rating = $average_rating;
                                                        $fullStars = floor($rating);
                                                        $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                                        
                                                        for ($i = 0; $i < $fullStars; $i++) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        }
                                                        
                                                        if ($hasHalfStar) {
                                                            echo '<i class="fas fa-star-half-alt"></i>';
                                                        }
                                                        
                                                        for ($i = $fullStars + ($hasHalfStar ? 1 : 0); $i < 5; $i++) {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                        ?>
                                                        <span class="rating-value">(<?= number_format($rating, 1) ?>)</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="card-content">
                                            <div class="card-section">
                                                <div class="card-label"><i class="far fa-calendar-alt"></i> Date & Time</div>
                                                <div class="card-value"><?= htmlspecialchars($date_time) ?></div>
                                            </div>

                                            <div class="card-section">
                                                <div class="card-label"><i class="fas fa-money-bill-wave"></i> Amount</div>
                                                <div style="color: #28a745;" class="card-value">Rs. <?= empty($charge) ? 'N/A' : htmlspecialchars($charge) ?></div>
                                            </div>

                                            <div class="card-section" style="grid-column: span 2;">
                                                <div class="status-container">
                                                    <div class="badge-container">
                                                        <i class="fas fa-tasks"></i>
                                                        <span style="margin-right:5px; font-size:12px;">Request:</span>
                                                        <span class="badge <?= $status_class ?>">
                                                            <i class="fas <?= $status == 0 ? 'fa-clock' : ($status == 1 ? 'fa-check-circle' : 'fa-times-circle') ?>"></i>
                                                            <?= $status_label ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="badge-container">
                                                        <i class="fas fa-hammer"></i>
                                                        <span style="margin-right:5px; font-size:12px;">Work:</span>
                                                        <span class="badge <?= $workstatus_class ?>">
                                                            <i class="fas <?= $workstatus == 0 ? 'fa-spinner' : 'fa-check' ?>"></i>
                                                            <?= $workstatus_label ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="badge-container">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                        <span style="margin-right:5px; font-size:12px;">Payment:</span>
                                                        <span class="badge <?= $paymentstatus_class ?>">
                                                            <i class="fas <?= $payment_status == 0 ? 'fa-hourglass-half' : 'fa-receipt' ?>"></i>
                                                            <?= $paymentstatus_label ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="button-container">
                                            <a href="javascript:void(0);" class="btn btn-view" 
                                               onclick="openModal(
                                                   '<?= htmlspecialchars($full_name) ?>',
                                                   '<?= htmlspecialchars($sname) ?>',
                                                   '<?= htmlspecialchars($full_address) ?>',
                                                   '<?= htmlspecialchars($phnno) ?>',
                                                   '<?= htmlspecialchars(date('M j, Y', strtotime($fdate))) ?>',
                                                   '<?= htmlspecialchars(date('g:i A', strtotime($rtime))) ?>',
                                                   '<?= $status ?>',
                                                   '<?= $workstatus ?>',
                                                   '<?= $payment_status ?>',
                                                   '<?= empty($charge) ? 'N/A' : htmlspecialchars($charge) ?>'
                                               )">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                            
                                            <?php if (!$pay_disabled): ?>
                                                <form id="esewaForm<?= $srid ?>" action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
                                                    <input type="hidden" name="amount" value="<?= $charge ?>">
                                                    <input type="hidden" name="tax_amount" value="0">
                                                    <input type="hidden" name="total_amount" value="<?= $charge ?>">
                                                    <input type="hidden" name="transaction_uuid" id="transaction_uuid_<?= $srid ?>">
                                                    <input type="hidden" name="product_code" value="EPAYTEST">
                                                    <input type="hidden" name="product_service_charge" value="0">
                                                    <input type="hidden" name="product_delivery_charge" value="0">
                                                    <input type="hidden" name="success_url" value="<?= $base_url . $_SERVER['PHP_SELF'] ?>?payment=success&srid=<?= $srid ?>">
                                                    <input type="hidden" name="failure_url" value="<?= $base_url . $_SERVER['PHP_SELF'] ?>?payment=failed&srid=<?= $srid ?>">
                                                    <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
                                                    <input type="hidden" name="signature" id="signature_<?= $srid ?>">
                                                    <input type="hidden" name="reference_id" value="<?= $srid ?>">
                                                    <button type="submit" class="btn btn-pay primary">
                                                        <i class="fas fa-wallet"></i> Pay with <img src="Esewa_logo.webp" class="esewa-logo">
                                                    </button>
                                                </form>
                                                
                                                <script>
                                                    document.addEventListener('DOMContentLoaded', function() {
                                                        const form = document.getElementById('esewaForm<?= $srid ?>');
                                                        const transactionUuid = 'homeservice_<?= $srid ?>_' + Date.now();
                                                        document.getElementById('transaction_uuid_<?= $srid ?>').value = transactionUuid;
                                                        
                                                        const totalAmount = '<?= $charge ?>';
                                                        const productCode = 'EPAYTEST';
                                                        const secretKey = '8gBm/:&EnhH.1/q';
                                                        
                                                        const stringToSign = `total_amount=${totalAmount},transaction_uuid=${transactionUuid},product_code=${productCode}`;
                                                        
                                                        const signature = CryptoJS.HmacSHA256(stringToSign, secretKey).toString(CryptoJS.enc.Base64);
                                                        
                                                        document.getElementById('signature_<?= $srid ?>').value = signature;
                                                        
                                                        form.addEventListener('submit', function(e) {
                                                            console.log('Payment submission:', {
                                                                reference_id: '<?= $srid ?>',
                                                                transaction_uuid: transactionUuid,
                                                                amount: totalAmount,
                                                                signature: signature
                                                            });
                                                        });
                                                    });
                                                </script>
                                            <?php else: ?>
                                                <button class="btn btn-pay primary" disabled>
                                                    <i class="fas fa-credit-card"></i> Pay Now
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else : ?>
                            <div class="no-bookings">
                                <h3><i class="fas fa-calendar-times" style="margin-right: 10px;"></i>No Active Bookings Found</h3>
                                <p>You don't have any active service requests at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="sidebar">
            <div class="inner">
                <?php include "cmenu.php"; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/browser.min.js"></script>
    <script src="assets/js/breakpoints.min.js"></script>
    <script src="assets/js/util.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    
    <script>
        function openModal(name, service, address, phone, date, time, status, workstatus, payment_status, amount) {
            const modal = document.getElementById('serviceModal');
            const span = document.getElementsByClassName('close')[0];
            
            document.getElementById('modal-provider-name').textContent = name;
            document.getElementById('modal-service-name').textContent = service;
            document.getElementById('modal-address').textContent = address;
            document.getElementById('modal-phone').textContent = phone;
            document.getElementById('modal-date-time').textContent = date + ' at ' + time;
            document.getElementById('modal-amount-to-pay').textContent = amount === 'N/A' ? 'N/A' : 'Rs. ' + amount;
            
            const statusBadge = document.getElementById('modal-status');
            const workStatusBadge = document.getElementById('modal-work-status');
            const paymentStatusBadge = document.getElementById('modal-payment-status');
            
            if (status == 0) {
                statusBadge.className = 'badge badge-warning';
                statusBadge.innerHTML = '<i class="fas fa-clock"></i> Pending';
            } else if (status == 1) {
                statusBadge.className = 'badge badge-success';
                statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Accepted';
            } else {
                statusBadge.className = 'badge badge-danger';
                statusBadge.innerHTML = '<i class="fas fa-times-circle"></i> Rejected';
            }
            
            if (workstatus == 0) {
                workStatusBadge.className = 'badge badge-warning';
                workStatusBadge.innerHTML = '<i class="fas fa-spinner"></i> Pending';
            } else {
                workStatusBadge.className = 'badge badge-success';
                workStatusBadge.innerHTML = '<i class="fas fa-check"></i> Completed';
            }
            
            if (payment_status == 0) {
                paymentStatusBadge.className = 'badge badge-warning';
                paymentStatusBadge.innerHTML = '<i class="fas fa-hourglass-half"></i> Pending';
            } else {
                paymentStatusBadge.className = 'badge badge-success';
                paymentStatusBadge.innerHTML = '<i class="fas fa-receipt"></i> Paid';
            }
            
            modal.style.display = 'block';
            
            span.onclick = function() {
                modal.style.display = 'none';
            }
            
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        }
        
        function openRatingModal(srid, providerId) {
            document.getElementById('rating_srid').value = srid;
            document.getElementById('rating_provider_id').value = providerId;
            document.getElementById('ratingModal').style.display = 'block';
        }
        
        function closeRatingModal() {
            document.getElementById('ratingModal').style.display = 'none';
            resetStars();
        }
        
        function resetStars() {
            const stars = document.querySelectorAll('.rating-stars i');
            const ratingValue = document.getElementById('rating_value');
            const submitBtn = document.getElementById('submitRating');
            
            stars.forEach(star => {
                star.classList.remove('fas');
                star.classList.add('far');
            });
            
            ratingValue.value = '0';
            submitBtn.disabled = true;
        }

        function filterBookings() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const cards = document.querySelectorAll('.booking-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const cardData = card.getAttribute('data-search');
                if (cardData.includes(filter)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });
            
            const noResults = document.getElementById('noResultsMessage');
            if (visibleCount === 0 && cards.length > 0) {
                if (!noResults) {
                    const container = document.getElementById('bookingCardsContainer');
                    const message = document.createElement('div');
                    message.id = 'noResultsMessage';
                    message.className = 'no-bookings';
                    message.innerHTML = '<h3><i class="fas fa-search" style="margin-right: 10px;"></i>No Matching Bookings Found</h3><p>Try different search terms.</p>';
                    container.appendChild(message);
                }
            } else if (noResults) {
                noResults.remove();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.rating-stars i');
            const ratingValue = document.getElementById('rating_value');
            const submitBtn = document.getElementById('submitRating');
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    ratingValue.value = rating;
                    
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.remove('far');
                            s.classList.add('fas');
                        } else {
                            s.classList.remove('fas');
                            s.classList.add('far');
                        }
                    });
                    
                    submitBtn.disabled = false;
                });
                
                star.addEventListener('mouseover', function() {
                    const hoverRating = parseInt(this.getAttribute('data-rating'));
                    stars.forEach((s, index) => {
                        if (index < hoverRating) {
                            s.style.transform = 'scale(1.1)';
                        }
                    });
                });
                
                star.addEventListener('mouseout', function() {
                    const currentRating = parseInt(ratingValue.value);
                    stars.forEach((s, index) => {
                        s.style.transform = 'scale(1)';
                    });
                });
            });
        });
        
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            document.body.classList.add('modal-open');
            modal.style.display = "block";
            modalImg.src = imageSrc;
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            document.body.classList.remove('modal-open');
            modal.style.display = "none";
        }

        document.addEventListener('click', function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeImageModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeImageModal();
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$con->close();
?>