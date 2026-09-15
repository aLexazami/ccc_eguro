-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 15, 2026 at 02:06 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_eguro`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `activity_log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `date_log` datetime NOT NULL DEFAULT current_timestamp(),
  `action` longtext NOT NULL DEFAULT '',
  `session_id` varchar(255) NOT NULL DEFAULT '',
  `user_level` varchar(100) NOT NULL DEFAULT '0',
  `system_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`activity_log_id`, `user_id`, `date_log`, `action`, `session_id`, `user_level`, `system_id`) VALUES
(1, 1, '2026-09-11 19:18:00', 'ADDED EMPLOYEE ACCOUNT SYSTEM ACCESS :: Details: {\"user_id\":\"1\",\"ref_id\":0,\"system_type\":\"E-APP\",\"system_role\":\"1\",\"access_tag\":\"EMPLOYEE\",\"student_update\":0,\"employee_update\":0,\"flag_access\":0,\"date_modify\":\"2026-09-11 19:18:00\",\"assigned_access_systems\":{\"E-APP\":\"1\"}}', '4401eaf569eeedbd35f927c8344b669a243ff3a431e82d784e3d27fc794c2e2e', '1', 0),
(2, 1, '2026-09-14 09:28:43', 'ADDED EMPLOYEE ACCOUNT SYSTEM ACCESS :: Details: {\"user_id\":\"1\",\"ref_id\":0,\"system_type\":\"E-APP\",\"system_role\":\"1\",\"access_tag\":\"EMPLOYEE\",\"student_update\":0,\"employee_update\":0,\"flag_access\":0,\"date_modify\":\"2026-09-14 09:28:43\",\"assigned_access_systems\":{\"E-APP\":\"1\"}}', '4401eaf569eeedbd35f927c8344b669a243ff3a431e82d784e3d27fc794c2e2e', '1', 0),
(17, 1, '2026-09-14 09:55:25', 'ADDED EMPLOYEE ACCOUNT SYSTEM ACCESS :: Details: {\"user_id\":\"1\",\"ref_id\":0,\"system_type\":\"E-APP\",\"system_role\":\"1\",\"access_tag\":\"EMPLOYEE\",\"student_update\":0,\"employee_update\":0,\"flag_access\":0,\"date_modify\":\"2026-09-14 09:55:25\",\"assigned_access_systems\":{\"E-APP\":\"1\"}}', '4401eaf569eeedbd35f927c8344b669a243ff3a431e82d784e3d27fc794c2e2e', '1', 0),
(20, 1, '2026-09-14 10:18:51', 'ADDED EMPLOYEE ACCOUNT SYSTEM ACCESS :: Details: {\"user_id\":\"1\",\"ref_id\":0,\"system_type\":\"E-APP\",\"system_role\":\"1\",\"access_tag\":\"EMPLOYEE\",\"student_update\":0,\"employee_update\":0,\"flag_access\":0,\"date_modify\":\"2026-09-14 10:18:51\",\"assigned_access_systems\":{\"E-APP\":\"1\"}}', '4401eaf569eeedbd35f927c8344b669a243ff3a431e82d784e3d27fc794c2e2e', '1', 0),
(21, 1, '2026-09-14 10:23:49', 'ADDED EMPLOYEE ACCOUNT SYSTEM ACCESS :: Details: {\"user_id\":\"1\",\"ref_id\":0,\"system_type\":\"E-APP\",\"system_role\":\"1\",\"access_tag\":\"EMPLOYEE\",\"student_update\":0,\"employee_update\":0,\"flag_access\":0,\"date_modify\":\"2026-09-14 10:23:49\",\"assigned_access_systems\":{\"E-APP\":\"1\"}}', '4401eaf569eeedbd35f927c8344b669a243ff3a431e82d784e3d27fc794c2e2e', '1', 0);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL DEFAULT '',
  `department_code` varchar(100) NOT NULL DEFAULT '',
  `flag_status` int(11) NOT NULL DEFAULT 0,
  `flag_update` int(11) NOT NULL DEFAULT 0 COMMENT '0 - not updated\r\n1 - updated',
  `date_modify` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `department_code`, `flag_status`, `flag_update`, `date_modify`) VALUES
(1, 'Department of Arts and Sciences', 'DAS', 0, 0, '2026-07-11 11:58:12'),
(2, 'Department of Business and Accounting', 'DBA', 0, 0, '2026-07-11 11:58:12'),
(3, 'Department of Computing and Informatics', 'DCI', 0, 0, '2026-07-11 11:58:12'),
(4, 'Department of Teacher Education', 'DTE', 0, 0, '2026-07-11 11:58:12'),
(5, 'Department of Lifelong and Flexible Learning', 'DLFL', 0, 0, '2026-07-11 11:58:12');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `employee_id` varchar(100) NOT NULL DEFAULT '',
  `personnel_classification` enum('TEACHING PERSONNEL','NON-TEACHING PERSONNEL','') NOT NULL DEFAULT '' COMMENT 'PERSONNEL CLASSIFICATION\r\n- TEACHING PERSONNEL\r\n- NON-TEACHING PERSONNEL',
  `employment_status` enum('PERMANENT','CONTRACT OF SERVICE',' JOB ORDER','') NOT NULL DEFAULT '' COMMENT 'EMPLOYMENT STATUS\r\n- PERMANENT\r\n- CONTRACT OF SERVICE\r\n- JOB ORDER',
  `employment_basis` enum('','PART TIME','FULL TIME') NOT NULL DEFAULT '' COMMENT 'PART TIME\r\nFULL TIME\r\n',
  `position` varchar(255) NOT NULL DEFAULT '' COMMENT 'JOB TITLE',
  `profile_pic` varchar(100) DEFAULT NULL,
  `cover_photo` varchar(100) DEFAULT NULL,
  `employment_date` longtext NOT NULL DEFAULT '[""]',
  `office_id` int(11) NOT NULL DEFAULT 0,
  `department_id` int(11) NOT NULL DEFAULT 0,
  `room_id` int(11) NOT NULL DEFAULT 0,
  `service_status` int(11) NOT NULL DEFAULT 0,
  `flag_update` int(11) NOT NULL DEFAULT 0,
  `date_modify` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`id`, `user_id`, `employee_id`, `personnel_classification`, `employment_status`, `employment_basis`, `position`, `profile_pic`, `cover_photo`, `employment_date`, `office_id`, `department_id`, `room_id`, `service_status`, `flag_update`, `date_modify`) VALUES
(1, 1, 'CGC-123456', 'NON-TEACHING PERSONNEL', 'CONTRACT OF SERVICE', '', '', NULL, NULL, '[\"\"]', 0, 0, 0, 0, 0, '2026-08-12 14:57:44');

-- --------------------------------------------------------

--
-- Table structure for table `facility`
--

CREATE TABLE `facility` (
  `facility_id` int(11) NOT NULL,
  `facility_code` varchar(100) NOT NULL DEFAULT '',
  `facility_name` varchar(500) NOT NULL DEFAULT '',
  `facility_floor` longtext NOT NULL DEFAULT '{}',
  `flag_status` int(11) NOT NULL DEFAULT 0,
  `flag_update` int(11) NOT NULL DEFAULT 0 COMMENT '0 - not updated\r\n1 - updated',
  `date_modify` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `facility`
--

INSERT INTO `facility` (`facility_id`, `facility_code`, `facility_name`, `facility_floor`, `flag_status`, `flag_update`, `date_modify`) VALUES
(1, 'ADMIN BLDG', 'Admin Building', '{\"Under Ground Floor\", \"Ground Floor\", \"Second Floor\"}', 0, 0, '2026-09-09 16:49:34'),
(2, 'RIZAL BLDG', 'Rizal Building', '{\"Under Ground Floor\", \"Ground Floor\", \"Second Floor\"}', 0, 0, '2026-09-09 16:49:34'),
(3, 'JMC BLDG', 'Joaquin M. Chipeco Building', '{\"Under Ground Floor\", \"Ground Floor\", \"Second Floor\"}', 0, 0, '2026-09-09 16:49:34');

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `system_access` varchar(255) NOT NULL DEFAULT '',
  `data_log` longtext NOT NULL DEFAULT '\'\'',
  `process_flag` int(11) NOT NULL DEFAULT 0,
  `action_flag` int(11) NOT NULL DEFAULT 0,
  `date_log` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `username` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `recovery_email` varchar(100) DEFAULT '',
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0-active 1-deactivate 2-delete',
  `locked` int(11) NOT NULL DEFAULT 0 COMMENT '0-unlock 1-locked',
  `flag_update` int(11) NOT NULL DEFAULT 0 COMMENT '0 - not updated\r\n1 - updated',
  `flag_validity` int(11) NOT NULL DEFAULT 0,
  `date_modify` datetime NOT NULL DEFAULT current_timestamp(),
  `date_username` date NOT NULL DEFAULT current_timestamp(),
  `date_password` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `user_id`, `username`, `password`, `recovery_email`, `status`, `locked`, `flag_update`, `flag_validity`, `date_modify`, `date_username`, `date_password`) VALUES
(1, '1', 'mlreolo@ccc.edu.ph', '$2y$10$Z/7cFcWlArZTuUNiK4VfJu52BBwt2ezyiHzZO7AfZu1eaIWt7CFbS', NULL, 0, 0, 0, 0, '2026-08-12 17:04:12', '2026-08-12', '2026-08-12');

-- --------------------------------------------------------

--
-- Table structure for table `office`
--

CREATE TABLE `office` (
  `office_id` int(11) NOT NULL,
  `office_code` varchar(100) NOT NULL DEFAULT '',
  `office_name` varchar(255) NOT NULL DEFAULT '',
  `office_logo` varchar(100) DEFAULT NULL,
  `flag_status` int(11) NOT NULL DEFAULT 0,
  `flag_update` int(11) NOT NULL DEFAULT 0,
  `date_modify` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `office`
--

INSERT INTO `office` (`office_id`, `office_code`, `office_name`, `office_logo`, `flag_status`, `flag_update`, `date_modify`) VALUES
(1, 'Office of the College President', 'OCP', NULL, 0, 0, '2026-09-09 17:10:00'),
(2, 'Office of the Vice President for Academic Affairs', 'OVPAA', NULL, 0, 0, '2026-09-09 17:10:00'),
(3, 'Office of the Vice President for Student Development and Auxiliary', 'OVPSDA', NULL, 0, 0, '2026-09-09 17:10:00'),
(4, 'Office of the Vice President for Administration and Finance', 'OVPAF', NULL, 0, 0, '2026-09-09 17:10:00'),
(5, 'Office of the Vice President for Research, Extension, Planning, and Quality Assurance', 'OVPREPQA', NULL, 0, 0, '2026-09-09 17:10:00'),
(6, 'Office of the Vice President for College Advancement and Relations', 'OVPCAR', NULL, 0, 0, '2026-09-09 17:10:00'),
(7, 'Department of Teacher Education', 'DTE', NULL, 0, 0, '2026-09-09 17:10:00'),
(8, 'Department of Arts and Sciences', 'DAS', NULL, 0, 0, '2026-09-09 17:10:00'),
(9, 'Department of Business and Accountancy', 'DBA', NULL, 0, 0, '2026-09-09 17:10:00'),
(10, 'Department of Computing and Informatics', 'DCI', NULL, 0, 0, '2026-09-09 17:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `password_history`
--

CREATE TABLE `password_history` (
  `pass_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `user_type` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(100) NOT NULL DEFAULT '',
  `inc` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `password_history`
