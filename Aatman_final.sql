-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 27, 2025 at 06:23 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Aatman_final`
--

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` enum('Education','Health','Environment','Women','Other') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `contact_person_name` varchar(255) DEFAULT NULL,
  `contact_person_phone` varchar(20) DEFAULT NULL,
  `status` enum('active','completed','disabled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaigns`
--

INSERT INTO `campaigns` (`id`, `ngo_id`, `title`, `description`, `category`, `start_date`, `end_date`, `contact_person_name`, `contact_person_phone`, `status`, `created_at`) VALUES
(1, 11, 'peace', 'good mental health', 'Health', '2025-10-23', '2025-10-23', NULL, NULL, 'completed', '2025-10-24 10:01:18'),
(2, 11, 'hi', 'trial', 'Education', '2025-10-24', '2025-10-25', NULL, NULL, 'active', '2025-10-25 17:55:51');

-- --------------------------------------------------------

--
-- Table structure for table `donor_details`
--

CREATE TABLE `donor_details` (
  `id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `donation_type` enum('Money','Goods','Services','Other') NOT NULL,
  `preferred_category` enum('Education','Health','Environment','Women','Other') DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donor_details`
--

INSERT INTO `donor_details` (`id`, `address`, `city`, `state`, `donation_type`, `preferred_category`, `status`, `created_at`) VALUES
(9, 'B-18 Aangan bunglows', 'Vadodara', 'Gujarat', 'Money', 'Health', 'active', '2025-10-23 17:56:28'),
(13, 'B-18 Aangan bunglows', 'Vadodara', 'Gujarat', 'Goods', 'Health', 'active', '2025-10-27 10:06:17'),
(15, 'B-18 Aangan bunglows', 'Vadodara', 'Gujarat', 'Money', 'Health', 'active', '2025-10-27 10:20:26');

-- --------------------------------------------------------

--
-- Table structure for table `NGO_details`
--

CREATE TABLE `NGO_details` (
  `id` int(11) NOT NULL,
  `organisation_name` varchar(255) NOT NULL,
  `registration_number` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` enum('Education','Health','Environment','Women','Other') NOT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `NGO_details`
--

INSERT INTO `NGO_details` (`id`, `organisation_name`, `registration_number`, `address`, `city`, `state`, `description`, `category`, `status`, `created_at`) VALUES
(11, 'aura', '98754242', 'B-18 Aangan bunglows', 'Vadodara', 'Gujarat', 'hi', 'Education', 'active', '2025-10-23 17:58:49');

-- --------------------------------------------------------

--
-- Table structure for table `people_in_need_details`
--

CREATE TABLE `people_in_need_details` (
  `id` int(11) NOT NULL,
  `contact_person_name` varchar(255) NOT NULL,
  `contact_person_phone` varchar(20) NOT NULL,
  `alt_phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `people_in_need_details`
--

INSERT INTO `people_in_need_details` (`id`, `contact_person_name`, `contact_person_phone`, `alt_phone`, `address`, `city`, `state`, `status`, `created_at`) VALUES
(12, 'suzzan', '657832456', '4567890123', 'B-18 Aangan bunglows', 'Vadodara', 'Gujarat', 'active', '2025-10-23 18:00:06'),
(14, 'Suzzan Rafiquahemad Patel', '09898006097', '09898006097', 'B-18 Aangan bunglows', 'Vadodara', 'Gujarat', 'active', '2025-10-27 10:18:25');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_role` enum('ngo','need') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `request_type` enum('Food','Shelter','Clothes','Medicine','Education','Other') NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit` varchar(50) DEFAULT 'items',
  `location` varchar(255) NOT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `contact_person_name` varchar(100) NOT NULL,
  `contact_person_phone` varchar(20) NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `user_id`, `user_name`, `user_role`, `title`, `description`, `request_type`, `quantity`, `unit`, `location`, `priority`, `contact_person_name`, `contact_person_phone`, `status`, `created_at`) VALUES
(2, 12, 'needy', 'need', 'food', 'pizza', 'Food', 10, 'pieces', '0', 'Medium', 'suzzan', '9898006097', 'pending', '2025-10-24 15:21:15'),
(3, 12, 'needy', 'need', 'hi', 'trial 2 ', 'Clothes', 10, 'pieces', '0', 'Medium', 'Suzzan Rafiquahemad Patel', '4903490220', 'pending', '2025-10-27 10:52:51'),
(4, 12, 'needy', 'need', 'hi', 'trial 3 ', 'Clothes', 100, 'fax', '0', 'Medium', 'Suzzan Rafiquahemad Patel', '2134567890', 'pending', '2025-10-27 11:56:45'),
(5, 12, 'needy', 'need', 'di', 'trial 4 ', 'Medicine', 10, '100', '0', 'Low', 'suzzan', '9898006097', 'pending', '2025-10-27 12:01:04'),
(6, 12, 'needy', 'need', 'water', 'need water', 'Food', 100, 'ml', '0', 'High', 'suzzan', '9898006097', 'pending', '2025-10-27 12:04:26'),
(7, 12, 'needy', 'need', 'trial 4', 'food ', 'Clothes', 100, 'ml', 'vadodara', 'Low', 'suzzan', '9898006097', 'completed', '2025-10-27 12:05:45'),
(8, 14, 'dp', 'need', 'hfh', 'vhvf', 'Food', 79, 'ml', 'Vadodara', 'Low', 'fduo', '9898006097', 'pending', '2025-10-27 15:49:02'),
(9, 12, 'needy', 'need', 'dfbdsfbv', 'sdivndsio', 'Clothes', 10, 'unit', 'Vadodara', 'Medium', 'suzzan', '9898006097', 'pending', '2025-10-27 16:01:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(89) NOT NULL,
  `password` varchar(64) NOT NULL,
  `role` enum('ngo','donor','need','volunteer','admin') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `created_at`, `status`) VALUES
(9, 'donor ', 'donor1@gmail.com', '$2y$10$JQFn5uCdcca4s8cfO6F7..3vVcgnUbyG7G38axHl45pb0k9R96BqC', 'donor', '9898006097', '2025-10-23 23:26:19', 'active'),
(10, 'volunteer ', 'volunteer@gmail.com', '$2y$10$LkLR.TlTVkf62wUZjnxFY.rfxu5sMUNcGMRmGRcIPOo14a91aKhNe', 'volunteer', '9898006097', '2025-10-23 23:26:53', 'active'),
(11, 'ngo ', 'ngo@gmail.com', '$2y$10$1zqztpVCEHxfLycIRVWfle1o8CubZtAuMXO7FQgKoW/BqLMc8npl2', 'ngo', '9898006097', '2025-10-23 23:28:35', 'active'),
(12, 'needy', 'needy@gmail.com', '$2y$10$GxNSugVEz3JjXSQ9RyuxlOFabX0Y8v/jqGVq2yJA9SLDcZoUrhUo2', 'need', '9898006097', '2025-10-23 23:29:50', 'active'),
(13, 'Suzzan Rafiquahemad Patel', 'donorr1@gmail.com', '$2y$10$ZHKOQ7wMCr5AKJpCRACmfedYzMjOb/yEzz4VlHmA444GpTmX/CHo.', 'donor', '09898006097', '2025-10-27 15:36:06', 'active'),
(14, 'dp', 'dp@gmai.com', '$2y$10$7cnIM2SXsXc80J3Q3MuXJ.atCXGLlWA44g3CW6VRy9pdN2hEh.1hi', 'need', '9898006097', '2025-10-27 15:48:13', 'active'),
(15, 'don', 'dom@gmail.com', '$2y$10$gkBo0Og/7tG5jGcYNsmycujHSlUYmJEy4HwIwqySxuNcU0flvjjei', 'donor', '09898006097', '2025-10-27 15:50:06', 'active'),
(16, 'Suzzan Rafiquahemad Patel', '34s@gmail.com', '$2y$10$M70H6jzubP5KToXbOKi.dO3AyIiZeJob0rDkggD2lQXp0vn3nCs.m', 'need', '09898006097', '2025-10-27 16:47:33', 'active'),
(18, 'Suzzan Rafiquahemad Patel', '45fidfn@gmail.com', '$2y$10$/.nSTlJDtTVbO9EUEBweFONhDDG6qA7om.wBcG7ND89EVC1NFukRO', 'ngo', '09898006097', '2025-10-27 17:26:14', 'active'),
(19, 'Suzzan Rafiquahemad Patel', 'uigfhs@gmail.com', '$2y$10$Yqcl5IHkJ0t.XSagPzxH..79TLnP3ALQ.yvbkFvXj5aLxteNOAi0O', 'need', '09898006097', '2025-10-27 17:32:04', 'active'),
(20, 'Suzzan Rafiquahemad Patel', 'uigfs@gmail.com', '$2y$10$t5Ovf8zKNKctTUgTj88ORuXIxXFaOOrqWa46C/bG1f0RzjsTXdECK', 'volunteer', '09898006097', '2025-10-27 17:34:27', 'active'),
(21, 'Suzzan Rafiquahemad Patel', 'uigs@gmail.com', '$2y$10$t670MlrMSLMqy8F1p5RtieK5VdoXO5UnIeFMAQZGoeMJURicQN472', 'donor', '09898006097', '2025-10-27 17:34:35', 'active'),
(22, 'ildbfaiosd', 'buifuf@gmail.com', '$2y$10$/PQP9CNB0q10/Z/YXsy/H.8jvGy0ZrSJd9olmNhSzB.8.QI0ugvYq', 'volunteer', '09898006097', '2025-10-27 17:38:50', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_details`
--

CREATE TABLE `volunteer_details` (
  `id` int(11) NOT NULL,
  `alt_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `availability` varchar(255) DEFAULT NULL,
  `preferred_category` varchar(255) DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `volunteer_details`
--

INSERT INTO `volunteer_details` (`id`, `alt_phone`, `emergency_contact_name`, `emergency_contact_phone`, `address`, `city`, `state`, `skills`, `availability`, `preferred_category`, `status`, `created_at`) VALUES
(10, '9898006097', 'Suzzan Rafiquahemad Patel', '09898006097', 'B-18 Aangan bunglows', 'Vadodara', 'Gujarat', 'sssss', 'mondays - 7 to 9', 'Education', 'active', '2025-10-23 17:57:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ngo_id` (`ngo_id`);

--
-- Indexes for table `donor_details`
--
ALTER TABLE `donor_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `NGO_details`
--
ALTER TABLE `NGO_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`);

--
-- Indexes for table `people_in_need_details`
--
ALTER TABLE `people_in_need_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `volunteer_details`
--
ALTER TABLE `volunteer_details`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`ngo_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `donor_details`
--
ALTER TABLE `donor_details`
  ADD CONSTRAINT `donor_details_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `NGO_details`
--
ALTER TABLE `NGO_details`
  ADD CONSTRAINT `ngo_details_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `people_in_need_details`
--
ALTER TABLE `people_in_need_details`
  ADD CONSTRAINT `people_in_need_details_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `volunteer_details`
--
ALTER TABLE `volunteer_details`
  ADD CONSTRAINT `volunteer_details_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
