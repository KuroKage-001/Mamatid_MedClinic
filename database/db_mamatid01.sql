-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 15, 2025 at 12:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_mamatid01`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_name` varchar(60) NOT NULL,
  `phone_number` varchar(12) NOT NULL,
  `address` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(6) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('pending','approved','cancelled','completed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_name`, `phone_number`, `address`, `date_of_birth`, `gender`, `appointment_date`, `appointment_time`, `reason`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Vikir Baskerville', '09918719610', 'Mamatid', '2025-02-10', 'Male', '2025-04-15', '15:00:00', 'kkkkk', 'completed', 'Done', '2025-04-14 08:14:37', '2025-04-14 20:58:53'),
(2, 'Tess Maldove', '09918719610', 'Mamatid 01', '2025-04-14', 'Female', '2025-04-15', '12:00:00', 'checkup', 'completed', 'Done', '2025-04-14 08:16:53', '2025-04-14 20:59:22'),
(3, 'admin', '09918719610', 'admin', '2002-09-23', 'Male', '2025-04-15', '22:15:00', 'Checkup', 'completed', 'Okay!', '2025-04-14 09:09:42', '2025-04-14 09:28:31'),
(4, 'admin', '09918719610', 'admin', '2002-09-23', 'Male', '2025-04-20', '05:30:00', 'None', 'completed', 'Done\r\n', '2025-04-14 18:19:35', '2025-04-14 20:59:37'),
(5, 'admin', '09918719610', 'admin', '2002-09-23', 'Male', '2025-04-17', '10:11:00', 'Checkup', 'pending', NULL, '2025-04-14 21:07:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bp_monitoring`
--

CREATE TABLE `bp_monitoring` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `address` text NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `bp` varchar(20) NOT NULL,
  `alcohol` tinyint(1) NOT NULL DEFAULT 0,
  `smoke` tinyint(1) NOT NULL DEFAULT 0,
  `obese` tinyint(1) NOT NULL DEFAULT 0,
  `cp_number` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bp_monitoring`
--

INSERT INTO `bp_monitoring` (`id`, `name`, `date`, `address`, `sex`, `bp`, `alcohol`, `smoke`, `obese`, `cp_number`, `created_at`) VALUES
(1, 'Vikir Baskerville 01', '2025-04-14', 'Mamatid 01', 'Male', '100', 1, 1, 0, '09918719610', '2025-04-14 11:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `full_name` varchar(60) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `phone_number` varchar(12) NOT NULL,
  `address` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `full_name`, `email`, `password`, `phone_number`, `address`, `date_of_birth`, `gender`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES
(1, 'Vikir Baskerville', 'vikir@gmail.com', '0ecc8a29dd0f2feb66805502e89013b6', '09918719610', 'Mamatid 01', '2016-06-14', 'Male', '2025-04-14 08:25:43', '90301cd1e766b6ea4c21aed6196782cab92223e8ad34700c599b9a8a6319510d', '2025-04-14 12:02:00'),
(2, 'admin', 'admin@gmail.com', '0192023a7bbd73250516f069df18b500', '09918719610', 'admin', '2002-09-23', 'Male', '2025-04-14 08:54:37', NULL, NULL),
(3, 'Doc Leo', 'leomaresc853@gmail.com', 'b9818d72c31e400826d5f19ed8b7d36f', '09918719610', 'Baskerville Main Villa', '2020-07-15', 'Male', '2025-04-14 09:03:17', '371dc36b4be5583ae6075db18fa7296787c6460324850637b27537b6e1e11615', '2025-04-14 12:03:34'),
(4, 'Doc Vikir', 'docvikir@gmail.com', '8e6abe9f6ae4aabaf88ab9d3ecee4e41', '09918719610', 'Main Villa', '2018-06-06', 'Male', '2025-04-14 22:03:53', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deworming`
--

CREATE TABLE `deworming` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `age` int(11) NOT NULL,
  `birthday` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deworming`
--

INSERT INTO `deworming` (`id`, `name`, `date`, `age`, `birthday`, `created_at`) VALUES
(1, 'Vikir Baskerville 01', '2025-04-14', 50, '2025-04-14', '2025-04-14 10:32:04'),
(2, 'Pomeranian Baskerville', '2025-04-15', 50, '2025-04-07', '2025-04-14 22:37:11');

-- --------------------------------------------------------

--
-- Table structure for table `family_members`
--

CREATE TABLE `family_members` (
  `id` int(11) NOT NULL,
  `serial_number` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `family_members`
--

INSERT INTO `family_members` (`id`, `serial_number`, `name`, `date`, `created_at`) VALUES
(1, '1', 'Vikir Baskerville 01', '2025-04-14', '2025-04-14 10:12:51'),
(2, '2', 'Pomeranian Baskerville', '2025-04-15', '2025-04-14 22:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `family_planning`
--

CREATE TABLE `family_planning` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `age` int(11) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `family_planning`
--

INSERT INTO `family_planning` (`id`, `name`, `date`, `age`, `address`, `created_at`) VALUES
(1, 'Vikir Baskerville 01', '2025-04-15', 50, 'Mamatid 01', '2025-04-14 18:02:33');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `medicine_name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `medicine_name`) VALUES
(1, 'Amoxicillin'),
(4, 'Antibiotic'),
(5, 'Antihistamine'),
(6, 'Atorvastatin'),
(3, 'Losartan'),
(2, 'Mefenamic'),
(7, 'Oxymetazoline');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_details`
--

CREATE TABLE `medicine_details` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `packing` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `medicine_details`
--

INSERT INTO `medicine_details` (`id`, `medicine_id`, `packing`) VALUES
(1, 1, '50'),
(2, 4, '50'),
(3, 5, '50'),
(4, 6, '25'),
(5, 3, '80'),
(6, 2, '100'),
(7, 7, '25');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_inventory`
--