--

INSERT INTO `password_history` (`pass_id`, `user_id`, `user_type`, `password`, `inc`) VALUES
(1, 1, 'user', '$2y$10$1IVneaN/OgILge5vfDbPg.3.T4ZXYCziU4z1XFchOdyJvJ0JH9Q4e', 0),
(2, 1, 'user', '$2y$10$labDoWaCEIn6zXYXvRPobO15KAsJy5Uuc7ws0nvEzrhAXjtZ9oWuq', 0);

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL DEFAULT 0,
  `program_code` varchar(100) NOT NULL DEFAULT '',
  `program_name` varchar(255) NOT NULL DEFAULT '',
  `major` varchar(100) DEFAULT '',
  `program_major` varchar(500) DEFAULT '' COMMENT 'Program Major (UNIQUE)',
  `flag_offer` int(11) NOT NULL DEFAULT 0 COMMENT '0 - still offered\r\n1 - not offered\r\n',
  `flag_status` int(11) NOT NULL DEFAULT 0,
  `flag_update` int(11) NOT NULL DEFAULT 0 COMMENT '0 - not updated\r\n1 - updated',
  `date_modify` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `department_id`, `program_code`, `program_name`, `major`, `program_major`, `flag_offer`, `flag_status`, `flag_update`, `date_modify`) VALUES
