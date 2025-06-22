-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2025 at 01:29 AM
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
  `schedule_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_name`, `phone_number`, `address`, `date_of_birth`, `gender`, `appointment_date`, `appointment_time`, `reason`, `status`, `notes`, `schedule_id`, `doctor_id`, `created_at`, `updated_at`) VALUES
(23, 'admin04', '09918719610', 'Main Baskerville Villa', '2010-09-23', 'Male', '2025-06-24', '10:30:00', 'note 01', 'pending', NULL, 8, 19, '2025-06-21 21:41:49', NULL);

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `after_appointment_delete` AFTER DELETE ON `appointments` FOR EACH ROW BEGIN
    -- Update the slot when an appointment is deleted
    UPDATE appointment_slots
    SET is_booked = 0, appointment_id = NULL
    WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_appointment_insert` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
    DECLARE slot_id INT;
    
    -- Check if slot exists
    SELECT id INTO slot_id FROM appointment_slots 
    WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time
    LIMIT 1;
    
    IF slot_id IS NULL THEN
        -- Create slot if it doesn't exist
        INSERT INTO appointment_slots (schedule_id, slot_time, is_booked, appointment_id)
        VALUES (NEW.schedule_id, NEW.appointment_time, 1, NEW.id);
    ELSE
        -- Update existing slot
        UPDATE appointment_slots 
        SET is_booked = 1, appointment_id = NEW.id
        WHERE id = slot_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_appointment_update` AFTER UPDATE ON `appointments` FOR EACH ROW BEGIN
    -- If status changed to cancelled, update the slot
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
        UPDATE appointment_slots
        SET is_booked = 0, appointment_id = NULL
        WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
    END IF;
    
    -- If time slot changed, update both old and new slots
    IF NEW.appointment_time != OLD.appointment_time THEN
        -- Update old slot
        UPDATE appointment_slots
        SET is_booked = 0, appointment_id = NULL
        WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
        
        -- Update new slot
        UPDATE appointment_slots
        SET is_booked = 1, appointment_id = NEW.id
        WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_slots`
--

CREATE TABLE `appointment_slots` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `slot_time` time NOT NULL,
  `is_booked` tinyint(1) NOT NULL DEFAULT 0,
  `appointment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_slots`
--

INSERT INTO `appointment_slots` (`id`, `schedule_id`, `slot_time`, `is_booked`, `appointment_id`) VALUES
(1, 8, '10:30:00', 0, 23),
(6, 8, '11:00:00', 0, NULL),
(8, 8, '11:30:00', 0, NULL),
(10, 8, '12:00:00', 0, NULL),
(12, 8, '12:30:00', 0, NULL),
(14, 8, '13:00:00', 0, NULL),
(16, 8, '13:30:00', 0, NULL),
(18, 8, '14:00:00', 0, NULL),
(20, 8, '14:30:00', 0, NULL),
(22, 8, '15:00:00', 0, NULL),
(24, 8, '15:30:00', 0, NULL),
(26, 8, '16:00:00', 0, NULL),
(28, 8, '16:30:00', 0, NULL),
(29, 8, '17:00:00', 0, NULL);

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
(2, 'Pomeranian Baskerville', '2025-04-17', 'Baskerville Main House 1', 'Female', '100', 1, 1, 1, '09918719610', '2025-04-17 10:07:19'),
(3, 'Vikir Baskerville', '2025-04-17', 'Baskerville Main House', 'Male', '120', 1, 0, 0, '09918719610', '2025-04-17 10:07:43'),
(5, 'Yeomra Baskerville', '2025-04-19', 'Hell Main House', 'Male', '50/20', 1, 1, 0, '00000000000', '2025-04-19 16:45:13'),
(6, 'Osiris Baskerville', '2025-04-19', 'Baskerville Main House 2', 'Male', '70/80', 0, 1, 0, '88888888888', '2025-04-19 16:46:16');

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
(2, 'admin', 'admin@gmail.com', '0192023a7bbd73250516f069df18b500', '09918719610', 'admin', '2002-09-23', 'Male', '2025-04-14 08:54:37', NULL, NULL),
(5, 'admin04', 'admin04@gmail.com', '7488e331b8b64e5794da3fa4eb10ad5d', '09918719610', 'Main Baskerville Villa', '2010-09-23', 'Male', '2025-04-17 08:36:04', NULL, NULL),
(6, 'admin01', 'admin01@gmail.com', '7488e331b8b64e5794da3fa4eb10ad5d', '09676667567', 'Main House Baskerville01', '2009-09-22', 'Female', '2025-04-17 08:37:27', NULL, NULL);

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
(3, 'Vikir Baskerville', '2025-04-17', 20, '2000-09-22', '2025-04-17 10:05:00'),
(4, 'Pomeranian Baskerville', '2025-04-17', 20, '2010-12-15', '2025-04-17 10:05:32'),
(5, 'Yeomra Baskerville', '2025-04-20', 20, '2025-04-20', '2025-04-19 18:04:07'),
(6, 'Osiris Baskerville', '2025-04-20', 90, '2025-04-20', '2025-04-19 18:04:27');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `time_slot_minutes` int(11) NOT NULL DEFAULT 30,
  `max_patients` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `approval_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`id`, `doctor_id`, `schedule_date`, `start_time`, `end_time`, `time_slot_minutes`, `max_patients`, `notes`, `created_at`, `updated_at`, `is_approved`, `approval_notes`) VALUES
