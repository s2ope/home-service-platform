-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 15, 2026 at 04:11 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `homeservice`
--

-- --------------------------------------------------------

--
-- Table structure for table `consumer`
--

CREATE TABLE `consumer` (
  `cid` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `mname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Male',
  `country` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phnno` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consumer`
--

INSERT INTO `consumer` (`cid`, `fname`, `mname`, `lname`, `dob`, `gender`, `country`, `state`, `city`, `address`, `phnno`, `email`, `password`, `photo`, `created_at`) VALUES
(6, 'User', NULL, 'Dai', '2010-02-12', 'Male', 'Nepal', 'Bagmati', 'Kathmandu', 'fsaf', '9800000360', 'userdai@gmail.com', '$2y$10$mYZpAurATHmmLIe9KEXYl.LGYPuNfwnHF0ZAGnZstmel0XnDl7L.2', '9800000360.png', '2026-02-12 11:33:10'),
(7, 'User', NULL, 'Bhai', '2010-02-15', 'Male', 'Nepal', 'Bagmati', 'Kathmandu', 'fd', '9800000086', 'userbhai@gmail.com', '$2y$10$Q9ucdGxABVR.2vQnl4tjC.dWKpTpYj.U8hqc4hSFgZ6YisZT1io52', '9800000086.png', '2026-02-15 08:02:38');

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `fid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`fid`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'S2ope', 'jptmax123@gmail.com', NULL, 'hi', '2026-02-05 10:15:03'),
(2, 'User  Bhai', 'userbhai@gmail.com', NULL, 'hi', '2026-02-05 10:22:23'),
(3, 'S2ope', 'jptmax123@gmail.com', NULL, 'hi', '2026-02-10 08:46:56');

-- --------------------------------------------------------

--
-- Table structure for table `provider`
--

CREATE TABLE `provider` (
  `pid` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `mname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Male',
  `country` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phnno` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `average_rating` decimal(2,1) DEFAULT 0.0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) NOT NULL,
  `rating_count` int(11) NOT NULL DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `provider`
--

INSERT INTO `provider` (`pid`, `fname`, `mname`, `lname`, `dob`, `gender`, `country`, `state`, `city`, `address`, `phnno`, `email`, `photo`, `average_rating`, `created_at`, `password`, `rating_count`, `latitude`, `longitude`) VALUES
(8, 'Admin', NULL, 'Dai', '2010-02-12', 'Male', 'Nepal', 'Bagmati', 'Kathmandu', 'gh', '9800220022', 'admin@gmail.com', '9800220022.png', 0.0, '2026-02-12 07:20:22', '$2y$10$0KaSZl1YtCdgVsXTBngQ4eOeQ2XmKp8Mrc44ilQE3ZtCByJsPna1W', 0, 57.70090581, 45.29373169),
(9, 'Provider', NULL, 'Dai', '2010-02-03', 'Male', 'Nepal', 'Bagmati', 'Kathmandu', 'ftet', '9811223344', 'providerdai@gmail.com', 'provider_9_1771136958.png', 4.0, '2026-02-12 11:19:54', '$2y$10$0dOJQ.NG8ufOt6ymLRnVIuMJxXJ4eLea1ptr8acLSXg5W2aNZtnam', 2, 27.71650945, 85.26897323),
(11, 'Provider', NULL, 'Bhai', '2010-02-09', 'Male', 'Nepal', 'Bagmati', 'Kathmandu', 'hi', '9800000667', 'providerbhai@gmail.com', '9800000667.png', 0.0, '2026-02-15 04:00:17', '$2y$10$QnYbal.pb1fMjYpAixItDeX8XPZSEOQ6.zyWD8p1Wx.gZYYIVTwvS', 0, 27.67022633, 85.42651330);

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `service_id` int(11) DEFAULT NULL,
  `rating` decimal(2,1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `request_id` int(11) DEFAULT NULL,
  `provider_id` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `user_id`, `service_id`, `rating`, `comment`, `created_at`, `request_id`, `provider_id`, `consumer_id`) VALUES
(1, 0, NULL, 5.0, '', '2026-02-12 03:11:33', NULL, 6, 4),
(2, 0, NULL, 5.0, '', '2026-02-12 03:11:59', NULL, 6, 4),
(3, 0, NULL, 3.0, '', '2026-02-12 03:12:52', NULL, 6, 4),
(4, 0, NULL, 5.0, '', '2026-02-12 03:13:31', NULL, 6, 4),
(5, 0, NULL, 2.0, '', '2026-02-12 04:08:26', NULL, 7, 4),
(6, 0, NULL, 5.0, '', '2026-02-12 11:24:49', NULL, 9, 5),
(7, 0, NULL, 3.0, '', '2026-02-14 11:57:37', NULL, 9, 6);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `sid` int(11) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`sid`, `sname`, `description`, `icon`, `created_at`) VALUES
(1, 'Plumbing', NULL, NULL, '2025-12-23 05:45:57'),
(2, 'Electrical', NULL, NULL, '2025-12-23 05:45:57'),
(3, 'House Cleaning', NULL, NULL, '2025-12-23 05:45:57'),
(4, 'Carpentry', NULL, NULL, '2025-12-23 05:45:57'),
(5, 'Painting', NULL, NULL, '2025-12-23 05:45:57'),
(6, 'Wiring', NULL, 'uploads/7012038750.jpg', '2026-02-07 08:20:42'),
(9, 'Cleaning', NULL, 'uploads/Screenshot 2026-02-10 at 09.23.26.png', '2026-02-10 11:38:19'),
(10, 'Home tuition', NULL, 'uploads/Screenshot 2026-02-12 at 13.11.36.png', '2026-02-12 11:27:00');