(1, 1, 'BSPsy', 'Bachelor of Science in Psychology', '', '', 0, 0, 0, '2026-07-11 13:39:37'),
(2, 2, 'BSA', 'Bachelor of Science in Accountancy', '', '', 0, 0, 0, '2026-07-11 13:39:37'),
(3, 2, 'BSAIS', 'Bachelor of Science in Accounting Information System', '', '', 0, 0, 0, '2026-07-11 13:39:37'),
(4, 3, 'BSCS', 'Bachelor of Science in Computer Science', '', '', 0, 0, 0, '2026-07-11 13:39:37'),
(5, 3, 'BSIT', 'Bachelor of Science in Information Technology', '', '', 0, 0, 0, '2026-07-11 13:39:37'),
(6, 4, 'BEEd', 'Bachelor of Elementary Education', '', '', 0, 0, 0, '2026-07-11 13:39:37'),
(7, 4, 'BSEdM', 'Bachelor of Secondary Education', 'Mathematics', '', 0, 0, 0, '2026-07-11 13:39:37'),
(8, 4, 'BSEdE', 'Bachelor of Secondary Education', 'English', '', 0, 0, 0, '2026-07-11 13:39:37'),
(9, 4, 'BSEdS', 'Bachelor of Secondary Education', 'Science', '', 0, 0, 0, '2026-07-11 13:39:37'),
(10, 4, 'BSEdF', 'Bachelor of Secondary Education', 'Filipino', '', 0, 0, 0, '2026-07-11 13:39:37'),
(11, 4, 'BSEdSoc', 'Bachelor of Secondary Education', 'Social Studies', '', 0, 0, 0, '2026-07-11 13:39:37'),
(12, 4, 'BECED', 'Bachelor of Early Childhood Education', '', '', 0, 0, 0, '2026-07-11 13:39:37');

