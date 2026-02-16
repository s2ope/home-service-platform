<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION["utype"]) || $_SESSION["utype"] != "Admin") {
    header("location:Signin.php");
    exit();
}

// Database connection
$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno()) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get active tab from URL or default to 'all'
$active_tab = isset($_GET['type']) ? $_GET['type'] : 'all';

// Function to generate star rating HTML
function generateStarRating($rating) {
    $stars = '';
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    
    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $stars .= '<i class="fas fa-star"></i>';
    }
    
    // Half star
    if ($hasHalfStar) {
        $stars .= '<i class="fas fa-star-half-alt"></i>';
        $fullStars++; // Count the half star as one for remaining empty stars
    }
    
    // Empty stars
    for ($i = $fullStars; $i < 5; $i++) {
        $stars .= '<i class="far fa-star"></i>';
    }
    
    return '<div class="star-rating">' . $stars . '<span class="rating-value">(' . number_format($rating, 1) . ')</span></div>';
}

// Function to fetch users
function fetchUsers($con, $type) {
    $users = [];
    
    if ($type == 'all' || $type == 'consumers') {
        $query = "SELECT cid as id, fname, mname, lname, dob, gender, email, photo, phnno,
                 country, state, city, address, 'consumer' as type, NULL as average_rating
                 FROM consumer";
        $result = mysqli_query($con, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
    
    if ($type == 'all' || $type == 'providers') {
        $query = "SELECT pid as id, fname, mname, lname, dob, gender, email, photo, phnno,
                 country, state, city, address, average_rating, 'provider' as type 
                 FROM provider";
        $result = mysqli_query($con, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
    
    return $users;
}

// Fetch users based on active tab
$users = fetchUsers($con, $active_tab);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | homeservice</title>
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
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fc;
            margin: 0;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .user-type-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background: #f1f1f1;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            text-decoration: none;
            color: #333;
            display: flex;
            align-items: center;
        }

        .tab i {
            margin-right: 8px;
        }

        .tab.active {
            background: #e74a3b;
            color: white;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
        }

        .search-input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
            font-size: 14px;
        }

        .search-button {
            padding: 10px 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            margin-left: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }

        .search-button i {
            margin-right: 8px;
        }

        .users-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .user-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-5px);
            transform:scale(1.05);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
        }

        .user-header {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            margin-right: 15px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .user-name {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .user-name i {
            margin-right: 8px;
        }

        .user-type {
            font-size: 12px;
            background: rgba(255, 255, 255, 0.2);
            padding: 3px 8px;
            border-radius: 20px;
            margin-top: 5px;
            text-transform: capitalize;
            display: inline-flex;
            align-items: center;
        }

        .user-type i {
            margin-right: 5px;
        }

        .user-details {
            padding: 15px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: 600;
            color: var(--dark-color);
            min-width: 100px;
            font-size: 13px;
            display: flex;
            align-items: center;
        }

        .detail-label i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
            color: var(--primary-color);
        }

        .detail-value {
            color: #6c757d;
            font-size: 13px;
        }

        .user-actions {
            padding: 10px 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
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

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 8px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
        }

        .action-btn i {
            margin-right: 5px;
        }

        .edit-btn {
            background: var(--info-color);
            color: white;
        }

        .delete-btn {
            background: var(--danger-color);
            color: white;
        }

        .no-users {
            text-align: center;
            padding: 50px;
            color: #6c757d;
            grid-column: 1 / -1;
        }

        @media (max-width: 768px) {
            .users-container {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-container {
                width: 100%;
                margin-top: 15px;
            }
            
            .search-input {
                width: 100%;
            }
            
            .close-image-modal {
                right: 15px;
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
                    <a href="view_arequest.php" class="logo"><strong>home</strong>service</a>
                </header>
                
                <header class="main">
                    <h2><i class="fas fa-users-cog"></i> User Management</h2>
                </header>

                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search users..." id="searchInput">
                    <button class="search-button primary" id="searchButton">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>

                <div class="user-type-tabs">
                    <a href="?type=all" class="tab <?= $active_tab == 'all' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> All Users
                    </a>
                    <a href="?type=consumers" class="tab <?= $active_tab == 'consumers' ? 'active' : '' ?>">
                        <i class="fas fa-user-tag"></i> Consumers
                    </a>
                    <a href="?type=providers" class="tab <?= $active_tab == 'providers' ? 'active' : '' ?>">
                        <i class="fas fa-user-tie"></i> Service Providers
                    </a>
                </div>

                <div class="users-container" id="usersContainer">
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <div class="user-card" data-type="<?= htmlspecialchars($user['type']) ?>">
                                <div class="user-header">
                                    <img src="<?= htmlspecialchars($user['photo'] ? 'uploads/' . $user['photo'] : 'https://via.placeholder.com/60') ?>" 
                                         alt="User Avatar" class="user-avatar" 
                                         onclick="openImageModal('<?= htmlspecialchars($user['photo'] ? 'uploads/' . $user['photo'] : 'https://via.placeholder.com/150') ?>')">
                                    <div>
                                        <h3 class="user-name">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($user['fname'] . ' ' . $user['mname'] . ' ' . $user['lname']) ?>
                                        </h3>
                                        <span class="user-type">
                                            <i class="<?= $user['type'] == 'consumer' ? 'fas fa-user-tag' : 'fas fa-user-tie' ?>"></i>
                                            <?= ucfirst($user['type']) ?>
                                        </span>
                                        <?php if ($user['type'] == 'provider' && $user['average_rating'] !== null): ?>
                                            <div class="star-rating">
                                                <?= generateStarRating($user['average_rating']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="user-details">
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-envelope"></i> Email:
                                        </span>
                                        <span class="detail-value"><?= htmlspecialchars($user['email']) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-venus-mars"></i> Gender:
                                        </span>
                                        <span class="detail-value"><?= htmlspecialchars($user['gender']) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-birthday-cake"></i> Date of Birth:
                                        </span>
                                        <span class="detail-value"><?= date('d M Y', strtotime($user['dob'])) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-phone"></i> Contact:
                                        </span>
                                        <span class="detail-value"><?= htmlspecialchars($user['phnno']) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-map-marker-alt"></i> Location:
                                        </span>
                                        <span class="detail-value"><?= htmlspecialchars($user['city'] . ', ' . $user['country']) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-home"></i> Address:
                                        </span>
                                        <span class="detail-value"><?= htmlspecialchars($user['address']) ?></span>
                                    </div>
                                </div>
                               
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-users">
                            <i class="fas fa-users" style="font-size: 50px; margin-bottom: 15px;"></i>
                            <h3>No users found</h3>
                            <p>There are no <?= $active_tab == 'all' ? '' : $active_tab ?> users registered in the system.</p>
                        </div>
                    <?php endif; ?>
                </div>
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
        // Search functionality
        document.getElementById('searchButton').addEventListener('click', function() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const userCards = document.querySelectorAll('.user-card');
            let hasResults = false;
            
            userCards.forEach(card => {
                const userName = card.querySelector('.user-name').textContent.toLowerCase();
                const userEmail = card.querySelector('.detail-value').textContent.toLowerCase();
                
                if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
                    card.style.display = 'block';
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
                    noResults.className = 'no-users no-results';
                    noResults.innerHTML = '<h3>No Matching Users Found</h3><p>Try a different search term.</p>';
                    document.getElementById('usersContainer').appendChild(noResults);
                }
            } else if (noResultsElement) {
                noResultsElement.remove();
            }
        });

        // Allow search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('searchButton').click();
            }
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
<?php
mysqli_close($con);
?>