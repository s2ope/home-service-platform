<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if(!isset($_SESSION["provider_utype"]) || $_SESSION["provider_utype"] != "Provider") {
    header("location:Signin.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "gharsewa");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

$msg = isset($_SESSION['verification_msg']) ? $_SESSION['verification_msg'] : '';
unset($_SESSION['verification_msg']);

// Handle verification request submission
if (isset($_POST['sbbtn'])) {
    $service = $_POST['service'];
    $specification = $_POST['sspec'];
    $estd_date = $_POST['bdate'];
    $provider_id = $_SESSION['provider_id'];

    $check_query = "SELECT * FROM verification_request WHERE provider_id = ? AND service_id = ? AND status IN (0,1)";
    $check_stmt = $con->prepare($check_query);
    $check_stmt->bind_param("ii", $provider_id, $service);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['verification_msg'] = "You already have an active verification request for this service.";
    } else {
        if (isset($_FILES['photo1']) && $_FILES['photo1']['error'] == 0) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES['photo1']['name']);
            move_uploaded_file($_FILES['photo1']['tmp_name'], $target_file);

            // Start transaction
            $con->begin_transaction();
            
            try {
                // Insert verification request
                $query = "INSERT INTO verification_request (service_id, provider_id, specification, estd_date, certificate_pic, status, read_status, read_status_a, msg, msga) 
                          VALUES (?, ?, ?, ?, ?, 0, 0, 0, 'New verification request submitted', 'You have a new verification request')";
                $stmt = $con->prepare($query);
                $stmt->bind_param("iisss", $service, $provider_id, $specification, $estd_date, $target_file);
                $stmt->execute();
                
                $con->commit();
                $_SESSION['verification_msg'] = "Verification Request Submitted Successfully!";
            } catch (Exception $e) {
                $con->rollback();
                $_SESSION['verification_msg'] = "Error: " . $e->getMessage();
            }
        } else {
            $_SESSION['verification_msg'] = "Please upload a valid certificate image.";
        }
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Handle delete/cancel request
if (isset($_GET['btn'])) {
    $sid = $_GET['sid'];
    $provider_id = $_SESSION['provider_id'];
    
    if ($_GET['btn'] == 'del') {
        // Delete pending request
        $query = "DELETE FROM verification_request WHERE service_id = ? AND provider_id = ? AND status = 0";
        $stmt = $con->prepare($query);
        $stmt->bind_param("ii", $sid, $provider_id);
        if ($stmt->execute()) {
            $_SESSION['verification_msg'] = "Request Deleted Successfully!";
        } else {
            $_SESSION['verification_msg'] = "Error: " . $stmt->error;
        }
    } elseif ($_GET['btn'] == 'cancel') {
        // Request cancellation of verified service
        $query = "UPDATE verification_request SET status = 1, read_status = 0, read_status_a = 0, 
                  msg = 'Cancellation requested', msga = 'You have a new cancellation request' 
                  WHERE service_id = ? AND provider_id = ? AND status = 1";
        $stmt = $con->prepare($query);
        $stmt->bind_param("ii", $sid, $provider_id);
        if ($stmt->execute()) {
            $_SESSION['verification_msg'] = "Cancellation Request Submitted Successfully!";
        } else {
            $_SESSION['verification_msg'] = "Error: " . $stmt->error;
        }
    }
    
    header("Location: Mservices.php");
    exit();
}

// Handle mark as read request
if (isset($_GET['mark_read'])) {
    $sid = $_GET['sid'];
    $provider_id = $_SESSION['provider_id'];

    $query = "UPDATE verification_request SET read_status = 1 WHERE service_id = ? AND provider_id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $sid, $provider_id);
    if ($stmt->execute()) {
        $_SESSION['verification_msg'] = "Marked as read successfully!";
    } else {
        $_SESSION['verification_msg'] = "Error: " . $stmt->error;
    }

    header("Location: Mservices.php");
    exit();
}

// Fetch verification requests for display
$id = $_SESSION['provider_id'];
$query1 = "SELECT p.fname, p.mname, p.lname, s.sname, v.specification, v.estd_date, 
          v.status, v.last_modified, s.sid, v.read_status, v.msg 
          FROM provider p 
          JOIN verification_request v ON p.pid = v.provider_id 
          JOIN services s ON v.service_id = s.sid 
          WHERE v.provider_id = ? AND (v.status IN (0,1,2) OR (v.status = 3 AND v.read_status = 0))
          ORDER BY v.last_modified DESC";

$stmt = $con->prepare($query1);
$stmt->bind_param("i", $id);
$stmt->execute();
$verification_result = $stmt->get_result(); // Changed variable name here
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Online Household Service Portal</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        .request-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .request-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 20px;
            position: relative;
            transition: all 0.3s ease;
            border-left: 5px solid #ddd;
            overflow: hidden;
        }
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }
        .request-card.pending {
            border-left-color: #FFC107;
        }
        .request-card.verified {
            border-left-color: #28A745;
        }
        .request-card.rejected {
            border-left-color: #DC3545;
        }
        .request-card.cancelled {
            border-left-color: #6c757d;
        }
        .request-card h3 {
            color: #2C3E50;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .request-detail {
            margin-bottom: 8px;
            display: flex;
        }
        .request-label {
            font-weight: 600;
            color: #495057;
            min-width: 120px;
        }
        .request-value {
            color: #6C757D;
            flex: 1;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        .badge-pending {
            background-color: #FFC107;
        }
        .badge-verified {
            background-color: #28A745;
        }
        .badge-rejected {
            background-color: #DC3545;
        }
        .badge-cancelled {
            background-color: #6c757d;
        }
        .action-btn {
            position: absolute;
            bottom: 15px;
            right: 15px;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            color: white;
            border: none;
            cursor: pointer;
        }
        .cancel-btn {
            background-color: #E74C3C;
        }
        .cancel-btn:hover {
            background-color: #C0392B;
            transform: translateY(-2px);
        }
        .request-btn {
            background-color: #3498DB;
        }
        .request-btn:hover {
            background-color: #2980B9;
            transform: translateY(-2px);
        }
        .no-requests {
            text-align: center;
            padding: 30px;
            background: #F8F9FA;
            border-radius: 8px;
            grid-column: 1 / -1;
        }
        .no-requests i {
            font-size: 50px;
            color: #CED4DA;
            margin-bottom: 15px;
        }
        .no-requests h3 {
            color: #6C757D;
            margin-bottom: 10px;
        }
        
        /* Notification styles */
        .card-notification {
            padding: 10px 15px;
            margin: -20px -20px 15px -20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .mark-read-btn {
            padding: 4px 10px;
            background-color: #6C757D;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .mark-read-btn:hover {
            background-color: #5A6268;
            transform: translateY(-1px);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 767px) {
            .request-container {
                grid-template-columns: 1fr;
            }
            .card-notification {
                flex-direction: column;
                align-items: flex-start;
            }
            .mark-read-btn {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <div id="main">
            <div class="inner">
                <header id="header">
                    <a href="view_request.php" class="logo"><strong>Ghar Sewa</strong></a>
                </header>
            
                    <h2>Verify Services</h2>
                    <hr class="minor"/>
                    <h4>You won't be able to receive any requests from consumers unless you verify the services that you provide.</h4>
                    <hr class="minor" />
                    <?php if (!empty($msg)): ?>
        <h3 style="color: <?php echo (stripos($msg, 'success') !== false ? 'green' : '#721C24'); ?>;">
            <?php echo htmlspecialchars($msg); ?>
        </h3>
    <?php endif; ?>
                    
                    <form method="post" action="Mservices.php" enctype="multipart/form-data">
                        <label>Service:</label>
                        <select name="service" required style="width:40%;">
                            <option value="">- Select Service -</option>
                            <?php
                            $service_query = "SELECT sid, sname FROM services";
                            $service_result = $con->query($service_query);
                            while ($service_row = $service_result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($service_row['sid']) . "'>" . htmlspecialchars($service_row['sname']) . "</option>";
                            }
                            ?>
                        </select>
                        <label>Service Specification:</label>
                        <input type="text" name="sspec" required style="width:40%;" />
                        <label>Estd. Date:</label>
                        <input type="date" name="bdate" style="width:40%;" required max="<?php echo date('Y-m-d'); ?>" >
                        <label>Certificate/Licence Image:</label>
                        <input type="file" name="photo1" style="width:40%;" required />
                        <br>
                        <input type="submit" name="sbbtn" value="Request Verification" class="primary" />
                        <input type="reset" value="Reset" />
                    </form>
                    <hr />
                    
                    <div class="request-container">
                        <?php
                        if ($verification_result->num_rows > 0) {
                            while ($res = $verification_result->fetch_assoc()) {
                                $card_class = '';
                                $status_badge = '';
                                
                                if ($res['status'] == 0) {
                                    $card_class = 'pending';
                                    $status_badge = '<span class="status-badge badge-pending"><i class="fas fa-clock"></i> Pending</span>';
                                } elseif ($res['status'] == 1) {
                                    $card_class = 'verified';
                                    $status_badge = '<span class="status-badge badge-verified"><i class="fas fa-check-circle"></i> Verified</span>';
                                } elseif ($res['status'] == 2) {
                                    $card_class = 'rejected';
                                    $status_badge = '<span class="status-badge badge-rejected"><i class="fas fa-times-circle"></i> Rejected</span>';
                                } else {
                                    $card_class = 'cancelled';
                                    $status_badge = '<span class="status-badge badge-cancelled"><i class="fas fa-ban"></i> Cancelled</span>';
                                }
                                
                                $formatted_date = date('M j, Y', strtotime($res['estd_date']));
                        ?>
                        <div class="request-card <?php echo $card_class; ?>">
                            <?php if ($res['read_status'] == 0 && !empty($res['msg'])): ?>
                                <div class="card-notification">
                                    <i class="fas fa-bell"></i>
                                    <span><?php echo htmlspecialchars($res['msg']); ?></span>
                                    <a href="Mservices.php?mark_read=1&sid=<?php echo $res['sid']; ?>" class="mark-read-btn">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <h3><?php echo htmlspecialchars($res['sname']); ?></h3>
                            
                            <div class="request-detail">
                                <span class="request-label"><i class="fas fa-user"></i> Provider:</span>
                                <span class="request-value"><?php echo htmlspecialchars($res['fname'] . " " . $res['mname'] . " " . $res['lname']); ?></span>
                            </div>
                            
                            <div class="request-detail">
                                <span class="request-label"><i class="fas fa-info-circle"></i> Spec:</span>
                                <span class="request-value"><?php echo htmlspecialchars($res['specification']); ?></span>
                            </div>
                            
                            <div class="request-detail">
                                <span class="request-label"><i class="fas fa-calendar"></i> Estd. Date:</span>
                                <span class="request-value"><?php echo $formatted_date; ?></span>
                            </div>
                            
                            <div class="request-detail">
                                <span class="request-label"><i class="fas fa-tag"></i> Status:</span>
                                <span class="request-value"><?php echo $status_badge; ?></span>
                            </div>
                            
                            <?php if ($res['status'] == 0) { ?>
                                <a href="Mservices.php?btn=del&sid=<?php echo $res['sid']; ?>" class="action-btn cancel-btn">
                                    <i class="fas fa-trash-alt"></i> Cancel
                                </a>
                            <?php } elseif ($res['status'] == 1) { ?>
                                <a href="Mservices.php?btn=cancel&sid=<?php echo $res['sid']; ?>" class="action-btn request-btn">
                                    <i class="fas fa-ban"></i> Request Cancellation
                                </a>
                            <?php } ?>
                        </div>
                        <?php 
                            } 
                        } else { 
                        ?>
                        <div class="no-requests">
                            <i class="fas fa-inbox"></i>
                            <h3>No Verification Requests</h3>
                            <p>You haven't submitted any verification requests yet.</p>
                        </div>
                        <?php } ?>
                    </div>
                </section>
            </div>
        </div>
        <div id="sidebar">
            <div class="inner">
                <?php include "pmenu.php"; ?>
            </div>
        </div>
    </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/browser.min.js"></script>
    <script src="assets/js/breakpoints.min.js"></script>
    <script src="assets/js/util.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>