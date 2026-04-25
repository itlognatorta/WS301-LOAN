-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2026 at 09:22 AM
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
-- Database: `loan_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(3, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-12 03:27:56');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `generated_date` date NOT NULL,
  `due_date` date NOT NULL,
  `loan_principal` decimal(10,2) DEFAULT NULL,
  `monthly_amount` decimal(10,2) DEFAULT NULL,
  `interest` decimal(10,2) DEFAULT NULL,
  `penalty` decimal(10,2) DEFAULT 0.00,
  `total_due` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','completed','overdue') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_emails`
--

CREATE TABLE `blocked_emails` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `blocked_by` int(11) DEFAULT NULL,
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_earnings`
--

CREATE TABLE `company_earnings` (
  `id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_income` decimal(12,2) NOT NULL,
  `money_back_distributed` decimal(10,2) DEFAULT 0.00,
  `distributed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_earnings`
--

INSERT INTO `company_earnings` (`id`, `year`, `total_income`, `money_back_distributed`, `distributed_at`) VALUES
(1, 2024, 1000000.00, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `principal` decimal(10,2) NOT NULL,
  `interest` decimal(10,2) NOT NULL COMMENT '3%',
  `received_amount` decimal(10,2) NOT NULL,
  `tenure_months` int(11) NOT NULL,
  `current_month` int(11) DEFAULT 1,
  `status` enum('active','paid','default') DEFAULT 'active',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `principal`, `interest`, `received_amount`, `tenure_months`, `current_month`, `status`, `started_at`) VALUES
(1, 1, 10000.00, 300.00, 9700.00, 12, 1, 'active', '2026-04-12 02:54:49'),
(6, 2, 5000.00, 150.00, 4850.00, 1, 1, 'active', '2026-04-21 06:23:58'),
(7, 2, 5000.00, 150.00, 4850.00, 1, 1, 'active', '2026-04-21 06:45:43'),
(8, 2, 5000.00, 150.00, 4850.00, 1, 1, 'active', '2026-04-21 06:45:45'),
(9, 2, 5500.00, 165.00, 5335.00, 1, 1, 'active', '2026-04-21 06:51:38'),
(10, 2, 5500.00, 165.00, 5335.00, 1, 1, 'active', '2026-04-21 07:06:58'),
(11, 2, 5500.00, 165.00, 5335.00, 1, 1, 'active', '2026-04-21 07:08:39'),
(12, 2, 5500.00, 165.00, 5335.00, 1, 1, 'active', '2026-04-21 07:18:16'),
(13, 2, 5000.00, 150.00, 4850.00, 1, 1, 'active', '2026-04-21 07:19:17'),
(14, 2, 5000.00, 150.00, 4850.00, 1, 1, 'active', '2026-04-21 07:22:12'),
(15, 2, 5000.00, 150.00, 4850.00, 1, 1, 'active', '2026-04-21 07:22:14');

-- --------------------------------------------------------

--
-- Table structure for table `loan_payment`
--

CREATE TABLE `loan_payment` (
  `pay_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `amount_paid` varchar(255) NOT NULL,
  `reference_no` varchar(255) NOT NULL,
  `notes` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_requests`
--

CREATE TABLE `loan_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tenure_months` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_requests`
--

INSERT INTO `loan_requests` (`id`, `user_id`, `amount`, `tenure_months`, `status`, `rejection_reason`, `approved_by`, `approved_at`, `created_at`) VALUES
(1, 1, 10000.00, 12, 'approved', NULL, 1, NULL, '2026-04-12 02:54:49'),
(17, 2, 5000.00, 1, 'pending', NULL, NULL, NULL, '2026-04-21 07:19:11');

-- --------------------------------------------------------

--
-- Table structure for table `loan_transactions`
--

