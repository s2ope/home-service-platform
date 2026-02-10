<?php
// Check which type of user is logged in and destroy only that specific session

// Check for Admin session
session_name('admin_session');
session_start();
if (isset($_SESSION['utype']) && $_SESSION['utype'] == "Admin") {
    // Destroy only the admin session
    session_destroy();
    header("Location: signin.php");
    exit();
}

// Check for Consumer session
session_name('consumer_session');
session_start();
if (isset($_SESSION['consumer_utype']) && $_SESSION['consumer_utype'] == "Consumer") {
    // Destroy only the consumer session
    session_destroy();
    header("Location: signin.php");
    exit();
}

// Check for Provider session
session_name('provider_session');
session_start();
if (isset($_SESSION['provider_utype']) && $_SESSION['provider_utype'] == "Provider") {
    // Destroy only the provider session
    session_destroy();
    header("Location: signin.php");
    exit();
}

// If no session was found, redirect to login page
header("Location: signin.php");
exit();
?>