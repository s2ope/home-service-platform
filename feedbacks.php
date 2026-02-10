<?php
session_start();

// Verify admin session
if (!isset($_SESSION["utype"]) || $_SESSION["utype"] != "Admin") {
  header("location:Signin.php");
  exit();
}

// Database connection
$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}

// Fetch all feedbacks
$query = "SELECT f.*, c.photo as consumer_photo, p.photo as provider_photo 
          FROM feedbacks f
          LEFT JOIN consumer c ON c.email = f.email
          LEFT JOIN provider p ON p.email = f.email";
$result = mysqli_query($con, $query);

$feedbacks = [];
while($row = mysqli_fetch_assoc($result)) {
    $feedbacks[] = $row;
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Admin Feedback - Ghar Sewa</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        .dataTables_wrapper {
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Table lines styling */
        #feedbackTable {
            border-collapse: collapse !important;
            width: 100%;
            border: 1px solid #ddd;
        }

        #feedbackTable thead th {
            border-bottom: 2px solid #dee2e6 !important;
            border-top: 1px solid #ddd;
            border-right: 1px solid #ddd;
            border-left: 1px solid #ddd;
            padding: 12px 15px;
            background-color: #f8f9fa;
        }

        #feedbackTable tbody td {
            border: 1px solid #ddd;
            padding: 10px 15px;
        }

        #feedbackTable tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        #feedbackTable tbody tr:hover {
            background-color: #f1f1f1;
        }
        
        /* User cell alignment */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #17a2b8;
            flex-shrink: 0;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .user-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .action-btn {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            margin: 0 5px;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            color: #17a2b8;
            transform: scale(1.2);
        }
        
        /* Modal styles */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            overflow: auto;
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            display: block;
            margin: auto;
            max-width: 90%;
            max-height: 90%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: zoomIn 0.3s;
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: #bbb;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
        
        @keyframes zoomIn {
            from {transform: translate(-50%, -50%) scale(0.9);}
            to {transform: translate(-50%, -50%) scale(1);}
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dataTables_wrapper {
                padding: 10px;
            }
            
            .close-modal {
                top: 10px;
                right: 20px;
                font-size: 30px;
            }
        }
    </style>
</head>
<body class="is-preload">
    <div id="wrapper">
        <div id="main">
            <div class="inner">
                <header id="header">
                    <a href="view_arequest.php" class="logo"><strong>Ghar</strong>Sewa</a>
                </header>
                
                    <header class="major">
                        <h2>Customer Feedback</h2>
                    </header>
                    
                    <div class="table-responsive">
                        <table id="feedbackTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Message</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($feedbacks as $feedback): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <?php 
                                                    $photo = '';
                                                    if(!empty($feedback['consumer_photo'])) {
                                                        $photo = 'uploads/' . $feedback['consumer_photo'];
                                                    } elseif(!empty($feedback['provider_photo'])) {
                                                        $photo = 'uploads/' . $feedback['provider_photo'];
                                                    } else {
                                                        $photo = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                                                    }
                                                ?>
                                                <img src="<?php echo $photo; ?>" class="user-avatar" alt="User" onclick="openImageModal('<?php echo $photo; ?>')">
                                                <span class="user-name"><?php echo htmlspecialchars($feedback['name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($feedback['email']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></td>
                                       
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div id="sidebar">
            <div class="inner">
                <?php include "amenu.php"; ?>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <span class="close-modal" onclick="closeImageModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <!-- jQuery and DataTables JS -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/browser.min.js"></script>
    <script src="assets/js/breakpoints.min.js"></script>
    <script src="assets/js/util.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#feedbackTable').DataTable({
            responsive: true,
            columnDefs: [
                { responsivePriority: 1, targets: 0 }, // User column
                { responsivePriority: 2, targets: 2 }, // Message column
                { responsivePriority: 3, targets: 1 }, // Email column
                { orderable: false, targets: 3 } // Actions column
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search feedback...",
                lengthMenu: "Show _MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)"
            }
        });
        
        // Delete button functionality
        $('.action-btn').click(function(e) {
            e.stopPropagation();
            if (confirm('Are you sure you want to delete this feedback?')) {
                $(this).closest('tr').fadeOut();
            }
        });
    });
    
    // Image modal functions
    function openImageModal(imageSrc) {
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        modal.style.display = "block";
        modalImg.src = imageSrc;
        
        // Adjust image size based on screen
        if (window.innerWidth <= 768) {
            modalImg.style.maxWidth = "95%";
            modalImg.style.maxHeight = "95%";
        } else {
            modalImg.style.maxWidth = "80%";
            modalImg.style.maxHeight = "80%";
        }
    }
    
    function closeImageModal() {
        document.getElementById("imageModal").style.display = "none";
    }
    
    // Close modal when clicking outside image
    window.onclick = function(event) {
        const modal = document.getElementById("imageModal");
        if (event.target == modal) {
            closeImageModal();
        }
    }
    </script>
</body>
</html>