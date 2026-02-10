<?php
session_start();
if (!isset($_SESSION["utype"]) || $_SESSION["utype"] != "Admin") {
    header("location:Signin.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "gharsewa");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

// Initialize message variable from session
$msg = isset($_SESSION['system_msg']) ? $_SESSION['system_msg'] : '';
unset($_SESSION['system_msg']); // Clear the message after displaying

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["sbbtn"])) {
    // Store form data in session for repopulation if needed
    $_SESSION['form_data'] = $_POST;

    // Retrieve and sanitize form data
    $system_name = trim($_POST["orgname"]);
    $email1 = trim($_POST["email1"]);
    $phhno1 = trim($_POST["phone1"]);
    $email2 = trim($_POST["email2"]);
    $phhno2 = trim($_POST["phone2"]);
    $email3 = trim($_POST["email3"]);
    $phhno3 = trim($_POST["phone3"]);

    // Server-side validation
    $errors = [];

    // Validate organization name
    if (!preg_match("/^[a-zA-Z0-9\s]+$/", $system_name)) {
        $errors[] = "Organization name must contain only letters, numbers, and spaces.";
    }

    // Validate email1
    if (!filter_var($email1, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email 1 is not in a valid format.";
    }

    // Validate phone1
    if (!preg_match("/^\d{10}$/", $phhno1)) {
        $errors[] = "Phone 1 must be exactly 10 digits and contain only numbers.";
    }

    // Validate email2 (if provided)
    if (!empty($email2) && !filter_var($email2, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email 2 is not in a valid format.";
    }

    // Validate phone2 (if provided)
    if (!empty($phhno2) && !preg_match("/^\d{10}$/", $phhno2)) {
        $errors[] = "Phone 2 must be exactly 10 digits and contain only numbers.";
    }

    // Validate email3 (if provided)
    if (!empty($email3) && !filter_var($email3, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email 3 is not in a valid format.";
    }

    // Validate phone3 (if provided)
    if (!empty($phhno3) && !preg_match("/^\d{10}$/", $phhno3)) {
        $errors[] = "Phone 3 must be exactly 10 digits and contain only numbers.";
    }

    // Check if there are validation errors
    if (empty($errors)) {
        // Insert into the database
        $query = "INSERT INTO system_settings (system_name, email1, phnno1, email2, phnno2, email3, phnno3)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($query);
        $stmt->bind_param("sssssss", $system_name, $email1, $phhno1, $email2, $phhno2, $email3, $phhno3);

        if ($stmt->execute()) {
            $_SESSION['system_msg'] = "System details updated successfully.";
        } else {
            $_SESSION['system_msg'] = "Failed to update system details. Please try again.";
        }

        $stmt->close();
    } else {
        // Combine all validation errors into the message
        $_SESSION['system_msg'] = implode("<br>", $errors);
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Retrieve form data from session if it exists
$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);
?>

<!DOCTYPE HTML>
<html>
    <head>
        <title>Online Household Service Portal</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
        <link rel="stylesheet" href="assets/css/main.css" />
        <style>
            .form-section {
                background: #f8f9fa;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                margin-bottom: 30px;
            }
            .form-row {
                display: flex;
                flex-wrap: wrap;
                margin-bottom: 20px;
            }
            .form-group {
                flex: 0 0 50%;
                padding: 0 15px;
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
            }
            .form-group input {
                width: 100%;
                padding: 10px 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            .form-group input:focus {
                border-color: #4e73df;
                outline: none;
                box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.25);
            }
            .form-actions {
                display: flex;
                justify-content: flex-end;
                gap: 15px;
                padding: 0 15px;
                margin-top: 20px;
            }
          /*  .form-actions button[type="submit"] {
                background-color: #4e73df;
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s;
            }
            .form-actions button[type="submit"]:hover {
                background-color: #2e59d9;
            }
            .form-actions button[type="reset"] {
                background-color: #e74a3b;
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s;
            }
            .form-actions button[type="reset"]:hover {
                background-color: #d52a1b;
            }*/
            .message {
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
                text-align: center;
            }
            .success {
                background-color: #d4edda;
                color: #155724;
            }
            .error {
                background-color: #f8d7da;
                color: #721c24;
            }
            @media (max-width: 768px) {
                .form-group {
                    flex: 0 0 100%;
                }
            }
        </style>
    </head>
    <body class="is-preload">

        <div id="wrapper">

            <div id="main">
                <div class="inner">

                    <header id="header">
                        <a href="view_arequest.php" class="logo"><strong>Ghar Sewa</strong></a>
                    </header>

                    <h2 id="elements">Update Organization Details</h2>
                        <hr class="minor" />

                        <?php if (!empty($msg)): ?>
                            <div class="message <?php echo strpos($msg, 'successfully') !== false ? 'success' : 'error'; ?>">
                                <?php echo $msg; ?>
                            </div>
                        <?php endif; ?>

                        <form name="f1" method="post" action="system_settings.php" onsubmit="return validate();">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="orgname">Organization Name</label>
                                    <input type="text" name="orgname" id="orgname"
                                           value="<?php echo isset($formData['orgname']) ? htmlspecialchars($formData['orgname']) : ''; ?>"
                                           required placeholder="Enter Organization Name" />
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email1">Primary Email</label>
                                    <input type="email" name="email1" id="email1"
                                           value="<?php echo isset($formData['email1']) ? htmlspecialchars($formData['email1']) : ''; ?>"
                                           required placeholder="Enter Primary Email" />
                                </div>
                                <div class="form-group">
                                    <label for="phone1">Primary Phone</label>
                                    <input type="text" name="phone1" id="phone1"
                                           value="<?php echo isset($formData['phone1']) ? htmlspecialchars($formData['phone1']) : ''; ?>"
                                           required placeholder="Enter Primary Phone" />
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email2">Secondary Email (Optional)</label>
                                    <input type="email" name="email2" id="email2"
                                           value="<?php echo isset($formData['email2']) ? htmlspecialchars($formData['email2']) : ''; ?>"
                                           placeholder="Enter Secondary Email" />
                                </div>
                                <div class="form-group">
                                    <label for="phone2">Secondary Phone (Optional)</label>
                                    <input type="text" name="phone2" id="phone2"
                                           value="<?php echo isset($formData['phone2']) ? htmlspecialchars($formData['phone2']) : ''; ?>"
                                           placeholder="Enter Secondary Phone" />
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email3">Additional Email (Optional)</label>
                                    <input type="email" name="email3" id="email3"
                                           value="<?php echo isset($formData['email3']) ? htmlspecialchars($formData['email3']) : ''; ?>"
                                           placeholder="Enter Additional Email" />
                                </div>
                                <div class="form-group">
                                    <label for="phone3">Additional Phone (Optional)</label>
                                    <input type="text" name="phone3" id="phone3"
                                           value="<?php echo isset($formData['phone3']) ? htmlspecialchars($formData['phone3']) : ''; ?>"
                                           placeholder="Enter Additional Phone" />
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="sbbtn" class="btn button primary">Apply Changes</button>
                                <button type="reset">Reset Form</button>
                            </div>
                        </form>
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

        <script>
            function validate() {
                // Client-side validation
                const orgname = document.getElementById('orgname').value;
                const email1 = document.getElementById('email1').value;
                const phone1 = document.getElementById('phone1').value;
                const email2 = document.getElementById('email2').value;
                const phone2 = document.getElementById('phone2').value;
                const email3 = document.getElementById('email3').value;
                const phone3 = document.getElementById('phone3').value;

                // Validate organization name
                if (!/^[a-zA-Z0-9\s]+$/.test(orgname)) {
                    alert('Organization name must contain only letters, numbers, and spaces.');
                    return false;
                }

                // Validate primary email
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email1)) {
                    alert('Please enter a valid primary email address.');
                    return false;
                }

                // Validate primary phone
                if (!/^\d{10}$/.test(phone1)) {
                    alert('Primary phone must be exactly 10 digits.');
                    return false;
                }

                // Validate secondary email if provided
                if (email2 && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email2)) {
                    alert('Please enter a valid secondary email address or leave it blank.');
                    return false;
                }

                // Validate secondary phone if provided
                if (phone2 && !/^\d{10}$/.test(phone2)) {
                    alert('Secondary phone must be exactly 10 digits or leave it blank.');
                    return false;
                }

                // Validate additional email if provided
                if (email3 && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email3)) {
                    alert('Please enter a valid additional email address or leave it blank.');
                    return false;
                }

                // Validate additional phone if provided
                if (phone3 && !/^\d{10}$/.test(phone3)) {
                    alert('Additional phone must be exactly 10 digits or leave it blank.');
                    return false;
                }

                return true;
            }
        </script>
    </body>
</html>