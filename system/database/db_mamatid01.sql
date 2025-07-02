-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2025 at 03:45 PM
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `email_sent` tinyint(1) DEFAULT 0,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If 1, appointment is archived',
  `view_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `after_doctor_appointment_insert` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
    DECLARE slot_id INT;
    
    
    IF NEW.schedule_id IN (SELECT id FROM doctor_schedules) THEN
        
        SELECT id INTO slot_id FROM appointment_slots 
        WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time
        LIMIT 1;
        
        IF slot_id IS NULL THEN
            
            INSERT INTO appointment_slots (schedule_id, slot_time, is_booked, appointment_id)
            VALUES (NEW.schedule_id, NEW.appointment_time, 1, NEW.id);
        ELSE
            
            UPDATE appointment_slots 
            SET is_booked = 1, appointment_id = NEW.id
            WHERE id = slot_id;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_doctor_appointment_update` AFTER UPDATE ON `appointments` FOR EACH ROW BEGIN
    
    IF NEW.schedule_id IN (SELECT id FROM doctor_schedules) THEN
        
        IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
            UPDATE appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
        
        
        IF NEW.appointment_time != OLD.appointment_time THEN
            
            UPDATE appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
            
            
            UPDATE appointment_slots
            SET is_booked = 1, appointment_id = NEW.id
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_staff_appointment_delete` AFTER DELETE ON `appointments` FOR EACH ROW BEGIN
    -- Update the staff slot when an appointment is deleted (only if it was a staff appointment)
    IF OLD.schedule_id IN (SELECT id FROM staff_schedules) THEN
        UPDATE staff_appointment_slots
        SET is_booked = 0, appointment_id = NULL
        WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_staff_appointment_insert` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
    DECLARE slot_id INT;
    
    -- Check if this is a staff appointment
    IF NEW.schedule_id IN (SELECT id FROM staff_schedules) THEN
        -- Check if slot exists
        SELECT id INTO slot_id FROM staff_appointment_slots 
        WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time
        LIMIT 1;
        
        IF slot_id IS NULL THEN
            -- Create slot if it doesn't exist
            INSERT INTO staff_appointment_slots (schedule_id, slot_time, is_booked, appointment_id)
            VALUES (NEW.schedule_id, NEW.appointment_time, 1, NEW.id);
        ELSE
            -- Update existing slot
            UPDATE staff_appointment_slots 
            SET is_booked = 1, appointment_id = NEW.id
            WHERE id = slot_id;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_staff_appointment_update` AFTER UPDATE ON `appointments` FOR EACH ROW BEGIN
    -- Check if this is a staff appointment
    IF NEW.schedule_id IN (SELECT id FROM staff_schedules) THEN
        -- If status changed to cancelled, update the slot
        IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
            UPDATE staff_appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
        
        -- If time slot changed, update both old and new slots
        IF NEW.appointment_time != OLD.appointment_time THEN
            -- Update old slot
            UPDATE staff_appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
            
            -- Update new slot
            UPDATE staff_appointment_slots
            SET is_booked = 1, appointment_id = NEW.id
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
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
  `profile_picture` varchar(100) NOT NULL DEFAULT 'default_client.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `full_name`, `email`, `password`, `phone_number`, `address`, `date_of_birth`, `gender`, `profile_picture`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES
(6, 'Leomar Escobin', 'leomaresc853@gmail.com', '9f9974d013e8c0b3b51fc70c01db38ab', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '6_1750865193.jpg', '2025-04-17 08:37:27', NULL, NULL),
(7, 'Pauline Oliveros', 'oliverospaulinekaye03@gmail.com', 'e84d01bdb3aca89bcfca98d1bfd0db9d', '09765455654', '001', '2003-03-25', 'Female', 'default_client.png', '2025-06-24 20:51:14', NULL, NULL),
(8, 'Aila Drine Niala', 'nialaaila38@gmail.com', 'e4db616efaffdbb51d538843480330f5', '09787876787', '002', '2003-03-26', 'Female', 'default_client.png', '2025-06-24 20:55:11', NULL, NULL);

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
  `approval_notes` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_bp_monitoring`
--

