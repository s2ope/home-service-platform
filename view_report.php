<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
session_start();
if (!isset($_SESSION["provider_utype"]) || $_SESSION["provider_utype"] != "Provider") {
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

$id = $_SESSION["provider_id"];

$query = "SELECT consumer.fname, consumer.mname, consumer.lname, consumer.address, consumer.city, consumer.state, consumer.country,
            consumer.phnno, consumer.photo,
            service_request.req_date AS fdate, service_request.req_time,
            service_request.status AS status, service_request.work_status,
            service_request.payment_status, service_request.srid, services.sname AS service_name,
            service_request.charge AS charge
          FROM service_request
          JOIN consumer ON consumer.cid = service_request.consumer_id
          JOIN provider ON provider.pid = service_request.provider_id
          JOIN services ON services.sid = service_request.service_id
          WHERE service_request.provider_id = ?";

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

        /* Rest of your existing styles remain the same */
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

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        .badge i {
        margin-right: 5px;
    }
    
    .detail-item i {
        margin-right: 8px;
        color: #17a2b8;
        width: 16px;
        text-align: center;
    }
    
    .status-item i {
        margin-right: 5px;
    }
    </style>
</head>
<body class="is-preload">
    <div id="wrapper">
        <div id="main">
            <div class="inner">
                <header id="header">
                    <a href="welcome.php" class="logo"><strong>homeservice</strong></a>
                </header>

                <h2>My Services</h2>
                <hr class="minor" />
                
                <?php echo $filterMessage; ?>
                
                <form method="post" action="view_report.php" class="filter-form">
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
                    <button onclick="searchServices()" class="button primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <div id="noResults" class="no-results">No matching results found</div>

                <div class="service-requests" id="serviceRequestsContainer">
                    <?php
                    if ($stmt->num_rows > 0) {
                        $stmt->bind_result($fname, $mname, $lname, $address, $city, $state, $country, $phnno, $photo, $fdate, $rtime, $status, $workstatus, $payment_status, $srid, $service_name, $charge);
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
                            
                            // Determine image source
                            $imageSrc = !empty($photo) ? 'uploads/' . htmlspecialchars($photo) : 'https://via.placeholder.com/60?text=User';
                            $modalImageSrc = !empty($photo) ? 'uploads/' . htmlspecialchars($photo) : 'https://via.placeholder.com/400?text=User';
                            
                            echo '<div class="service-request-card ' . $status_class . '" data-searchable="' . 
                                htmlspecialchars(strtolower($fname . ' ' . $mname . ' ' . $lname . ' ' . 
                                $service_name . ' ' . $city . ' ' . $state . ' ' . 
                                $formatted_date . ' ' . $formatted_time . ' ' . 
                                $phnno . ' ' . $status_label . ' ' . $workstatus_label . ' ' . 
                                $payment_label . ' ' . $formatted_charge)) . '">
                                <div class="consumer-image-container">
                                    <img src="' . $imageSrc . '" 
                                         alt="Consumer Photo" 
                                         class="consumer-image" 
                                         onclick="openModal(\'' . $modalImageSrc . '\')">
                                    <div>
                                        <h3 class="consumer-name"><i class="fas fa-user"></i> ' . $fname . ' ' . $mname . ' ' . $lname . '</h3>
                                        <span class="badge ' . $status_badge . '">
                                            <i class="' . ($status == 0 ? 'far fa-clock' : ($status == 1 ? 'fas fa-check-circle' : 'fas fa-times-circle')) . '"></i>
                                            ' . $status_label . '
                                        </span>
                                    </div>
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
                                        <span class="label"><i class="fas fa-money-bill-wave"></i> Charge</span>
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
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["provider_utype"]) || $_SESSION["provider_utype"] != "Provider") {
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

$id = $_SESSION["provider_id"];

$query = "SELECT consumer.fname, consumer.mname, consumer.lname, consumer.address, consumer.city, consumer.state, consumer.country,
            consumer.phnno, consumer.photo,
            service_request.req_date AS fdate, service_request.req_time,
            service_request.status AS status, service_request.work_status,
            service_request.payment_status, service_request.srid, services.sname AS service_name,
            service_request.charge AS charge
          FROM service_request
          JOIN consumer ON consumer.cid = service_request.consumer_id
          JOIN provider ON provider.pid = service_request.provider_id
          JOIN services ON services.sid = service_request.service_id
          WHERE service_request.provider_id = ?";

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

        /* Rest of your existing styles remain the same */
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

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        .badge i {
        margin-right: 5px;
    }
    
    .detail-item i {
        margin-right: 8px;
        color: #17a2b8;
        width: 16px;
        text-align: center;
    }
    
    .status-item i {
        margin-right: 5px;
    }
    </style>
</head>
<body class="is-preload">
    <div id="wrapper">
        <div id="main">
            <div class="inner">
                <header id="header">
                    <a href="welcome.php" class="logo"><strong>homeservice</strong></a>
                </header>

                <h2>My Services</h2>
                <hr class="minor" />
                
                <?php echo $filterMessage; ?>
                
                <form method="post" action="view_report.php" class="filter-form">
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
                    <button onclick="searchServices()" class="button primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <div id="noResults" class="no-results">No matching results found</div>

                <div class="service-requests" id="serviceRequestsContainer">
                    <?php
                    if ($stmt->num_rows > 0) {
                        $stmt->bind_result($fname, $mname, $lname, $address, $city, $state, $country, $phnno, $photo, $fdate, $rtime, $status, $workstatus, $payment_status, $srid, $service_name, $charge);
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
                            
                            // Determine image source
                            $imageSrc = !empty($photo) ? 'uploads/' . htmlspecialchars($photo) : 'https://via.placeholder.com/60?text=User';
                            $modalImageSrc = !empty($photo) ? 'uploads/' . htmlspecialchars($photo) : 'https://via.placeholder.com/400?text=User';
                            
                            echo '<div class="service-request-card ' . $status_class . '" data-searchable="' . 
                                htmlspecialchars(strtolower($fname . ' ' . $mname . ' ' . $lname . ' ' . 
                                $service_name . ' ' . $city . ' ' . $state . ' ' . 
                                $formatted_date . ' ' . $formatted_time . ' ' . 
                                $phnno . ' ' . $status_label . ' ' . $workstatus_label . ' ' . 
                                $payment_label . ' ' . $formatted_charge)) . '">
                                <div class="consumer-image-container">
                                    <img src="' . $imageSrc . '" 
                                         alt="Consumer Photo" 
                                         class="consumer-image" 
                                         onclick="openModal(\'' . $modalImageSrc . '\')">
                                    <div>
                                        <h3 class="consumer-name"><i class="fas fa-user"></i> ' . $fname . ' ' . $mname . ' ' . $lname . '</h3>
                                        <span class="badge ' . $status_badge . '">
                                            <i class="' . ($status == 0 ? 'far fa-clock' : ($status == 1 ? 'fas fa-check-circle' : 'fas fa-times-circle')) . '"></i>
                                            ' . $status_label . '
                                        </span>
                                    </div>
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
                                        <span class="label"><i class="fas fa-money-bill-wave"></i> Charge</span>
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

<!-- <?php
$stmt->close();

?> -->