-- --------------------------------------------------------

--
-- Table structure for table `reset_code`
--

CREATE TABLE `reset_code` (
  `reset_id` int(11) NOT NULL,
  `reset_code` varchar(50) NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL DEFAULT 0,
  `email_address` varchar(50) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `expire_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 0,
  `user_type` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_id` int(11) NOT NULL,
  `room_code` varchar(100) NOT NULL DEFAULT '',
  `room_name` varchar(255) NOT NULL DEFAULT '',
  `room_type` varchar(100) NOT NULL DEFAULT '' COMMENT 'ROOM\r\nOFFICE\r\nLABORATORY',
  `room_details` text NOT NULL DEFAULT '',
  `facility_id` int(11) NOT NULL DEFAULT 0,
  `facility_floor` varchar(100) NOT NULL DEFAULT '',
  `flag_status` int(11) NOT NULL DEFAULT 0,
  `flag_update` int(11) NOT NULL DEFAULT 0 COMMENT '0 - not updated\r\n1 - updated',
  `date_modify` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `student_id` varchar(100) NOT NULL DEFAULT '',
  `year_level` int(11) DEFAULT 0,
  `department_id` int(11) DEFAULT 0,
  `program_id` int(11) DEFAULT 0,
  `major` varchar(100) DEFAULT NULL,
  `profile_pic` varchar(100) DEFAULT 'default-profile.jpg',
  `cover_photo` varchar(100) DEFAULT 'default-cover.jpg',
  `flag_status` int(11) NOT NULL DEFAULT 0,
  `flag_update` int(11) NOT NULL DEFAULT 0 COMMENT '0 - not updated\r\n1 - updated',
  `date_modify` datetime NOT NULL DEFAULT current_timestamp(),
  `graduated_data` longtext DEFAULT NULL,
  `additional_data` longtext DEFAULT NULL,
  `academic_status` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `user_id`, `student_id`, `year_level`, `department_id`, `program_id`, `major`, `profile_pic`, `cover_photo`, `flag_status`, `flag_update`, `date_modify`, `graduated_data`, `additional_data`, `academic_status`) VALUES
