-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 06, 2025 at 03:29 PM
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
-- Database: `edulux`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notes`
--

CREATE TABLE `admin_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED NOT NULL,
  `note` text NOT NULL,
  `type` enum('approval','rejection','warning','ban','general') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-folder',
  `color` varchar(7) DEFAULT '#6366f1',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `color`, `is_active`, `created_at`) VALUES
(1, 'Finance & Accounting', 'finance', 'Elite financial mastery', 'fas fa-chart-line', '#10b981', 1, '2025-11-21 07:42:47'),
(2, 'Leadership & Strategy', 'leadership', 'Command with excellence', 'fas fa-crown', '#8b5cf6', 1, '2025-11-21 07:42:47'),
(3, 'Data Science & AI', 'data-science', 'Future-proof skills', 'fas fa-brain', '#3b82f6', 1, '2025-11-21 07:42:47'),
(4, 'Professional Development', 'professional', 'Career acceleration', 'fas fa-rocket', '#f59e0b', 1, '2025-11-21 07:42:47'),
(6, 'Engineering', '', NULL, 'fas fa-folder', '#6366f1', 1, '2025-12-03 23:56:32');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `certificate_code` varchar(20) NOT NULL,
  `issued_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_description` text NOT NULL,
  `full_description` longtext DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_weeks` int(11) DEFAULT 8,
  `level` enum('Beginner','Intermediate','Advanced','Expert') DEFAULT 'Intermediate',
  `format` enum('Live','Recorded','Hybrid') DEFAULT 'Hybrid',
  `language` varchar(50) DEFAULT 'English',
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `instructor_id` bigint(20) UNSIGNED NOT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `description` longtext DEFAULT NULL,
  `status` enum('draft','pending','published','rejected') DEFAULT 'draft',
  `discount_price` decimal(10,2) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `slug`, `short_description`, `full_description`, `thumbnail`, `price`, `duration_weeks`, `level`, `format`, `language`, `category_id`, `instructor_id`, `is_published`, `published_at`, `created_at`, `updated_at`, `description`, `status`, `discount_price`, `submitted_at`, `rejection_reason`) VALUES
(1, 'Digital Electronics', 'digital-electronics', 'Digital Electronics introduces the principles and applications of electronic circuits that operate using digital signals. The course covers fundamental concepts such as logic gates, Boolean algebra, combinational and sequential circuits, flip-flops, counters, and memory devices. Students learn how digital systems are designed, analyzed, and implemented in modern technologies, from microprocessors to embedded systems. It provides the foundation for understanding computer hardware, communication systems, and advanced electronic design.', NULL, 'thumb_6934209d16e8e.webp', 0.00, 8, 'Intermediate', 'Hybrid', 'English', 3, 2, 0, NULL, '2025-12-06 12:25:01', '2025-12-06 12:25:54', '<p><strong>Course Overview</strong></p><p>Digital Electronics is a foundational course that explores the principles, design, and applications of electronic systems that operate using digital signals. It equips students with the knowledge and skills to analyze, design, and implement digital circuits, which form the backbone of modern computing, communication, and control systems.</p><p><strong>Learning Objectives</strong></p><p>By the end of this course, students will be able to:</p><ul><li>Understand the difference between analog and digital signals.</li><li>Apply Boolean algebra and logic simplification techniques to design efficient circuits.</li><li>Analyze and construct combinational logic circuits such as adders, multiplexers, and decoders.</li><li>Design sequential circuits including flip-flops, counters, and registers.</li><li>Explore memory devices and their role in digital systems.</li><li>Gain practical experience with digital circuit simulation and hardware implementation.</li></ul><p><strong>Key Topics Covered</strong></p><ul><li>Number systems and codes (binary, octal, hexadecimal, BCD, ASCII)</li><li>Logic gates and Boolean algebra</li><li>Combinational logic design (encoders, decoders, multiplexers, arithmetic circuits)</li><li>Sequential logic design (flip-flops, latches, counters, shift registers)</li><li>Timing diagrams and clocking concepts</li><li>Memory devices and programmable logic</li><li>Introduction to digital system design using microcontrollers and FPGAs</li></ul><p><strong>Course Relevance</strong></p><p>Digital Electronics is essential for students pursuing careers in electrical engineering, computer engineering, robotics, and embedded systems. It provides the theoretical foundation and practical skills needed to understand how modern digital devices—from smartphones to industrial automation systems—are built and function.</p><p><strong>Practical Component</strong></p><p>Students will engage in laboratory sessions and projects involving:</p><ul><li>Circuit simulation software (e.g., Multisim, Proteus, or Logisim)</li><li>Breadboard prototyping with logic ICs</li><li>Introduction to programmable devices (Arduino, FPGA basics)</li></ul><p><strong>Outcome</strong></p><p>Upon completion, students will have a strong grasp of digital circuit design and implementation, preparing them for advanced courses in microprocessors, embedded systems, and VLSI design.</p>', 'pending', NULL, '2025-12-06 12:25:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course_lessons`
--