-- --------------------------------------------------------

--
-- Table structure for table `service_request`
--

CREATE TABLE `service_request` (
  `srid` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `req_date` date NOT NULL,
  `req_time` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `work_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `payment_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `read_status_c` tinyint(1) NOT NULL DEFAULT 0,
  `msgc` varchar(255) DEFAULT NULL,
  `read_status` tinyint(1) DEFAULT 0,
  `msgp` varchar(255) DEFAULT NULL,
  `wallet` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service_duration` int(11) NOT NULL DEFAULT 60 COMMENT 'Duration of service in minutes',
  `transaction_id` text DEFAULT NULL,
  `user_lat` decimal(10,8) DEFAULT NULL,
  `user_lng` decimal(11,8) DEFAULT NULL,
  `distance_km` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_request`
--

INSERT INTO `service_request` (`srid`, `consumer_id`, `provider_id`, `service_id`, `req_date`, `req_time`, `status`, `work_status`, `payment_status`, `charge`, `last_modified`, `read_status_c`, `msgc`, `read_status`, `msgp`, `wallet`, `service_duration`, `transaction_id`, `user_lat`, `user_lng`, `distance_km`) VALUES
(60, 7, 9, 5, '2026-02-20', '17:00', '1', '1', '0', 300.00, '2026-02-15 09:54:43', 0, 'Bill Amount Updated..Please Proceed with the payment..', 1, 'You have a new request', 0.00, 60, NULL, 27.70090581, 85.29373169, NULL),
(61, 6, 11, 5, '2026-02-16', '10:00', '1', '0', '0', 500.00, '2026-02-15 09:56:11', 0, 'Bill Amount Updated..Please Proceed with the payment..', 1, 'You have a new request', 0.00, 60, NULL, 27.70029786, 85.29201508, NULL),
(62, 6, 9, 5, '2026-02-17', '12:00', '1', '0', '0', 500.00, '2026-02-15 09:56:31', 0, 'Bill Amount Updated..Please Proceed with the payment..', 1, 'You have a new request', 0.00, 60, NULL, 0.00000000, 0.00000000, NULL),
(63, 6, 9, 5, '2026-02-16', '10:00', '0', '0', '1', 0.00, '2026-02-15 13:51:35', 0, 'Your Request has been sent to the provider. The provider will respond ASAP', 0, 'You have a new request', 0.00, 60, NULL, 27.69984190, 85.29321671, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `service_table`
--

CREATE TABLE `service_table` (
  `Provider_emailid` varchar(100) NOT NULL,
  `Service_Id` int(11) NOT NULL,
  `Service_Name` varchar(200) NOT NULL,
  `provider_name` varchar(200) NOT NULL,
  `Specification` varchar(200) NOT NULL,
  `Built_date` date NOT NULL,
  `country` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `address` varchar(1000) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `pic1` varchar(100) NOT NULL,
  `pic2` varchar(100) NOT NULL,
  `pic3` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `system_name` varchar(255) NOT NULL,
  `email1` varchar(255) NOT NULL,
  `phnno1` varchar(50) NOT NULL,
  `email2` varchar(255) DEFAULT NULL,
  `phnno2` varchar(50) DEFAULT NULL,
  `email3` varchar(255) DEFAULT NULL,
  `phnno3` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `system_name`, `email1`, `phnno1`, `email2`, `phnno2`, `email3`, `phnno3`, `created_at`) VALUES
(1, 'melo', 'jptmax123@gmail.com', '9800000055', '', '', '', '', '2026-02-12 03:33:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uid` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`uid`, `email`, `password`, `user_type`) VALUES
(14, 'admin@gmail.com', '$2y$10$0KaSZl1YtCdgVsXTBngQ4eOeQ2XmKp8Mrc44ilQE3ZtCByJsPna1W', 2),
(15, 'providerdai@gmail.com', '$2y$10$0dOJQ.NG8ufOt6ymLRnVIuMJxXJ4eLea1ptr8acLSXg5W2aNZtnam', 2),
(16, 'userdai@gmail.com', '$2y$10$mYZpAurATHmmLIe9KEXYl.LGYPuNfwnHF0ZAGnZstmel0XnDl7L.2', 1),
(17, 'providerbhai@gmail.com', '$2y$10$QnYbal.pb1fMjYpAixItDeX8XPZSEOQ6.zyWD8p1Wx.gZYYIVTwvS', 2),
(18, 'userbhai@gmail.com', '$2y$10$Q9ucdGxABVR.2vQnl4tjC.dWKpTpYj.U8hqc4hSFgZ6YisZT1io52', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_table`
--

CREATE TABLE `user_table` (
  `First_Name` varchar(100) NOT NULL,
  `Middle_Name` varchar(100) NOT NULL,
  `Last_Name` varchar(100) NOT NULL,
  `Gender` varchar(20) NOT NULL,
  `Birth_date` date NOT NULL,
  `Mobile_No` varchar(20) NOT NULL,
  `LL_No` varchar(20) NOT NULL,
  `Country` varchar(100) NOT NULL,
  `State` varchar(100) NOT NULL,
  `City` varchar(100) NOT NULL,
  `Address` varchar(1000) NOT NULL,
  `Pincode` varchar(10) NOT NULL,
  `Question` varchar(200) NOT NULL,
  `Answer` varchar(100) NOT NULL,
  `Photo` varchar(50) NOT NULL,
  `Email_id` varchar(100) NOT NULL,
  `Password` varchar(50) NOT NULL,
  `Register_As` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_table`
--

INSERT INTO `user_table` (`First_Name`, `Middle_Name`, `Last_Name`, `Gender`, `Birth_date`, `Mobile_No`, `LL_No`, `Country`, `State`, `City`, `Address`, `Pincode`, `Question`, `Answer`, `Photo`, `Email_id`, `Password`, `Register_As`) VALUES
('John', 'A.', 'Doe', 'Male', '1990-01-01', '+9779800000000', 'LL123456', 'Nepal', 'Bagmati', 'Lalitpur', '123, Example Street', '44700', 'What is your pet’s name?', 'Fluffy', 'default.png', 'dummyuser@example.com', 'password123', 'Consumer');

-- --------------------------------------------------------

--
-- Table structure for table `verification_request`
--

CREATE TABLE `verification_request` (
  `vrid` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `specification` varchar(255) DEFAULT NULL,
  `estd_date` date DEFAULT NULL,
  `certificate_pic` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `read_status` tinyint(1) NOT NULL DEFAULT 0,
  `read_status_a` tinyint(1) NOT NULL DEFAULT 0,
  `msg` varchar(255) NOT NULL DEFAULT 'New verification request submitted',
  `msga` varchar(255) NOT NULL DEFAULT 'You have a new verification request',
  `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_request`
--

INSERT INTO `verification_request` (`vrid`, `service_id`, `provider_id`, `specification`, `estd_date`, `certificate_pic`, `status`, `read_status`, `read_status_a`, `msg`, `msga`, `last_modified`) VALUES
(3, 1, 6, 'Good', '2026-01-21', 'uploads/Screenshot 2026-01-13 at 19.57.30.png', 3, 1, 1, 'Your verification has been cancelled', 'You have a new cancellation request', '2026-02-12 02:30:02'),
(4, 2, 6, 'Good', '2026-02-04', 'uploads/2 (1).png', 1, 1, 1, 'Your verification has been cancelled', 'You have a new verification request', '2026-02-12 02:30:03'),
(5, 4, 7, 'Good', '2026-02-12', 'uploads/Screenshot 2026-02-11 at 10.56.19.png', 1, 0, 1, 'Your verification request has been accepted', 'You have a new verification request', '2026-02-12 03:39:07'),
(6, 5, 9, 'Good', '2026-02-05', 'uploads/Screenshot 2026-02-12 at 13.12.04.png', 1, 0, 1, 'Your verification request has been accepted', 'You have a new verification request', '2026-02-12 11:20:45'),
(7, 5, 11, 'Good', '2026-02-15', 'uploads/Screenshot 2026-02-13 at 17.42.58.png', 1, 0, 1, 'Your verification request has been accepted', 'You have a new verification request', '2026-02-15 04:01:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `consumer`
--
ALTER TABLE `consumer`
  ADD PRIMARY KEY (`cid`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`fid`);

--
-- Indexes for table `provider`
--
ALTER TABLE `provider`
  ADD PRIMARY KEY (`pid`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`sid`);

--
-- Indexes for table `service_request`
--
ALTER TABLE `service_request`
  ADD PRIMARY KEY (`srid`),
  ADD KEY `fk_provider` (`provider_id`);

--
-- Indexes for table `service_table`
--
ALTER TABLE `service_table`
  ADD PRIMARY KEY (`Provider_emailid`,`Service_Id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_table`
--
ALTER TABLE `user_table`
  ADD PRIMARY KEY (`Email_id`),
  ADD UNIQUE KEY `Mobile_No` (`Mobile_No`);

--
-- Indexes for table `verification_request`
--
ALTER TABLE `verification_request`
  ADD PRIMARY KEY (`vrid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `consumer`
--
ALTER TABLE `consumer`
  MODIFY `cid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `fid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `provider`
--
ALTER TABLE `provider`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `service_request`
--
ALTER TABLE `service_request`
  MODIFY `srid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `verification_request`
--
ALTER TABLE `verification_request`
  MODIFY `vrid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `service_request`
--
ALTER TABLE `service_request`
  ADD CONSTRAINT `fk_provider` FOREIGN KEY (`provider_id`) REFERENCES `provider` (`pid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