(8, 19, '2025-06-24', '10:30:00', '17:30:00', 30, 10, 'checkup 02', '2025-06-21 19:35:16', '2025-06-21 19:35:51', 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `family_members`
--

CREATE TABLE `family_members` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `family_members`
--

INSERT INTO `family_members` (`id`, `name`, `date`, `created_at`) VALUES
(3, 'Vikir Baskerville', '2025-04-17', '2025-04-17 10:01:46'),
(5, 'Hugo Le Barkerville', '2025-04-19', '2025-04-19 14:09:03'),
(9, 'Ghisllain Perdium', '2025-05-14', '2025-05-13 22:47:15'),
(10, 'Seth Marlon', '2025-06-09', '2025-06-09 11:20:21');

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
(2, 'Vikir Baskerville', '2025-04-17', 20, 'Baskerville Main House', '2025-04-17 10:08:04'),
(3, 'Pomeranian Baskerville', '2025-04-17', 10, 'Baskerville Main House 1', '2025-04-17 10:08:16'),
(6, 'Yeomra Baskerville', '2025-04-19', 20, 'Hell 0001', '2025-04-19 15:56:41'),
(7, 'Osiris Baskerville', '2025-04-19', 25, 'Baskerville Main House 2', '2025-04-19 16:18:22');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `medicine_name` varchar(100) NOT NULL,
  `generic_name` varchar(100) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `dosage_form` varchar(50) DEFAULT NULL,
  `dosage_strength` varchar(50) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_categories`
--

CREATE TABLE `medicine_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_dispensing`
--

CREATE TABLE `medicine_dispensing` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `dispensed_by` int(11) DEFAULT NULL,
  `dispensed_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `medicine_dispensing_history`
-- (See below for the actual view)
--
CREATE TABLE `medicine_dispensing_history` (
`id` int(11)
,`dispensed_date` date
,`medicine_name` varchar(100)
,`generic_name` varchar(100)
,`category_name` varchar(100)
,`batch_number` varchar(50)
,`quantity` int(11)
,`patient_name` varchar(100)
,`remarks` text
,`dispensed_by` varchar(30)
);

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
(1, 2, 50, '1', '2025-04-15', 10.00, '2025-04-14 09:43:58', '2025-04-14 09:43:58'),
(2, 7, 10, '2', '2029-11-30', 50.00, '2025-04-17 14:24:37', '2025-04-17 14:24:37');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_stock`
--

CREATE TABLE `medicine_stock` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `medicine_stock`
--
DELIMITER $$
CREATE TRIGGER `medicine_stock_update` BEFORE UPDATE ON `medicine_stock` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `medicine_stock_summary`
-- (See below for the actual view)
--
CREATE TABLE `medicine_stock_summary` (
`medicine_id` int(11)
,`medicine_name` varchar(100)
,`generic_name` varchar(100)
,`category_name` varchar(100)
,`total_quantity` decimal(32,0)
,`earliest_expiry` date
,`batch_count` bigint(21)
,`avg_purchase_price` decimal(14,6)
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
(4, 'Pomeranian Baskerville', '2025-04-17', 'Baskerville Main House 1', 10, 'None', '2025-04-17 10:03:29'),
(5, 'Yeomra Baskerville', '2025-04-20', 'Hell Main House', 500, 'None', '2025-04-19 18:35:33'),
(6, 'Osiris Baskerville', '2025-04-20', 'Baskerville Main House 2', 70, 'None', '2025-04-19 18:35:56');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movement_log`
--

CREATE TABLE `stock_movement_log` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `movement_type` enum('IN','OUT','ADJUSTMENT') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, 'Pomeranian Baskerville', '2025-04-17', 'Baskerville Main House 1', 10, 'None', 'None', '2025-04-17 10:06:19'),
(4, 'Yeomra Baskerville', '2025-04-20', 'Hell Main House', 1000, 'None', 'None', '2025-04-19 17:22:21'),
(5, 'Osiris Baskerville', '2025-04-20', 'Baskerville Main House 2', 29, 'None', 'none', '2025-04-19 17:38:40');

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
(8, 14, '2025-06-19', '14:32:08');

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
(27, 14, '2025-06-19', '14:32:08', '14:32:16', 0.00);

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
(5, 14, '2025-06-19', '14:32:16');

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
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `user_name` varchar(30) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','health_worker','doctor') NOT NULL DEFAULT 'health_worker',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `profile_picture` varchar(40) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `display_name`, `email`, `phone`, `user_name`, `password`, `role`, `status`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin@gmail.com', '09876777656', 'admin', '0192023a7bbd73250516f069df18b500', 'admin', 'active', '1656551981avatar.png ', '2025-06-08 16:35:40', '2025-06-15 23:33:01'),
(5, 'Administrator01', 'admin01@gmail.com', '09876765654', 'admin01', '7488e331b8b64e5794da3fa4eb10ad5d', 'admin', 'active', '1744879233leo.jpg', '2025-06-08 16:35:40', '2025-06-15 23:31:23'),
(6, 'Administrator02', 'admin02@gmail.com', '09918719610', 'admin02', '7488e331b8b64e5794da3fa4eb10ad5d', 'admin', 'active', '1745150573cat1.jpg ', '2025-06-08 16:35:40', '2025-06-08 18:42:25'),
(13, 'Leo01', 'leo001@gmail.com', '09878887678', 'leo01', '9f9974d013e8c0b3b51fc70c01db38ab', 'health_worker', 'active', '1749462447_ChiefTechnologyOfficer.jpg', '2025-06-09 09:47:27', '2025-06-16 12:41:14'),
(14, 'Leo02', 'leow01@gmail.com', '09888767675', 'Leow01', '06fd0e7ac68caca3851d0dd8da204a55', 'health_worker', 'active', '1749741784_AidanReturn.jpg', '2025-06-12 15:23:04', '2025-06-12 15:41:12'),
(16, 'Pomeranian', 'pome01@gmail.com', '09887765456', 'Pome01', '01731ac63a4570a7fda8f7de0f92b151', 'doctor', 'active', '1750013496_ShikimoriWallpaper.jpg', '2025-06-15 18:51:36', '2025-06-16 15:47:41'),
(19, 'Doctor Vikir', 'vikir12345@gmail.com', '09765654321', 'vikir01', '3a667b3b4453775d5b52d795fdb05721', 'doctor', 'active', 'default_profile.jpg', '2025-06-16 12:42:24', '2025-06-21 19:00:15'),
(20, 'Maria01', 'maria12345@gmail.com', '09878854323', 'maria01', '76eb1cfbe718656c4e028d05e456db5d', 'admin', 'active', 'default_profile.jpg', '2025-06-16 16:39:52', '2025-06-16 16:39:52'),
(21, 'Jasper01', 'jasper12345@gmail.com', '09756453611', 'jasper01', 'e82ee392520546e944542c8c0ed9ac33', 'health_worker', 'active', 'default_profile.jpg', '2025-06-16 16:41:05', '2025-06-16 16:41:05');