CREATE TABLE `general_bp_monitoring` (
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
-- Dumping data for table `general_bp_monitoring`
--

INSERT INTO `general_bp_monitoring` (`id`, `name`, `date`, `address`, `sex`, `bp`, `alcohol`, `smoke`, `obese`, `cp_number`, `created_at`) VALUES
(8, 'Test 01', '2025-07-02', 'Baskerville  Main Baskerville', 'Male', '120/30', 1, 1, 0, '09878776765', '2025-07-01 19:36:28');

-- --------------------------------------------------------

--
-- Table structure for table `general_deworming`
--

CREATE TABLE `general_deworming` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `age` int(11) NOT NULL,
  `birthday` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_deworming`
--

INSERT INTO `general_deworming` (`id`, `name`, `date`, `age`, `birthday`, `created_at`) VALUES
(8, 'Test 01', '2025-07-02', 21, '2024-12-31', '2025-07-01 20:47:40');

-- --------------------------------------------------------

--
-- Table structure for table `general_family_members`
--

CREATE TABLE `general_family_members` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_family_members`
--

INSERT INTO `general_family_members` (`id`, `name`, `date`, `created_at`) VALUES
(11, 'Test 01', '2025-07-02', '2025-07-01 16:35:03');

-- --------------------------------------------------------

--
-- Table structure for table `general_family_planning`
--

CREATE TABLE `general_family_planning` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `age` int(11) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_family_planning`
--

INSERT INTO `general_family_planning` (`id`, `name`, `date`, `age`, `address`, `created_at`) VALUES
(9, 'Test 01', '2025-07-02', 20, 'Main House Baskerville01', '2025-07-01 18:01:20');

-- --------------------------------------------------------

--
-- Table structure for table `general_rbs`
--

CREATE TABLE `general_rbs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `address` text NOT NULL,
  `age` int(11) NOT NULL,
  `result` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_rbs`
--

INSERT INTO `general_rbs` (`id`, `name`, `date`, `address`, `age`, `result`, `created_at`) VALUES
(8, 'Test 01', '2025-07-02', 'Main House Baskerville01', 21, 'B', '2025-07-02 12:52:17');

-- --------------------------------------------------------

--
-- Table structure for table `general_tetanus_toxoid`
--

CREATE TABLE `general_tetanus_toxoid` (
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
-- Dumping data for table `general_tetanus_toxoid`
--

INSERT INTO `general_tetanus_toxoid` (`id`, `name`, `date`, `address`, `age`, `diagnosis`, `remarks`, `created_at`) VALUES
(7, 'Test 01', '2025-07-02', 'Main House Baskerville01', 21, 'test 01', 'test 01', '2025-07-02 13:39:55');

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
-- Table structure for table `staff_appointment_slots`
--

CREATE TABLE `staff_appointment_slots` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `slot_time` time NOT NULL,
  `is_booked` tinyint(1) NOT NULL DEFAULT 0,
  `appointment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_schedules`
--

CREATE TABLE `staff_schedules` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `time_slot_minutes` int(11) NOT NULL DEFAULT 30,
  `max_patients` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_approved` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `time_in_logs`
--

CREATE TABLE `time_in_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `time_in` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Administrator 01', 'admin@gmail.com', '09876777656', 'admin', '0192023a7bbd73250516f069df18b500', 'admin', 'active', '1_1750716275.jpeg', '2025-06-08 16:35:40', '2025-06-23 22:04:35'),
(22, 'Doctor Leo', 'leomaresc853@gmail.com', '09918719610', 'docleo', 'c2a3a61e408026e908521ffc626f7429', 'doctor', 'active', 'default_profile.jpg', '2025-07-01 16:11:44', '2025-07-01 16:11:44'),
(23, 'HW - Leo', 'hcleo@gmail.com', '09918719610', 'hcleo', 'b5404cef28ce8df23ba14929bb3b7768', 'health_worker', 'active', 'default_profile.jpg', '2025-07-01 16:13:25', '2025-07-01 16:13:25');

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
  ADD UNIQUE KEY `unique_active_appointment` (`schedule_id`,`appointment_time`,`status`),
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
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `schedule_date` (`schedule_date`),
  ADD KEY `idx_doctor_schedules_doctor_id` (`doctor_id`),
  ADD KEY `idx_doctor_schedules_schedule_date` (`schedule_date`),
  ADD KEY `idx_doctor_schedules_is_approved` (`is_approved`);

--
-- Indexes for table `general_bp_monitoring`
--
ALTER TABLE `general_bp_monitoring`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_deworming`
--
ALTER TABLE `general_deworming`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_family_members`
--
ALTER TABLE `general_family_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_family_planning`
--
ALTER TABLE `general_family_planning`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_rbs`
--
ALTER TABLE `general_rbs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_tetanus_toxoid`
--
ALTER TABLE `general_tetanus_toxoid`
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
-- Indexes for table `staff_appointment_slots`
--
ALTER TABLE `staff_appointment_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_schedule_slot` (`schedule_id`,`slot_time`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_slot_time` (`slot_time`),
  ADD KEY `idx_is_booked` (`is_booked`),
  ADD KEY `idx_appointment_id` (`appointment_id`);

--
-- Indexes for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `schedule_date` (`schedule_date`),
  ADD KEY `idx_staff_schedules_staff_id` (`staff_id`),
  ADD KEY `idx_staff_schedules_schedule_date` (`schedule_date`);

--
-- Indexes for table `stock_movement_log`
--
ALTER TABLE `stock_movement_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `stock_id` (`stock_id`),
  ADD KEY `performed_by` (`performed_by`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `appointment_slots`
--
ALTER TABLE `appointment_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `general_bp_monitoring`
--
ALTER TABLE `general_bp_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `general_deworming`
--
ALTER TABLE `general_deworming`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `general_family_members`
--
ALTER TABLE `general_family_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `general_family_planning`
--
ALTER TABLE `general_family_planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `general_rbs`
--
ALTER TABLE `general_rbs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `general_tetanus_toxoid`
--
ALTER TABLE `general_tetanus_toxoid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- AUTO_INCREMENT for table `staff_appointment_slots`
--
ALTER TABLE `staff_appointment_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `stock_movement_log`
--
ALTER TABLE `stock_movement_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_in_logs`
--
ALTER TABLE `time_in_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `time_out_logs`
--
ALTER TABLE `time_out_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
-- Constraints for table `staff_appointment_slots`
--
ALTER TABLE `staff_appointment_slots`
  ADD CONSTRAINT `staff_appointment_slots_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `staff_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_appointment_slots_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  ADD CONSTRAINT `staff_schedules_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
