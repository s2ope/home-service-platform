<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
session_start();
if (!isset($_SESSION["utype"]) || $_SESSION["utype"] != "Admin") {
    header("location:Signin.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

// Initialize message variable from session if it exists
$msg = isset($_SESSION['service_msg']) ? $_SESSION['service_msg'] : '';
unset($_SESSION['service_msg']); // Clear the message after displaying

// Handle form submission for adding services
if (isset($_POST["addService"])) {
    $serviceName = trim($_POST["serviceName"]);
    $image = $_FILES["serviceImage"];

    // Validate service name
    if (empty($serviceName)) {
        $_SESSION['service_msg'] = "Service name cannot be empty.";
    } elseif ($image["error"] != 0) {
        $_SESSION['service_msg'] = "Error uploading the image.";
    } else {
        // Handle image upload
        $uploadDir = "uploads/";
        $fileName = basename($image["name"]);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allow only certain file types
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];
        if (!in_array(strtolower($fileType), $allowedTypes)) {
            $_SESSION['service_msg'] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } elseif (move_uploaded_file($image["tmp_name"], $targetFilePath)) {
            // Insert service details into the database
            $query = "INSERT INTO services (sname, icon) VALUES (?, ?)";
            $stmt = $con->prepare($query);
            $stmt->bind_param("ss", $serviceName, $targetFilePath);

            if ($stmt->execute()) {
                $_SESSION['service_msg'] = "Service added successfully.";
            } else {
                $_SESSION['service_msg'] = "Failed to add the service.";
            }
            $stmt->close();
        } else {
            $_SESSION['service_msg'] = "Failed to upload the image.";
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle form submission for editing services
if (isset($_POST["updateService"])) {
    $serviceName = trim($_POST["serviceName"]);
    $image = $_FILES["serviceImage"];
    $sid = $_POST["sid"]; // Service ID

    // Validate service name
    if (empty($serviceName)) {
        $_SESSION['service_msg'] = "Service name cannot be empty.";
    } else {
        $uploadDir = "uploads/";
        $fileName = basename($image["name"]);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Handle image upload
        if ($image["error"] == 0) {
            $allowedTypes = ["jpg", "jpeg", "png", "gif"];
            if (in_array(strtolower($fileType), $allowedTypes)) {
                if (move_uploaded_file($image["tmp_name"], $targetFilePath)) {
                    $query = "UPDATE services SET sname = ?, icon = ? WHERE sid = ?";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param("ssi", $serviceName, $targetFilePath, $sid);

                    if ($stmt->execute()) {
                        $_SESSION['service_msg'] = "Service updated successfully.";
                    } else {
                        $_SESSION['service_msg'] = "Failed to update the service.";
                    }
                    $stmt->close();
                } else {
                    $_SESSION['service_msg'] = "Failed to upload the image.";
                }
            } else {
                $_SESSION['service_msg'] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            }
        } else {
            $query = "UPDATE services SET sname = ? WHERE sid = ?";
            $stmt = $con->prepare($query);
            $stmt->bind_param("si", $serviceName, $sid);

            if ($stmt->execute()) {
                $_SESSION['service_msg'] = "Service updated successfully.";
            } else {
                $_SESSION['service_msg'] = "Failed to update the service.";
            }
            $stmt->close();
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle service deletion
if (isset($_GET["delete"])) {
    $sid = $_GET["delete"];
    $query = "DELETE FROM services WHERE sid = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $sid);

    if ($stmt->execute()) {
        $_SESSION['service_msg'] = "Service deleted successfully.";
    } else {
        $_SESSION['service_msg'] = "Failed to delete the service.";
    }
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all services for display
$services = [];
$query = "SELECT sid, sname, icon FROM services";
$result = $con->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Manage Services</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <!-- Include DataTable CSS and JS files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">
    <style>
        .form-container input[type="text"], .form-container input[type="file"], .form-container button {
            width: 50%;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #f2f2f2;
        }
        button {
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #c0392b;
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

                
                    <h2>Manage Services</h2>
                    <hr class="minor" />
                    <div>
                        <label><h3 style="color:green"><?php echo isset($msg) ? $msg : ""; ?></h3></label>
                    </div>

                    <div class="form-container">
                        <!-- Add Service Form -->
                        <form method="post" action="" enctype="multipart/form-data">
                            <div>
                                <label>Service Name:</label>
                                <input type="text" name="serviceName" placeholder="Enter Service Name" required />
                            </div>
                            <div>
                                <label>Upload Image:</label>
                                <input type="file" name="serviceImage" required />
                            </div>
                            <div class="col-12">
                                <ul class="actions">
                                    <li><input type="submit" name="addService" value="Add Service" class="primary" /></li>
                                    <li><input type="reset" value="Reset" /></li>
                                </ul>
                            </div>
                        </form>

                        <!-- Edit Service Form (Populated when the Edit button is clicked) -->
                        <?php if (isset($_GET['edit']) && isset($service)) : ?>
                            <form method="post" action="" enctype="multipart/form-data">
                                <input type="hidden" name="sid" value="<?php echo $service['sid']; ?>" />
                                <div>
                                    <label>Service Name:</label>
                                    <input type="text" name="serviceName" value="<?php echo htmlspecialchars($service['service_name']); ?>" required />
                                </div>
                                <div>
                                    <label>Upload New Image:</label>
                                    <input type="file" name="serviceImage" />
                                    <image src="<?php echo htmlspecialchars($service['image_path']); ?>" alt="Current Image" style="width: 100px; height: auto; margin-top: 10px;">
                                </div>
                                <div class="col-12">
                                    <ul class="actions">
                                        <li><input type="submit" name="updateService" value="Update Service" class="primary" /></li>
                                        <li><input type="reset" value="Reset" /></li>
                                    </ul>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Service Table -->
                    <div class="table-container">
                        <table id="serviceTable">
                            <thead>
                                <tr>
                                    <th>Service Name</th>
                                    <th>Image</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($services)) : ?>
                                    <?php foreach ($services as $service) : ?>
                                        <tr>
                                            <td style="text-align: center; vertical-align: middle;">
                                                <?php echo htmlspecialchars($service["sname"]); ?>
                                            </td>
                                            <td style="text-align: center; vertical-align: middle;">
                                                <image src="<?php echo htmlspecialchars($service["icon"]); ?>" alt="Service Image" style="width: 100px; height: auto; display: block; margin: 0 auto;">
                                            </td>
                                            <td style="text-align: center; vertical-align: middle;">
                                                <a href="?delete=<?php echo $service['sid']; ?>" class="button primary">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center;">No services found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </section>
            </div>
        </div>

        <div id="sidebar">
            <div class="inner">
                <?php include "amenu.php" ?>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/browser.min.js"></script>
    <script src="assets/js/breakpoints.min.js"></script>
    <script src="assets/js/util.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.3/js/jquery.dataTables.min.js"></script>

    <!-- Initialize DataTable -->
    <script>
        $(document).ready(function() {
            $('#serviceTable').DataTable({
                "paging": true,  // Enables pagination
                "searching": true,  // Enables search functionality
                "ordering": true,  // Enables column sorting
                "info": true  // Shows information (e.g., showing 1 to 10 of 50 entries)
            });
        });
    </script>
</body>
</html>