-- --------------------------------------------------------

--
-- Structure for view `medicine_dispensing_history`
--
DROP TABLE IF EXISTS `medicine_dispensing_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `medicine_dispensing_history`  AS SELECT `d`.`id` AS `id`, `d`.`dispensed_date` AS `dispensed_date`, `m`.`medicine_name` AS `medicine_name`, `m`.`generic_name` AS `generic_name`, `c`.`category_name` AS `category_name`, `s`.`batch_number` AS `batch_number`, `d`.`quantity` AS `quantity`, `d`.`patient_name` AS `patient_name`, `d`.`remarks` AS `remarks`, `u`.`display_name` AS `dispensed_by` FROM ((((`medicine_dispensing` `d` join `medicine_stock` `s` on(`d`.`stock_id` = `s`.`id`)) join `medicines` `m` on(`d`.`medicine_id` = `m`.`id`)) join `medicine_categories` `c` on(`m`.`category_id` = `c`.`id`)) left join `users` `u` on(`d`.`dispensed_by` = `u`.`id`)) ORDER BY `d`.`dispensed_date` DESC, `d`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `medicine_stock_summary`
--
DROP TABLE IF EXISTS `medicine_stock_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `medicine_stock_summary`  AS SELECT `m`.`id` AS `medicine_id`, `m`.`medicine_name` AS `medicine_name`, `m`.`generic_name` AS `generic_name`, `c`.`category_name` AS `category_name`, sum(`s`.`quantity`) AS `total_quantity`, min(`s`.`expiry_date`) AS `earliest_expiry`, count(distinct `s`.`batch_number`) AS `batch_count`, avg(`s`.`purchase_price`) AS `avg_purchase_price` FROM ((`medicines` `m` join `medicine_categories` `c` on(`m`.`category_id` = `c`.`id`)) left join `medicine_stock` `s` on(`m`.`id` = `s`.`medicine_id` and `s`.`quantity` > 0)) GROUP BY `m`.`id`, `m`.`medicine_name`, `m`.`generic_name`, `c`.`category_name` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_patient_appointment` (`patient_name`,`schedule_id`,`appointment_time`,`status`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`);