(1, 1, '', 0, 0, 0, NULL, '1788179954_6a9575f28bf28_Photo_ID__1_.webp', '1788179963_6a9575fb16195_HEADER_WEBSITE__3_.webp', 0, 0, '2026-08-31 20:31:12', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `system_access`
--

CREATE TABLE `system_access` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `ref_id` int(11) NOT NULL DEFAULT 0 COMMENT 'user id [Ref API]',
  `system_type` varchar(100) NOT NULL DEFAULT '',
  `system_role` int(11) NOT NULL DEFAULT 0,
  `access_tag` enum('','EMPLOYEE','STUDENT') NOT NULL DEFAULT '',
  `employee_update` int(11) NOT NULL DEFAULT 0,
  `student_update` int(11) NOT NULL DEFAULT 0,
  `flag_access` int(11) NOT NULL DEFAULT 0,
  `date_modify` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `system_access`
--

INSERT INTO `system_access` (`id`, `user_id`, `ref_id`, `system_type`, `system_role`, `access_tag`, `employee_update`, `student_update`, `flag_access`, `date_modify`) VALUES
(1, 1, 0, 'E-GURO++', 1, 'EMPLOYEE', 0, 0, 0, '2026-09-11 19:12:34'),
(2, 1, 1, 'E-APP', 1, 'EMPLOYEE', 0, 0, 0, '2026-09-14 10:23:49');

-- --------------------------------------------------------

--
-- Table structure for table `system_key`
--

CREATE TABLE `system_key` (
  `system_id` int(11) NOT NULL,
  `system_type` varchar(255) NOT NULL,
  `system_key` varchar(255) NOT NULL,
  `public_key` varchar(255) NOT NULL,
  `secret_key` varchar(255) NOT NULL,
  `img` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `system_key`
--

INSERT INTO `system_key` (`system_id`, `system_type`, `system_key`, `public_key`, `secret_key`, `img`) VALUES
(1, 'E-GURO++', '239cf01a82900e2ae22aa1c2daebc778353438345acf066989dd896c9a267584', '700aa2c4072294be8b4dfd86a7efd1e88d176fc38c02c4660800174c5eae5cc2', 'K0txckNjanJhSFJWQmlrSlpwRC9nREhmMzJ3SnRMRmxvSERLSGdqQVY2c2JHS1RYZTRPZ3I3L0t5ellBUXcwUHI2bExpeUV1cHpxaTBpdDh4QkJMaytYVUxvZTZrNzkzVjlITVVVRXVORHc9Ojo_PLUS_WVNXi9pedXZrJWb507xd', NULL),
(2, 'E-APP', '4c564a29da1696b7c2668456d30ad442054cccf615b427ff2cc2619f14d9a832', '8d170effcea914034b2b437c30549166a6facb79fbcce9157bb3dd5d7d28cc94', 'QmxSOE16TGsySGdxRVdNOThNalMrdldsTjRLMmNXUEcxcHJFb2NSbUV6VjBJMXpORHZSOGg4UENBdWVuaFBJNGJKNzVRM01IdmZVYzluc0tvSnNvczlSTVlTWmlGNTh5OVpuVmJCTWc4Y0U9Ojo3Kwg0zq_PLUS_3243A_PLUS_N_SLASH_fbiFV', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `ref_id` int(11) NOT NULL DEFAULT 0 COMMENT 'user id [Ref API]',
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `middle_name` varchar(255) NOT NULL DEFAULT '',
  `middle_initial` varchar(10) NOT NULL DEFAULT '',
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `suffix` varchar(255) NOT NULL DEFAULT '',
  `post_nominal` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `sex` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `personal_email` varchar(100) DEFAULT '' COMMENT 'serve as Recovery Email Address [CCC Email Default]',
  `civil_status` varchar(100) NOT NULL DEFAULT '',
  `nationality` varchar(100) NOT NULL DEFAULT '',
  `birth_place` varchar(100) NOT NULL DEFAULT '',
  `contact_no` varchar(50) NOT NULL DEFAULT '',
  `brgy` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `province` varchar(255) NOT NULL DEFAULT '',
  `home_address` text NOT NULL DEFAULT '',
  `profile_pic` varchar(100) DEFAULT 'profile-img.png',
  `cover_photo` varchar(100) DEFAULT 'default-cover.jpg',
  `e_name` longtext NOT NULL DEFAULT '',
  `e_relationship` varchar(100) NOT NULL DEFAULT '',
  `e_contact` longtext NOT NULL DEFAULT '',
  `e_address` longtext NOT NULL DEFAULT '',
  `flag_status` int(11) NOT NULL DEFAULT 0,
  `flag_update` int(11) NOT NULL DEFAULT 0,
  `date_modify` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `ref_id`, `first_name`, `middle_name`, `middle_initial`, `last_name`, `suffix`, `post_nominal`, `birth_date`, `sex`, `email`, `personal_email`, `civil_status`, `nationality`, `birth_place`, `contact_no`, `brgy`, `city`, `province`, `home_address`, `profile_pic`, `cover_photo`, `e_name`, `e_relationship`, `e_contact`, `e_address`, `flag_status`, `flag_update`, `date_modify`) VALUES
(1, 0, 'MARLON', '', '', 'REOLO', '', NULL, '1995-12-04', 'male', 'mlreolo@ccc.edu.ph', NULL, 'single', 'Filipino', 'Calamba, Laguna', '09199834580', 'Mayapa', 'Calamba City', 'Laguna', 'Christopher II', NULL, NULL, '', '', '', '', 0, 0, '2026-09-03 10:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `user_log`
--

CREATE TABLE `user_log` (
  `user_log_id` int(11) NOT NULL,
  `login_date` datetime NOT NULL,
  `logout_date` datetime NOT NULL,
  `action` varchar(20) NOT NULL,
  `user_id` text NOT NULL,
  `session_id` text NOT NULL,
  `ip_address` varchar(20) NOT NULL,
  `device` varchar(255) NOT NULL,
  `system_id` int(11) NOT NULL DEFAULT 0,
  `token_id` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`token_id`)),
  `login_flag` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_log`
--

INSERT INTO `user_log` (`user_log_id`, `login_date`, `logout_date`, `action`, `user_id`, `session_id`, `ip_address`, `device`, `system_id`, `token_id`, `login_flag`) VALUES
(1, '2026-09-11 19:12:55', '0000-00-00 00:00:00', 'LOGIN', '1', '4401eaf569eeedbd35f927c8344b669a243ff3a431e82d784e3d27fc794c2e2e', '::1', '{\"device\":\"Chrome\",\"version\":\"151.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 151.0.0.0 on Windows 10 64-bit\"}', 0, '[]', 0),
(2, '2026-09-14 09:20:59', '0000-00-00 00:00:00', 'LOGIN', '1', '4401eaf569eeedbd35f927c8344b669a243ff3a431e82d784e3d27fc794c2e2e', '::1', '{\"device\":\"Chrome\",\"version\":\"151.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 151.0.0.0 on Windows 10 64-bit\"}', 0, '[]', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`activity_log_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `facility`
--
ALTER TABLE `facility`
  ADD PRIMARY KEY (`facility_id`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `LOGIN ACCESS` (`user_id`,`username`);

--
-- Indexes for table `office`
--
ALTER TABLE `office`
  ADD PRIMARY KEY (`office_id`);

--
-- Indexes for table `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`pass_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`);

--
-- Indexes for table `reset_code`
--
ALTER TABLE `reset_code`
  ADD PRIMARY KEY (`reset_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_access`
--
ALTER TABLE `system_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `SYSTEM ACCESSS` (`user_id`,`system_type`,`system_role`);

--
-- Indexes for table `system_key`
--
ALTER TABLE `system_key`
  ADD PRIMARY KEY (`system_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_log`
--
ALTER TABLE `user_log`
  ADD PRIMARY KEY (`user_log_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `activity_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `facility`
--
ALTER TABLE `facility`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `office`
--
ALTER TABLE `office`
  MODIFY `office_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `password_history`
--
ALTER TABLE `password_history`
  MODIFY `pass_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reset_code`
--
ALTER TABLE `reset_code`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_access`
--
ALTER TABLE `system_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_key`
--
ALTER TABLE `system_key`
  MODIFY `system_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_log`
--
ALTER TABLE `user_log`
  MODIFY `user_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
