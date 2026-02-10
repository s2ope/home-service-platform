<?php
session_start();
if (!isset($_SESSION["utype"]) || $_SESSION["utype"] != "Admin") {
    header("location:Signin.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno()) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Handle filters
$status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$date_filter = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : null;
$work_payment_filter = isset($_GET['work_payment']) && $_GET['work_payment'] !== '' ? $_GET['work_payment'] : null;

$query = "SELECT 
            sr.srid, sr.req_date, sr.req_time, sr.status, sr.work_status, sr.payment_status, sr.charge,
            c.fname AS cfname, c.mname AS cmname, c.lname AS clname, c.phnno AS cphone, c.photo AS cphoto,
            p.fname AS pfname, p.mname AS pmname, p.lname AS plname, p.phnno AS pphone, p.photo AS pphoto,
            s.sname AS service_name
          FROM service_request sr
          JOIN consumer c ON sr.consumer_id = c.cid
          JOIN provider p ON sr.provider_id = p.pid
          JOIN services s ON sr.service_id = s.sid ";


$where_clauses = [];
$params = [];
$types = '';

if ($status_filter !== null) {
    $where_clauses[] = "sr.status = ?";
    $params[] = $status_filter;
    $types .= 'i';
}

if ($date_filter !== null) {
    $where_clauses[] = "DATE(sr.req_date) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

if ($work_payment_filter !== null) {
    if ($work_payment_filter === 'done_not_paid') {
        $where_clauses[] = "sr.work_status = 1 AND sr.payment_status = 0";
    } elseif ($work_payment_filter === 'done_paid') {
        $where_clauses[] = "sr.work_status = 1 AND sr.payment_status = 1";
    }
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY sr.last_modified DESC";

$stmt = $con->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Bookings | homeservice</title>
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        :root {
            --primary: #4e73df;
            --success: #1cc88a;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --info: #36b9cc;
            --dark: #5a5c69;
            --light: #f8f9fc;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .dashboard-header {
            padding: 20px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .filter-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 20px;
        }
        .filter-group.additional {
            min-width: 250px;
        }
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .filter-group select, 
        .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d3e2;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .bookings-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .booking-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            overflow: hidden;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
        }
        
        .booking-header {
            padding: 15px;
            border-bottom: 1px solid #e3e6f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fc;
        }
        
        .booking-id {
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .booking-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .status-pending {
            background-color: var(--warning);
        }
        
        .status-accepted {
            background-color: var(--success);
        }
        
        .status-rejected {
            background-color: var(--danger);
        }
        
        .status-completed {
            background-color: var(--info);
        }
        
        .status-cancelled {
            background-color: var(--danger);
        }
        
        .status-paid {
            background-color: var(--success);
        }
        
        .status-unpaid {
            background-color: var(--warning);
        }
        
        .booking-body {
            padding: 15px;
            flex: 1;
        }
        
        .user-info {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .user-avatar:hover {
            transform: scale(1.1);
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            margin: 0 0 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-role {
            font-size: 12px;
            color: var(--dark);
            background: #f8f9fc;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-size: 12px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 3px;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .booking-footer {
            padding: 15px;
            border-top: 1px solid #e3e6f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fc;
        }
        
        .status-badges {
            display: flex;
            gap: 10px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-badge i {
            font-size: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .no-bookings {
            text-align: center;
            padding: 40px 20px;
            grid-column: 1 / -1;
        }
        
        .no-bookings i {
            font-size: 50px;
            color: #adb5bd;
            margin-bottom: 15px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
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
        
        @media (max-width: 768px) {
            .bookings-container {
                grid-template-columns: 1fr;
            }
            
            .filter-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .status-badges {
                flex-direction: column;
                gap: 5px;
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
    </style>
</head>
<body>
<div id="wrapper">
        <div id="main">
            <div class="inner">
                <header id="header">
                    <a href="view_arequest.php" class="logo"><strong>Ghar</strong>Sewa </a>
                </header>
                
                <div class="dashboard-header">
                    <h2><i class="fas fa-calendar-check"></i> Service Bookings</h2>
                </div>
                
                <div class="filter-container">
                    <form method="GET" action="">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="status"><i class="fas fa-filter"></i> Filter by Status</label>
                                <select id="status" name="status">
                                    <option value="">All</option>
                                    <option value="0" <?= isset($_GET['status']) && $_GET['status'] === '0' ? 'selected' : '' ?>>Pending</option>
                                    <option value="1" <?= isset($_GET['status']) && $_GET['status'] === '1' ? 'selected' : '' ?>>Accepted</option>
                                    <option value="2" <?= isset($_GET['status']) && $_GET['status'] === '2' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="filter-group additional">
                                <label for="work_payment"><i class="fas fa-tasks"></i> Work & Payment</label>
                                <select id="work_payment" name="work_payment">
                                    <option value="">All</option>
                                    <option value="done_not_paid" <?= isset($_GET['work_payment']) && $_GET['work_payment'] === 'done_not_paid' ? 'selected' : '' ?>>Work Done & Not Paid</option>
                                    <option value="done_paid" <?= isset($_GET['work_payment']) && $_GET['work_payment'] === 'done_paid' ? 'selected' : '' ?>>Work Done & Paid</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="date"><i class="fas fa-calendar-day"></i> Filter by Date</label>
                                <input type="date" id="date" name="date" value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '' ?>">
                            </div>
                            
                           
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary button primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="admin_bookings.php" class="btn button primary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="bookings-container">
                    <?php if ($result->num_rows > 0) : ?>
                        <?php while ($row = $result->fetch_assoc()) : ?>
                            <?php
                            // Determine status
                            $status_class = '';
                            $status_icon = '';
                            $status_text = '';
                            
                            switch ($row['status']) {
                                case 0:
                                    $status_class = 'status-pending';
                                    $status_icon = 'far fa-hourglass';
                                    $status_text = 'Pending';
                                    break;
                                case 1:
                                    $status_class = 'status-accepted';
                                    $status_icon = 'fas fa-check-circle';
                                    $status_text = 'Accepted';
                                    break;
                                case 2:
                                    $status_class = 'status-rejected';
                                    $status_icon = 'fas fa-times-circle';
                                    $status_text = 'Rejected';
                                    break;
                            }
                            
                            // Determine work status
                            if ($row['status'] == 2) {
                                $work_status_class = 'status-cancelled';
                                $work_status_icon = 'fas fa-ban';
                                $work_status_text = 'Cancelled';
                            } else {
                                $work_status_class = $row['work_status'] == 1 ? 'status-completed' : 'status-pending';
                                $work_status_icon = $row['work_status'] == 1 ? 'fas fa-check' : 'far fa-hourglass';
                                $work_status_text = $row['work_status'] == 1 ? 'Completed' : 'Pending';
                            }
                            
                            // Determine payment status
                            if ($row['status'] == 2) {
                                $payment_status_class = 'status-cancelled';
                                $payment_status_icon = 'fas fa-ban';
                                $payment_status_text = 'Cancelled';
                            } else {
                                $payment_status_class = $row['payment_status'] == 1 ? 'status-paid' : 'status-unpaid';
                                $payment_status_icon = $row['payment_status'] == 1 ? 'fas fa-check' : 'far fa-hourglass';
                                $payment_status_text = $row['payment_status'] == 1 ? 'Paid' : 'Pending';
                            }
                            
                            $formatted_date = date('M j, Y', strtotime($row['req_date']));
                            $formatted_time = $row['req_time'];
                            $formatted_charge = 'Rs. ' . number_format($row['charge'], 2);
                            ?>
                            
                            <div class="booking-card">
                                <div class="booking-header">
                                    <span class="booking-id">Booking #<?= $row['srid'] ?></span>
                                    <span class="booking-status <?= $status_class ?>">
                                        <i class="<?= $status_icon ?>"></i> <?= $status_text ?>
                                    </span>
                                </div>
                                
                                <div class="booking-body">
                                    <div class="user-info">
                                        <img src="<?= $row['cphoto'] ? 'uploads/' . $row['cphoto'] : 'https://via.placeholder.com/60?text=C' ?>" 
                                             alt="Consumer" class="user-avatar" 
                                             onclick="openModal('<?= $row['cphoto'] ? 'uploads/' . $row['cphoto'] : 'https://via.placeholder.com/150?text=C' ?>')">
                                        <div class="user-details">
                                            <h3 class="user-name">
                                                <i class="fas fa-user"></i> 
                                                <?= $row['cfname'] . ' ' . $row['cmname'] . ' ' . $row['clname'] ?>
                                            </h3>
                                            <span class="user-role">
                                                <i class="fas fa-user-tag"></i> Consumer
                                            </span>
                                            <div style="margin-top: 5px;">
                                                <i class="fas fa-phone"></i> <?= $row['cphone'] ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="user-info">
                                        <img src="<?= $row['pphoto'] ? 'uploads/' . $row['pphoto'] : 'https://via.placeholder.com/60?text=P' ?>" 
                                             alt="Provider" class="user-avatar" 
                                             onclick="openModal('<?= $row['pphoto'] ? 'uploads/' . $row['pphoto'] : 'https://via.placeholder.com/150?text=P' ?>')">
                                        <div class="user-details">
                                            <h3 class="user-name">
                                                <i class="fas fa-user-tie"></i> 
                                                <?= $row['pfname'] . ' ' . $row['pmname'] . ' ' . $row['plname'] ?>
                                            </h3>
                                            <span class="user-role">
                                                <i class="fas fa-briefcase"></i> Service Provider
                                            </span>
                                            <div style="margin-top: 5px;">
                                                <i class="fas fa-phone"></i> <?= $row['pphone'] ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="booking-details">
                                        <div class="detail-item">
                                            <span class="detail-label">
                                                <i class="fas fa-concierge-bell"></i> Service
                                            </span>
                                            <span class="detail-value"><?= $row['service_name'] ?></span>
                                        </div>
                                        
                                        <div class="detail-item">
                                            <span class="detail-label">
                                                <i class="fas fa-calendar-alt"></i> Date
                                            </span>
                                            <span class="detail-value"><?= $formatted_date ?></span>
                                        </div>
                                        
                                        <div class="detail-item">
                                            <span class="detail-label">
                                                <i class="fas fa-clock"></i> Time
                                            </span>
                                            <span class="detail-value"><?= $formatted_time ?></span>
                                        </div>
                                        
                                        <div class="detail-item">
                                            <span class="detail-label">
                                                <i class="fas fa-money-bill-wave"></i> Charge
                                            </span>
                                            <span class="detail-value"><?= $formatted_charge ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="booking-footer">
                                    <div class="status-badges">
                                        <span class="status-badge <?= $work_status_class ?> " style="color:white;">
                                            <i class="<?= $work_status_icon ?>"></i> <?= $work_status_text ?>
                                        </span>
                                        <span class="status-badge <?= $payment_status_class ?>"style="color:white;">
                                            <i class="<?= $payment_status_icon ?>"></i> <?= $payment_status_text ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div class="no-bookings">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Bookings Found</h3>
                            <p>There are no bookings matching your criteria</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="sidebar">
            <div class="inner">
                <?php include "amenu.php"; ?>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
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

        function viewDetails(bookingId) {
            // Implement view details functionality
            alert('View details for booking #' + bookingId);
        }

        function updateStatus(bookingId, action) {
            // Implement status update functionality
            if (confirm('Are you sure you want to ' + action + ' this booking?')) {
                alert('Booking #' + bookingId + ' has been ' + action + 'ed');
                // In a real implementation, you would make an AJAX call here
                window.location.reload();
            }
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$con->close();
?>