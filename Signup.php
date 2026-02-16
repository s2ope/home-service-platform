<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
session_start();

$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

// Initialize message variable from session if it exists
$msg = isset($_SESSION['signup_msg']) ? $_SESSION['signup_msg'] : '';
unset($_SESSION['signup_msg']); // Clear the message after displaying

// Initialize form data from session if it exists
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : array();
unset($_SESSION['form_data']); // Clear the form data after using

if (isset($_POST["sbbtn"])) {
    // Store all form data in session for repopulation if needed
    $_SESSION['form_data'] = $_POST;
    
    $fname = $_POST["fname"];
    $mname = empty($_POST["mname"]) ? NULL : $_POST["mname"];
    $lname = $_POST["lname"];
    $gender = $_POST["gender"];
    $bdate = $_POST["bdate"];
    $mno = $_POST["mno"];
    $country = $_POST["country"];
    $state = $_POST["state"];
    $city = $_POST["city"];
    $address = $_POST["address"];
    $emailid = $_POST["emailid"];
    $pass = $_POST["pass"];
    $cpass = $_POST["cpass"];
    $utype = $_POST["utype"];
    $img = $_FILES["photo"]["tmp_name"];
    $size = $_FILES["photo"]["size"];
    $imgname = $_FILES["photo"]["name"];
    $imgtype = strtolower(pathinfo($imgname, PATHINFO_EXTENSION));
    $user;

    // Validate password length
    if (strlen($pass) < 8) {
        $_SESSION['signup_msg'] = "Password must contain at least 8 characters.";
    }
    // Validate password match
    elseif ($pass != $cpass) {
        $_SESSION['signup_msg'] = "Password and Confirm Password do not match.";
    }
    // Validate phone number
    elseif (!preg_match("/^\d{10}$/", $mno)) {
        $_SESSION['signup_msg'] = "Invalid Phone Number....";
    }
    // Validate email exists
    else {
        $email_check_query = "SELECT * FROM users WHERE email = ?";
        $email_check_stmt = $con->prepare($email_check_query);
        $email_check_stmt->bind_param("s", $emailid);
        $email_check_stmt->execute();
        $email_check_stmt->store_result();

        if ($email_check_stmt->num_rows > 0) {
            $_SESSION['signup_msg'] = "Email already used...";
        }
        // Validate phone exists
        else {
            $phone_check_query = "SELECT phnno FROM consumer WHERE phnno = ? UNION SELECT phnno FROM provider WHERE phnno = ?";
            $phone_check_stmt = $con->prepare($phone_check_query);
            $phone_check_stmt->bind_param("ss", $mno, $mno);
            $phone_check_stmt->execute();
            $phone_check_stmt->store_result();

            if ($phone_check_stmt->num_rows > 0) {
                $_SESSION['signup_msg'] = "Phone number already in use....";
            }
            // Validate image size
            elseif ($size > 1000000) {
                $_SESSION['signup_msg'] = "Image is too large..";
            }
            // Validate image type
            elseif ($imgtype != "jpg" && $imgtype != "jpeg" && $imgtype != "png" && $imgtype != "gif") {
                $_SESSION['signup_msg'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            }
            // If all validations pass
            else {
                $nimgname = $mno . "." . $imgtype;
                if (move_uploaded_file($img, "uploads/" . $nimgname)) {
                    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);


                    if ($utype == "Consumer") {
                        $user = 1;
                        $query1 = "INSERT INTO consumer (fname, mname, lname, Gender, dob, phnno, country, state, city, address, photo, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt1 = $con->prepare($query1);
                        $stmt1->bind_param("sssssssssssss", $fname, $mname, $lname, $gender, $bdate, $mno, $country, $state, $city, $address, $nimgname, $emailid, $hashed_pass);
                        $stmt1->execute();
                        $stmt1->store_result();
                    } else {
                        $user = 2;
                        $query2 = "INSERT INTO provider (fname, mname, lname, Gender, dob, phnno, country, state, city, address, photo, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt2 = $con->prepare($query2);
                        $stmt2->bind_param("sssssssssssss", $fname, $mname, $lname, $gender, $bdate, $mno, $country, $state, $city, $address, $nimgname, $emailid, $hashed_pass);
                        $stmt2->execute();
                        $stmt2->store_result();
                    }

                    $query = "INSERT INTO users (email, password, user_type) values (?, ?, ?)";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param("sss", $emailid, $hashed_pass, $user);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->affected_rows > 0) {
                        $_SESSION['signup_msg'] = "Registration is Completed... <a href='Signin.php'>Click here to login</a>";
                        $_SESSION['signup_success'] = true;
                        // Clear form data on successful registration
                        unset($_SESSION['form_data']);
                    } else {
                        $_SESSION['signup_msg'] = "Registration Failed...";
                    }
                } else {
                    $_SESSION['signup_msg'] = "Sorry, there was an error uploading your file....";
                }
            }
        }
    }
    $con->close();

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Online HouseHold Service Portal</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
</head>
<body class="is-preload">