CREATE TABLE `loan_transactions` (
  `no` int(11) NOT NULL,
  `tx_id` varchar(20) NOT NULL COMMENT 'Random unique e.g. LN-YYYYMMDD-XXXX',
  `user_id` int(11) NOT NULL,
  `type` enum('apply','increase') NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `tenure_months` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_transactions`
--

INSERT INTO `loan_transactions` (`no`, `tx_id`, `user_id`, `type`, `amount`, `tenure_months`, `status`, `admin_note`, `created_at`) VALUES
(1, 'LN202604124229', 1, 'apply', 6000.00, 6, 'pending', NULL, '2026-04-12 03:50:26'),
(14, 'TX-20260421-FC8E', 2, 'apply', 5000.00, 1, 'approved', NULL, '2026-04-21 07:19:17'),
(15, 'TX-20260421-478F', 2, 'apply', 5000.00, 1, 'approved', NULL, '2026-04-21 07:22:12'),
(16, 'TX-20260421-5206', 2, 'apply', 5000.00, 1, 'approved', NULL, '2026-04-21 07:22:14');

-- --------------------------------------------------------

--
-- Table structure for table `registration_requests`
--

CREATE TABLE `registration_requests` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings_requests`
--

CREATE TABLE `savings_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings_transactions`
--

CREATE TABLE `savings_transactions` (
  `no` int(11) NOT NULL,
  `tx_id` varchar(20) NOT NULL COMMENT 'Random unique SV-YYYYMMDD-XXXX',
  `user_id` int(11) NOT NULL,
  `category` enum('deposit','withdrawal') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','completed','failed','rejected') DEFAULT 'pending',
  `request_id` int(11) DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `savings_transactions`
--

INSERT INTO `savings_transactions` (`no`, `tx_id`, `user_id`, `category`, `amount`, `balance_after`, `status`, `request_id`, `admin_note`, `created_at`) VALUES
(1, 'SV-20240101-1234', 1, 'deposit', 1000.00, 1000.00, 'completed', NULL, NULL, '2026-04-12 02:54:49');

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('proof_billing','valid_id','coe') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL COMMENT 'Username or email',
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `account_type` enum('basic','premium') NOT NULL DEFAULT 'basic',
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `birthday` date NOT NULL,
  `age` int(11) DEFAULT 0 COMMENT 'Computed from birthday',
  `phone` varchar(11) NOT NULL COMMENT 'PH format 09xxxxxxxxx',
  `bank_name` varchar(255) NOT NULL,
  `bank_account` varchar(50) NOT NULL,
  `account_holder` varchar(255) NOT NULL,
  `tin` varchar(20) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_address` text NOT NULL,
  `company_phone` varchar(11) NOT NULL,
  `position` varchar(255) NOT NULL,
  `monthly_earnings` decimal(10,2) NOT NULL,
  `proof_billing_path` varchar(500) DEFAULT NULL,
  `valid_id_path` varchar(500) DEFAULT NULL,
  `coe_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','active','disabled') DEFAULT 'pending',
  `verified` tinyint(1) DEFAULT 0,
  `savings_balance` decimal(10,2) DEFAULT 0.00,
  `current_loan_amount` decimal(10,2) DEFAULT 0.00,
  `max_loan_amount` decimal(10,2) DEFAULT 10000.00 COMMENT 'Increases gradually up to 50000',
  `max_tenure_months` int(11) DEFAULT 12,
  `last_savings_activity` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `account_type`, `name`, `address`, `gender`, `birthday`, `age`, `phone`, `bank_name`, `bank_account`, `account_holder`, `tin`, `company_name`, `company_address`, `company_phone`, `position`, `monthly_earnings`, `proof_billing_path`, `valid_id_path`, `coe_path`, `status`, `verified`, `savings_balance`, `current_loan_amount`, `max_loan_amount`, `max_tenure_months`, `last_savings_activity`, `created_at`, `updated_at`) VALUES
(1, 'testpremium', 'test@premium.com', '$2y$10$DhQoONm5yRguHzDV3S7BfONHBk7lGFY/8EUKsqvQ/FI6ZEExFlA7G', 'premium', 'Test Premium User', '123 Test St, Manila', 'male', '1990-01-01', 0, '09171234567', 'BPI', '1234567890', 'Test Premium', '123456789', 'Test Corp', '456 Corp St', '028123456', 'Manager', 50000.00, NULL, NULL, NULL, 'active', 1, 1000.00, 0.00, 10000.00, 12, NULL, '2026-04-12 02:54:49', '2026-04-12 03:28:09'),
(2, 'testbasic', 'test@basic.com', '$2y$10$DhQoONm5yRguHzDV3S7BfONHBk7lGFY/8EUKsqvQ/FI6ZEExFlA7G', 'basic', 'Mark Christian Cañedo', 'Lipata Minglanilla, Cebu', 'male', '1985-05-15', 0, '09223254679', 'BDO', '0987654321', 'Juan Dela Cruz', '987654321', 'Basic Inc', '789 Inc Ave', '029876543', 'Staff', 4000.00, NULL, NULL, NULL, 'active', 1, 0.00, 40500.00, 10000.00, 12, NULL, '2026-04-12 02:54:49', '2026-04-21 06:51:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_billing_user_status` (`user_id`,`status`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `blocked_emails`
--
ALTER TABLE `blocked_emails`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `company_earnings`
--
ALTER TABLE `company_earnings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_year` (`year`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loans_user` (`user_id`);

--
-- Indexes for table `loan_payment`
--
ALTER TABLE `loan_payment`
  ADD PRIMARY KEY (`pay_id`),
  ADD KEY `u_id` (`u_id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `loan_requests`
--
ALTER TABLE `loan_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`);

--
-- Indexes for table `loan_transactions`
--
ALTER TABLE `loan_transactions`
MODIFY no INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`no`),
  ADD UNIQUE KEY `tx_id` (`tx_id`),
  ADD KEY `user_id` (`user_id`),
  ADD interest DECIMAL(10,2) DEFAULT 0 AFTER amount,
  ADD net_amount DECIMAL(10,2) DEFAULT 0 AFTER interest;

--
-- Indexes for table `registration_requests`
--
ALTER TABLE `registration_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `savings_requests`
--
ALTER TABLE `savings_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `savings_transactions`
--
ALTER TABLE `savings_transactions`
  ADD PRIMARY KEY (`no`),
  ADD UNIQUE KEY `tx_id` (`tx_id`),
  ADD KEY `idx_user_category` (`user_id`,`category`),
  ADD KEY `idx_status_date` (`status`,`created_at`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `tin` (`tin`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_account_type` (`account_type`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_users_status_type` (`status`,`account_type`);
 

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `blocked_emails`
--
ALTER TABLE `blocked_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_earnings`
--
ALTER TABLE `company_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `loan_payment`
--
ALTER TABLE `loan_payment`
  MODIFY `pay_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_requests`
--
ALTER TABLE `loan_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `loan_transactions`
--
ALTER TABLE `loan_transactions`
  MODIFY `no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `registration_requests`
--
ALTER TABLE `registration_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings_requests`
--
ALTER TABLE `savings_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings_transactions`
--
ALTER TABLE `savings_transactions`
  MODIFY `no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `billing_ibfk_2` FOREIGN KEY (`loan_id`) REFERENCES `loan_transactions` (`no`);

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_payment`
--
ALTER TABLE `loan_payment`
  ADD CONSTRAINT `loan_payment_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `loan_payment_ibfk_2` FOREIGN KEY (`loan_id`) REFERENCES `loan_transactions` (`no`);

--
-- Constraints for table `loan_requests`
--
ALTER TABLE `loan_requests`
  ADD CONSTRAINT `loan_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_transactions`
--
ALTER TABLE `loan_transactions`
  ADD CONSTRAINT `loan_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `savings_requests`
--
ALTER TABLE `savings_requests`
  ADD CONSTRAINT `savings_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