--
-- Indexes for table `appointment_slots`
--
ALTER TABLE `appointment_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_schedule_slot` (`schedule_id`,`slot_time`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_slot_time` (`slot_time`),
  ADD KEY `idx_is_booked` (`is_booked`),
  ADD KEY `idx_appointment_id` (`appointment_id`);

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
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `schedule_date` (`schedule_date`);

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
  ADD UNIQUE KEY `medicine_name` (`medicine_name`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_medicine_name` (`medicine_name`);

--
-- Indexes for table `medicine_categories`
--
ALTER TABLE `medicine_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `medicine_dispensing`
--
ALTER TABLE `medicine_dispensing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `stock_id` (`stock_id`),
  ADD KEY `dispensed_by` (`dispensed_by`);

--
-- Indexes for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_details_id` (`medicine_details_id`);

--
-- Indexes for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `random_blood_sugar`
--
ALTER TABLE `random_blood_sugar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_movement_log`
--
ALTER TABLE `stock_movement_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `stock_id` (`stock_id`),
  ADD KEY `performed_by` (`performed_by`);

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
  ADD UNIQUE KEY `user_name` (`user_name`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `appointment_slots`
--
ALTER TABLE `appointment_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `bp_monitoring`
--
ALTER TABLE `bp_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deworming`
--
ALTER TABLE `deworming`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `family_members`
--
ALTER TABLE `family_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `family_planning`
--
ALTER TABLE `family_planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_categories`
--
ALTER TABLE `medicine_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_dispensing`
--
ALTER TABLE `medicine_dispensing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `random_blood_sugar`
--
ALTER TABLE `random_blood_sugar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stock_movement_log`
--
ALTER TABLE `stock_movement_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tetanus_toxoid`
--
ALTER TABLE `tetanus_toxoid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `time_in_logs`
--
ALTER TABLE `time_in_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `time_out_logs`
--
ALTER TABLE `time_out_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointment_slots`
--
ALTER TABLE `appointment_slots`
  ADD CONSTRAINT `appointment_slots_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `doctor_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_slots_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `medicine_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicine_dispensing`
--
ALTER TABLE `medicine_dispensing`
  ADD CONSTRAINT `medicine_dispensing_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicine_dispensing_ibfk_2` FOREIGN KEY (`stock_id`) REFERENCES `medicine_stock` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicine_dispensing_ibfk_3` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  ADD CONSTRAINT `fk_inventory_medicine_details` FOREIGN KEY (`medicine_details_id`) REFERENCES `medicine_details` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  ADD CONSTRAINT `medicine_stock_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movement_log`
--
ALTER TABLE `stock_movement_log`
  ADD CONSTRAINT `stock_movement_log_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movement_log_ibfk_2` FOREIGN KEY (`stock_id`) REFERENCES `medicine_stock` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movement_log_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