<div id="wrapper">

    <div id="main">
        <div class="inner">

            <header id="header">
                <a href="index.php" class="logo"><strong>homeservice</strong></a>
            </header>

            <h2 id="elements">Sign Up</h2>
            <hr class="minor" />
            <div class="row gtr-200">
                <script type="text/javascript">
                    function showpass(x) {
                        if (x.type == "password") {
                            x.type = "text";
                        } else {
                            x.type = "password";
                        }
                    }
                </script>

                <div class="col-6 col-12-medium">
                    <form name="f1" method="post" action="Signup.php" enctype="multipart/form-data">
                        <div class="row gtr-uniform">
                            <?php if (!empty($msg)): ?>
                                <div class="col-12">
                                    <h3 style="color: <?php echo (isset($_SESSION['signup_success']) && $_SESSION['signup_success'] === true ? 'green' : 'red'); ?>">
                                        <?php echo $msg; ?>
                                    </h3>
                                </div>
                                <?php
                                unset($_SESSION['signup_success']);
                            endif; ?>

                            <div class="col-4 col-12-xsmall">
                                <label>First Name :</label>
                                <input type="text" name="fname" id="fname" value="<?php echo isset($form_data['fname']) ? htmlspecialchars($form_data['fname']) : ''; ?>" placeholder="First Name" required="" />
                            </div>
                            <div class="col-4 col-12-xsmall">
                                <label>Middle Name :</label>
                                <input type="text" name="mname" id="mname" value="<?php echo isset($form_data['mname']) ? htmlspecialchars($form_data['mname']) : ''; ?>" placeholder="Middle Name"  />
                            </div>
                            <div class="col-4 col-12-xsmall">
                                <label>Last Name :</label>
                                <input type="text" name="lname" id="lname" value="<?php echo isset($form_data['lname']) ? htmlspecialchars($form_data['lname']) : ''; ?>" placeholder="Last Name" required="" />
                            </div>

                            <div class="col-3 col-12-small">
                                <label>Gender :</label>
                            </div>
                            <div class="col-3 col-12-small">
                                <input type="radio" id="gender-male" name="gender" value="Male" <?php echo (!isset($form_data['gender']) || (isset($form_data['gender']) && $form_data['gender'] == 'Male')) ? 'checked' : ''; ?>>
                                <label for="gender-male">Male</label>
                            </div>
                            <div class="col-3 col-12-small">
                                <input type="radio" id="gender-female" name="gender" value="Female" <?php echo (isset($form_data['gender']) && $form_data['gender'] == 'Female') ? 'checked' : ''; ?>>
                                <label for="gender-female">Female</label>
                            </div>
                            <div class="col-3 col-12-small">
                            </div>

                            <div class="col-12">
                                <label for="birth_date">Birth Date :</label>
                                <input type="date" name="bdate" id="bdate" value="<?php echo isset($form_data['bdate']) ? htmlspecialchars($form_data['bdate']) : ''; ?>" required="" placeholder="Birth Date" 
                                max="<?php echo date('Y-m-d', strtotime('-16 years')); ?>" />
                            </div>

                            <div class="col-12">
                                <label>Mobile No :</label>
                                <input type="text" name="mno" id="mno" value="<?php echo isset($form_data['mno']) ? htmlspecialchars($form_data['mno']) : ''; ?>" required="" placeholder="Mobile No" pattern="^(97|98)\d{8}$" title="Mobile number must be exactly 10 digits and start with 97 or 98" />
                            </div>

                            <div class="col-12">
                                <label>Country :</label>
                                <select name="country" id="country" required="">
                                    <option value="">- Select Country -</option>
                                    <option value="Nepal" <?php echo (isset($form_data['country']) && $form_data['country'] == 'Nepal') ? 'selected' : ''; ?>>Nepal</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label>State :</label>
                                <select name="state" id="state" required="">
                                    <option value="">- Select State -</option>
                                    <option value="Bagmati" <?php echo (isset($form_data['state']) && $form_data['state'] == 'Bagmati') ? 'selected' : ''; ?>>Bagmati</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label>City :</label>
                                <select name="city" id="city" required="">
                                    <option value="">- Select City -</option>
                                    <option value="Kathmandu" <?php echo (isset($form_data['city']) && $form_data['city'] == 'Kathmandu') ? 'selected' : ''; ?>>Kathmandu</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label>Address :</label>
                                <textarea name="address" id="address" placeholder="Enter Your Address" rows="3" required=""><?php echo isset($form_data['address']) ? htmlspecialchars($form_data['address']) : ''; ?></textarea>
                            </div>

                            <div class="col-12">
                                <label>Your Photo :</label>
                                <input type="file" name="photo" id="photo" value="" required="" placeholder="Select Your Photo" />
                            </div>

                            <div class="col-12">
                                <label>Email Id :</label>
                                <input type="email" name="emailid" id="emailid" value="<?php echo isset($form_data['emailid']) ? htmlspecialchars($form_data['emailid']) : ''; ?>" required="" placeholder="Email id" />
                            </div>

                            <div class="col-12">
                                <label>Password</label>
                                <input type="password" name="pass" id="pass" value="<?php echo isset($form_data['pass']) ? htmlspecialchars($form_data['pass']) : ''; ?>" required="" placeholder="Password (Atleast 8 Characters)" /><br>
                                <input type="checkbox" id="ch1" name="ch1" onclick="showpass(document.f1.pass)">
                                <label for="ch1">Show Password</label>
                            </div>

                            <div class="col-12">
                                <label>Confirm Password</label>
                                <input type="password" name="cpass" id="cpass" value="<?php echo isset($form_data['cpass']) ? htmlspecialchars($form_data['cpass']) : ''; ?>" required="" placeholder="Confirm Password" /><br>
                                <input type="checkbox" id="ch2" name="ch2" onclick="showpass(document.f1.cpass)">
                                <label for="ch2">Show Password</label>
                            </div>

                            <div class="col-12">
                                <label>Register As :</label>
                                <select name="utype" id="utype" required="">
                                    <option value="">- Register As -</option>
                                    <option value="Provider" <?php echo (isset($form_data['utype']) && $form_data['utype'] == 'Provider') ? 'selected' : ''; ?>>Provider</option>
                                    <option value="Consumer" <?php echo (isset($form_data['utype']) && $form_data['utype'] == 'Consumer') ? 'selected' : ''; ?>>Consumer</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <ul class="actions">
                                    <li><input type="submit" name="sbbtn" value="Submit" class="primary" /></li>
                                    <li><input type="reset" value="Reset" /></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="sidebar">
        <div class="inner">
            <?php include "menu.php"; ?>
        </div>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/browser.min.js"></script>
<script src="assets/js/breakpoints.min.js"></script>
<script src="assets/js/util.js"></script>
<script src="assets/js/main.js"></script>

</body>
</html>