<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["utype"]) || $_SESSION["utype"] !== "Admin") {
    header("Location: Signin.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

// Handle mark as read
if (isset($_GET['mark_read']) && isset($_GET['vrid'])) {
    $vrid = $_GET['vrid'];
    $query = "UPDATE verification_request SET read_status_a = 1 WHERE vrid = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $vrid);
    $stmt->execute();
    header("Location: view_arequest.php");
    exit();
}

// Handle Accept/Reject/Cancel actions
if (isset($_POST["btn"]) && isset($_POST["vrid"])) {
    $action = $_POST["btn"];
    $vrid = $_POST["vrid"];
    
    if ($action == "Accept") {
        $status = 1;
        $notification_msg = "Your verification request has been accepted";
    } elseif ($action == "Reject") {
        $status = 2;
        $notification_msg = "Your verification request has been rejected";
    } elseif ($action == "Cancel") {
        $status = 3;
        $notification_msg = "Your verification has been cancelled";
    }
    
    $get_provider_query = "SELECT provider_id FROM verification_request WHERE vrid = ?";
    $get_stmt = $con->prepare($get_provider_query);
    $get_stmt->bind_param("i", $vrid);
    $get_stmt->execute();
    $provider_result = $get_stmt->get_result();
    $provider_row = $provider_result->fetch_assoc();
    $provider_id = $provider_row['provider_id'];
    
    $query = "UPDATE verification_request SET status = ?, last_modified = NOW(), read_status_a = 1, 
              read_status = 0, msg = ? WHERE vrid = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("isi", $status, $notification_msg, $vrid);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        if ($action == "Cancel"){
           $_SESSION['verification_message'] = "Verification cancellation process completed!";
        } else {
           $_SESSION['verification_message'] = "Request " . strtolower($action) . "ed successfully!";
        }
   } else {
       $_SESSION['verification_message'] = "Failed to update request!";
   }
    
    header("Location: view_arequest.php");
    exit();
}

// Handle filter application
if (isset($_POST['sbbtn']) && isset($_POST['rp1'])) {
    $status_filter = $_POST['rp1'];
    $_SESSION['verification_filter'] = $status_filter;
    header("Location: view_arequest.php");
    exit();
} elseif (isset($_GET['clear_filters'])) {
    unset($_SESSION['verification_filter']);
    header("Location: view_arequest.php");
    exit();
} elseif (isset($_SESSION['verification_filter'])) {
    $status_filter = $_SESSION['verification_filter'];
} else {
    $status_filter = '';
}

$query = "SELECT p.fname, p.mname, p.lname, p.phnno, p.country, p.state, p.city, p.address,
            p.photo, p.average_rating,
            vr.specification, vr.estd_date, vr.certificate_pic, vr.status, vr.vrid, vr.read_status_a, 
            vr.msga, vr.last_modified, vr.provider_id
            FROM provider p
            JOIN verification_request vr ON p.pid = vr.provider_id
            JOIN services s ON vr.service_id = s.sid";

if ($status_filter !== '') {
    $query .= " WHERE vr.status = ?";
    $query .= " ORDER BY vr.last_modified DESC";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $status_filter);
} else {
    $query .= " ORDER BY vr.last_modified DESC";
    $stmt = $con->prepare($query);
}

if (!$stmt) {
    die("Prepare failed: " . $con->error);
}
$stmt->execute();
$result = $stmt->get_result();

$msg = isset($_SESSION['verification_message']) ? $_SESSION['verification_message'] : '';
unset($_SESSION['verification_message']);

$current_filter = $status_filter;
?>

<!DOCTYPE HTML>
<html>