CREATE TABLE `medicine_inventory` (
  `id` int(11) NOT NULL,
  `medicine_details_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `medicine_inventory`
--

INSERT INTO `medicine_inventory` (`id`, `medicine_details_id`, `quantity`, `batch_number`, `expiry_date`, `unit_price`, `created_at`, `updated_at`) VALUES
(1, 2, 50, '1', '2025-04-15', 10.00, '2025-04-14 09:43:58', '2025-04-14 09:43:58');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_inventory_transactions`
--

CREATE TABLE `medicine_inventory_transactions` (
  `id` int(11) NOT NULL,
  `medicine_inventory_id` int(11) NOT NULL,
  `transaction_type` enum('IN','OUT') NOT NULL,
  `quantity` int(11) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `reference_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `medicine_inventory_transactions`
--

INSERT INTO `medicine_inventory_transactions` (`id`, `medicine_inventory_id`, `transaction_type`, `quantity`, `transaction_date`, `notes`, `reference_id`) VALUES
(1, 1, 'IN', 50, '2025-04-14 09:43:58', 'Initial stock entry', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `medicine_movement_view`
-- (See below for the actual view)
--
CREATE TABLE `medicine_movement_view` (
`medicine_name` varchar(60)
,`packing` varchar(60)
,`total_transactions` bigint(21)
,`total_quantity_out` decimal(32,0)
,`avg_quantity_per_transaction` decimal(14,4)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `medicine_stock_view`
-- (See below for the actual view)
--
CREATE TABLE `medicine_stock_view` (
`medicine_name` varchar(60)
,`packing` varchar(60)
,`batch_number` varchar(50)
,`current_stock` int(11)
,`expiry_date` date
,`stock_status` varchar(6)
,`days_until_expiry` int(7)
);

-- --------------------------------------------------------

--
-- Table structure for table `random_blood_sugar`
--

CREATE TABLE `random_blood_sugar` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `address` text NOT NULL,
  `age` int(11) NOT NULL,
  `result` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `random_blood_sugar`
--

INSERT INTO `random_blood_sugar` (`id`, `name`, `date`, `address`, `age`, `result`, `created_at`) VALUES
(1, 'Vikir Baskerville 01', '2025-04-14', 'Mamatid 01', 50, 'None', '2025-04-14 10:28:13'),
(2, 'Pomeranian Baskerville', '2025-04-15', 'Mamatid 01', 50, 'None', '2025-04-14 22:36:52');

-- --------------------------------------------------------

--
-- Table structure for table `tetanus_toxoid`
--

CREATE TABLE `tetanus_toxoid` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `address` text NOT NULL,
  `age` int(11) NOT NULL,
  `diagnosis` text NOT NULL,
  `remarks` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tetanus_toxoid`
--

INSERT INTO `tetanus_toxoid` (`id`, `name`, `date`, `address`, `age`, `diagnosis`, `remarks`, `created_at`) VALUES
(1, 'Vikir Baskerville 01', '2025-04-14', 'Mamatid 01', 50, 'None', 'None', '2025-04-14 10:34:23');

-- --------------------------------------------------------

--
-- Table structure for table `time_in_logs`
--

CREATE TABLE `time_in_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `time_in` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_in_logs`
--

INSERT INTO `time_in_logs` (`id`, `user_id`, `log_date`, `time_in`) VALUES
(4, 3, '2025-03-16', '03:02:04');

--
-- Triggers `time_in_logs`
--
DELIMITER $$
CREATE TRIGGER `after_time_in_insert` AFTER INSERT ON `time_in_logs` FOR EACH ROW BEGIN
  INSERT INTO `time_logs` (`user_id`, `log_date`, `time_in`, `time_out`, `total_hours`)
  VALUES (NEW.user_id, NEW.log_date, NEW.time_in, NULL, 0.00)
  ON DUPLICATE KEY UPDATE `time_in` = NEW.time_in;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `time_logs`
--

INSERT INTO `time_logs` (`id`, `user_id`, `log_date`, `time_in`, `time_out`, `total_hours`) VALUES
(12, 3, '2025-03-16', '03:02:04', '03:03:33', 0.02);

-- --------------------------------------------------------

--
-- Table structure for table `time_out_logs`
--

CREATE TABLE `time_out_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `time_out` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_out_logs`
--

INSERT INTO `time_out_logs` (`id`, `user_id`, `log_date`, `time_out`) VALUES
(2, 3, '2025-03-16', '03:03:33');

--
-- Triggers `time_out_logs`
--
DELIMITER $$
CREATE TRIGGER `after_time_out_insert` AFTER INSERT ON `time_out_logs` FOR EACH ROW BEGIN
  DECLARE v_time_in TIME;
  -- Get the corresponding Time In
  SELECT `time_in` INTO v_time_in
  FROM `time_in_logs`
  WHERE `user_id` = NEW.user_id AND `log_date` = NEW.log_date;
  -- Update time_logs with Time Out and calculate total hours
  INSERT INTO `time_logs` (`user_id`, `log_date`, `time_in`, `time_out`, `total_hours`)
  VALUES (NEW.user_id, NEW.log_date, v_time_in, NEW.time_out, 
          ROUND(TIMESTAMPDIFF(SECOND, v_time_in, NEW.time_out) / 3600, 2))
  ON DUPLICATE KEY UPDATE 
    `time_out` = NEW.time_out,
    `total_hours` = ROUND(TIMESTAMPDIFF(SECOND, `time_in`, NEW.time_out) / 3600, 2);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `display_name` varchar(30) NOT NULL,
  `user_name` varchar(30) NOT NULL,
  `password` varchar(100) NOT NULL,
  `profile_picture` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `display_name`, `user_name`, `password`, `profile_picture`) VALUES
(1, 'Administrator', 'admin', '0192023a7bbd73250516f069df18b500', '1656551981avatar.png '),
(2, 'John Doe', 'jdoe', '9c86d448e84d4ba23eb089e0b5160207', '1656551999avatar_.png'),
(3, 'Dr. Leo', 'Doc Leo', '489a0257b661b64dac6618593232b1de', '1741792677IMG_20250131_221137_601.jpg'),
(4, 'Dr. Vikir', 'Doc Vikir', '2f3763b2a0daa3ba0baa8f23e4206048', '1741794222vikir.png');

-- --------------------------------------------------------

--
-- Structure for view `medicine_movement_view`
--
DROP TABLE IF EXISTS `medicine_movement_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `medicine_movement_view`  AS SELECT `m`.`medicine_name` AS `medicine_name`, `md`.`packing` AS `packing`, count(case when `mit`.`transaction_type` = 'OUT' then 1 end) AS `total_transactions`, sum(case when `mit`.`transaction_type` = 'OUT' then `mit`.`quantity` else 0 end) AS `total_quantity_out`, avg(case when `mit`.`transaction_type` = 'OUT' then `mit`.`quantity` else NULL end) AS `avg_quantity_per_transaction` FROM (((`medicines` `m` join `medicine_details` `md` on(`m`.`id` = `md`.`medicine_id`)) join `medicine_inventory` `mi` on(`md`.`id` = `mi`.`medicine_details_id`)) left join `medicine_inventory_transactions` `mit` on(`mi`.`id` = `mit`.`medicine_inventory_id`)) GROUP BY `m`.`medicine_name`, `md`.`packing` ;

-- --------------------------------------------------------

--
-- Structure for view `medicine_stock_view`
--
DROP TABLE IF EXISTS `medicine_stock_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `medicine_stock_view`  AS SELECT `m`.`medicine_name` AS `medicine_name`, `md`.`packing` AS `packing`, `mi`.`batch_number` AS `batch_number`, `mi`.`quantity` AS `current_stock`, `mi`.`expiry_date` AS `expiry_date`, CASE WHEN `mi`.`quantity` <= 10 THEN 'LOW' WHEN `mi`.`quantity` <= 20 THEN 'MEDIUM' ELSE 'GOOD' END AS `stock_status`, to_days(`mi`.`expiry_date`) - to_days(curdate()) AS `days_until_expiry` FROM ((`medicine_inventory` `mi` join `medicine_details` `md` on(`mi`.`medicine_details_id` = `md`.`id`)) join `medicines` `m` on(`md`.`medicine_id` = `m`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bp_monitoring`
--
ALTER TABLE `bp_monitoring`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `deworming`
--
ALTER TABLE `deworming`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `family_members`
--
ALTER TABLE `family_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `family_planning`
--
ALTER TABLE `family_planning`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medicine_name` (`medicine_name`);

--
-- Indexes for table `medicine_details`
--
ALTER TABLE `medicine_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_medicine_details_medicine_id` (`medicine_id`);

--
-- Indexes for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_details_id` (`medicine_details_id`);

--
-- Indexes for table `medicine_inventory_transactions`
--
ALTER TABLE `medicine_inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_inventory_id` (`medicine_inventory_id`);

--
-- Indexes for table `random_blood_sugar`
--
ALTER TABLE `random_blood_sugar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tetanus_toxoid`
--
ALTER TABLE `tetanus_toxoid`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_in_logs`
--
ALTER TABLE `time_in_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_time_in` (`user_id`,`log_date`);

--
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`log_date`),
  ADD KEY `fk_time_logs_user_id` (`user_id`);

--
-- Indexes for table `time_out_logs`
--
ALTER TABLE `time_out_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_time_out` (`user_id`,`log_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_name` (`user_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bp_monitoring`
--
ALTER TABLE `bp_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deworming`
--
ALTER TABLE `deworming`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `family_members`
--
ALTER TABLE `family_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `family_planning`
--
ALTER TABLE `family_planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `medicine_details`
--
ALTER TABLE `medicine_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `medicine_inventory_transactions`
--
ALTER TABLE `medicine_inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `random_blood_sugar`
--
ALTER TABLE `random_blood_sugar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tetanus_toxoid`
--
ALTER TABLE `tetanus_toxoid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `time_in_logs`
--
ALTER TABLE `time_in_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `time_out_logs`
--
ALTER TABLE `time_out_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `medicine_details`
--
ALTER TABLE `medicine_details`
  ADD CONSTRAINT `fk_medicine_details_medicine_id` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  ADD CONSTRAINT `fk_inventory_medicine_details` FOREIGN KEY (`medicine_details_id`) REFERENCES `medicine_details` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medicine_inventory_transactions`
--
ALTER TABLE `medicine_inventory_transactions`
  ADD CONSTRAINT `fk_transactions_inventory` FOREIGN KEY (`medicine_inventory_id`) REFERENCES `medicine_inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `time_in_logs`
--
ALTER TABLE `time_in_logs`
  ADD CONSTRAINT `time_in_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD CONSTRAINT `fk_time_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `time_out_logs`
--
ALTER TABLE `time_out_logs`
  ADD CONSTRAINT `time_out_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
