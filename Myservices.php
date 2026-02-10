<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
// session_name('consumer_session');

session_start();
if (!isset($_SESSION["consumer_utype"]) || $_SESSION["consumer_utype"] != "Consumer") {
    header("Location: Signin.php");
    exit();
}

// PRG Pattern Implementation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["sbbtn"])) {
    $_SESSION['form_data'] = $_POST;
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

$filterMessage = '';
if (!empty($formData)) {
    $filterMessage = '<h3 style="color: green;">Filters applied successfully!</h3>';
}

$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

$id = $_SESSION["consumer_id"];

$query = "SELECT provider.fname, provider.mname, provider.lname, provider.photo, provider.average_rating,
          provider.address, provider.city, provider.state, provider.country, provider.phnno,
          service_request.req_date AS fdate, service_request.req_time,
          service_request.status AS status, service_request.work_status,
          service_request.payment_status, service_request.srid, services.sname AS service_name,
          service_request.charge AS charge
          FROM service_request
          JOIN provider ON provider.pid = service_request.provider_id
          JOIN services ON services.sid = service_request.service_id
          WHERE service_request.consumer_id = ?";

$params = [$id];
$param_types = "s";

if (!empty($formData)) {
    $frdate = $formData["fdate"] ?? '';
    $todate = $formData["tdate"] ?? '';
    $sts = $formData["rp1"] ?? '';
    
    // Add date filter only if both dates are provided
    if (!empty($frdate) && !empty($todate)) {
        $query .= " AND service_request.req_date BETWEEN ? AND ?";
        $params[] = $frdate;
        $params[] = $todate;
        $param_types .= "ss";
    }
    
    // Add status filter if provided
    if ($sts !== "") {
        $query .= " AND service_request.status = ?";
        $params[] = $sts;
        $param_types .= "s";
    }
}

$query .= " ORDER BY service_request.last_modified DESC";

$stmt = $con->prepare($query);

// Bind parameters dynamically
if (count($params) > 1) {
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param($param_types, $id);
}

