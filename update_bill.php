<?php
session_start();

// Check if the provider is logged in
if (!isset($_SESSION["provider_utype"]) || $_SESSION["provider_utype"] != "Provider") {
    header("location:Signin.php");
    exit();
}

// Database connection
$con = mysqli_connect("localhost", "root", "", "homeservice");
if (mysqli_connect_errno() > 0) {
    echo mysqli_connect_error();
    exit();
}

// Check if the form is submitted to update the bill
if (isset($_POST['update_bill'])) {
    $srid = $_POST['srid'];
    $bill_amount = $_POST['bill_amount'];
    $msgc = "Bill Amount Updated..Please Proceed with the payment..";

    if ($bill_amount < 100) {
        // Redirect with an error message if the bill amount is less than 100
        header("Location: update_bill.php?srid=" . $srid . "&msg=invalid_amount");
        exit();
    }

    // Update the bill amount, last_modified, and read_status in the database
    $query = "UPDATE service_request SET charge = ?, last_modified = CURRENT_TIMESTAMP, read_status_c = 0, msgc = ? WHERE srid = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("dsi", $bill_amount, $msgc, $srid);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Redirect to view_request.php with success message
        header("Location: view_request.php?bill_update=success&srid=" . $srid);
        exit();
    } else {
        // Redirect to view_request.php with error message
        header("Location: view_request.php?msg=error");
        exit();
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Update Bill - Ghar Sewa</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <style>
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .form-group input[type="number"] {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-width: 300px;
        }
    </style>
</head>
<body class="is-preload">

<div id="wrapper">

    <div id="main">
        <div class="inner">

            <header id="header">
                <a href="Mservices.php" class="logo"><strong>Ghar Sewa</strong></a>
            </header>

            <h2>Update Bill</h2>
                <hr class="minor" />
                <h3>Consult with the Consumer briefly before entering the bill amount.Once entered,It cannot be Changed.</h3>
                <div class="col-12">
                    <?php if (isset($_GET['msg'])): ?>
                        <?php if ($_GET['msg'] == "success"): ?>
                            <h3 style="color:green">Bill updated successfully.</h3>
                        <?php elseif ($_GET['msg'] == "error"): ?>
                            <h3 style="color:red">Error updating bill.</h3>
                        <?php elseif ($_GET['msg'] == "invalid_amount"): ?>
                            <h3 style="color:red">Bill amount must be greater than or equal to 100.</h3>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <form method="post" action="update_bill.php">
                        <div class="form-group">
                            <label for="bill_amount">Enter Bill Amount</label>
                            <input type="number" name="bill_amount" id="bill_amount" step="0.01" min="100" required />
                        </div>
                        <input type="hidden" name="srid" value="<?php echo isset($_GET['srid']) ? htmlspecialchars($_GET['srid']) : ''; ?>" />
                        <input type="submit" name="update_bill" value="Update Bill" class="button primary" />
                        <a href="view_request.php" class="button">Cancel</a>
                    </form>
                </div>

            </section>

        </div>
    </div>

    <div id="sidebar">
        <div class="inner">
            <?php include "pmenu.php"; ?>
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
<?php
$con->close();
?>