<head>
    <title>Verification Requests | homeservice</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
    :root {
        --primary-color: #4e73df;
        --success-color: #1cc88a;
        --danger-color: #e74a3b;
        --warning-color: #f6c23e;
        --info-color: #36b9cc;
        --dark-color: #5a5c69;
        --light-color: #f8f9fc;
    }
    
    body {
        background-color: #f8f9fc;
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }
    
    .filter-form {
        background-color: white;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }
    
    .search-bar {
        background-color: white;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        display: flex;
        gap: 10px;
    }
    
    .search-input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .search-button {
        padding: 10px 20px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .radio-group {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
        background-color: white;
    }
    
    .radio-option {
        display: flex;
        align-items: center;
        gap: 5px;
        background-color: white;
    }
    
    .radio-option input[type="radio"] {
        margin: 0;
        background-color: white;
    }
    
    .requests-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin: 20px 0;
    }

    .request-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        padding: 15px;
        position: relative;
        transition: all 0.2s ease;
        width: 100%;
        border-left: 4px solid transparent;
    }
    
    .request-card:hover {
        transform: translateY(-3px);
        transform:scale(1.05);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .status-pending {
        background-color: var(--warning-color);
    }

    .status-accepted {
        background-color: var(--success-color);
    }

    .status-rejected, .status-cancelled {
        background-color: var(--danger-color);
    }

    .card-notification {
        padding: 10px 15px;
        margin: -15px -15px 15px -15px;
        border-radius: 10px 10px 0 0;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: fadeIn 0.5s ease-out;
        background-color: #fff3cd;
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
        white-space: nowrap;
        border: none;
        cursor: pointer;
        height: 28px;
        line-height: 1;
        text-decoration: none;
        outline: none;
    }

    .notification-btn.accept {
        background-color: var(--success-color);
        color: white;
    }

    .notification-btn.reject {
        background-color: var(--danger-color);
        color: white;
    }

    .notification-btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: none;
    }
    
    .notification-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card-header {
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e3e6f0;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .card-header h3 {
        margin: 0;
        color: var(--dark-color);
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--primary-color);
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .user-avatar:hover {
        transform: scale(1.1);
    }

    .card-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        font-size: 13px;
    }

    .detail-row {
        display: flex;
        margin-bottom: 8px;
    }

    .detail-label {
        font-weight: 600;
        color: #4a4a4a;
        min-width: 90px;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .detail-value {
        color: #6c757d;
        flex: 1;
        word-break: break-word;
        font-size: 12px;
    }

    /* Star rating styles */
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

    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .button {
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        text-decoration: none;
        height: 28px;
        line-height: 1;
    }
    
    .button.view {
        background: var(--info-color);
        color: white;
    }

    form {
        margin: 0;
        padding: 0;
        display: inline;
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

    .no-requests {
        text-align: center;
        padding: 30px 20px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 2px dashed #dde2e6;
    }

    .no-requests i {
        font-size: 50px;
        color: #adb5bd;
        margin-bottom: 10px;
    }

    .no-requests h3 {
        color: #6c757d;
        margin-bottom: 8px;
        font-size: 18px;
    }

    .no-results-search {
        text-align: center;
        padding: 30px 20px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 2px dashed #dde2e6;
        grid-column: 1 / -1;
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

    @media (max-width: 768px) {
        .card-content {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            justify-content: flex-start;
        }
        
        .card-notification {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .notification-actions {
            align-self: flex-end;
            width: 100%;
        }
        
        .search-bar {
            flex-direction: column;
        }
        
        .search-button {
            width: 100%;
        }

        .close-image-modal {
            right: 15px;
            font-size: 30px;
        }
    }

    @media (max-width: 480px) {
        .detail-row {
            flex-direction: column;
            gap: 2px;
        }

        .detail-label {
            min-width: auto;
        }
        
        .button, 
        .notification-btn {
            width: 100%;
            justify-content: center;
            border:none;
        }
        
        .notification-actions {
            flex-direction: column;
            gap: 5px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
    </style>
</head>

<body class="is-preload">

    <div id="wrapper">
        <div id="main">
            <div class="inner">
                <header id="header">
                    <a href="view_arequest.php" class="logo"><strong>home</strong>service</a>
                </header>

                <header class="main">
                    <h2><i class="fas fa-user-check"></i> Verification/Cancellation Requests</h2>
                </header>

                <?php if (!empty($msg)) : ?>
                    <div class="alert-message">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="view_arequest.php" class="filter-form">
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="all" name="rp1" value="" <?= $current_filter === '' ? 'checked' : '' ?>>
                            <label for="all" style="display: inline; font-weight: normal;"><i class="fas fa-list"></i> All Requests</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="rp1" name="rp1" value="1" <?= $current_filter === '1' ? 'checked' : '' ?>>
                            <label for="rp1" style="display: inline; font-weight: normal;"> Accepted</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="rp2" name="rp1" value="2" <?= $current_filter === '2' ? 'checked' : '' ?>>
                            <label for="rp2" style="display: inline; font-weight: normal;"> Rejected</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="rp3" name="rp1" value="0" <?= $current_filter === '0' ? 'checked' : '' ?>>
                            <label for="rp3" style="display: inline; font-weight: normal;"> Pending</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="rp4" name="rp1" value="3" <?= $current_filter === '3' ? 'checked' : '' ?>>
                            <label for="rp4" style="display: inline; font-weight: normal;"> Cancelled</label>
                        </div>
                    </div>
                    <button type="submit" name="sbbtn" class="button primary"><i class="fas fa-filter"></i> Apply Filters</button>
                </form>
                
                <div class="search-bar">
                    <input type="text" class="search-input" id="globalSearch" placeholder="Search by name, phone, location, service...">
                    <button class="search-button primary" id="searchBtn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                
                <div class="requests-container" id="requestsContainer">
                    <?php if ($result->num_rows > 0) : ?>
                        <?php while ($row = $result->fetch_assoc()) : ?>
                            <div class="request-card">
                                <?php if ($row['read_status_a'] == 0 && !empty($row['msga'])) : ?>
                                    <div class="card-notification">
                                        <i class="fas fa-bell"></i>
                                        <span><?php echo htmlspecialchars($row['msga']); ?></span>
                                        <div class="notification-actions">
                                            <?php if ($row['status'] == 0) : ?>
                                                <form method="post" action="view_arequest.php">
                                                    <input type="hidden" name="vrid" value="<?php echo $row['vrid']; ?>">
                                                    <button type="submit" name="btn" value="Accept" class="button primary notification-btn accept">
                                                        <i class="fas fa-check"></i> Accept
                                                    </button>
                                                </form>
                                                <form method="post" action="view_arequest.php">
                                                    <input type="hidden" name="vrid" value="<?php echo $row['vrid']; ?>">
                                                    <button type="submit" name="btn" value="Reject" class="button primary notification-btn reject">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php elseif ($row['status'] == 1) : ?>
                                                <form method="post" action="view_arequest.php">
                                                    <input type="hidden" name="vrid" value="<?php echo $row['vrid']; ?>">
                                                    <button type="submit" name="btn" value="Cancel" class="button primary notification-btn">
                                                        <i class="fas fa-ban"></i> Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="card-header">
                                    <img src="<?php echo htmlspecialchars($row['photo'] ? 'uploads/' . $row['photo'] : 'https://via.placeholder.com/60'); ?>" 
                                         alt="User Avatar" class="user-avatar" 
                                         onclick="openImageModal('<?php echo htmlspecialchars($row['photo'] ? 'uploads/' . $row['photo'] : 'https://via.placeholder.com/150'); ?>')">
                                    <div>
                                        <h3>
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars(strtoupper($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname'])); ?>
                                        </h3>
                                        <?php if ($row['average_rating'] !== null) : ?>
                                            <div class="star-rating">
                                                <?php
                                                $rating = $row['average_rating'];
                                                $fullStars = floor($rating);
                                                $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                                
                                                // Full stars
                                                for ($i = 0; $i < $fullStars; $i++) {
                                                    echo '<i class="fas fa-star"></i>';
                                                }
                                                
                                                // Half star
                                                if ($hasHalfStar) {
                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                }
                                                
                                                // Empty stars
                                                for ($i = $fullStars + ($hasHalfStar ? 1 : 0); $i < 5; $i++) {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                                ?>
                                                <span class="rating-value">(<?php echo number_format($rating, 1); ?>)</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card-content">
                                    <div class="detail-row">
                                        <span class="detail-label"><i class="fas fa-tag"></i> Status:</span>
                                        <span class="detail-value">
                                            <?php if ($row['status'] == 0) : ?>
                                                <span class="status-badge status-pending"><i class="fas fa-clock"></i> Pending</span>
                                            <?php elseif ($row['status'] == 1) : ?>
                                                <span class="status-badge status-accepted"><i class="fas fa-check-circle"></i> Verified</span>
                                            <?php elseif ($row['status'] == 2) : ?>
                                                <span class="status-badge status-rejected"><i class="fas fa-times-circle"></i> Rejected</span>
                                            <?php else : ?>
                                                <span class="status-badge status-cancelled"><i class="fas fa-ban"></i> Cancelled</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <div class="detail-row">
                                        <span class="detail-label"><i class="fas fa-phone"></i> Phone:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($row['phnno']); ?></span>
                                    </div>

                                    <div class="detail-row">
                                        <span class="detail-label"><i class="fas fa-map-marker-alt"></i> Location:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($row['address'] . ', '.$row['city'] . ', ' . $row['country']); ?></span>
                                    </div>

                                    <div class="detail-row">
                                        <span class="detail-label"><i class="fas fa-tools"></i> Service:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($row['specification']); ?></span>
                                    </div>

                                    <div class="detail-row">
                                        <span class="detail-label"><i class="fas fa-calendar-alt"></i> Since:</span>
                                        <span class="detail-value"><?php echo date('M d Y', strtotime($row['estd_date'])); ?></span>
                                    </div>

                                    <div class="detail-row">
                                        <span class="detail-label"><i class="fas fa-clock"></i> Last Modified:</span>
                                        <span class="detail-value"><?php echo date('M j, Y H:i', strtotime($row['last_modified'])); ?></span>
                                    </div>
                                </div>

                                <div class="action-buttons">
                                    <a href="<?php echo htmlspecialchars($row['certificate_pic']); ?>"
                                        target="_blank" class="button view button primary">
                                        <i class="fas fa-eye"></i> View Certificate
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div class="no-requests">
                            <i class="fas fa-inbox"></i>
                            <h3>No Requests Found</h3>
                            <p>There are no verification requests to display with the current filters</p>
                        </div>
                    <?php endif; ?>
                </div>
                </section>
            </div>
        </div>

        <div id="sidebar">
            <div class="inner">
                <?php include "amenu.php"; ?>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <span class="close-image-modal" onclick="closeImageModal()">&times;</span>
        <div class="image-modal-content">
            <img id="modalImage" class="modal-image">
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/browser.min.js"></script>
    <script src="assets/js/breakpoints.min.js"></script>
    <script src="assets/js/util.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('globalSearch');
            const searchBtn = document.getElementById('searchBtn');
            const requestCards = document.querySelectorAll('.request-card');
            
            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase();
                let hasResults = false;
                let visibleCards = 0;
                
                requestCards.forEach(card => {
                    const cardText = card.textContent.toLowerCase();
                    if (cardText.includes(searchTerm)) {
                        card.style.display = 'block';
                        hasResults = true;
                        visibleCards++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show no results message if needed
                const noResultsElement = document.querySelector('.no-results-search');
                const noRequestsElement = document.querySelector('.no-requests');
                
                if (!hasResults && searchTerm.length > 0) {
                    if (noRequestsElement) {
                        noRequestsElement.style.display = 'none';
                    }
                    if (!noResultsElement) {
                        const noResults = document.createElement('div');
                        noResults.className = 'no-requests no-results-search';
                        noResults.innerHTML = `
                            <i class="fas fa-search"></i>
                            <h3>No Matching Requests Found</h3>
                            <p>Try a different search term</p>
                        `;
                        document.getElementById('requestsContainer').appendChild(noResults);
                    }
                } else {
                    if (noResultsElement) {
                        noResultsElement.remove();
                    }
                    if (noRequestsElement && visibleCards > 0) {
                        noRequestsElement.style.display = 'none';
                    } else if (noRequestsElement) {
                        noRequestsElement.style.display = 'block';
                    }
                }
            }
            
            // Search on button click
            searchBtn.addEventListener('click', performSearch);
            
            // Search on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            // Clear search when filter form is submitted
            document.querySelector('.filter-form').addEventListener('submit', function() {
                searchInput.value = '';
            });
        });

        // Image modal functions
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

        // Close modal when clicking outside of image
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeImageModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeImageModal();
            }
        });
    </script>
</body>
</html>