$stmt->execute();
$stmt->store_result();
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Online Household Service Portal</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        .service-request-card {
            width: 100%;
            max-width: 800px;
            margin: 0 auto 15px;
            padding: 15px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            border-top: 4px solid #ddd;
        }

        .service-request-card:hover {
            transform: translateY(-3px);
            transform:scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .status-pending { border-top-color: #ffc107; }
        .status-accepted { border-top-color: #28a745; }
        .status-rejected { border-top-color: #dc3545; }

        /* Provider Avatar Styles */
        .provider-avatar-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .provider-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #4e73df;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .provider-avatar:hover {
            transform: scale(1.1);
        }

        .provider-info {
            flex: 1;
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

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }

        .card-header h3 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }

        .card-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            font-size: 13px;
        }

        .detail-item {
            margin-bottom: 5px;
        }

        .detail-item span.label {
            font-weight: bold;
            color: #666;
            display: block;
            margin-bottom: 2px;
            font-size: 12px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-primary { background-color: #ffc107; color: #000; }
        .badge-success { background-color: #28a745; color: white; }
        .badge-danger { background-color: #dc3545; color: white; }
        .badge-info { background-color: #17a2b8; color: white; }

        .status-indicators {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-item .label {
            font-size: 12px;
            color: #666;
        }

        .filter-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .filter-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .filter-form input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            max-width: 200px;
        }

        .filter-form .radio-group {
            display: flex;
            gap: 15px;
            margin: 10px 0;
        }

        .filter-form .radio-option {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-container input[type="text"] {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-container button {
            padding: 10px 20px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .search-container button:hover {
            background-color: #138496;
        }

        .highlight {
            background-color: yellow;
            font-weight: bold;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
            display: none;
        }

        @media (max-width: 768px) {
            .card-details {
                grid-template-columns: 1fr;
            }
            
            .filter-form input[type="date"] {
                max-width: 100%;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 8px;
            }
            
            .search-container {
                flex-direction: column;
            }
            
            .provider-avatar-container {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        .detail-item .label i,
.status-item .label i {
    margin-right: 8px;
    width: 16px;
    text-align: center;
}

.badge i {
    margin-right: 4px;
}
    </style>
</head>
<body class="is-preload">
    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <span class="close-image-modal" onclick="closeImageModal()">&times;</span>
        <div class="image-modal-content">
            <img id="modalImage" class="modal-image">
        </div>
    </div>

    <div id="wrapper">
        <div id="main">
            <div class="inner">
                <header id="header">
                    <a href="welcome.php" class="logo"><strong>homeservice</strong></a>
                </header>

                <h2>My Services</h2>
                <hr class="minor" />
                
                <?php echo $filterMessage; ?>
                
                <form method="post" action="myservices.php" class="filter-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>From Date:</label>
                            <input type="date" name="fdate" value="<?= $formData['fdate'] ?? '' ?>">
                        </div>
                        <div>
                            <label>To Date:</label>
                            <input type="date" name="tdate" value="<?= $formData['tdate'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <label>Service Status:</label>
                    <div class="radio-group">
                    <div class="radio-option">
                            <input type="radio" id="rp4" name="rp1" value="" <?= empty($formData['rp1']) ? 'checked' : '' ?>>
                            <label for="rp4" style="display: inline; font-weight: normal;">All Statuses</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="rp1" name="rp1" value="1" <?= ($formData['rp1'] ?? '') == '1' ? 'checked' : '' ?>>
                            <label for="rp1" style="display: inline; font-weight: normal;">Accepted</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="rp2" name="rp1" value="2" <?= ($formData['rp1'] ?? '') == '2' ? 'checked' : '' ?>>
                            <label for="rp2" style="display: inline; font-weight: normal;">Rejected</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="rp3" name="rp1" value="0" <?= ($formData['rp1'] ?? '') == '0' ? 'checked' : '' ?>>
                            <label for="rp3" style="display: inline; font-weight: normal;">Pending</label>
                        </div>
                       
                    </div>
                    <button type="submit" name="sbbtn" class="button primary">Apply Filters</button>
                </form>

                <hr class="major" />

                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search by any data (name, service, location, etc.)">
                    <button onclick="searchServices()" class="button primary">Search</button>
                </div>
                <div id="noResults" class="no-results">No matching results found</div>

                <div class="service-requests" id="serviceRequestsContainer">
                    <?php
                    if ($stmt->num_rows > 0) {
                        $stmt->bind_result($fname, $mname, $lname, $photo, $average_rating, $address, $city, $state, $country, $phnno, $fdate, $rtime, $status, $workstatus, $payment_status, $srid, $service_name, $charge);
                        while ($stmt->fetch()) {
                            $status_class = $status == 0 ? 'status-pending' : ($status == 1 ? 'status-accepted' : 'status-rejected');
                            $status_label = $status == 0 ? 'Pending' : ($status == 1 ? 'Accepted' : 'Rejected');
                            $status_badge = $status == 0 ? 'badge-primary' : ($status == 1 ? 'badge-success' : 'badge-danger');
                            
                            // Modified work status logic
                            $workstatus_label = ($status == 2) ? 'Cancelled' : ($workstatus == 0 ? 'Pending' : 'Completed');
                            $workstatus_badge = ($status == 2) ? 'badge-danger' : ($workstatus == 0 ? 'badge-primary' : 'badge-success');
                            
                            // Modified payment status logic
                            $payment_label = ($status == 2) ? 'Cancelled' : ($payment_status == 0 ? 'Pending' : 'Paid');
                            $payment_badge = ($status == 2) ? 'badge-danger' : ($payment_status == 0 ? 'badge-primary' : 'badge-success');
                            
                            $formatted_date = date('M j, Y', strtotime($fdate));
                            $formatted_time = $rtime;
                            $formatted_charge = ($charge === null) ? 'N/A' : 'Rs. ' . number_format($charge, 2);
                            
                            echo '<div class="service-request-card ' . $status_class . '" data-searchable="' . 
                                htmlspecialchars(strtolower($fname . ' ' . $mname . ' ' . $lname . ' ' . 
                                $service_name . ' ' . $city . ' ' . $state . ' ' . 
                                $formatted_date . ' ' . $formatted_time . ' ' . 
                                $phnno . ' ' . $status_label . ' ' . $workstatus_label . ' ' . 
                                $payment_label . ' ' . $formatted_charge)) . '">
                                <div class="card-header">
                                    <div class="provider-avatar-container">
                                        <img src="' . htmlspecialchars($photo ? 'uploads/' . $photo : 'https://via.placeholder.com/60') . '" 
                                             alt="Provider Avatar" class="provider-avatar" 
                                             onclick="openImageModal(\'' . htmlspecialchars($photo ? 'uploads/' . $photo : 'https://via.placeholder.com/150') . '\')">
                                        <div class="provider-info">
                                            <h3><i class="fas fa-user"></i> ' . $fname . ' ' . $mname . ' ' . $lname . '</h3>';
                                            
                                            if ($average_rating !== null) {
                                                $fullStars = floor($average_rating);
                                                $hasHalfStar = ($average_rating - $fullStars) >= 0.5;
                                                
                                                echo '<div class="star-rating">';
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
                                                echo '<span class="rating-value">(' . number_format($average_rating, 1) . ')</span>';
                                                echo '</div>';
                                            }
                                            
                                    echo '</div>
                                    </div>
                                    <span class="badge ' . $status_badge . '"><i class="fas ' . 
                                        ($status == 0 ? 'fa-clock' : ($status == 1 ? 'fa-check-circle' : 'fa-times-circle')) . 
                                        '"></i> ' . $status_label . '</span>
                                </div>
                                
                                <div class="card-details">
                                    <div class="detail-item">
                                        <span class="label"><i class="fas fa-concierge-bell"></i> Service</span>
                                        ' . $service_name . '
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><i class="fas fa-map-marker-alt"></i> Location</span>
                                        ' . $city . ', ' . $state . '
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><i class="far fa-calendar-alt"></i> Date/Time</span>
                                        ' . $formatted_date . ' at ' . $formatted_time . '
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><i class="fas fa-phone"></i> Contact</span>
                                        ' . $phnno . '
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><i class="fas fa-rupee-sign"></i> Charge</span>
                                        ' . $formatted_charge . '
                                    </div>
                                </div>
                                
                                <div class="status-indicators">
                                    <div class="status-item">
                                        <span class="label"><i class="fas fa-hammer"></i> Work:</span>
                                        <span class="badge ' . $workstatus_badge . '"><i class="fas ' . 
                                            ($status == 2 ? 'fa-ban' : ($workstatus == 0 ? 'fa-spinner' : 'fa-check')) . '"></i> ' . $workstatus_label . '</span>
                                    </div>
                                    <div class="status-item">
                                        <span class="label"><i class="fas fa-credit-card"></i> Payment:</span>
                                        <span class="badge ' . $payment_badge . '"><i class="fas ' . 
                                            ($status == 2 ? 'fa-ban' : ($payment_status == 0 ? 'fa-hourglass-half' : 'fa-receipt')) . '"></i> ' . $payment_label . '</span>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo '<div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 8px;">
                                <h3 style="color:#666">No service requests found</h3>
                                <p>There are no records matching your criteria</p>
                              </div>';
                    }
                    ?>
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
    
    <script>
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

        function searchServices() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const cards = document.querySelectorAll('.service-request-card');
            const noResults = document.getElementById('noResults');
            let foundResults = false;
            
            document.querySelectorAll('.highlight').forEach(el => {
                el.classList.remove('highlight');
            });
            
            cards.forEach(card => {
                const searchableText = card.getAttribute('data-searchable');
                if (searchableText.includes(filter)) {
                    card.style.display = '';
                    foundResults = true;
                    
                    if (filter.trim() !== '') {
                        const textElements = card.querySelectorAll('h3, .detail-item, .status-item');
                        textElements.forEach(el => {
                            const text = el.textContent || el.innerText;
                            if (text.toLowerCase().includes(filter)) {
                                const regex = new RegExp(filter, 'gi');
                                el.innerHTML = text.replace(regex, match => 
                                    `<span class="highlight">${match}</span>`);
                            }
                        });
                    }
                } else {
                    card.style.display = 'none';
                }
            });
            
            noResults.style.display = foundResults ? 'none' : 'block';
        }
        
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchServices();
            }
        });
        
        document.getElementById('searchInput').addEventListener('input', function() {
            if (this.value.trim() === '') {
                document.querySelectorAll('.service-request-card').forEach(card => {
                    card.style.display = '';
                });
                document.getElementById('noResults').style.display = 'none';
                
                document.querySelectorAll('.highlight').forEach(el => {
                    el.classList.remove('highlight');
                });
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$con->close();
?>