CREATE TABLE `course_lessons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `section_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('video','reading','quiz') DEFAULT 'video',
  `duration` varchar(50) DEFAULT NULL,
  `video_url` text DEFAULT NULL,
  `video_duration` int(11) DEFAULT 0,
  `content` longtext DEFAULT NULL,
  `is_free_preview` tinyint(1) DEFAULT 0,
  `order_index` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `file_type` varchar(50) DEFAULT 'pdf',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_progress`
--

CREATE TABLE `course_progress` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `watched_seconds` int(11) DEFAULT 0,
  `is_completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_sections`
--

CREATE TABLE `course_sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_sections`
--

INSERT INTO `course_sections` (`id`, `course_id`, `title`, `order_index`, `created_at`, `updated_at`) VALUES
(1, 1, 'Week 1 - Logics', 0, '2025-12-06 12:25:29', '2025-12-06 12:25:29');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `progress` int(11) DEFAULT 0,
  `last_accessed` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','completed','dropped') DEFAULT 'active',
  `progress_percent` decimal(5,2) DEFAULT 0.00,
  `completed_at` timestamp NULL DEFAULT NULL,
  `certificate_issued` tinyint(1) DEFAULT 0,
  `certificate_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructor_details`
--

CREATE TABLE `instructor_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `nationality` varchar(100) NOT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `id_upload` varchar(255) DEFAULT NULL,
  `residential_address` text NOT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `linkedin_url` varchar(255) NOT NULL,
  `emergency_contact_name` varchar(150) NOT NULL,
  `emergency_contact_relationship` varchar(100) NOT NULL,
  `emergency_contact_phone` varchar(20) NOT NULL,
  `highest_qualification` varchar(150) NOT NULL,
  `professional_certifications` text NOT NULL,
  `professional_memberships` text DEFAULT NULL,
  `areas_of_specialization` text NOT NULL,
  `modules_qualified` text NOT NULL,
  `teaching_experience_years` int(11) NOT NULL,
  `preferred_teaching_format` varchar(100) NOT NULL,
  `availability_schedule` text NOT NULL,
  `current_employer` varchar(150) DEFAULT NULL,
  `current_role` varchar(150) DEFAULT NULL,
  `institutional_reference` text DEFAULT NULL,
  `credentials_upload` varchar(255) NOT NULL,
  `consent_qr_code` tinyint(1) DEFAULT 0,
  `consent_cpd_standards` tinyint(1) DEFAULT 0,
  `consent_recording` tinyint(1) DEFAULT 0,
  `preferred_payment_method` varchar(100) DEFAULT NULL,
  `faith_integration` tinyint(1) DEFAULT 0,
  `teaching_philosophy` text DEFAULT NULL,
  `blessing_dedication` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instructor_details`
--

INSERT INTO `instructor_details` (`id`, `user_id`, `date_of_birth`, `gender`, `nationality`, `id_number`, `id_upload`, `residential_address`, `mobile_number`, `linkedin_url`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `highest_qualification`, `professional_certifications`, `professional_memberships`, `areas_of_specialization`, `modules_qualified`, `teaching_experience_years`, `preferred_teaching_format`, `availability_schedule`, `current_employer`, `current_role`, `institutional_reference`, `credentials_upload`, `consent_qr_code`, `consent_cpd_standards`, `consent_recording`, `preferred_payment_method`, `faith_integration`, `teaching_philosophy`, `blessing_dedication`, `created_at`) VALUES
(1, 4, '2005-11-27', 'Male', 'Ghanaian', NULL, NULL, 'Tesano', '233552231377', 'eben.com', 'Wise', 'brother', '233552231377', 'degree', 'none', NULL, 'none', 'none', 5, 'Live Webinars', 'none', NULL, NULL, NULL, 'credentials/1764138436_cred_69269dc44d9e5.jpeg', 1, 1, 1, NULL, 0, NULL, NULL, '2025-11-26 06:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `live_sessions`
--

CREATE TABLE `live_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `meeting_link` text NOT NULL,
  `start_time` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `payment_method` varchar(50) DEFAULT 'stripe',
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_details`
--

CREATE TABLE `student_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `nationality` varchar(100) NOT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `residential_address` text NOT NULL,
  `emergency_contact_name` varchar(150) NOT NULL,
  `emergency_contact_relationship` varchar(100) NOT NULL,
  `emergency_contact_phone` varchar(20) NOT NULL,
  `highest_qualification` varchar(150) NOT NULL,
  `current_occupation` varchar(150) DEFAULT NULL,
  `employer` varchar(150) DEFAULT NULL,
  `professional_certifications` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `intended_modules` text NOT NULL,
  `preferred_learning_format` varchar(50) NOT NULL,
  `availability_schedule` text NOT NULL,
  `enrollment_reason` text NOT NULL,
  `id_upload` varchar(255) NOT NULL,
  `certificate_upload` varchar(255) NOT NULL,
  `consent_digital_verification` tinyint(1) DEFAULT 0,
  `consent_recording` tinyint(1) DEFAULT 0,
  `consent_code_of_conduct` tinyint(1) DEFAULT 0,
  `consent_qr_certificate` tinyint(1) DEFAULT 0,
  `legacy_statement` text DEFAULT NULL,
  `blessing_dedication` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_details`
--

INSERT INTO `student_details` (`id`, `user_id`, `date_of_birth`, `gender`, `nationality`, `mobile_number`, `residential_address`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `highest_qualification`, `current_occupation`, `employer`, `professional_certifications`, `experience_years`, `intended_modules`, `preferred_learning_format`, `availability_schedule`, `enrollment_reason`, `id_upload`, `certificate_upload`, `consent_digital_verification`, `consent_recording`, `consent_code_of_conduct`, `consent_qr_certificate`, `legacy_statement`, `blessing_dedication`, `created_at`) VALUES
(1, 2, '2005-01-28', 'Male', 'Ghanaian', '233552231477', 'Tesano', 'EBEN', 'DAD', '233552231477', 'Degree', NULL, NULL, NULL, 0, 'Test', 'Live Sessions', 'Everyday', 'Test', 'student_ids/1763717797_id_692032a5573f8.jpeg', 'certificates/1763717797_cert_692032a557c82.jpeg', 1, 1, 1, 1, NULL, NULL, '2025-11-21 09:36:37'),
(2, 3, '2005-01-28', 'Male', 'Ghanaian', '233552231478', 'Abokobi', 'EBEN', 'DAD', '233552231477', 'Degree', NULL, NULL, NULL, 0, 'None', 'Live Sessions', 'Everytime', 'None', 'student_ids/1764136951_id_692697f7d7a2c.jpeg', 'certificates/1764136951_cert_692697f7d8247.jpeg', 1, 1, 1, 1, NULL, NULL, '2025-11-26 06:02:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `avatar` varchar(300) DEFAULT NULL,
  `role` enum('student','instructor','admin') DEFAULT 'student',
  `approved` tinyint(1) DEFAULT 1,
  `applied_at` datetime DEFAULT current_timestamp(),
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `verified` tinyint(1) DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `email_verified_at`, `password_hash`, `first_name`, `middle_name`, `last_name`, `bio`, `avatar`, `role`, `approved`, `applied_at`, `approval_status`, `verified`, `remember_token`, `created_at`, `updated_at`, `last_login_at`) VALUES
(2, 'etefe_6585', 'ebenezertepe209@gmail.com', NULL, '$2y$10$TZxEEruTYi.e0OmE5QplIuta/4HuhKaa.ye/KHkB0DKmHimIc6Ewq', 'EBEN', 'Mat', 'TEFE', NULL, 'avatars/default.jpg', 'instructor', 1, '2025-11-22 17:00:09', 'approved', 1, NULL, '2025-11-21 09:36:37', '2025-11-26 00:30:12', NULL),
(3, 'etefe_6704', 'eben23713@gmail.com', NULL, '$2y$10$q6cVLRRFxvtCn395wrJoD.us3XIUU3Kxm0nFEEDPNFJoo5/vauTpW', 'EBENEZER', 'MAWUFEMOR', 'TEFE', NULL, 'avatars/default.jpg', 'student', 1, '2025-11-26 06:02:31', 'approved', 1, NULL, '2025-11-26 06:02:31', '2025-12-03 21:02:08', NULL),
(4, 'wtefe_b994', 'tefeebenezer@gmail.com', NULL, '$2y$10$sB7a2T9Up1KpMZNnfYFZ0OvGoBsZSQdRVoqX4bQTnYSRlFWFFAwcW', 'Wise', '', 'Tefe', NULL, 'avatars/default.jpg', 'admin', 1, '2025-11-26 06:27:16', 'pending', 1, NULL, '2025-11-26 06:27:16', '2025-12-03 21:01:23', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notes`
--
ALTER TABLE `admin_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_code` (`certificate_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_instructor` (`instructor_id`),
  ADD KEY `idx_published` (`is_published`),
  ADD KEY `idx_published_category` (`is_published`,`category_id`);

--
-- Indexes for table `course_lessons`
--
ALTER TABLE `course_lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_section` (`section_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lesson` (`lesson_id`);

--
-- Indexes for table `course_progress`
--
ALTER TABLE `course_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`user_id`,`lesson_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_lesson` (`lesson_id`);

--
-- Indexes for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_course` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `instructor_details`
--
ALTER TABLE `instructor_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user` (`user_id`);

--
-- Indexes for table `live_sessions`
--
ALTER TABLE `live_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_course` (`course_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `student_details`
--
ALTER TABLE `student_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_approval` (`approval_status`),
  ADD KEY `idx_role_approval` (`role`,`approval_status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notes`
--
ALTER TABLE `admin_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course_lessons`
--
ALTER TABLE `course_lessons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_progress`
--
ALTER TABLE `course_progress`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_sections`
--
ALTER TABLE `course_sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `instructor_details`
--
ALTER TABLE `instructor_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `live_sessions`
--
ALTER TABLE `live_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_details`
--
ALTER TABLE `student_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_notes`
--
ALTER TABLE `admin_notes`
  ADD CONSTRAINT `admin_notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_notes_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_lessons`
--
ALTER TABLE `course_lessons`
  ADD CONSTRAINT `course_lessons_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_progress`
--
ALTER TABLE `course_progress`
  ADD CONSTRAINT `course_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD CONSTRAINT `course_sections_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instructor_details`
--
ALTER TABLE `instructor_details`
  ADD CONSTRAINT `instructor_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `live_sessions`
--
ALTER TABLE `live_sessions`
  ADD CONSTRAINT `live_sessions_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_details`
--
ALTER TABLE `student_details`
  ADD CONSTRAINT `student_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
