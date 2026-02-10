<section id="search" class="alt" style="height:200px;">
    <center>
        <!-- Replace image with Font Awesome user icon -->
        <i class="fas fa-user" style="height:80px;font-size: 150px; color: white; border-radius:50%;  padding: 10px;"></i>
        <br><br>
        <h4>Welcome Admin</h4>
        <br><br>
    </center>
</section>

<?php
// Get count of unread verification requests
$unread_count = 0;
if ($con) {
    $count_query = "SELECT COUNT(*) as unread_count FROM verification_request WHERE read_status_a = 0";
    $count_result = $con->query($count_query);
    if ($count_result) {
        $count_row = $count_result->fetch_assoc();
        $unread_count = $count_row['unread_count'];
    }
}
?>

<!-- Menu -->
<nav id="menu">
    <header class="major">
        <h2>Menu</h2>
    </header>
    <ul>
		<li>
    <a href="view_arequest.php" style="display: flex; align-items: center;">
        Verification/Cancellation Requests
        <?php if ($unread_count > 0): ?>
            <span class="unread-badge" style="margin-left: 5px; display: inline-flex;color:white; align-items: center; justify-content: center;">
                <?php echo $unread_count; ?>
            </span>
        <?php endif; ?>
    </a>
</li>
<li><a href="all_users.php">View All users</a></li>
<li><a href="view11.php">View Bookings</a></li>
        <li><a href="add_services.php">Add or Remove Services</a></li>
        <li><a href="system_settings.php">System Settings</a></li>
        <li><a href="feedbacks.php">View Feedbacks</a></li>
        <li><a href="admin_Cpass.php">My Account</a></li>
        <li><a href="Signout.php">Signout</a></li>
    </ul>
</nav>

<!-- Section -->
<section>
    <header>
        <h2>Get in touch</h2>
    </header>
    <ul class="contact">
        <li class="icon solid fa-envelope"><a href="mailto:shoujanya57@pmc.edu.np">shoujanya57@pmc.edu.np</a></li>
        <li class="icon solid fa-phone">+977 9700000000</li>
        <li class="icon solid fa-envelope"><a href="mailto:sandesh52@pmc.edu.np">sandesh52@pmc.edu.np</a></li>
        <li class="icon solid fa-phone">+977 98000000</li>
        <li class="icon solid fa-home">PMC,<br />Patandhoka,<br />Lalitpur</li>
    </ul>
</section>

<!-- Footer -->
<footer id="footer">
    <p class="copyright">&copy; GharSewa. All rights reserved. 
    </p>
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