<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php

$con = mysqli_connect("localhost", "root", "", "homeservice");

if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

session_start();

if (!isset($_SESSION["consumer_utype"])) {
    header("Location: Signin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $srid = $_POST['srid'];
    $provider_id = $_POST['provider_id'];
    $rating = (int)$_POST['rating_value'];
    $consumer_id = $_SESSION["consumer_id"];
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $_SESSION['rating_msg'] = "Invalid rating value";
        $_SESSION['rating_msg_color'] = 'red';
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    // Insert the rating
    $insert_query = "INSERT INTO ratings (request_id, provider_id, consumer_id, rating)
                    VALUES (?, ?, ?, ?)";
    $stmt = $con->prepare($insert_query);
    $stmt->bind_param("iiii", $srid, $provider_id, $consumer_id, $rating);
    
    if ($stmt->execute()) {
        // Update provider's average rating and count
        $update_query = "UPDATE provider SET
                        average_rating = (
                            SELECT ROUND(AVG(rating), 2) 
                            FROM ratings 
                            WHERE provider_id = ?
                        ),
                        rating_count = (
                            SELECT COUNT(*) 
                            FROM ratings 
                            WHERE provider_id = ?
                        )
                        WHERE pid = ?";
        $stmt = $con->prepare($update_query);
        $stmt->bind_param("iii", $provider_id, $provider_id, $provider_id);
        $stmt->execute();
        
        // Update service request status (mark as rated and update read status)
        $update_request = "UPDATE service_request 
                          SET read_status_c = 1, 
                              read_status = 0 
                          WHERE srid = ?";
        $stmt = $con->prepare($update_request);
        $stmt->bind_param("i", $srid);
        $stmt->execute();
        
        $_SESSION['rating_msg'] = "Thank you for your rating!";
        $_SESSION['rating_msg_color'] = 'green';
    } else {
        $_SESSION['rating_msg'] = "Error: You may have already rated this service";
        $_SESSION['rating_msg_color'] = 'red';
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

?>