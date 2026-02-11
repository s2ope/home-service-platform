<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Request Modal</title>
    <style>
        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Fixed position */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.4); /* Semi-transparent black background */
            padding-top: 50px; /* Padding for top */
        }

        /* Modal Content */
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Default width of modal */
            max-width: 600px; /* Max width */
            border-radius: 10px;
        }

        /* Close Button */
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 25px;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Service Details */
        .service-details {
            padding: 20px 0;
        }

        .service-details .detail {
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .service-details .detail strong {
            color: #333;
        }

        /* Button Container */
        .button-container {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .btn-pay {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-pay:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.65;
        }

        /* For small screens */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%; /* Adjust modal width for smaller screens */
            }
        }
    </style>
</head>
<body>
    <!-- Button to trigger modal -->
    <a href="javascript:void(0);" class="btn btn-view" onclick="openModal()">View More</a>

    <!-- Modal (will be triggered on click of "View More") -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Service Request Details</h2>
            <div class="service-details">
                <!-- Full Information: Add the fetched details here -->
                <div class="detail">
                    <strong>Provider:</strong> John Doe Smith<br>
                    <strong>Service Name:</strong> Plumbing Repair<br>
                    <strong>Address:</strong> 123 Main St, Springfield, IL, USA<br>
                    <strong>Phone Number:</strong> +1 (555) 123-4567<br>
                    <strong>Request Date & Time:</strong> Mar 30, 2025 at 10:30 AM<br>
                    <strong>Status:</strong> Pending<br>
                    <strong>Work Status:</strong> Not Started<br>
                    <strong>Payment Status:</strong> Pending<br>
                </div>
                <div class="button-container">
                    <button class="btn btn-pay primary" disabled>Pay Now</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to open the modal and populate it with the correct information
        function openModal() {
            var modal = document.getElementById("serviceModal");
            var span = document.getElementsByClassName("close")[0];

            // Open the modal
            modal.style.display = "block";

            // Close the modal when the 'X' is clicked
            span.onclick = function() {
                modal.style.display = "none";
            }

            // Close the modal if the user clicks anywhere outside of the modal
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
