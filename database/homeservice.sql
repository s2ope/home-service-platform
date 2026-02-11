-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 11, 2026 at 04:52 AM
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
(4, 'User', NULL, 'Bhai', '2010-01-05', 'Male', 'Nepal', 'Bagmati', 'Kathmandu', 'good', '9800000066', 'userbhai@gmail.com', '11112222', 'consumer_4_1770777727.png', '2026-01-08 14:43:16');

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
  `wallet` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `provider`
--

INSERT INTO `provider` (`pid`, `fname`, `mname`, `lname`, `dob`, `gender`, `country`, `state`, `city`, `address`, `phnno`, `email`, `photo`, `average_rating`, `created_at`, `password`, `wallet`) VALUES
(5, 'Admin', NULL, 'Bhai', '2010-02-03', 'Male', 'Nepal', 'Bagmati', 'Kathmandu', 'k', '9800000555', 'admin@gmail.com', '9800000555.png', 0.0, '2026-02-07 08:08:07', '11112222', 0.00),
(6, 'Provider', NULL, 'Bhai', '2010-02-01', 'Male', 'Nepal', 'Bagmati', 'Kathmandu', 'hlo', '9800002222', 'providerbhai@gmail.com', 'provider_6_1770774821.png', 0.0, '2026-02-07 08:36:47', '11112222', 0.00),
(7, 'Provider', NULL, 'Dai', '2010-02-10', 'Male', 'Nepal', 'Bagmati', 'Kathmandu', 'hio', '9800000111', 'providerdai@gmail.com', '9800000111.png', 0.0, '2026-02-11 00:24:46', '11112222', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(8, 'test', NULL, 'uploads/Screenshot 2026-01-11 at 15.48.35.png', '2026-02-10 02:20:04'),
(9, 'Cleaning', NULL, 'uploads/Screenshot 2026-02-10 at 09.23.26.png', '2026-02-10 11:38:19');

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
  `wallet` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_request`
--

INSERT INTO `service_request` (`srid`, `consumer_id`, `provider_id`, `service_id`, `req_date`, `req_time`, `status`, `work_status`, `payment_status`, `charge`, `last_modified`, `read_status_c`, `msgc`, `read_status`, `msgp`, `wallet`) VALUES
(1, 4, 3, 1, '2026-02-05', 'Morning(Before Noon)', '0', '0', '0', 0.00, '2026-02-05 10:29:11', 1, 'Your Request has been sent to the provider. The provider will respond ASAP', 0, 'You have a new request', 0.00),
(2, 4, 6, 1, '2026-02-11', 'Morning(Before Noon)', '0', '0', '0', 0.00, '2026-02-11 03:34:38', 0, 'Your Request has been sent to the provider. The provider will respond ASAP', 0, 'You have a new request', 0.00),
(3, 4, 6, 2, '2026-02-12', 'Morning(Before Noon)', '0', '0', '0', 0.00, '2026-02-11 03:37:12', 0, 'Your Request has been sent to the provider. The provider will respond ASAP', 0, 'You have a new request', 0.00);

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
(7, 'userbhai@gmail.com', '11112222', 1),
(10, 'admin@gmail.com', '11112222', 3),
(11, 'providerbhai@gmail.com', '11112222', 2),
(12, 'providerdai@gmail.com', '11112222', 2);

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
(3, 1, 6, 'Good', '2026-01-21', 'uploads/Screenshot 2026-01-13 at 19.57.30.png', 1, 0, 0, 'Cancellation requested', 'You have a new cancellation request', '2026-02-11 03:33:37'),
(4, 2, 6, 'Good', '2026-02-04', 'uploads/2 (1).png', 1, 0, 1, 'Your verification has been cancelled', 'You have a new verification request', '2026-02-11 03:36:32');

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
  ADD PRIMARY KEY (`srid`);

--
-- Indexes for table `service_table`
--
ALTER TABLE `service_table`
  ADD PRIMARY KEY (`Provider_emailid`,`Service_Id`);

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
  MODIFY `cid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `fid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `provider`
--
ALTER TABLE `provider`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `service_request`
--
ALTER TABLE `service_request`
  MODIFY `srid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `verification_request`
--
ALTER TABLE `verification_request`
  MODIFY `vrid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
