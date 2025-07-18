-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2025 at 03:29 PM
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
-- Table structure for table `admin_clients_appointments`
--

CREATE TABLE `admin_clients_appointments` (
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
  `token_expiry` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL COMMENT 'Timestamp when appointment was archived',
  `archived_by` int(11) DEFAULT NULL COMMENT 'User ID who archived the appointment',
  `archive_reason` text DEFAULT NULL COMMENT 'Reason for archiving appointment',
  `is_walkin` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If 1, appointment is a walk-in appointment'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_clients_appointments`
--

INSERT INTO `admin_clients_appointments` (`id`, `patient_name`, `phone_number`, `address`, `date_of_birth`, `gender`, `appointment_date`, `appointment_time`, `reason`, `status`, `notes`, `schedule_id`, `doctor_id`, `created_at`, `updated_at`, `email_sent`, `reminder_sent`, `is_archived`, `view_token`, `token_expiry`, `archived_at`, `archived_by`, `archive_reason`, `is_walkin`) VALUES
(58, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-04', '06:00:00', 'test 1', 'completed', NULL, 17, 1, '2025-07-03 10:55:31', '2025-07-10 16:49:43', 1, 0, 0, '79338764422b8faf161360fa44e9f916c3f472e5fdd8d552354654692b4a6188', '2025-08-02 12:55:31', NULL, NULL, NULL, 0),
(59, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-05', '08:00:00', 'test 2', 'completed', NULL, 18, 1, '2025-07-03 15:14:35', '2025-07-05 08:13:29', 1, 0, 0, '3d5107baaeae702006ea748c43720bc2b78c3cca2f4eaae1d4be253b1b50c074', '2025-08-02 17:14:35', NULL, NULL, NULL, 0),
(60, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-05', '05:00:00', 'test 3', 'completed', NULL, 19, 24, '2025-07-03 16:11:19', '2025-07-06 04:35:51', 1, 0, 0, '2787740ce012e99aa2f28981db78965eaa9efddf2afbd38b1336186180bbc768', '2025-08-02 18:11:19', NULL, NULL, NULL, 0),
(61, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-05', '10:30:00', 'test 4', 'completed', NULL, 18, 1, '2025-07-05 03:20:36', '2025-07-06 02:30:07', 1, 0, 0, '9ec672ad22ca48fe2bfd1fc289da30729967e6d0af9beb5abf5afae2fdc6f706', '2025-08-04 05:20:36', NULL, NULL, NULL, 0),
(62, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-05', '12:00:00', 'test 5', 'completed', NULL, 19, 24, '2025-07-05 03:28:26', '2025-07-06 04:35:51', 0, 0, 0, '196b4fc84bee58e9581989e6e9d0656578447424e08513d2338649b069821778', '2025-08-04 05:28:26', NULL, NULL, NULL, 0),
(63, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-05', '11:00:00', 'test 6', 'completed', NULL, 18, 1, '2025-07-05 03:41:02', '2025-07-06 02:30:07', 1, 0, 0, 'aa009d15f2f0c95289b5193f1e77df03b97372c4031aaec63119a12065ca8626', '2025-08-04 05:41:02', NULL, NULL, NULL, 0),
(64, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-05', '11:30:00', 'test 7', 'completed', NULL, 18, 1, '2025-07-05 04:03:33', '2025-07-06 02:30:07', 1, 0, 0, 'f9723864df537bf3ff873a9e529a19d65a69855c928d4faf710c4ae226f9eacd', '2025-08-04 06:03:33', NULL, NULL, NULL, 0),
(65, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-09', '06:30:00', 'test 8', 'completed', NULL, 22, 1, '2025-07-08 13:49:13', '2025-07-10 03:56:53', 1, 0, 0, 'f9b3866db5a3370ee783369599d2c2b6fce19a987fd5ee49e921ae2630aa281c', '2025-08-07 15:49:13', NULL, NULL, NULL, 0),
(66, 'test 1', '09999999999', 'test 1', '2025-07-11', 'Male', '2025-07-11', '05:00:00', 'test 1', 'completed', '[Walk-in Appointment] test 1', 33, 24, '2025-07-10 05:05:21', '2025-07-11 10:20:41', 0, 0, 0, NULL, NULL, NULL, NULL, NULL, 1),
(67, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-11', '05:30:00', 'test 8', 'completed', NULL, 33, 24, '2025-07-10 05:20:44', '2025-07-11 10:20:41', 1, 0, 0, '07a277e6edd1291715a76276716642c2e1c92ee0771806ce2cf45abee51a5df7', '2025-08-09 07:20:45', NULL, NULL, NULL, 0),
(68, 'test 2', '09999999999', 'test 2', '2025-07-10', 'Male', '2025-07-11', '06:00:00', 'test 2', 'completed', '[Walk-in Appointment] test 2', 33, 24, '2025-07-10 05:59:21', '2025-07-11 10:20:41', 0, 0, 0, NULL, NULL, NULL, NULL, NULL, 1),
(69, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-11', '06:00:00', 'test 9', 'completed', NULL, 24, 22, '2025-07-10 10:03:51', '2025-07-11 10:16:17', 1, 0, 0, '0bdaf9578ceb8a4ff93faeb0c31a0a2e8b571aa61248f3e615ebfc2c4be09bbb', '2025-08-09 12:03:51', NULL, NULL, NULL, 0),
(70, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-12', '05:00:00', 'test 10', 'completed', NULL, 25, 22, '2025-07-11 10:13:33', '2025-07-12 09:16:40', 1, 0, 0, 'f99ea989b2e4f80351082b7409afcbd4ea7255bbf956bc9cdf2a721de1f5aee6', '2025-08-10 12:13:33', NULL, NULL, NULL, 0),
(71, 'Leomar Escobin', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '2025-07-12', '05:30:00', 'test 11', 'completed', NULL, 34, 24, '2025-07-11 10:23:24', '2025-07-12 09:33:16', 1, 0, 0, '7c0d9808b5733d53eb1fdf8462559ae9428e889af0d4f479b9da00b5aade5938', '2025-08-10 12:23:25', NULL, NULL, NULL, 0);

--
-- Triggers `admin_clients_appointments`
--
DELIMITER $$
CREATE TRIGGER `after_doctor_appointment_insert` AFTER INSERT ON `admin_clients_appointments` FOR EACH ROW BEGIN
    DECLARE slot_id INT;
    
    -- Check if this is a doctor appointment
    IF NEW.schedule_id IN (SELECT id FROM admin_doctor_schedules) THEN
        -- Check if slot exists
        SELECT id INTO slot_id FROM admin_doctor_appointment_slots 
        WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time
        LIMIT 1;
        
        IF slot_id IS NULL THEN
            -- Create slot if it doesn't exist
            INSERT INTO admin_doctor_appointment_slots (schedule_id, slot_time, is_booked, appointment_id)
            VALUES (NEW.schedule_id, NEW.appointment_time, 1, NEW.id);
        ELSE
            -- Update existing slot
            UPDATE admin_doctor_appointment_slots 
            SET is_booked = 1, appointment_id = NEW.id
            WHERE id = slot_id;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_doctor_appointment_update` AFTER UPDATE ON `admin_clients_appointments` FOR EACH ROW BEGIN
    -- Check if this is a doctor appointment
    IF NEW.schedule_id IN (SELECT id FROM admin_doctor_schedules) THEN
        -- If status changed to cancelled, update the slot
        IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
            UPDATE admin_doctor_appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
        
        -- If time slot changed, update both old and new slots
        IF NEW.appointment_time != OLD.appointment_time THEN
            -- Update old slot
            UPDATE admin_doctor_appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
            
            -- Update new slot
            UPDATE admin_doctor_appointment_slots
            SET is_booked = 1, appointment_id = NEW.id
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_staff_appointment_delete` AFTER DELETE ON `admin_clients_appointments` FOR EACH ROW BEGIN
    -- Update the staff slot when an appointment is deleted (only if it was a staff appointment)
    IF OLD.schedule_id IN (SELECT id FROM admin_hw_schedules) THEN
        UPDATE admin_hw_appointment_slots
        SET is_booked = 0, appointment_id = NULL
        WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_staff_appointment_insert` AFTER INSERT ON `admin_clients_appointments` FOR EACH ROW BEGIN
    DECLARE slot_id INT;
    
    -- Check if this is a staff appointment
    IF NEW.schedule_id IN (SELECT id FROM admin_hw_schedules) THEN
        -- Check if slot exists
        SELECT id INTO slot_id FROM admin_hw_appointment_slots 
        WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time
        LIMIT 1;
        
        IF slot_id IS NULL THEN
            -- Create slot if it doesn't exist
            INSERT INTO admin_hw_appointment_slots (schedule_id, slot_time, is_booked, appointment_id)
            VALUES (NEW.schedule_id, NEW.appointment_time, 1, NEW.id);
        ELSE
            -- Update existing slot
            UPDATE admin_hw_appointment_slots 
            SET is_booked = 1, appointment_id = NEW.id
            WHERE id = slot_id;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_staff_appointment_update` AFTER UPDATE ON `admin_clients_appointments` FOR EACH ROW BEGIN
    -- Check if this is a staff appointment
    IF NEW.schedule_id IN (SELECT id FROM admin_hw_schedules) THEN
        -- If status changed to cancelled, update the slot
        IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
            UPDATE admin_hw_appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
        
        -- If time slot changed, update both old and new slots
        IF NEW.appointment_time != OLD.appointment_time THEN
            -- Update old slot
            UPDATE admin_hw_appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
            
            -- Update new slot
            UPDATE admin_hw_appointment_slots
            SET is_booked = 1, appointment_id = NEW.id
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_doctor_appointment_slots`
--

CREATE TABLE `admin_doctor_appointment_slots` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `slot_time` time NOT NULL,
  `is_booked` tinyint(1) NOT NULL DEFAULT 0,
  `appointment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_doctor_appointment_slots`
--

INSERT INTO `admin_doctor_appointment_slots` (`id`, `schedule_id`, `slot_time`, `is_booked`, `appointment_id`) VALUES
(171, 24, '05:00:00', 1, 7),
(172, 24, '05:30:00', 1, 8),
(173, 24, '06:00:00', 1, 69),
(174, 24, '06:30:00', 1, 9),
(175, 24, '07:00:00', 0, NULL),
(176, 24, '07:30:00', 0, NULL),
(177, 24, '08:00:00', 0, NULL),
(178, 24, '08:30:00', 0, NULL),
(179, 24, '09:00:00', 0, NULL),
(180, 24, '09:30:00', 0, NULL),
(181, 24, '10:00:00', 0, NULL),
(182, 24, '10:30:00', 0, NULL),
(183, 24, '11:00:00', 0, NULL),
(184, 24, '11:30:00', 0, NULL),
(185, 24, '12:00:00', 0, NULL),
(186, 24, '12:30:00', 0, NULL),
(187, 24, '13:00:00', 0, NULL),
(188, 24, '13:30:00', 0, NULL),
(189, 24, '14:00:00', 0, NULL),
(190, 24, '14:30:00', 0, NULL),
(191, 24, '15:00:00', 0, NULL),
(192, 24, '15:30:00', 0, NULL),
(193, 24, '16:00:00', 0, NULL),
(194, 24, '16:30:00', 0, NULL),
(195, 25, '05:00:00', 1, 70),
(196, 25, '05:30:00', 0, NULL),
(197, 25, '06:00:00', 0, NULL),
(198, 25, '06:30:00', 0, NULL),
(199, 25, '07:00:00', 0, NULL),
(200, 25, '07:30:00', 0, NULL),
(201, 25, '08:00:00', 0, NULL),
(202, 25, '08:30:00', 0, NULL),
(203, 25, '09:00:00', 0, NULL),
(204, 25, '09:30:00', 0, NULL),
(205, 25, '10:00:00', 0, NULL),
(206, 25, '10:30:00', 0, NULL),
(207, 25, '11:00:00', 0, NULL),
(208, 25, '11:30:00', 0, NULL),
(209, 25, '12:00:00', 0, NULL),
(210, 25, '12:30:00', 0, NULL),
(211, 25, '13:00:00', 0, NULL),
(212, 25, '13:30:00', 0, NULL),
(213, 25, '14:00:00', 0, NULL),
(214, 25, '14:30:00', 0, NULL),
(215, 25, '15:00:00', 0, NULL),
(216, 25, '15:30:00', 0, NULL),
(217, 25, '16:00:00', 0, NULL),
(218, 25, '16:30:00', 0, NULL),
(219, 26, '05:00:00', 1, 11),
(220, 26, '05:30:00', 0, NULL),
(221, 26, '06:00:00', 0, NULL),
(222, 26, '06:30:00', 0, NULL),
(223, 26, '07:00:00', 0, NULL),
(224, 26, '07:30:00', 0, NULL),
(225, 26, '08:00:00', 0, NULL),
(226, 26, '08:30:00', 0, NULL),
(227, 26, '09:00:00', 0, NULL),
(228, 26, '09:30:00', 0, NULL),
(229, 26, '10:00:00', 0, NULL),
(230, 26, '10:30:00', 0, NULL),
(231, 26, '11:00:00', 0, NULL),
(232, 26, '11:30:00', 0, NULL),
(233, 26, '12:00:00', 0, NULL),
(234, 26, '12:30:00', 0, NULL),
(235, 26, '13:00:00', 0, NULL),
(236, 26, '13:30:00', 0, NULL),
(237, 26, '14:00:00', 0, NULL),
(238, 26, '14:30:00', 0, NULL),
(239, 26, '15:00:00', 0, NULL),
(240, 26, '15:30:00', 0, NULL),
(241, 26, '16:00:00', 0, NULL),
(242, 26, '16:30:00', 0, NULL),
(243, 27, '05:00:00', 0, NULL),
(244, 27, '05:30:00', 0, NULL),
(245, 27, '06:00:00', 0, NULL),
(246, 27, '06:30:00', 0, NULL),
(247, 27, '07:00:00', 0, NULL),
(248, 27, '07:30:00', 0, NULL),
(249, 27, '08:00:00', 0, NULL),
(250, 27, '08:30:00', 0, NULL),
(251, 27, '09:00:00', 0, NULL),
(252, 27, '09:30:00', 0, NULL),
(253, 27, '10:00:00', 0, NULL),
(254, 27, '10:30:00', 0, NULL),
(255, 27, '11:00:00', 0, NULL),
(256, 27, '11:30:00', 1, 13),
(257, 27, '12:00:00', 0, NULL),
(258, 27, '12:30:00', 0, NULL),
(259, 27, '13:00:00', 0, NULL),
(260, 27, '13:30:00', 0, NULL),
(261, 27, '14:00:00', 0, NULL),
(262, 27, '14:30:00', 0, NULL),
(263, 27, '15:00:00', 1, 72),
(264, 27, '15:30:00', 0, NULL),
(265, 27, '16:00:00', 0, NULL),
(266, 27, '16:30:00', 0, NULL),
(267, 28, '05:00:00', 0, NULL),
(268, 28, '05:30:00', 0, NULL),
(269, 28, '06:00:00', 0, NULL),
(270, 28, '06:30:00', 0, NULL),
(271, 28, '07:00:00', 0, NULL),
(272, 28, '07:30:00', 0, NULL),
(273, 28, '08:00:00', 0, NULL),
(274, 28, '08:30:00', 0, NULL),
(275, 28, '09:00:00', 0, NULL),
(276, 28, '09:30:00', 0, NULL),
(277, 28, '10:00:00', 0, NULL),
(278, 28, '10:30:00', 0, NULL),
(279, 28, '11:00:00', 0, NULL),
(280, 28, '11:30:00', 0, NULL),
(281, 28, '12:00:00', 0, NULL),
(282, 28, '12:30:00', 0, NULL),
(283, 28, '13:00:00', 0, NULL),
(284, 28, '13:30:00', 0, NULL),
(285, 28, '14:00:00', 0, NULL),
(286, 28, '14:30:00', 0, NULL),
(287, 28, '15:00:00', 0, NULL),
(288, 28, '15:30:00', 0, NULL),
(289, 28, '16:00:00', 0, NULL),
(290, 28, '16:30:00', 0, NULL),
(291, 29, '05:00:00', 0, NULL),
(292, 29, '05:30:00', 0, NULL),
(293, 29, '06:00:00', 0, NULL),
(294, 29, '06:30:00', 0, NULL),
(295, 29, '07:00:00', 0, NULL),
(296, 29, '07:30:00', 0, NULL),
(297, 29, '08:00:00', 0, NULL),
(298, 29, '08:30:00', 0, NULL),
(299, 29, '09:00:00', 0, NULL),
(300, 29, '09:30:00', 0, NULL),
(301, 29, '10:00:00', 0, NULL),
(302, 29, '10:30:00', 0, NULL),
(303, 29, '11:00:00', 0, NULL),
(304, 29, '11:30:00', 0, NULL),
(305, 29, '12:00:00', 0, NULL),
(306, 29, '12:30:00', 0, NULL),
(307, 29, '13:00:00', 0, NULL),
(308, 29, '13:30:00', 0, NULL),
(309, 29, '14:00:00', 0, NULL),
(310, 29, '14:30:00', 0, NULL),
(311, 29, '15:00:00', 0, NULL),
(312, 29, '15:30:00', 0, NULL),
(313, 29, '16:00:00', 0, NULL),
(314, 29, '16:30:00', 0, NULL),
(315, 30, '05:00:00', 0, NULL),
(316, 30, '05:30:00', 0, NULL),
(317, 30, '06:00:00', 0, NULL),
(318, 30, '06:30:00', 0, NULL),
(319, 30, '07:00:00', 0, NULL),
(320, 30, '07:30:00', 0, NULL),
(321, 30, '08:00:00', 0, NULL),
(322, 30, '08:30:00', 0, NULL),
(323, 30, '09:00:00', 0, NULL),
(324, 30, '09:30:00', 0, NULL),
(325, 30, '10:00:00', 0, NULL),
(326, 30, '10:30:00', 0, NULL),
(327, 30, '11:00:00', 0, NULL),
(328, 30, '11:30:00', 0, NULL),
(329, 30, '12:00:00', 0, NULL),
(330, 30, '12:30:00', 0, NULL),
(331, 30, '13:00:00', 0, NULL),
(332, 30, '13:30:00', 0, NULL),
(333, 30, '14:00:00', 0, NULL),
(334, 30, '14:30:00', 0, NULL),
(335, 30, '15:00:00', 0, NULL),
(336, 30, '15:30:00', 0, NULL),
(337, 30, '16:00:00', 0, NULL),
(338, 30, '16:30:00', 0, NULL),
(339, 31, '05:00:00', 0, NULL),
(340, 31, '05:30:00', 0, NULL),
(341, 31, '06:00:00', 0, NULL),
(342, 31, '06:30:00', 0, NULL),
(343, 31, '07:00:00', 0, NULL),
(344, 31, '07:30:00', 0, NULL),
(345, 31, '08:00:00', 0, NULL),
(346, 31, '08:30:00', 0, NULL),
(347, 31, '09:00:00', 0, NULL),
(348, 31, '09:30:00', 0, NULL),
(349, 31, '10:00:00', 0, NULL),
(350, 31, '10:30:00', 0, NULL),
(351, 31, '11:00:00', 0, NULL),
(352, 31, '11:30:00', 0, NULL),
(353, 31, '12:00:00', 0, NULL),
(354, 31, '12:30:00', 0, NULL),
(355, 31, '13:00:00', 0, NULL),
(356, 31, '13:30:00', 0, NULL),
(357, 31, '14:00:00', 0, NULL),
(358, 31, '14:30:00', 0, NULL),
(359, 31, '15:00:00', 0, NULL),
(360, 31, '15:30:00', 0, NULL),
(361, 31, '16:00:00', 0, NULL),
(362, 31, '16:30:00', 0, NULL),
(363, 32, '05:00:00', 0, NULL),
(364, 32, '05:30:00', 0, NULL),
(365, 32, '06:00:00', 0, NULL),
(366, 32, '06:30:00', 0, NULL),
(367, 32, '07:00:00', 0, NULL),
(368, 32, '07:30:00', 0, NULL),
(369, 32, '08:00:00', 0, NULL),
(370, 32, '08:30:00', 0, NULL),
(371, 32, '09:00:00', 0, NULL),
(372, 32, '09:30:00', 0, NULL),
(373, 32, '10:00:00', 0, NULL),
(374, 32, '10:30:00', 0, NULL),
(375, 32, '11:00:00', 0, NULL),
(376, 32, '11:30:00', 0, NULL),
(377, 32, '12:00:00', 0, NULL),
(378, 32, '12:30:00', 0, NULL),
(379, 32, '13:00:00', 0, NULL),
(380, 32, '13:30:00', 0, NULL),
(381, 32, '14:00:00', 0, NULL),
(382, 32, '14:30:00', 0, NULL),
(383, 32, '15:00:00', 0, NULL),
(384, 32, '15:30:00', 0, NULL),
(385, 32, '16:00:00', 0, NULL),
(386, 32, '16:30:00', 0, NULL),
(387, 33, '05:00:00', 0, NULL),
(388, 33, '05:30:00', 0, NULL),
(389, 33, '06:00:00', 0, NULL),
(390, 33, '06:30:00', 0, NULL),
(391, 33, '07:00:00', 0, NULL),
(392, 33, '07:30:00', 0, NULL),
(393, 33, '08:00:00', 0, NULL),
(394, 33, '08:30:00', 0, NULL),
(395, 33, '09:00:00', 0, NULL),
(396, 33, '09:30:00', 0, NULL),
(397, 33, '10:00:00', 0, NULL),
(398, 33, '10:30:00', 0, NULL),
(399, 33, '11:00:00', 0, NULL),
(400, 33, '11:30:00', 0, NULL),
(401, 33, '12:00:00', 0, NULL),
(402, 33, '12:30:00', 0, NULL),
(403, 33, '13:00:00', 0, NULL),
(404, 33, '13:30:00', 0, NULL),
(405, 33, '14:00:00', 0, NULL),
(406, 33, '14:30:00', 0, NULL),
(407, 33, '15:00:00', 0, NULL),
(408, 33, '15:30:00', 0, NULL),
(409, 33, '16:00:00', 0, NULL),
(410, 33, '16:30:00', 0, NULL),
(411, 34, '06:00:00', 0, NULL),
(412, 34, '06:30:00', 0, NULL),
(413, 34, '07:00:00', 0, NULL),
(414, 34, '07:30:00', 0, NULL),
(415, 34, '08:00:00', 0, NULL),
(416, 34, '08:30:00', 0, NULL),
(417, 34, '09:00:00', 0, NULL),
(418, 34, '09:30:00', 0, NULL),
(419, 34, '10:00:00', 0, NULL),
(420, 34, '10:30:00', 0, NULL),
(421, 34, '11:00:00', 0, NULL),
(422, 34, '11:30:00', 0, NULL),
(423, 34, '12:00:00', 0, NULL),
(424, 34, '12:30:00', 0, NULL),
(425, 34, '13:00:00', 0, NULL),
(426, 34, '13:30:00', 0, NULL),
(427, 34, '14:00:00', 0, NULL),
(428, 34, '14:30:00', 0, NULL),
(429, 34, '15:00:00', 0, NULL),
(430, 34, '15:30:00', 0, NULL),
(431, 34, '16:00:00', 0, NULL),
(432, 34, '16:30:00', 0, NULL),
(433, 34, '17:00:00', 0, NULL),
(434, 34, '17:30:00', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_doctor_schedules`
--

CREATE TABLE `admin_doctor_schedules` (
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

--
-- Dumping data for table `admin_doctor_schedules`
--

INSERT INTO `admin_doctor_schedules` (`id`, `doctor_id`, `schedule_date`, `start_time`, `end_time`, `time_slot_minutes`, `max_patients`, `notes`, `created_at`, `updated_at`, `is_approved`, `approval_notes`, `is_deleted`) VALUES
(23, 22, '2025-07-08', '10:00:00', '16:00:00', 30, 1, 'Test doctor schedule for walk-in testing', '2025-07-08 13:22:56', '2025-07-14 10:20:12', 1, '', 1),
(24, 22, '2025-07-11', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-10 07:19:45', '2025-07-14 10:20:22', 1, '', 1),
(25, 22, '2025-07-12', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-10 07:19:45', '2025-07-10 07:47:04', 1, '', 0),
(26, 22, '2025-07-13', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-10 07:19:45', '2025-07-14 10:19:51', 1, '', 1),
(27, 22, '2025-07-14', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-10 07:19:45', '2025-07-10 08:42:54', 1, 'Bulk approved by Administrator 01 on 2025-07-10 10:42:54', 0),
(28, 22, '2025-07-15', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-10 07:19:45', '2025-07-10 08:42:54', 1, 'Bulk approved by Administrator 01 on 2025-07-10 10:42:54', 0),
(29, 22, '2025-07-16', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-10 07:19:45', '2025-07-10 08:42:54', 1, 'Bulk approved by Administrator 01 on 2025-07-10 10:42:54', 0),
(30, 22, '2025-07-17', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-10 07:19:45', '2025-07-10 08:42:54', 1, 'Bulk approved by Administrator 01 on 2025-07-10 10:42:54', 0),
(31, 22, '2025-07-18', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-10 07:19:45', '2025-07-10 08:42:54', 1, 'Bulk approved by Administrator 01 on 2025-07-10 10:42:54', 0),
(32, 22, '2025-07-19', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-10 07:19:45', '2025-07-10 08:42:54', 1, 'Bulk approved by Administrator 01 on 2025-07-10 10:42:54', 0),
(33, 22, '2025-07-20', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-10 07:19:45', '2025-07-10 08:42:54', 1, 'Bulk approved by Administrator 01 on 2025-07-10 10:42:54', 0),
(34, 22, '2025-07-21', '06:00:00', '18:00:00', 30, 1, 'test 2', '2025-07-14 10:38:25', '2025-07-14 10:39:15', 1, 'test 2', 0);

-- --------------------------------------------------------

--
-- Table structure for table `admin_hw_appointment_slots`
--

CREATE TABLE `admin_hw_appointment_slots` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `slot_time` time NOT NULL,
  `is_booked` tinyint(1) NOT NULL DEFAULT 0,
  `appointment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_hw_appointment_slots`
--

INSERT INTO `admin_hw_appointment_slots` (`id`, `schedule_id`, `slot_time`, `is_booked`, `appointment_id`) VALUES
(6, 17, '06:00:00', 1, 58),
(7, 18, '08:00:00', 1, 59),
(8, 19, '05:00:00', 1, 60),
(9, 18, '10:30:00', 1, 61),
(10, 19, '12:00:00', 1, 62),
(11, 18, '11:00:00', 1, 63),
(12, 18, '11:30:00', 1, 64),
(13, 22, '06:30:00', 1, 65),
(14, 33, '05:00:00', 1, 66),
(15, 33, '05:30:00', 1, 67),
(16, 33, '06:00:00', 1, 68),
(17, 33, '06:30:00', 1, 2),
(18, 23, '06:00:00', 1, 3),
(19, 24, '06:00:00', 1, 69),
(20, 34, '05:00:00', 1, 10),
(21, 25, '05:00:00', 1, 70),
(22, 34, '05:30:00', 1, 71),
(23, 35, '05:00:00', 1, 12),
(24, 27, '15:00:00', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_hw_schedules`
--

CREATE TABLE `admin_hw_schedules` (
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

--
-- Dumping data for table `admin_hw_schedules`
--

INSERT INTO `admin_hw_schedules` (`id`, `staff_id`, `schedule_date`, `start_time`, `end_time`, `time_slot_minutes`, `max_patients`, `notes`, `created_at`, `updated_at`, `is_approved`) VALUES
(17, 1, '2025-07-04', '06:00:00', '18:00:00', 30, 1, '', '2025-07-03 10:53:39', '2025-07-03 10:53:39', 1),
(18, 1, '2025-07-05', '08:00:00', '16:00:00', 30, 1, 'test 2', '2025-07-03 15:12:49', '2025-07-03 15:12:49', 1),
(19, 24, '2025-07-05', '05:00:00', '17:00:00', 30, 1, 'test 1', '2025-07-03 16:09:43', '2025-07-03 16:09:43', 1),
(21, 24, '2025-07-08', '09:00:00', '17:00:00', 30, 1, 'Test schedule for walk-in testing', '2025-07-08 13:22:30', '2025-07-08 13:22:30', 1),
(22, 1, '2025-07-09', '06:00:00', '17:00:00', 30, 1, 'test 4', '2025-07-08 13:32:43', '2025-07-08 13:32:43', 1),
(23, 1, '2025-07-11', '06:00:00', '17:00:00', 30, 1, 'test 5', '2025-07-10 04:11:43', '2025-07-10 04:11:43', 1),
(24, 1, '2025-07-12', '06:00:00', '17:00:00', 30, 1, 'test 5', '2025-07-10 04:11:43', '2025-07-10 04:11:43', 1),
(25, 1, '2025-07-13', '06:00:00', '17:00:00', 30, 1, 'test 5', '2025-07-10 04:11:43', '2025-07-10 04:11:43', 1),
(26, 1, '2025-07-14', '06:00:00', '17:00:00', 30, 1, 'test 5', '2025-07-10 04:11:43', '2025-07-10 04:11:43', 1),
(27, 1, '2025-07-15', '06:00:00', '17:00:00', 30, 1, 'test 5', '2025-07-10 04:11:43', '2025-07-10 04:11:43', 1),
(28, 1, '2025-07-16', '06:00:00', '17:00:00', 30, 1, 'test 5', '2025-07-10 04:11:43', '2025-07-10 04:11:43', 1),
(29, 1, '2025-07-17', '06:00:00', '17:00:00', 30, 1, 'test 5', '2025-07-10 04:11:43', '2025-07-10 04:11:43', 1),
(30, 1, '2025-07-18', '06:00:00', '17:00:00', 30, 1, 'test 5', '2025-07-10 04:11:43', '2025-07-10 04:11:43', 1),
(31, 1, '2025-07-19', '06:00:00', '17:00:00', 30, 1, 'test 5', '2025-07-10 04:11:43', '2025-07-10 04:11:43', 1),
(32, 1, '2025-07-20', '06:00:00', '17:00:00', 30, 1, 'test 5', '2025-07-10 04:11:43', '2025-07-10 04:11:43', 1),
(33, 24, '2025-07-11', '05:00:00', '17:00:00', 30, 1, 'test 2', '2025-07-10 04:28:20', '2025-07-10 04:28:20', 1),
(34, 24, '2025-07-12', '05:00:00', '17:00:00', 30, 1, 'test 2', '2025-07-10 04:28:20', '2025-07-10 04:28:20', 1),
(35, 24, '2025-07-13', '05:00:00', '17:00:00', 30, 1, 'test 2', '2025-07-10 04:28:20', '2025-07-10 04:28:20', 1),
(36, 24, '2025-07-14', '05:00:00', '17:00:00', 30, 1, 'test 2', '2025-07-10 04:28:20', '2025-07-10 04:28:20', 1),
(37, 24, '2025-07-15', '05:00:00', '17:00:00', 30, 1, 'test 2', '2025-07-10 04:28:20', '2025-07-10 04:28:20', 1),
(38, 24, '2025-07-16', '05:00:00', '17:00:00', 30, 1, 'test 2', '2025-07-10 04:28:20', '2025-07-10 04:28:20', 1),
(39, 24, '2025-07-17', '05:00:00', '17:00:00', 30, 1, 'test 2', '2025-07-10 04:28:20', '2025-07-10 04:28:20', 1),
(40, 24, '2025-07-18', '05:00:00', '17:00:00', 30, 1, 'test 2', '2025-07-10 04:28:20', '2025-07-10 04:28:20', 1),
(41, 24, '2025-07-19', '05:00:00', '17:00:00', 30, 1, 'test 2', '2025-07-10 04:28:20', '2025-07-10 04:28:20', 1),
(42, 24, '2025-07-20', '05:00:00', '17:00:00', 30, 1, 'test 2', '2025-07-10 04:28:20', '2025-07-10 04:28:20', 1);

-- --------------------------------------------------------

--
-- Table structure for table `admin_time_in_attendance_logs`
--

CREATE TABLE `admin_time_in_attendance_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `time_in` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `admin_time_in_attendance_logs`
--
DELIMITER $$
CREATE TRIGGER `after_time_in_insert` AFTER INSERT ON `admin_time_in_attendance_logs` FOR EACH ROW BEGIN
    INSERT INTO admin_time_logs (user_id, log_date, time_in, time_out, total_hours)
    VALUES (NEW.user_id, NEW.log_date, NEW.time_in, NULL, NULL)
    ON DUPLICATE KEY UPDATE time_in = NEW.time_in;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_time_logs`
--

CREATE TABLE `admin_time_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_time_out_attendance_logs`
--

CREATE TABLE `admin_time_out_attendance_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `time_out` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `admin_time_out_attendance_logs`
--
DELIMITER $$
CREATE TRIGGER `after_time_out_insert` AFTER INSERT ON `admin_time_out_attendance_logs` FOR EACH ROW BEGIN
    DECLARE v_time_in TIME;
    
    -- Get the corresponding Time In from correct table
    SELECT time_in INTO v_time_in
    FROM admin_time_in_attendance_logs
    WHERE user_id = NEW.user_id AND log_date = NEW.log_date;
    
    -- Update admin_time_logs with Time Out and calculate total hours
    INSERT INTO admin_time_logs (user_id, log_date, time_in, time_out, total_hours)
    VALUES (NEW.user_id, NEW.log_date, v_time_in, NEW.time_out, 
            ROUND(TIMESTAMPDIFF(SECOND, v_time_in, NEW.time_out) / 3600, 2))
    ON DUPLICATE KEY UPDATE 
        time_out = NEW.time_out,
        total_hours = ROUND(TIMESTAMPDIFF(SECOND, time_in, NEW.time_out) / 3600, 2);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_user_accounts`
--

CREATE TABLE `admin_user_accounts` (
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
-- Dumping data for table `admin_user_accounts`
--

INSERT INTO `admin_user_accounts` (`id`, `display_name`, `email`, `phone`, `user_name`, `password`, `role`, `status`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 'Administrator 01', 'admin@gmail.com', '09876777656', 'admin', '0192023a7bbd73250516f069df18b500', 'admin', 'active', '1_1750716275.jpeg', '2025-06-08 16:35:40', '2025-06-23 22:04:35'),
(22, 'Doctor Leo', 'leomaresc853@gmail.com', '09918719610', 'docleo', 'c2a3a61e408026e908521ffc626f7429', 'doctor', 'active', '22_1751546845.jpg', '2025-07-01 16:11:44', '2025-07-03 12:47:25'),
(24, 'HW - Leo', 'hcleo@gmail.com', '09787676566', 'hwleo', '40496d7b1e3df268628fa14e3959f58a', 'health_worker', 'active', 'default_profile.jpg', '2025-07-03 11:48:02', '2025-07-03 11:48:02');

-- --------------------------------------------------------

--
-- Table structure for table `admin_walkin_appointments`
--

CREATE TABLE `admin_walkin_appointments` (
  `id` int(11) NOT NULL,
  `patient_name` varchar(60) NOT NULL,
  `phone_number` varchar(12) NOT NULL,
  `email` varchar(100) DEFAULT NULL COMMENT 'Patient email address for notifications',
  `address` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(6) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('pending','approved','cancelled','completed') NOT NULL DEFAULT 'approved',
  `notes` text DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL COMMENT 'Doctor or Health Worker ID',
  `provider_type` enum('doctor','health_worker','admin') NOT NULL DEFAULT 'health_worker',
  `booked_by` int(11) NOT NULL COMMENT 'Admin/Health Worker who booked this walk-in',
  `walk_in_time` timestamp NULL DEFAULT NULL COMMENT 'When patient actually walked in',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If 1, appointment is archived',
  `archived_at` datetime DEFAULT NULL COMMENT 'Timestamp when appointment was archived',
  `archived_by` int(11) DEFAULT NULL COMMENT 'User ID who archived the appointment',
  `archive_reason` text DEFAULT NULL COMMENT 'Reason for archiving appointment',
  `email_sent` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If 1, email notification has been sent',
  `reminder_sent` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If 1, reminder email has been sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Table for storing walk-in appointments separately from regular appointments';

--
-- Dumping data for table `admin_walkin_appointments`
--

INSERT INTO `admin_walkin_appointments` (`id`, `patient_name`, `phone_number`, `email`, `address`, `date_of_birth`, `gender`, `appointment_date`, `appointment_time`, `reason`, `status`, `notes`, `schedule_id`, `provider_id`, `provider_type`, `booked_by`, `walk_in_time`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`, `email_sent`, `reminder_sent`) VALUES
(2, 'test 3', '09999999999', NULL, 'test 3', '2025-07-11', 'Female', '2025-07-11', '06:30:00', 'test 3', 'completed', '[Walk-in Appointment] test 3', 33, 24, 'health_worker', 24, NULL, '2025-07-10 06:50:04', '2025-07-11 10:20:41', 0, NULL, NULL, NULL, 0, 0),
(3, 'test 4', '09999999999', NULL, 'test 4', '2025-07-10', 'Female', '2025-07-11', '06:00:00', 'test 4', 'completed', '[Walk-in Appointment] test 4', 23, 1, 'admin', 1, NULL, '2025-07-10 07:29:40', '2025-07-11 07:44:06', 0, NULL, NULL, NULL, 0, 0),
(7, 'test 5', '09999999999', NULL, 'test 5', '2025-07-11', 'Male', '2025-07-11', '05:00:00', 'test 5', 'completed', '[Walk-in Appointment] test 5', 24, 22, 'doctor', 24, NULL, '2025-07-10 09:52:14', '2025-07-11 10:16:17', 0, NULL, NULL, NULL, 0, 0),
(8, 'test 6', '09999999999', NULL, 'test 6', '2025-07-10', 'Female', '2025-07-11', '05:30:00', 'test 6', 'completed', '[Walk-in Appointment] test 6', 24, 22, 'doctor', 24, NULL, '2025-07-10 09:53:57', '2025-07-11 10:16:17', 0, NULL, NULL, NULL, 0, 0),
(9, 'test 7', '09787878777', NULL, 'test 7', '2025-07-11', 'Female', '2025-07-11', '06:30:00', 'test 7', 'completed', '[Walk-in Appointment] test 7', 24, 22, 'doctor', 24, NULL, '2025-07-10 10:05:48', '2025-07-11 10:16:17', 1, '2025-07-10 16:50:00', 24, '', 0, 0),
(10, 'test 8', '09787878777', 'leomaresc853@gmail.com', 'test 8', '2025-07-11', 'Male', '2025-07-12', '05:00:00', 'test 8', 'completed', '[Walk-in Appointment] test 8', 34, 24, 'health_worker', 24, NULL, '2025-07-10 17:14:50', '2025-07-12 09:33:17', 0, NULL, NULL, NULL, 0, 0),
(11, 'test 9', '09877777777', 'leomaresc853@gmail.com', 'test 9', '2025-07-13', 'Male', '2025-07-13', '05:00:00', 'test 9', 'completed', '[Walk-in Appointment] test 9', 26, 22, 'doctor', 1, NULL, '2025-07-12 10:20:46', '2025-07-14 03:58:13', 0, NULL, NULL, NULL, 0, 0),
(12, 'test 10', '09877777777', 'leomaresc853@gmail.com', 'test 10', '2025-07-12', 'Female', '2025-07-13', '05:00:00', 'test 10', 'approved', '[Walk-in Appointment] test 10', 35, 24, 'health_worker', 1, NULL, '2025-07-12 10:32:15', NULL, 0, NULL, NULL, NULL, 0, 0),
(13, 'test 11', '09888888888', 'leomaresc853@gmail.com', 'test 10', '2025-07-14', 'Female', '2025-07-14', '11:30:00', 'test 11', 'completed', '[Walk-in Appointment] test 11', 27, 22, 'doctor', 1, NULL, '2025-07-14 03:56:58', '2025-07-15 16:14:06', 0, NULL, NULL, NULL, 0, 0);

--
-- Triggers `admin_walkin_appointments`
--
DELIMITER $$
CREATE TRIGGER `after_walkin_appointment_delete` AFTER DELETE ON `admin_walkin_appointments` FOR EACH ROW BEGIN
    -- Handle doctor appointments
    IF OLD.provider_type = 'doctor' AND OLD.schedule_id IN (SELECT id FROM admin_doctor_schedules) THEN
        UPDATE admin_doctor_appointment_slots
        SET is_booked = 0, appointment_id = NULL
        WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
    END IF;
    
    -- Handle health worker/admin appointments  
    IF (OLD.provider_type = 'health_worker' OR OLD.provider_type = 'admin') 
       AND OLD.schedule_id IN (SELECT id FROM admin_hw_schedules) THEN
        UPDATE admin_hw_appointment_slots
        SET is_booked = 0, appointment_id = NULL
        WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_walkin_appointment_update` AFTER UPDATE ON `admin_walkin_appointments` FOR EACH ROW BEGIN
    -- Handle doctor appointments
    IF NEW.provider_type = 'doctor' AND NEW.schedule_id IN (SELECT id FROM admin_doctor_schedules) THEN
        -- If status changed to cancelled, update the slot
        IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
            UPDATE admin_doctor_appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
        
        -- If time slot changed, update both old and new slots
        IF NEW.appointment_time != OLD.appointment_time THEN
            -- Update old slot
            UPDATE admin_doctor_appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
            
            -- Update new slot
            UPDATE admin_doctor_appointment_slots
            SET is_booked = 1, appointment_id = NEW.id
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
    END IF;
    
    -- Handle health worker/admin appointments
    IF (NEW.provider_type = 'health_worker' OR NEW.provider_type = 'admin') 
       AND NEW.schedule_id IN (SELECT id FROM admin_hw_schedules) THEN
        -- If status changed to cancelled, update the slot
        IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
            UPDATE admin_hw_appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
        
        -- If time slot changed, update both old and new slots
        IF NEW.appointment_time != OLD.appointment_time THEN
            -- Update old slot
            UPDATE admin_hw_appointment_slots
            SET is_booked = 0, appointment_id = NULL
            WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
            
            -- Update new slot
            UPDATE admin_hw_appointment_slots
            SET is_booked = 1, appointment_id = NEW.id
            WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_walkin_doctor_appointment_insert` AFTER INSERT ON `admin_walkin_appointments` FOR EACH ROW BEGIN
    DECLARE slot_id INT;
    
    -- Check if this is a doctor walk-in appointment
    IF NEW.provider_type = 'doctor' AND NEW.schedule_id IN (SELECT id FROM admin_doctor_schedules) THEN
        -- Check if slot exists
        SELECT id INTO slot_id FROM admin_doctor_appointment_slots 
        WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time
        LIMIT 1;
        
        IF slot_id IS NULL THEN
            -- Create slot if it doesn't exist
            INSERT INTO admin_doctor_appointment_slots (schedule_id, slot_time, is_booked, appointment_id)
            VALUES (NEW.schedule_id, NEW.appointment_time, 1, NEW.id);
        ELSE
            -- Update existing slot (this will handle multiple patients per slot based on max_patients)
            UPDATE admin_doctor_appointment_slots 
            SET is_booked = 1
            WHERE id = slot_id;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_walkin_hw_appointment_insert` AFTER INSERT ON `admin_walkin_appointments` FOR EACH ROW BEGIN
    DECLARE slot_id INT;
    
    -- Check if this is a health worker/admin walk-in appointment
    IF (NEW.provider_type = 'health_worker' OR NEW.provider_type = 'admin') 
       AND NEW.schedule_id IN (SELECT id FROM admin_hw_schedules) THEN
        -- Check if slot exists
        SELECT id INTO slot_id FROM admin_hw_appointment_slots 
        WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time
        LIMIT 1;
        
        IF slot_id IS NULL THEN
            -- Create slot if it doesn't exist
            INSERT INTO admin_hw_appointment_slots (schedule_id, slot_time, is_booked, appointment_id)
            VALUES (NEW.schedule_id, NEW.appointment_time, 1, NEW.id);
        ELSE
            -- Update existing slot
            UPDATE admin_hw_appointment_slots 
            SET is_booked = 1
            WHERE id = slot_id;
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `clients_user_accounts`
--

CREATE TABLE `clients_user_accounts` (
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
-- Dumping data for table `clients_user_accounts`
--

INSERT INTO `clients_user_accounts` (`id`, `full_name`, `email`, `password`, `phone_number`, `address`, `date_of_birth`, `gender`, `profile_picture`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES
(6, 'Leomar Escobin', 'leomaresc853@gmail.com', '9f9974d013e8c0b3b51fc70c01db38ab', '099198719610', 'Main House Baskerville01', '2003-09-23', 'Male', '6_1750865193.jpg', '2025-04-17 08:37:27', 'bd56e5a8a8b2d110ee834309da93b0f0ccda05f9059dd3ebb4e9aa353a816470', '2025-07-05 06:19:22'),
(7, 'Pauline Oliveros', 'oliverospaulinekaye03@gmail.com', 'e84d01bdb3aca89bcfca98d1bfd0db9d', '09765455654', '001', '2003-03-25', 'Female', 'default_client.png', '2025-06-24 20:51:14', NULL, NULL),
(8, 'Aila Drine Niala', 'nialaaila38@gmail.com', 'e4db616efaffdbb51d538843480330f5', '09787876787', '002', '2003-03-26', 'Female', 'default_client.png', '2025-06-24 20:55:11', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `client_password_resets`
--

CREATE TABLE `client_password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_password_resets`
--

INSERT INTO `client_password_resets` (`id`, `email`, `token`, `expiry`, `used`, `created_at`) VALUES
(1, 'leomaresc853@gmail.com', '29b87ece0be011cb664ffebdf227471579ed8147600128063b4a35964b24a155', '2025-07-05 07:17:41', 0, '2025-07-05 04:17:41'),
(2, 'leomaresc853@gmail.com', '693b53f5173953d4ab1c525589b54162bf7f05e65d49910c73be574d593414a3', '2025-07-05 07:54:12', 1, '2025-07-05 04:54:12'),
(3, 'leomaresc853@gmail.com', '74fea088fb55cd3f403974040e43dd01901c6276eb4be28829f2575b75e6b52d', '2025-07-08 20:46:04', 1, '2025-07-08 17:46:04');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If 1, record is archived',
  `archived_at` timestamp NULL DEFAULT NULL COMMENT 'When record was archived',
  `archived_by` int(11) DEFAULT NULL COMMENT 'User ID who archived the record',
  `archive_reason` text DEFAULT NULL COMMENT 'Reason for archiving'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_bp_monitoring`
--

INSERT INTO `general_bp_monitoring` (`id`, `name`, `date`, `address`, `sex`, `bp`, `alcohol`, `smoke`, `obese`, `cp_number`, `created_at`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`) VALUES
(8, 'Test 01', '2025-07-02', 'Baskerville  Main Baskerville', 'Male', '120/30', 1, 1, 0, '09878776765', '2025-07-01 19:36:28', 1, '2025-07-02 16:30:17', 1, 'test 2');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If 1, record is archived',
  `archived_at` timestamp NULL DEFAULT NULL COMMENT 'When record was archived',
  `archived_by` int(11) DEFAULT NULL COMMENT 'User ID who archived the record',
  `archive_reason` text DEFAULT NULL COMMENT 'Reason for archiving'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_deworming`
--

INSERT INTO `general_deworming` (`id`, `name`, `date`, `age`, `birthday`, `created_at`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`) VALUES
(8, 'Test 01', '2025-07-02', 21, '2024-12-31', '2025-07-01 20:47:40', 1, '2025-07-02 15:21:54', 1, 'test 2');

-- --------------------------------------------------------

--
-- Table structure for table `general_family_members`
--

CREATE TABLE `general_family_members` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If 1, record is archived',
  `archived_at` timestamp NULL DEFAULT NULL COMMENT 'When record was archived',
  `archived_by` int(11) DEFAULT NULL COMMENT 'User ID who archived the record',
  `archive_reason` text DEFAULT NULL COMMENT 'Reason for archiving'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_family_members`
--

INSERT INTO `general_family_members` (`id`, `name`, `date`, `created_at`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`) VALUES
(11, 'Test 01', '2025-07-02', '2025-07-01 16:35:03', 1, '2025-07-02 14:07:16', 1, 'test 2');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If 1, record is archived',
  `archived_at` timestamp NULL DEFAULT NULL COMMENT 'When record was archived',
  `archived_by` int(11) DEFAULT NULL COMMENT 'User ID who archived the record',
  `archive_reason` text DEFAULT NULL COMMENT 'Reason for archiving'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_family_planning`
--

INSERT INTO `general_family_planning` (`id`, `name`, `date`, `age`, `address`, `created_at`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`) VALUES
(9, 'Test 01', '2025-07-02', 20, 'Main House Baskerville01', '2025-07-01 18:01:20', 1, '2025-07-02 14:22:16', 1, 'test 2');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_rbs`
--

INSERT INTO `general_rbs` (`id`, `name`, `date`, `address`, `age`, `result`, `created_at`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`) VALUES
(8, 'Test 01', '2025-07-02', 'Main House Baskerville01', 21, 'B', '2025-07-02 12:52:17', 1, '2025-07-02 17:49:40', 1, 'test 2');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_tetanus_toxoid`
--

INSERT INTO `general_tetanus_toxoid` (`id`, `name`, `date`, `address`, `age`, `diagnosis`, `remarks`, `created_at`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`) VALUES
(7, 'Test 01', '2025-07-02', 'Main House Baskerville01', 21, 'test 01', 'test 01', '2025-07-02 13:39:55', 1, '2025-07-02 19:16:29', 1, 'test 2');

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
-- Structure for view `medicine_dispensing_history`
--
DROP TABLE IF EXISTS `medicine_dispensing_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `medicine_dispensing_history`  AS SELECT `d`.`id` AS `id`, `d`.`dispensed_date` AS `dispensed_date`, `m`.`medicine_name` AS `medicine_name`, `m`.`generic_name` AS `generic_name`, `c`.`category_name` AS `category_name`, `s`.`batch_number` AS `batch_number`, `d`.`quantity` AS `quantity`, `d`.`patient_name` AS `patient_name`, `d`.`remarks` AS `remarks`, `u`.`display_name` AS `dispensed_by` FROM ((((`medicine_dispensing` `d` join `medicine_stock` `s` on(`d`.`stock_id` = `s`.`id`)) join `medicines` `m` on(`d`.`medicine_id` = `m`.`id`)) join `medicine_categories` `c` on(`m`.`category_id` = `c`.`id`)) left join `admin_user_accounts` `u` on(`d`.`dispensed_by` = `u`.`id`)) ORDER BY `d`.`dispensed_date` DESC, `d`.`created_at` DESC ;

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
-- Indexes for table `admin_clients_appointments`
--
ALTER TABLE `admin_clients_appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_patient_appointment` (`patient_name`,`schedule_id`,`appointment_time`,`status`),
  ADD UNIQUE KEY `unique_active_appointment` (`schedule_id`,`appointment_time`,`status`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `fk_appointments_archived_by` (`archived_by`);

--
-- Indexes for table `admin_doctor_appointment_slots`
--
ALTER TABLE `admin_doctor_appointment_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_schedule_slot` (`schedule_id`,`slot_time`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_slot_time` (`slot_time`),
  ADD KEY `idx_is_booked` (`is_booked`),
  ADD KEY `idx_appointment_id` (`appointment_id`);

--
-- Indexes for table `admin_doctor_schedules`
--
ALTER TABLE `admin_doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `schedule_date` (`schedule_date`),
  ADD KEY `idx_doctor_schedules_doctor_id` (`doctor_id`),
  ADD KEY `idx_doctor_schedules_schedule_date` (`schedule_date`),
  ADD KEY `idx_doctor_schedules_is_approved` (`is_approved`);

--
-- Indexes for table `admin_hw_appointment_slots`
--
ALTER TABLE `admin_hw_appointment_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_schedule_slot` (`schedule_id`,`slot_time`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_slot_time` (`slot_time`),
  ADD KEY `idx_is_booked` (`is_booked`),
  ADD KEY `idx_appointment_id` (`appointment_id`);

--
-- Indexes for table `admin_hw_schedules`
--
ALTER TABLE `admin_hw_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `schedule_date` (`schedule_date`),
  ADD KEY `idx_staff_schedules_staff_id` (`staff_id`),
  ADD KEY `idx_staff_schedules_schedule_date` (`schedule_date`);

--
-- Indexes for table `admin_time_in_attendance_logs`
--
ALTER TABLE `admin_time_in_attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_time_in` (`user_id`,`log_date`);

--
-- Indexes for table `admin_time_logs`
--
ALTER TABLE `admin_time_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`log_date`),
  ADD KEY `fk_time_logs_user_id` (`user_id`);

--
-- Indexes for table `admin_time_out_attendance_logs`
--
ALTER TABLE `admin_time_out_attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_time_out` (`user_id`,`log_date`);

--
-- Indexes for table `admin_user_accounts`
--
ALTER TABLE `admin_user_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_name` (`user_name`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `admin_walkin_appointments`
--
ALTER TABLE `admin_walkin_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_provider_id` (`provider_id`),
  ADD KEY `idx_booked_by` (`booked_by`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_appointment_time` (`appointment_time`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_provider_type` (`provider_type`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_walk_in_time` (`walk_in_time`),
  ADD KEY `fk_walkin_archived_by` (`archived_by`);

--
-- Indexes for table `clients_user_accounts`
--
ALTER TABLE `clients_user_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `client_password_resets`
--
ALTER TABLE `client_password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `general_bp_monitoring`
--
ALTER TABLE `general_bp_monitoring`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_general_bp_monitoring_archived_by` (`archived_by`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Indexes for table `general_deworming`
--
ALTER TABLE `general_deworming`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_general_deworming_archived_by` (`archived_by`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Indexes for table `general_family_members`
--
ALTER TABLE `general_family_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_general_family_members_archived_by` (`archived_by`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Indexes for table `general_family_planning`
--
ALTER TABLE `general_family_planning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_general_family_planning_archived_by` (`archived_by`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Indexes for table `general_rbs`
--
ALTER TABLE `general_rbs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rbs_archived_by` (`archived_by`),
  ADD KEY `idx_rbs_is_archived` (`is_archived`),
  ADD KEY `idx_rbs_archived_at` (`archived_at`);

--
-- Indexes for table `general_tetanus_toxoid`
--
ALTER TABLE `general_tetanus_toxoid`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tetanus_toxoid_archived_by` (`archived_by`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_archived_at` (`archived_at`);

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
-- Indexes for table `stock_movement_log`
--
ALTER TABLE `stock_movement_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `stock_id` (`stock_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_clients_appointments`
--
ALTER TABLE `admin_clients_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `admin_doctor_appointment_slots`
--
ALTER TABLE `admin_doctor_appointment_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=435;

--
-- AUTO_INCREMENT for table `admin_doctor_schedules`
--
ALTER TABLE `admin_doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `admin_hw_appointment_slots`
--
ALTER TABLE `admin_hw_appointment_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `admin_hw_schedules`
--
ALTER TABLE `admin_hw_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `admin_time_in_attendance_logs`
--
ALTER TABLE `admin_time_in_attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `admin_time_logs`
--
ALTER TABLE `admin_time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `admin_time_out_attendance_logs`
--
ALTER TABLE `admin_time_out_attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `admin_user_accounts`
--
ALTER TABLE `admin_user_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `admin_walkin_appointments`
--
ALTER TABLE `admin_walkin_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `clients_user_accounts`
--
ALTER TABLE `clients_user_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `client_password_resets`
--
ALTER TABLE `client_password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `stock_movement_log`
--
ALTER TABLE `stock_movement_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_clients_appointments`
--
ALTER TABLE `admin_clients_appointments`
  ADD CONSTRAINT `fk_appointments_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `admin_user_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `admin_doctor_appointment_slots`
--
ALTER TABLE `admin_doctor_appointment_slots`
  ADD CONSTRAINT `admin_doctor_appointment_slots_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `admin_doctor_schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_doctor_schedules`
--
ALTER TABLE `admin_doctor_schedules`
  ADD CONSTRAINT `admin_doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `admin_user_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_walkin_appointments`
--
ALTER TABLE `admin_walkin_appointments`
  ADD CONSTRAINT `fk_walkin_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `admin_user_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_walkin_booked_by` FOREIGN KEY (`booked_by`) REFERENCES `admin_user_accounts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_walkin_provider_id` FOREIGN KEY (`provider_id`) REFERENCES `admin_user_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `general_bp_monitoring`
--
ALTER TABLE `general_bp_monitoring`
  ADD CONSTRAINT `fk_general_bp_monitoring_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `admin_user_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `general_deworming`
--
ALTER TABLE `general_deworming`
  ADD CONSTRAINT `fk_general_deworming_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `admin_user_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `general_family_members`
--
ALTER TABLE `general_family_members`
  ADD CONSTRAINT `fk_general_family_members_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `admin_user_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `general_family_planning`
--
ALTER TABLE `general_family_planning`
  ADD CONSTRAINT `fk_general_family_planning_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `admin_user_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `general_rbs`
--
ALTER TABLE `general_rbs`
  ADD CONSTRAINT `fk_rbs_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `admin_user_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `general_tetanus_toxoid`
--
ALTER TABLE `general_tetanus_toxoid`
  ADD CONSTRAINT `fk_tetanus_toxoid_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `admin_user_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
  ADD CONSTRAINT `medicine_dispensing_ibfk_3` FOREIGN KEY (`dispensed_by`) REFERENCES `admin_user_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  ADD CONSTRAINT `medicine_stock_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
