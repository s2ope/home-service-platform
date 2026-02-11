<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
session_start();

/* Completely destroy the session */
$_SESSION = [];
session_unset();
session_destroy();

/* Optional: destroy session cookie (best practice) */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* Redirect to login page */
header("Location: signin.php");
exit;
