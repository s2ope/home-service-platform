<?php
session_start();
if(isset($_POST['lat']) && isset($_POST['lng'])){
    $_SESSION['user_lat'] = $_POST['lat'];
    $_SESSION['user_lng'] = $_POST['lng'];
    echo "Location saved successfully!";
} else {
    echo "No location received!";
}
?>
