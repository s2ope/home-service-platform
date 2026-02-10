<?php
session_start();
if (!isset($_SESSION["utype"]) || $_SESSION["utype"] != "Admin") {
  header("location:Signin.php");
}
if (isset($_GET['photo'])) {
    $photo = $_GET['photo']; // Retrieve the photo URL from the form
    
} else {
    echo "No photo found.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Certificate Photo</title>
    <style>
        /* Styles to center the image and set a larger size */
        .photo-container {
            text-align: center;
            margin-top: 0px;
        }
        .photo-container img {
            max-width: 100%;
            max-height: 100%;
        }
    </style>
</head>
<body>

    <div class="photo-container">
        
        <img src="<?php echo $photo; ?>" alt="Certificate Photo">
    </div>

</body>
</html>
