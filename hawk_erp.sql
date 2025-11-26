-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 26, 2025 at 09:48 AM
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
-- Database: `tiger_erp`
--
CREATE DATABASE IF NOT EXISTS `garviterp-353034391dd2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `garviterp-353034391dd2`;

-- --------------------------------------------------------

--
-- Table structure for table `account_types`
--

DROP TABLE IF EXISTS `account_types`;
CREATE TABLE IF NOT EXISTS `account_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `category` enum('Asset','Liability','Equity','Income','Expense') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_types`
--

INSERT INTO `account_types` (`id`, `name`, `category`) VALUES
(1, 'Current Asset', 'Asset'),
(2, 'Fixed Asset', 'Asset'),
(3, 'Current Liability', 'Liability'),
(4, 'Long Term Liability', 'Liability'),
(5, 'Equity', 'Equity'),
(6, 'Direct Income', 'Income'),
(7, 'Indirect Income', 'Income'),
(8, 'Direct Expense', 'Expense'),
(9, 'Indirect Expense', 'Expense');

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
CREATE TABLE IF NOT EXISTS `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_type` enum('Call','Email','Meeting','Task','Note') NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `related_to_type` varchar(50) DEFAULT NULL,
  `related_to_id` int(11) DEFAULT NULL,
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`),
  KEY `idx_related` (`related_to_type`,`related_to_id`),
  KEY `idx_scheduled` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('Present','Absent','Half Day','Leave','Holiday') DEFAULT 'Present',
  `working_hours` decimal(4,2) DEFAULT NULL,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`employee_id`,`attendance_date`),
  KEY `idx_date` (`attendance_date`),
  KEY `idx_employee_date` (`employee_id`,`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_table` (`table_name`,`record_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-22 15:54:08'),
(2, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-23 07:45:47'),
(3, 1, 'logout', 'users', 1, NULL, NULL, '::1', '2025-11-23 08:25:56'),
(4, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-23 08:26:04'),
(5, 1, 'logout', 'users', 1, NULL, NULL, '::1', '2025-11-23 15:59:18'),
(6, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-24 10:43:23'),
(7, 1, 'logout', 'users', 1, NULL, NULL, '::1', '2025-11-24 16:28:18'),
(8, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-24 16:31:13'),
(9, 1, 'logout', 'users', 1, NULL, NULL, '::1', '2025-11-24 16:32:27'),
(10, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-25 04:05:02'),
(11, 3, 'logout', 'users', 3, NULL, NULL, '::1', '2025-11-25 04:32:42'),
(12, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-25 04:32:49'),
(13, 1, 'impersonate_start', 'users', 3, NULL, NULL, '::1', '2025-11-25 04:43:12'),
(14, 1, 'impersonate_end', 'users', 3, NULL, NULL, '::1', '2025-11-25 04:43:20'),
(15, 1, 'impersonate_start', 'users', 7, NULL, NULL, '::1', '2025-11-25 05:02:59'),
(16, 1, 'impersonate_end', 'users', 7, NULL, NULL, '::1', '2025-11-25 05:03:08'),
(17, 1, 'logout', 'users', 1, NULL, NULL, '::1', '2025-11-25 05:25:15'),
(18, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-25 08:19:27'),
(19, 1, 'logout', 'users', 1, NULL, NULL, '::1', '2025-11-25 13:19:05'),
(20, 8, 'logout', 'users', 8, NULL, NULL, '::1', '2025-11-25 13:24:17'),
(21, 9, 'logout', 'users', 9, NULL, NULL, '::1', '2025-11-25 14:05:09'),
(22, 10, 'login', 'users', 10, NULL, NULL, '::1', '2025-11-25 14:05:17'),
(23, 10, 'logout', 'users', 10, NULL, NULL, '::1', '2025-11-25 14:05:17'),
(24, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-25 14:05:29'),
(25, 1, 'logout', 'users', 1, NULL, NULL, '::1', '2025-11-25 14:06:52'),
(26, 10, 'login', 'users', 10, NULL, NULL, '::1', '2025-11-25 14:07:00'),
(27, 10, 'logout', 'users', 10, NULL, NULL, '::1', '2025-11-25 14:07:48'),
(28, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-25 14:07:56'),
(29, 1, 'logout', 'users', 1, NULL, NULL, '::1', '2025-11-25 14:14:42'),
(30, 11, 'login', 'users', 11, NULL, NULL, '::1', '2025-11-25 14:14:48'),
(31, 11, 'logout', 'users', 11, NULL, NULL, '::1', '2025-11-25 15:01:44'),
(32, 1, 'login', 'users', 1, NULL, NULL, '::1', '2025-11-25 15:01:54');

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `account_name` varchar(100) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `account_type` enum('Savings','Current','Cash Credit','Overdraft') DEFAULT 'Current',
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `chart_account_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `chart_account_id` (`chart_account_id`),
  KEY `idx_bank_accounts_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_of_materials`
--

DROP TABLE IF EXISTS `bill_of_materials`;
CREATE TABLE IF NOT EXISTS `bill_of_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bom_number` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `bom_number` (`bom_number`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bom_items`
--

DROP TABLE IF EXISTS `bom_items`;
CREATE TABLE IF NOT EXISTS `bom_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bom_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `scrap_percentage` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bom_id` (`bom_id`),
  KEY `component_id` (`component_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(200) NOT NULL,
  `account_type_id` int(11) NOT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_code` (`account_code`),
  KEY `parent_account_id` (`parent_account_id`),
  KEY `idx_code` (`account_code`),
  KEY `idx_type` (`account_type_id`),
  KEY `idx_chart_of_accounts_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chart_of_accounts`
--

INSERT INTO `chart_of_accounts` (`id`, `company_id`, `account_code`, `account_name`, `account_type_id`, `parent_account_id`, `description`, `is_active`, `created_at`) VALUES
(1, 1, '1000', 'Assets', 1, NULL, NULL, 1, '2025-11-22 15:41:13'),
(2, 1, '1100', 'Cash and Bank', 1, NULL, NULL, 1, '2025-11-22 15:41:13'),
(3, 1, '1200', 'Accounts Receivable', 1, NULL, NULL, 1, '2025-11-22 15:41:13'),
(4, 1, '1300', 'Inventory', 1, NULL, NULL, 1, '2025-11-22 15:41:13'),
(5, 1, '2000', 'Liabilities', 3, NULL, NULL, 1, '2025-11-22 15:41:13'),
(6, 1, '2100', 'Accounts Payable', 3, NULL, NULL, 1, '2025-11-22 15:41:13'),
(7, 1, '2200', 'Tax Payable', 3, NULL, NULL, 1, '2025-11-22 15:41:13'),
(8, 1, '3000', 'Equity', 5, NULL, NULL, 1, '2025-11-22 15:41:13'),
(9, 1, '3100', 'Capital', 5, NULL, NULL, 1, '2025-11-22 15:41:13'),
(10, 1, '4000', 'Revenue', 6, NULL, NULL, 1, '2025-11-22 15:41:13'),
(11, 1, '4100', 'Sales Revenue', 6, NULL, NULL, 1, '2025-11-22 15:41:13'),
(12, 1, '5000', 'Expenses', 8, NULL, NULL, 1, '2025-11-22 15:41:13'),
(13, 1, '5100', 'Cost of Goods Sold', 8, NULL, NULL, 1, '2025-11-22 15:41:13'),
(14, 1, '5200', 'Operating Expenses', 9, NULL, NULL, 1, '2025-11-22 15:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

DROP TABLE IF EXISTS `company_settings`;
CREATE TABLE IF NOT EXISTS `company_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(200) NOT NULL,
  `app_name` varchar(100) DEFAULT NULL,
  `theme_color` varchar(20) DEFAULT '#3b82f6',
  `address_line1` varchar(200) DEFAULT NULL,
  `address_line2` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `postal_code` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_ifsc` varchar(20) DEFAULT NULL,
  `bank_branch` varchar(100) DEFAULT NULL,
  `bank_account_holder` varchar(200) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `financial_year_start` int(11) DEFAULT 4 COMMENT 'Month number (1-12) when financial year starts',
  `currency_code` varchar(10) DEFAULT 'INR',
  `currency_symbol` varchar(10) DEFAULT '₹',
  `date_format` varchar(20) DEFAULT 'd-m-Y',
  `timezone` varchar(50) DEFAULT 'Asia/Kolkata',
  `invoice_prefix` varchar(20) DEFAULT 'INV',
  `quotation_prefix` varchar(20) DEFAULT 'QT',
  `invoice_footer` text DEFAULT NULL COMMENT 'Footer text for invoices',
  `company_registration_number` varchar(100) DEFAULT NULL,
  `tax_registration_date` date DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `smtp_host` varchar(100) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT 587,
  `smtp_username` varchar(100) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `smtp_encryption` varchar(10) DEFAULT 'tls',
  `email_from_name` varchar(100) DEFAULT NULL,
  `email_from_address` varchar(100) DEFAULT NULL,
  `enable_email_notifications` tinyint(1) DEFAULT 1,
  `invoice_due_days` int(11) DEFAULT 30 COMMENT 'Default payment terms in days',
  `low_stock_threshold` int(11) DEFAULT 10,
  `enable_multi_currency` tinyint(1) DEFAULT 0,
  `enable_barcode` tinyint(1) DEFAULT 1,
  `backup_frequency` varchar(20) DEFAULT 'daily',
  `last_backup_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `company_name`, `app_name`, `theme_color`, `address_line1`, `address_line2`, `city`, `state`, `country`, `postal_code`, `phone`, `email`, `website`, `gstin`, `pan`, `bank_name`, `bank_account_number`, `bank_ifsc`, `bank_branch`, `bank_account_holder`, `logo_path`, `terms_conditions`, `created_at`, `updated_at`, `financial_year_start`, `currency_code`, `currency_symbol`, `date_format`, `timezone`, `invoice_prefix`, `quotation_prefix`, `invoice_footer`, `company_registration_number`, `tax_registration_date`, `linkedin_url`, `facebook_url`, `twitter_url`, `instagram_url`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_encryption`, `email_from_name`, `email_from_address`, `enable_email_notifications`, `invoice_due_days`, `low_stock_threshold`, `enable_multi_currency`, `enable_barcode`, `backup_frequency`, `last_backup_date`) VALUES
(1, 'AD Herbals', 'App Store', '#0091ff', 'Business Park, Sector 18', '', 'New Delhi', 'Uttar Pradesh', 'India', '110001', '', 'info@tigererp.com', 'tigererp.com', '09BMAPK5506J1Z1', 'BMAPK5506J', 'ICICI Bank', '102000000012789', 'ICIC0001234', 'Connaught Place', 'Tiger ERP Solutions', 'public/uploads/logos/logo_1_1764142783.png', 'E & O.E\r\n1. Goods once sold will not be taken back.\r\n2. Interest @ 18% p.a. will be charged if the payment is not made within the stipulated time.\r\n3. Subject to local Jurisdiction only.', '2025-11-22 16:17:23', '2025-11-26 07:39:43', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', 'Thank you for your business. \r\nVisit Again.', '', NULL, '', '', '', '', '', 587, '', NULL, 'tls', '', '', 1, 30, 10, 0, 1, 'daily', NULL),
(2, 'Tiger ERP', NULL, '#3b82f6', '123 Tech Park', '', 'Noida', 'Uttar Pradesh', 'India', '201301', '', 'admin@tiger.com', '', '09ABCDE1234F1Z5', 'ABCDE1234F', 'HDFC Bank', '1234567890', 'HDFC0001234', 'Noida Sector 18', '', NULL, '', '2025-11-23 14:09:56', '2025-11-23 15:54:21', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', '', '', NULL, '', '', '', '', '', 587, '', NULL, 'tls', '', '', 1, 30, 10, 0, 0, 'daily', NULL),
(3, 'Test Company 1764044178', NULL, '#3b82f6', NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-25 04:16:18', '2025-11-25 04:16:18', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 587, NULL, NULL, 'tls', NULL, NULL, 1, 30, 10, 0, 1, 'daily', NULL),
(4, 'Test Company 1764044195', NULL, '#3b82f6', NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-25 04:16:35', '2025-11-25 04:16:35', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 587, NULL, NULL, 'tls', NULL, NULL, 1, 30, 10, 0, 1, 'daily', NULL),
(5, 'Test Company 1764044212', NULL, '#3b82f6', NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-25 04:16:52', '2025-11-25 04:16:52', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 587, NULL, NULL, 'tls', NULL, NULL, 1, 30, 10, 0, 1, 'daily', NULL),
(6, 'Test Company 1764044231', NULL, '#3b82f6', NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-25 04:17:11', '2025-11-25 04:17:11', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 587, NULL, NULL, 'tls', NULL, NULL, 1, 30, 10, 0, 1, 'daily', NULL),
(7, 'ddss', '', '#3b82f6', '', '', '', '', 'India', '', '', '', '', '', '', '', '', '', '', '', NULL, '', '2025-11-25 05:23:46', '2025-11-25 05:23:46', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', '', '', NULL, '', '', '', '', '', 587, '', NULL, 'tls', '', '', 1, 30, 10, 0, 1, 'daily', NULL),
(8, 'ddss', '', '#3b82f6', '', '', '', '', 'India', '', '', '', '', '', '', '', '', '', '', '', NULL, '', '2025-11-25 05:24:24', '2025-11-25 05:24:24', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', '', '', NULL, '', '', '', '', '', 587, '', NULL, 'tls', '', '', 1, 30, 10, 0, 1, 'daily', NULL),
(9, 'AD Herbals', NULL, '#3b82f6', NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-25 13:19:44', '2025-11-25 13:19:44', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 587, NULL, NULL, 'tls', NULL, NULL, 1, 30, 10, 0, 1, 'daily', NULL),
(10, 'AD Herbals', 'AD Herbals', '#3b82f6', '', '', '', '', 'India', '', '', '', '', '', '', '', '', '', '', '', 'public/uploads/logos/logo__1764077042.jpg', '', '2025-11-25 13:24:02', '2025-11-25 13:24:02', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', '', '', NULL, '', '', '', '', '', 587, '', NULL, 'tls', '', '', 1, 30, 10, 0, 1, 'daily', NULL),
(11, 'akash', 'Akash Logistics', '#3b82f6', '', '', '', '', 'India', '', '', '', '', '', '', '', '', '', '', '', 'public/uploads/logos/logo_11_1764079370.jpg', '', '2025-11-25 13:33:30', '2025-11-25 14:07:36', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', '', '', NULL, '', '', '', '', '', 587, '', NULL, 'tls', '', '', 1, 30, 10, 0, 1, 'daily', NULL),
(12, 'Akash Logistics', '', '#3b82f6', '', '', '', '', 'India', '', '', '', '', '', '', '', '', '', '', '', NULL, '', '2025-11-25 13:34:31', '2025-11-25 13:34:31', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', '', '', NULL, '', '', '', '', '', 587, '', NULL, 'tls', '', '', 1, 30, 10, 0, 1, 'daily', NULL),
(13, 'Akash Logistics', '', '#3b82f6', '', '', '', '', 'India', '', '', '', '', '', '', '', '', '', '', '', NULL, '', '2025-11-25 13:48:05', '2025-11-25 13:48:05', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', '', '', NULL, '', '', '', '', '', 587, '', NULL, 'tls', '', '', 1, 30, 10, 0, 1, 'daily', NULL),
(14, 'akash', '', '#3b82f6', '', '', '', '', 'India', '', '', '', '', '', '', '', '', '', '', '', NULL, '', '2025-11-25 13:48:11', '2025-11-25 13:48:11', 4, 'INR', '₹', 'd-m-Y', 'Asia/Kolkata', 'INV', 'QT', '', '', NULL, '', '', '', '', '', 587, '', NULL, 'tls', '', '', 1, 30, 10, 0, 1, 'daily', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cost_centers`
--

DROP TABLE IF EXISTS `cost_centers`;
CREATE TABLE IF NOT EXISTS `cost_centers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
CREATE TABLE IF NOT EXISTS `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_name` varchar(100) NOT NULL,
  `country_code` varchar(5) NOT NULL,
  `currency_code` varchar(10) DEFAULT NULL,
  `currency_symbol` varchar(10) DEFAULT NULL,
  `phone_code` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_name` (`country_name`),
  UNIQUE KEY `country_code` (`country_code`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `country_name`, `country_code`, `currency_code`, `currency_symbol`, `phone_code`, `is_active`, `created_at`) VALUES
(1, 'India', 'IN', 'INR', '₹', '+91', 1, '2025-11-23 04:28:44'),
(2, 'United States', 'US', 'USD', '$', '+1', 1, '2025-11-23 04:28:44'),
(3, 'United Kingdom', 'GB', 'GBP', '£', '+44', 1, '2025-11-23 04:28:44'),
(4, 'United Arab Emirates', 'AE', 'AED', 'د.إ', '+971', 1, '2025-11-23 04:28:44'),
(5, 'Singapore', 'SG', 'SGD', 'S$', '+65', 1, '2025-11-23 04:28:44'),
(6, 'Australia', 'AU', 'AUD', 'A$', '+61', 1, '2025-11-23 04:28:44'),
(7, 'Canada', 'CA', 'CAD', 'C$', '+1', 1, '2025-11-23 04:28:44'),
(8, 'Germany', 'DE', 'EUR', '€', '+49', 1, '2025-11-23 04:28:44'),
(9, 'France', 'FR', 'EUR', '€', '+33', 1, '2025-11-23 04:28:44'),
(10, 'Japan', 'JP', 'JPY', '¥', '+81', 1, '2025-11-23 04:28:44'),
(11, 'China', 'CN', 'CNY', '¥', '+86', 1, '2025-11-23 04:28:44'),
(12, 'Saudi Arabia', 'SA', 'SAR', '﷼', '+966', 1, '2025-11-23 04:28:44'),
(13, 'Malaysia', 'MY', 'MYR', 'RM', '+60', 1, '2025-11-23 04:28:44'),
(14, 'Thailand', 'TH', 'THB', '฿', '+66', 1, '2025-11-23 04:28:44'),
(15, 'South Africa', 'ZA', 'ZAR', 'R', '+27', 1, '2025-11-23 04:28:44');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `customer_code` varchar(20) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `payment_terms` int(11) DEFAULT 0,
  `customer_type` enum('Individual','Company') DEFAULT 'Company',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_segment` enum('VIP','Premium','Regular','New') DEFAULT 'Regular',
  `last_purchase_date` date DEFAULT NULL,
  `total_purchases` decimal(15,2) DEFAULT 0.00,
  `outstanding_balance` decimal(12,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  KEY `idx_code` (`customer_code`),
  KEY `idx_company` (`company_name`),
  KEY `idx_customers_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `company_id`, `customer_code`, `company_name`, `contact_person`, `email`, `phone`, `mobile`, `website`, `gstin`, `pan`, `credit_limit`, `payment_terms`, `customer_type`, `is_active`, `created_at`, `updated_at`, `customer_segment`, `last_purchase_date`, `total_purchases`, `outstanding_balance`) VALUES
(1, 1, 'CUST001', 'Garvit Rajput', 'Garvit Rajput', 'garvitrajput223@gmail.com', '', '9520447284', NULL, '', '', 0.00, 30, 'Individual', 1, '2025-11-22 16:22:20', '2025-11-25 04:15:03', 'Regular', '2025-11-23', 96172.52, 0.00),
(2, 1, 'CUST002', 'PULKIT', 'PULKIT', 'pulkit@gmail.com', '', '9878776545', NULL, '09HJNJK9989J1I1', '', 0.00, 0, 'Individual', 1, '2025-11-25 15:17:32', '2025-11-25 15:17:32', 'Regular', NULL, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

DROP TABLE IF EXISTS `customer_addresses`;
CREATE TABLE IF NOT EXISTS `customer_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `address_type` enum('Billing','Shipping','Both') DEFAULT 'Both',
  `address_line1` varchar(200) DEFAULT NULL,
  `address_line2` varchar(200) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'India',
  `postal_code` varchar(10) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_addresses`
--

INSERT INTO `customer_addresses` (`id`, `customer_id`, `address_type`, `address_line1`, `address_line2`, `city`, `state`, `country`, `postal_code`, `is_default`) VALUES
(1, 1, 'Both', 'Near SDM Court', '', 'Dhampur', 'Uttar Pradesh', 'India', '246761', 1),
(2, 2, 'Both', 'KAROL BAGH', '', 'NEW DELHI', 'Delhi', 'India', '110001', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customer_notes`
--

DROP TABLE IF EXISTS `customer_notes`;
CREATE TABLE IF NOT EXISTS `customer_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `note_type` enum('General','Follow-up','Complaint','Feedback') DEFAULT 'General',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `manager_id` (`manager_id`),
  KEY `idx_departments_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `company_id`, `name`, `parent_id`, `manager_id`, `description`, `is_active`, `created_at`) VALUES
(1, 1, 'IT Support', NULL, NULL, 'IT Support Department', 1, '2025-11-22 16:03:32'),
(2, 1, 'Sales', NULL, NULL, '', 1, '2025-11-23 03:14:41');

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

DROP TABLE IF EXISTS `designations`;
CREATE TABLE IF NOT EXISTS `designations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `idx_designations_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `company_id`, `title`, `department_id`, `level`, `description`, `created_at`) VALUES
(1, 1, 'Enginner', 1, 1, '', '2025-11-22 16:03:43'),
(2, 1, 'Sales Manager', 2, 1, '', '2025-11-23 03:14:52');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `employee_code` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `reporting_to` int(11) DEFAULT NULL,
  `date_of_joining` date NOT NULL,
  `date_of_leaving` date DEFAULT NULL,
  `employment_type` enum('Permanent','Contract','Intern','Temporary') DEFAULT 'Permanent',
  `status` enum('Active','Inactive','Terminated','Resigned') DEFAULT 'Active',
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_ifsc` varchar(20) DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `aadhar_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `designation_id` (`designation_id`),
  KEY `reporting_to` (`reporting_to`),
  KEY `idx_dept` (`department_id`),
  KEY `idx_status` (`status`),
  KEY `idx_employees_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `company_id`, `employee_code`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `gender`, `marital_status`, `blood_group`, `address`, `city`, `state`, `country`, `postal_code`, `department_id`, `designation_id`, `reporting_to`, `date_of_joining`, `date_of_leaving`, `employment_type`, `status`, `bank_name`, `bank_account_number`, `bank_ifsc`, `pan_number`, `aadhar_number`, `created_at`, `updated_at`) VALUES
(1, 1, 'EMP001', NULL, 'Garvit', 'Rajput', 'garvitrajput223@gmail.com', '', '2001-08-15', 'Male', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 2, NULL, '2025-11-23', NULL, 'Permanent', 'Active', NULL, NULL, NULL, NULL, NULL, '2025-11-23 14:32:49', '2025-11-25 04:15:03');

-- --------------------------------------------------------

--
-- Table structure for table `employee_salary_structure`
--

DROP TABLE IF EXISTS `employee_salary_structure`;
CREATE TABLE IF NOT EXISTS `employee_salary_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `component_id` (`component_id`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_effective` (`effective_from`,`effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fiscal_years`
--

DROP TABLE IF EXISTS `fiscal_years`;
CREATE TABLE IF NOT EXISTS `fiscal_years` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year_name` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_closed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fiscal_years`
--

INSERT INTO `fiscal_years` (`id`, `year_name`, `start_date`, `end_date`, `is_closed`, `created_at`) VALUES
(1, 'FY 2025-26', '2025-04-01', '2026-03-31', 0, '2025-11-22 15:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `goods_received_notes`
--

DROP TABLE IF EXISTS `goods_received_notes`;
CREATE TABLE IF NOT EXISTS `goods_received_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grn_number` varchar(50) NOT NULL,
  `po_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `received_date` date NOT NULL,
  `received_by` int(11) DEFAULT NULL,
  `status` enum('Draft','Completed') DEFAULT 'Draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `grn_number` (`grn_number`),
  KEY `po_id` (`po_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `received_by` (`received_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grn_items`
--

DROP TABLE IF EXISTS `grn_items`;
CREATE TABLE IF NOT EXISTS `grn_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grn_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_received` decimal(10,2) NOT NULL,
  `quantity_accepted` decimal(10,2) NOT NULL,
  `quantity_rejected` decimal(10,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grn_id` (`grn_id`),
  KEY `po_item_id` (`po_item_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `indian_states`
--

DROP TABLE IF EXISTS `indian_states`;
CREATE TABLE IF NOT EXISTS `indian_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_name` varchar(100) NOT NULL,
  `state_code` varchar(10) NOT NULL,
  `gst_code` varchar(5) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `state_name` (`state_name`),
  UNIQUE KEY `state_code` (`state_code`),
  UNIQUE KEY `gst_code` (`gst_code`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `indian_states`
--

INSERT INTO `indian_states` (`id`, `state_name`, `state_code`, `gst_code`, `is_active`, `created_at`) VALUES
(1, 'Andaman and Nicobar Islands', 'AN', '35', 1, '2025-11-23 04:28:44'),
(2, 'Andhra Pradesh', 'AP', '37', 1, '2025-11-23 04:28:44'),
(3, 'Arunachal Pradesh', 'AR', '12', 1, '2025-11-23 04:28:44'),
(4, 'Assam', 'AS', '18', 1, '2025-11-23 04:28:44'),
(5, 'Bihar', 'BR', '10', 1, '2025-11-23 04:28:44'),
(6, 'Chandigarh', 'CH', '04', 1, '2025-11-23 04:28:44'),
(7, 'Chhattisgarh', 'CG', '22', 1, '2025-11-23 04:28:44'),
(8, 'Dadra and Nagar Haveli and Daman and Diu', 'DD', '26', 1, '2025-11-23 04:28:44'),
(9, 'Delhi', 'DL', '07', 1, '2025-11-23 04:28:44'),
(10, 'Goa', 'GA', '30', 1, '2025-11-23 04:28:44'),
(11, 'Gujarat', 'GJ', '24', 1, '2025-11-23 04:28:44'),
(12, 'Haryana', 'HR', '06', 1, '2025-11-23 04:28:44'),
(13, 'Himachal Pradesh', 'HP', '02', 1, '2025-11-23 04:28:44'),
(14, 'Jammu and Kashmir', 'JK', '01', 1, '2025-11-23 04:28:44'),
(15, 'Jharkhand', 'JH', '20', 1, '2025-11-23 04:28:44'),
(16, 'Karnataka', 'KA', '29', 1, '2025-11-23 04:28:44'),
(17, 'Kerala', 'KL', '32', 1, '2025-11-23 04:28:44'),
(18, 'Ladakh', 'LA', '38', 1, '2025-11-23 04:28:44'),
(19, 'Lakshadweep', 'LD', '31', 1, '2025-11-23 04:28:44'),
(20, 'Madhya Pradesh', 'MP', '23', 1, '2025-11-23 04:28:44'),
(21, 'Maharashtra', 'MH', '27', 1, '2025-11-23 04:28:44'),
(22, 'Manipur', 'MN', '14', 1, '2025-11-23 04:28:44'),
(23, 'Meghalaya', 'ML', '17', 1, '2025-11-23 04:28:44'),
(24, 'Mizoram', 'MZ', '15', 1, '2025-11-23 04:28:44'),
(25, 'Nagaland', 'NL', '13', 1, '2025-11-23 04:28:44'),
(26, 'Odisha', 'OR', '21', 1, '2025-11-23 04:28:44'),
(27, 'Puducherry', 'PY', '34', 1, '2025-11-23 04:28:44'),
(28, 'Punjab', 'PB', '03', 1, '2025-11-23 04:28:44'),
(29, 'Rajasthan', 'RJ', '08', 1, '2025-11-23 04:28:44'),
(30, 'Sikkim', 'SK', '11', 1, '2025-11-23 04:28:44'),
(31, 'Tamil Nadu', 'TN', '33', 1, '2025-11-23 04:28:44'),
(32, 'Telangana', 'TS', '36', 1, '2025-11-23 04:28:44'),
(33, 'Tripura', 'TR', '16', 1, '2025-11-23 04:28:44'),
(34, 'Uttar Pradesh', 'UP', '09', 1, '2025-11-23 04:28:44'),
(35, 'Uttarakhand', 'UK', '05', 1, '2025-11-23 04:28:44'),
(36, 'West Bengal', 'WB', '19', 1, '2025-11-23 04:28:44');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sales_order_id` int(11) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `payment_status` enum('Unpaid','Partially Paid','Paid','Overdue') DEFAULT 'Unpaid',
  `status` enum('Draft','Sent','Paid','Partially Paid','Overdue','Cancelled') DEFAULT 'Draft',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `round_off_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `balance_amount` decimal(12,2) GENERATED ALWAYS AS (`total_amount` - `paid_amount`) STORED,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `sales_order_id` (`sales_order_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_date` (`invoice_date`),
  KEY `idx_status` (`status`),
  KEY `idx_invoices_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `company_id`, `invoice_number`, `customer_id`, `sales_order_id`, `invoice_date`, `due_date`, `payment_status`, `status`, `subtotal`, `tax_amount`, `round_off_amount`, `discount_amount`, `total_amount`, `paid_amount`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 1, 'INV-2025-001', 1, NULL, '2025-11-22', '2025-12-22', 'Unpaid', 'Paid', 41999.00, 7181.83, 0.00, 2099.95, 47080.88, 47080.88, '', 1, '2025-11-22 16:29:23', '2025-11-25 04:15:03'),
(3, 1, 'INV-2025-002', 1, NULL, '2025-11-23', '2025-12-23', 'Unpaid', 'Paid', 43298.00, 7793.64, 0.00, 2000.00, 49091.64, 98183.28, 'Converted from Quotation #QT-2025-001\r\n23000 Paid on 23 Nov 2025', 1, '2025-11-23 02:57:51', '2025-11-25 04:15:03'),
(4, 1, 'INV-2025-003', 1, NULL, '2025-11-23', '2025-12-23', 'Unpaid', 'Paid', 168.00, 8.40, 0.00, 0.00, 176.40, 176.40, '', 1, '2025-11-23 13:41:27', '2025-11-25 04:15:03'),
(5, 1, 'INV-2025-004', 1, NULL, '2025-11-24', '2025-12-24', 'Unpaid', 'Draft', 135.25, 24.35, 0.00, 8.40, 159.60, 0.00, '', 1, '2025-11-24 11:29:27', '2025-11-25 04:15:03'),
(6, 1, 'INV-2025-005', 1, NULL, '2025-11-24', '2025-12-24', 'Unpaid', 'Draft', 35592.37, 6406.63, 0.00, 0.00, 41999.00, 0.00, '', 1, '2025-11-24 11:37:31', '2025-11-25 04:15:03'),
(7, 1, 'INV-2025-006', 1, NULL, '2025-11-24', '2025-12-24', 'Unpaid', 'Draft', 3047.62, 152.38, 0.00, 0.00, 3200.00, 0.00, '', 1, '2025-11-24 11:38:06', '2025-11-25 04:15:03'),
(8, 1, 'INV-2025-007', 1, NULL, '2025-11-24', '2025-12-24', 'Unpaid', 'Paid', 168.00, 0.00, 0.00, 0.00, 168.00, 168.00, '', 1, '2025-11-24 11:38:22', '2025-11-25 04:15:03'),
(9, 1, 'INV-2025-008', 1, NULL, '2025-11-24', '2025-12-24', 'Unpaid', 'Paid', 1299.00, 233.82, 0.00, 0.00, 1532.82, 1532.82, 'Converted from Quotation #QT-2025-002', 1, '2025-11-24 12:11:57', '2025-11-25 04:15:03'),
(10, 1, 'INV-2025-009', 1, NULL, '2025-11-24', '2025-12-24', 'Unpaid', 'Paid', 36851.43, 6609.53, 0.00, 505.04, 42960.96, 42960.96, '', 1, '2025-11-24 12:33:06', '2025-11-25 04:15:03'),
(11, 1, 'INV-2025-010', 1, 2, '2025-11-24', '2025-12-24', 'Unpaid', 'Paid', 1299.00, 233.82, 0.00, 0.00, 1532.82, 1532.82, 'Converted from Sales Order #SO-2025-001', 1, '2025-11-24 13:19:01', '2025-11-25 04:15:03'),
(12, 1, 'INV-2025-011', 1, 1, '2025-11-24', '2025-12-24', 'Unpaid', 'Overdue', 1299.00, 187.06, 0.00, 2259.80, -773.74, 0.00, 'Converted from Sales Order #SO-20251123-2538', 1, '2025-11-24 13:19:16', '2025-11-25 14:31:16'),
(14, 1, 'INV-2025-012', 1, NULL, '2025-11-25', '2025-12-25', 'Paid', 'Cancelled', 102540.72, 18457.33, 0.00, 6299.95, 120998.05, 120998.05, '', 1, '2025-11-25 15:10:37', '2025-11-25 15:14:27'),
(16, 1, 'INV-2025-013', 2, NULL, '2025-11-25', '2025-12-25', 'Paid', 'Paid', 106778.81, 19220.19, 0.00, 0.00, 125999.00, 125999.00, '', 1, '2025-11-25 15:18:52', '2025-11-25 15:29:34'),
(22, 1, 'INV-2025-014', 2, NULL, '2025-11-26', '2025-12-26', 'Unpaid', 'Partially Paid', 106778.81, 19220.19, 0.00, 0.00, 125999.00, 15000.00, '', 1, '2025-11-26 05:10:25', '2025-11-26 05:16:35'),
(24, 1, 'INV-2025-015', 2, NULL, '2025-11-26', '2025-12-26', 'Unpaid', 'Draft', 33135.63, 5964.41, 0.00, 797.96, 39100.04, 0.00, '', 1, '2025-11-26 08:15:17', '2025-11-26 08:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `line_total` decimal(12,2) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `warranty_period` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_invoice` (`invoice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `company_id`, `product_id`, `description`, `quantity`, `unit_price`, `tax_rate`, `discount_percent`, `line_total`, `serial_number`, `warranty_period`, `expiry_date`) VALUES
(1, 2, 0, 1, 'Samsung S24 Purple 8/128 GB', 1.00, 41999.00, 18.00, 5.00, 47080.88, NULL, NULL, NULL),
(2, 3, 0, 1, 'Samsung S24 Purple 8/128 GB', 1.00, 41999.00, 18.00, 0.00, 49558.82, NULL, NULL, NULL),
(3, 3, 0, 2, 'Samsung 25W Charger C-C', 1.00, 1299.00, 18.00, 0.00, 1532.82, NULL, NULL, NULL),
(4, 4, 0, 3, 'Liv 52 DS 250 ML', 1.00, 168.00, 5.00, 0.00, 176.40, NULL, NULL, NULL),
(5, 5, 0, 3, 'Liv 52 DS 250 ML', 1.00, 168.00, 18.00, 5.00, 159.60, NULL, NULL, NULL),
(6, 6, 0, 3, 'Samsung S24 Purple 8/128 GB', 1.00, 41999.00, 18.00, 0.00, 41999.00, NULL, NULL, NULL),
(7, 7, 0, 4, 'CPPLUS 2MP', 1.00, 3200.00, 5.00, 0.00, 3200.00, NULL, NULL, NULL),
(8, 8, 0, 3, 'Liv 52 DS 250 ML', 1.00, 168.00, 0.00, 0.00, 168.00, NULL, NULL, NULL),
(9, 9, 0, 2, 'Samsung 25W Charger C-C', 1.00, 1299.00, 18.00, 0.00, 1532.82, NULL, NULL, NULL),
(10, 10, 0, 3, 'Liv 52 DS 250 ML', 1.00, 168.00, 3.00, 3.00, 162.96, NULL, NULL, NULL),
(11, 10, 0, 2, 'Samsung 25W Charger C-C', 1.00, 1299.00, 18.00, 0.00, 1299.00, NULL, NULL, NULL),
(12, 10, 0, 1, 'Samsung S24 Purple 8/128 GB', 1.00, 41999.00, 18.00, 0.00, 41999.00, NULL, NULL, NULL),
(13, 11, 0, 2, 'Samsung 25W Charger C-C', 1.00, 1299.00, 18.00, 0.00, 1532.82, NULL, NULL, NULL),
(14, 12, 0, 2, 'Samsung 25W Charger C-C', 1.00, 1299.00, 18.00, 20.00, 1226.26, NULL, NULL, NULL),
(15, 14, 1, 9, 'SAMSUNG S25 ULTRA 8/256 BLACK', 1.00, 125999.00, 18.00, 5.00, 119699.05, '862144048719226', '1Y', NULL),
(16, 14, 1, 2, 'Samsung 25W Charger C-C', 1.00, 1299.00, 18.00, 0.00, 1299.00, '', '', NULL),
(17, 16, 1, 9, 'SAMSUNG S25 ULTRA 8/256 BLACK', 1.00, 125999.00, 18.00, 0.00, 125999.00, '352831059178824', '1Y', NULL),
(26, 22, 1, 9, 'SAMSUNG S25 ULTRA 8/256 BLACK', 1.00, 125999.00, 18.00, 0.00, 125999.00, '863472105984612', '2y', NULL),
(28, 24, 1, 11, 'SONY BRAVIA 43\' 4K ', 1.00, 39898.00, 18.00, 2.00, 39100.04, 'SONY1', '1Y', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
CREATE TABLE IF NOT EXISTS `journal_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `entry_number` varchar(50) NOT NULL,
  `entry_date` date NOT NULL,
  `fiscal_year_id` int(11) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `total_debit` decimal(15,2) DEFAULT 0.00,
  `total_credit` decimal(15,2) DEFAULT 0.00,
  `status` enum('Draft','Posted','Cancelled') DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `entry_number` (`entry_number`),
  KEY `fiscal_year_id` (`fiscal_year_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_date` (`entry_date`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_journal_entries_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_lines`
--

DROP TABLE IF EXISTS `journal_entry_lines`;
CREATE TABLE IF NOT EXISTS `journal_entry_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_entry_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_journal` (`journal_entry_id`),
  KEY `idx_account` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
CREATE TABLE IF NOT EXISTS `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `lead_number` varchar(50) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `contact_person` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `expected_revenue` decimal(12,2) DEFAULT NULL,
  `probability` int(11) DEFAULT 0,
  `expected_close_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_number` (`lead_number`),
  KEY `source_id` (`source_id`),
  KEY `idx_status` (`status_id`),
  KEY `idx_assigned` (`assigned_to`),
  KEY `idx_leads_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_sources`
--

DROP TABLE IF EXISTS `lead_sources`;
CREATE TABLE IF NOT EXISTS `lead_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_sources`
--

INSERT INTO `lead_sources` (`id`, `name`, `description`) VALUES
(1, 'Website', NULL),
(2, 'Referral', NULL),
(3, 'Cold Call', NULL),
(4, 'Trade Show', NULL),
(5, 'Social Media', NULL),
(6, 'Email Campaign', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lead_statuses`
--

DROP TABLE IF EXISTS `lead_statuses`;
CREATE TABLE IF NOT EXISTS `lead_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `color` varchar(7) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_statuses`
--

INSERT INTO `lead_statuses` (`id`, `name`, `color`, `display_order`) VALUES
(1, 'New', '#3498db', 1),
(2, 'Contacted', '#f39c12', 2),
(3, 'Qualified', '#2ecc71', 3),
(4, 'Proposal Sent', '#9b59b6', 4),
(5, 'Negotiation', '#e67e22', 5),
(6, 'Won', '#27ae60', 6),
(7, 'Lost', '#e74c3c', 7);

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

DROP TABLE IF EXISTS `leave_applications`;
CREATE TABLE IF NOT EXISTS `leave_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(4,1) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `leave_type_id` (`leave_type_id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_date`,`end_date`),
  KEY `idx_leave_applications_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `days_per_year` int(11) DEFAULT 0,
  `is_paid` tinyint(1) DEFAULT 1,
  `requires_approval` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `name`, `days_per_year`, `is_paid`, `requires_approval`, `description`, `is_active`) VALUES
(1, 'Casual Leave', 12, 1, 1, NULL, 1),
(2, 'Sick Leave', 12, 1, 1, NULL, 1),
(3, 'Earned Leave', 15, 1, 1, NULL, 1),
(4, 'Maternity Leave', 180, 1, 1, NULL, 1),
(5, 'Paternity Leave', 15, 1, 1, NULL, 1),
(6, 'Loss of Pay', 0, 0, 1, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `opportunities`
--

DROP TABLE IF EXISTS `opportunities`;
CREATE TABLE IF NOT EXISTS `opportunities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opportunity_number` varchar(50) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `probability` int(11) DEFAULT 0,
  `stage` enum('Qualification','Needs Analysis','Proposal','Negotiation','Closed Won','Closed Lost') DEFAULT 'Qualification',
  `expected_close_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `opportunity_number` (`opportunity_number`),
  KEY `lead_id` (`lead_id`),
  KEY `customer_id` (`customer_id`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Cheque','Bank Transfer','UPI','Card','Other') DEFAULT 'Cash',
  `reference_number` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_payments_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments_made`
--

DROP TABLE IF EXISTS `payments_made`;
CREATE TABLE IF NOT EXISTS `payments_made` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `payment_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_mode` enum('Cash','Cheque','Bank Transfer','Credit Card','UPI','Other') DEFAULT 'Bank Transfer',
  `reference_number` varchar(100) DEFAULT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_number` (`payment_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_date` (`payment_date`),
  KEY `idx_payments_made_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments_received`
--

DROP TABLE IF EXISTS `payments_received`;
CREATE TABLE IF NOT EXISTS `payments_received` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `payment_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_mode` enum('Cash','Cheque','Bank Transfer','Credit Card','UPI','Other') DEFAULT 'Bank Transfer',
  `reference_number` varchar(100) DEFAULT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_number` (`payment_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_date` (`payment_date`),
  KEY `idx_payments_received_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments_received`
--

INSERT INTO `payments_received` (`id`, `company_id`, `payment_number`, `customer_id`, `payment_date`, `amount`, `payment_mode`, `reference_number`, `bank_account_id`, `notes`, `created_by`, `created_at`) VALUES
(3, 1, 'PAY-0003-1763904262', 1, '2025-11-23', 49091.64, 'Cash', '', NULL, '', 1, '2025-11-23 13:24:22'),
(4, 1, 'PAY-0003-1763904945', 1, '2025-11-23', 49091.64, 'Cash', '', NULL, '', 1, '2025-11-23 13:35:45'),
(5, 1, 'PAY-0004-1763905311', 1, '2025-11-23', 176.40, 'Cash', '', NULL, '', 1, '2025-11-23 13:41:51'),
(6, 1, 'PAY-0009-1763986330', 1, '2025-11-24', 1532.82, 'Cash', '', NULL, '', 1, '2025-11-24 12:12:10'),
(7, 1, 'PAY-0010-1763987697', 1, '2025-11-24', 42960.96, 'Cash', '', NULL, '', 1, '2025-11-24 12:34:57'),
(8, 1, 'PAY-0008-1763987716', 1, '2025-11-24', 168.00, 'Credit Card', '', NULL, '', 1, '2025-11-24 12:35:16'),
(9, 1, 'PAY-0011-1763990380', 1, '2025-11-24', 1532.82, 'Bank Transfer', '', NULL, '', 1, '2025-11-24 13:19:40'),
(11, 1, 'PAY-0014-1764083593', 1, '2025-11-25', 120998.05, 'Cash', '', NULL, '', 1, '2025-11-25 15:13:13'),
(12, 1, 'PAY-0016-1764084574', 2, '2025-11-25', 125999.00, 'Credit Card', '', NULL, '', 1, '2025-11-25 15:29:34'),
(13, 1, 'PAY-0022-1764134164', 2, '2025-11-26', 2000.00, 'Cheque', '', NULL, '', 1, '2025-11-26 05:16:04'),
(14, 1, 'PAY-0022-1764134195', 2, '2025-11-26', 13000.00, 'Cheque', '12898', NULL, '', 1, '2025-11-26 05:16:35');

-- --------------------------------------------------------

--
-- Table structure for table `payment_allocations`
--

DROP TABLE IF EXISTS `payment_allocations`;
CREATE TABLE IF NOT EXISTS `payment_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_id` (`payment_id`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_allocations`
--

INSERT INTO `payment_allocations` (`id`, `payment_id`, `invoice_id`, `company_id`, `allocated_amount`) VALUES
(1, 3, 3, 0, 49091.64),
(2, 4, 3, 0, 49091.64),
(3, 5, 4, 0, 176.40),
(4, 6, 9, 0, 1532.82),
(5, 7, 10, 0, 42960.96),
(6, 8, 8, 0, 168.00),
(7, 9, 11, 0, 1532.82),
(8, 11, 14, 1, 120998.05),
(9, 12, 16, 1, 125999.00),
(10, 13, 22, 1, 2000.00),
(11, 14, 22, 1, 13000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment_made_allocations`
--

DROP TABLE IF EXISTS `payment_made_allocations`;
CREATE TABLE IF NOT EXISTS `payment_made_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_id` (`payment_id`),
  KEY `bill_id` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `method_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `method_name` (`method_name`),
  KEY `idx_payment_methods_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `company_id`, `method_name`, `description`, `is_active`, `display_order`, `created_at`) VALUES
(1, 1, 'Cash', 'Cash payment', 1, 1, '2025-11-23 04:28:44'),
(2, 1, 'Cheque', 'Payment by cheque', 1, 2, '2025-11-23 04:28:44'),
(3, 1, 'Bank Transfer', 'Direct bank transfer/NEFT/RTGS/IMPS', 1, 3, '2025-11-23 04:28:44'),
(4, 1, 'UPI', 'UPI payment (Google Pay, PhonePe, Paytm, etc.)', 1, 4, '2025-11-23 04:28:44'),
(5, 1, 'Credit Card', 'Credit card payment', 1, 5, '2025-11-23 04:28:44'),
(6, 1, 'Debit Card', 'Debit card payment', 1, 6, '2025-11-23 04:28:44'),
(7, 1, 'Net Banking', 'Online net banking', 1, 7, '2025-11-23 04:28:44'),
(8, 1, 'Wallet', 'Digital wallet payment', 1, 8, '2025-11-23 04:28:44'),
(9, 1, 'Other', 'Other payment methods', 1, 99, '2025-11-23 04:28:44');

-- --------------------------------------------------------

--
-- Table structure for table `payment_reminders`
--

DROP TABLE IF EXISTS `payment_reminders`;
CREATE TABLE IF NOT EXISTS `payment_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `reminder_date` date NOT NULL,
  `reminder_type` enum('Email','SMS','Both') DEFAULT 'Email',
  `status` enum('Pending','Sent','Failed') DEFAULT 'Pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_reminder_date` (`reminder_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

DROP TABLE IF EXISTS `payment_transactions`;
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subscription_id` (`subscription_id`),
  KEY `idx_status` (`status`),
  KEY `idx_razorpay_payment` (`razorpay_payment_id`),
  KEY `idx_razorpay_order` (`razorpay_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
CREATE TABLE IF NOT EXISTS `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `gross_salary` decimal(10,2) NOT NULL,
  `total_deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_status` enum('Pending','Processed','Paid') DEFAULT 'Pending',
  `payment_method` enum('Bank Transfer','Cash','Cheque') DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_payroll` (`employee_id`,`month`,`year`),
  KEY `idx_period` (`year`,`month`),
  KEY `idx_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_components`
--

DROP TABLE IF EXISTS `payroll_components`;
CREATE TABLE IF NOT EXISTS `payroll_components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('Earning','Deduction') NOT NULL,
  `calculation_type` enum('Fixed','Percentage','Formula') DEFAULT 'Fixed',
  `formula` text DEFAULT NULL,
  `is_taxable` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_components`
--

INSERT INTO `payroll_components` (`id`, `name`, `type`, `calculation_type`, `formula`, `is_taxable`, `is_active`, `display_order`) VALUES
(1, 'Basic Salary', 'Earning', 'Fixed', NULL, 1, 1, 0),
(2, 'HRA', 'Earning', 'Percentage', NULL, 1, 1, 0),
(3, 'Conveyance Allowance', 'Earning', 'Fixed', NULL, 1, 1, 0),
(4, 'Medical Allowance', 'Earning', 'Fixed', NULL, 1, 1, 0),
(5, 'Special Allowance', 'Earning', 'Fixed', NULL, 1, 1, 0),
(6, 'Provident Fund', 'Deduction', 'Percentage', NULL, 0, 1, 0),
(7, 'Professional Tax', 'Deduction', 'Fixed', NULL, 0, 1, 0),
(8, 'Income Tax', 'Deduction', 'Fixed', NULL, 0, 1, 0),
(9, 'ESI', 'Deduction', 'Percentage', NULL, 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_details`
--

DROP TABLE IF EXISTS `payroll_details`;
CREATE TABLE IF NOT EXISTS `payroll_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_id` (`payroll_id`),
  KEY `component_id` (`component_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`module`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_entries`
--

DROP TABLE IF EXISTS `production_entries`;
CREATE TABLE IF NOT EXISTS `production_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_number` varchar(50) NOT NULL,
  `work_order_id` int(11) NOT NULL,
  `production_date` date NOT NULL,
  `quantity_produced` decimal(10,2) NOT NULL,
  `quantity_rejected` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `entry_number` (`entry_number`),
  KEY `work_order_id` (`work_order_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uom_id` int(11) NOT NULL,
  `product_type` enum('Goods','Service','Raw Material','Finished Goods') DEFAULT 'Goods',
  `hsn_code` varchar(20) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `reorder_level` decimal(10,2) DEFAULT 0.00,
  `reorder_quantity` decimal(10,2) DEFAULT 0.00,
  `standard_cost` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `has_serial_number` tinyint(1) DEFAULT 0,
  `has_warranty` tinyint(1) DEFAULT 0,
  `has_expiry_date` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `uom_id` (`uom_id`),
  KEY `idx_code` (`product_code`),
  KEY `idx_category` (`category_id`),
  KEY `idx_barcode` (`barcode`),
  KEY `idx_products_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `company_id`, `product_code`, `name`, `category_id`, `description`, `uom_id`, `product_type`, `hsn_code`, `barcode`, `sku`, `reorder_level`, `reorder_quantity`, `standard_cost`, `selling_price`, `tax_rate`, `is_active`, `has_serial_number`, `has_warranty`, `has_expiry_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'PRD001', 'Samsung S24 Purple 8/128 GB', 1, '', 8, 'Goods', '8517', '8517', NULL, 0.00, 0.00, 42999.00, 41999.00, 18.00, 1, 0, 0, 0, '2025-11-22 16:21:18', '2025-11-25 04:15:03'),
(2, 1, 'PRD002', 'Samsung 25W Charger C-C', 2, '', 1, 'Goods', '534543', '', NULL, 0.00, 0.00, 1299.00, 1299.00, 18.00, 1, 0, 0, 0, '2025-11-23 02:57:16', '2025-11-25 04:15:03'),
(3, 1, 'PRD003', 'Liv 52 DS 250 ML', 3, '', 1, 'Goods', '30049011', '', NULL, 0.00, 0.00, 0.00, 168.00, 220.00, 1, 0, 0, 0, '2025-11-23 09:36:29', '2025-11-25 04:15:03'),
(4, 1, 'PRD004', 'CPPLUS 2MP', 4, '', 1, 'Goods', '6876', '', NULL, 0.00, 0.00, 0.00, 3200.00, 5.00, 1, 0, 0, 0, '2025-11-24 11:30:43', '2025-11-25 04:15:03'),
(5, 6, 'P-1764044231', 'Test Product', 1, NULL, 1, 'Goods', NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 0, 0, 0, '2025-11-25 04:17:11', '2025-11-25 04:17:11'),
(6, 1, 'P-OTHER-1764044231', 'Other Company Product', 1, NULL, 1, 'Goods', NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 0, 0, 0, '2025-11-25 04:17:11', '2025-11-25 04:17:11'),
(9, 1, 'PRD005', 'SAMSUNG S25 ULTRA 8/256 BLACK', 1, '', 6, 'Goods', '890898', 'SAMSUNG S25 ULTRA 8/256 BLACK', NULL, 0.00, 0.00, 129999.00, 125999.00, 18.00, 1, 1, 1, 0, '2025-11-25 15:05:57', '2025-11-25 15:05:57'),
(10, NULL, 'PRD006', 'Sony 43\' LED TV 4k', 5, '', 1, 'Goods', '687687', '', NULL, 0.00, 0.00, 0.00, 39000.00, 18.00, 1, 1, 1, 0, '2025-11-26 07:57:28', '2025-11-26 07:57:28'),
(11, 1, 'PRD007', 'SONY BRAVIA 43\' 4K ', 5, '', 1, 'Goods', '67687', '', NULL, 0.00, 0.00, 0.00, 39898.00, 18.00, 1, 1, 1, 0, '2025-11-26 08:12:13', '2025-11-26 08:12:13');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE IF NOT EXISTS `product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_product_categories_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `company_id`, `name`, `parent_id`, `description`, `is_active`, `created_at`) VALUES
(1, 1, 'Mobile Phones', NULL, '', 1, '2025-11-22 16:20:04'),
(2, 1, 'Chargers', NULL, '', 1, '2025-11-23 02:57:03'),
(3, 1, 'Ayurveda Medicine', NULL, '', 1, '2025-11-23 09:35:44'),
(4, 1, 'CAMERA', NULL, '', 1, '2025-11-24 11:30:21'),
(5, NULL, 'TV', NULL, '', 1, '2025-11-26 07:57:12');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoices`
--

DROP TABLE IF EXISTS `purchase_invoices`;
CREATE TABLE IF NOT EXISTS `purchase_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `bill_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `po_id` int(11) DEFAULT NULL,
  `bill_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Draft','Submitted','Paid','Partially Paid','Overdue') DEFAULT 'Draft',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `balance_amount` decimal(12,2) GENERATED ALWAYS AS (`total_amount` - `paid_amount`) STORED,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_number` (`bill_number`),
  KEY `po_id` (`po_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_date` (`bill_date`),
  KEY `idx_purchase_invoices_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_invoices`
--

INSERT INTO `purchase_invoices` (`id`, `company_id`, `bill_number`, `supplier_id`, `po_id`, `bill_date`, `due_date`, `status`, `subtotal`, `tax_amount`, `discount_amount`, `total_amount`, `paid_amount`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'PB-001', 1, NULL, '2025-11-23', '2025-12-23', 'Draft', 168.00, 8.40, 0.00, 176.40, 0.00, '', 1, '2025-11-23 09:37:08');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoice_items`
--

DROP TABLE IF EXISTS `purchase_invoice_items`;
CREATE TABLE IF NOT EXISTS `purchase_invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `line_total` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_invoice_items`
--

INSERT INTO `purchase_invoice_items` (`id`, `bill_id`, `product_id`, `description`, `quantity`, `unit_price`, `tax_rate`, `discount_percent`, `line_total`) VALUES
(1, 1, 3, 'Liv 52 DS 250 ML', 1.00, 168.00, 5.00, 0.00, 176.40);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `status` enum('Draft','Sent','Confirmed','Partially Received','Received','Cancelled') DEFAULT 'Draft',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `shipping_charges` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_date` (`order_date`),
  KEY `idx_status` (`status`),
  KEY `idx_purchase_orders_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `received_quantity` decimal(10,2) DEFAULT 0.00,
  `unit_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `line_total` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `po_id` (`po_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
CREATE TABLE IF NOT EXISTS `quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `quotation_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `quotation_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `status` enum('Draft','Sent','Accepted','Rejected','Expired') DEFAULT 'Draft',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `round_off_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `terms_conditions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pipeline_stage` enum('Lead','Quotation','Negotiation','Won','Lost') DEFAULT 'Quotation',
  `expected_close_date` date DEFAULT NULL,
  `win_probability` int(11) DEFAULT 50,
  `lost_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotation_number` (`quotation_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_date` (`quotation_date`),
  KEY `idx_status` (`status`),
  KEY `idx_quotations_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `company_id`, `quotation_number`, `customer_id`, `quotation_date`, `valid_until`, `reference`, `status`, `subtotal`, `tax_amount`, `round_off_amount`, `discount_amount`, `total_amount`, `terms_conditions`, `notes`, `created_by`, `created_at`, `updated_at`, `pipeline_stage`, `expected_close_date`, `win_probability`, `lost_reason`) VALUES
(2, 1, 'QT-2025-001', 1, '2025-11-23', '2025-12-23', NULL, 'Accepted', 1299.00, 187.06, 0.00, 2259.80, -773.74, NULL, '', 1, '2025-11-23 02:41:53', '2025-11-25 04:20:41', 'Quotation', NULL, 50, NULL),
(3, 1, 'QT-2025-002', 1, '2025-11-24', '2025-12-24', NULL, 'Draft', 1299.00, 233.82, 0.00, 0.00, 1532.82, NULL, '', 1, '2025-11-24 12:11:52', '2025-11-25 04:20:41', 'Quotation', NULL, 50, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

DROP TABLE IF EXISTS `quotation_items`;
CREATE TABLE IF NOT EXISTS `quotation_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `line_total` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `unit_price` * (1 - `discount_percent` / 100) * (1 + `tax_rate` / 100)) STORED,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_quotation` (`quotation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`id`, `quotation_id`, `product_id`, `description`, `quantity`, `unit_price`, `tax_rate`, `discount_percent`) VALUES
(8, 2, 2, 'Samsung 25W Charger C-C', 1.00, 1299.00, 18.00, 20.00),
(9, 3, 2, 'Samsung 25W Charger C-C', 1.00, 1299.00, 18.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Super Admin', 'Full system access', '2025-11-22 15:41:13'),
(2, 'Admin', 'Administrative access', '2025-11-22 15:41:13'),
(3, 'Manager', 'Department manager access', '2025-11-22 15:41:13'),
(4, 'Employee', 'Basic employee access', '2025-11-22 15:41:13'),
(5, 'Accountant', 'Accounting module access', '2025-11-22 15:41:13'),
(6, 'Sales Person', 'Sales module access', '2025-11-22 15:41:13'),
(7, 'Purchase Officer', 'Purchase module access', '2025-11-22 15:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

DROP TABLE IF EXISTS `sales_orders`;
CREATE TABLE IF NOT EXISTS `sales_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `status` enum('Draft','Confirmed','In Progress','Completed','Cancelled') DEFAULT 'Draft',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `round_off_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `shipping_charges` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `payment_status` enum('Unpaid','Partially Paid','Paid') DEFAULT 'Unpaid',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `quotation_id` (`quotation_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_date` (`order_date`),
  KEY `idx_status` (`status`),
  KEY `idx_sales_orders_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_orders`
--

INSERT INTO `sales_orders` (`id`, `company_id`, `order_number`, `customer_id`, `quotation_id`, `order_date`, `expected_delivery_date`, `status`, `subtotal`, `tax_amount`, `round_off_amount`, `discount_amount`, `shipping_charges`, `total_amount`, `payment_status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'SO-20251123-2538', 1, 2, '2025-11-23', NULL, 'Completed', 1299.00, 187.06, 0.00, 2259.80, 0.00, -773.74, 'Unpaid', 'Converted from Quotation #QT-2025-001', 1, '2025-11-23 08:23:18', '2025-11-25 04:15:03'),
(2, 1, 'SO-2025-001', 1, 2, '2025-11-23', '2025-11-30', 'Completed', 1299.00, 233.82, 0.00, 0.00, 0.00, 1532.82, 'Unpaid', '', 1, '2025-11-23 08:45:05', '2025-11-25 04:15:03');

-- --------------------------------------------------------

--
-- Table structure for table `sales_order_items`
--

DROP TABLE IF EXISTS `sales_order_items`;
CREATE TABLE IF NOT EXISTS `sales_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `delivered_quantity` decimal(10,2) DEFAULT 0.00,
  `unit_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `line_total` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_order_items`
--

INSERT INTO `sales_order_items` (`id`, `order_id`, `product_id`, `description`, `quantity`, `delivered_quantity`, `unit_price`, `tax_rate`, `discount_percent`, `line_total`) VALUES
(1, 1, 2, 'Samsung 25W Charger C-C', 1.00, 0.00, 1299.00, 18.00, 20.00, 1226.26),
(2, 2, 2, 'Samsung 25W Charger C-C', 1.00, 0.00, 1299.00, 18.00, 0.00, 1532.82);

-- --------------------------------------------------------

--
-- Table structure for table `sales_targets`
--

DROP TABLE IF EXISTS `sales_targets`;
CREATE TABLE IF NOT EXISTS `sales_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `target_period` enum('Monthly','Quarterly','Yearly') DEFAULT 'Monthly',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `target_amount` decimal(15,2) NOT NULL,
  `achieved_amount` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_period` (`user_id`,`period_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_balance`
--

DROP TABLE IF EXISTS `stock_balance`;
CREATE TABLE IF NOT EXISTS `stock_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `reserved_quantity` decimal(10,2) DEFAULT 0.00,
  `available_quantity` decimal(10,2) GENERATED ALWAYS AS (`quantity` - `reserved_quantity`) STORED,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_stock` (`product_id`,`warehouse_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_warehouse` (`warehouse_id`),
  KEY `idx_stock_balance_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_balance`
--

INSERT INTO `stock_balance` (`id`, `company_id`, `product_id`, `warehouse_id`, `quantity`, `reserved_quantity`, `last_updated`) VALUES
(8, 1, 2, 1, 48.00, 0.00, '2025-11-25 15:10:37'),
(9, 1, 3, 1, 10.00, 0.00, '2025-11-25 04:15:03'),
(10, 1, 4, 1, 221.00, 0.00, '2025-11-25 04:15:03'),
(15, NULL, 10, 1, 20.00, 0.00, '2025-11-26 08:02:46');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE IF NOT EXISTS `stock_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `transaction_type` enum('IN','OUT','TRANSFER','ADJUSTMENT') NOT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_product` (`product_id`),
  KEY `idx_warehouse` (`warehouse_id`),
  KEY `idx_date` (`transaction_date`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_stock_transactions_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `company_id`, `transaction_type`, `product_id`, `warehouse_id`, `quantity`, `reference_type`, `reference_id`, `transaction_date`, `remarks`, `created_by`) VALUES
(1, 1, 'IN', 3, 1, 1.00, 'purchase_invoice', 1, '2025-11-23 09:37:08', 'Purchase from PB-001', 1),
(4, 1, 'IN', 2, 1, 50.00, 'Manual Adjustment', NULL, '2025-11-23 09:39:19', '', 1),
(5, 1, 'OUT', 3, 1, 1.00, 'invoice', 4, '2025-11-23 13:41:27', 'Sale from invoice INV-2025-003', 1),
(6, 1, 'OUT', 2, 1, 1.00, 'invoice', 10, '2025-11-24 12:33:06', 'Sale from invoice INV-2025-009', 1),
(7, 1, 'IN', 4, 1, 200.00, 'Manual Adjustment', NULL, '2025-11-24 16:01:26', '', 1),
(8, 1, 'IN', 4, 1, 1.00, 'Manual Adjustment', NULL, '2025-11-24 16:04:09', '', 1),
(9, 1, 'IN', 3, 1, 20.00, 'Manual Adjustment', NULL, '2025-11-24 16:15:05', '', 1),
(10, 1, 'IN', 3, 1, 20.00, 'Manual Adjustment', NULL, '2025-11-24 16:18:57', '', 1),
(11, 1, 'OUT', 3, 1, 20.00, 'Manual Adjustment', NULL, '2025-11-24 16:20:47', '', 1),
(12, 1, 'OUT', 3, 1, 20.00, 'Manual Adjustment', NULL, '2025-11-24 16:20:57', '', 1),
(13, 1, 'IN', 4, 1, 10.00, 'Manual Adjustment', NULL, '2025-11-24 16:21:31', '', 1),
(14, 1, 'IN', 3, 1, 10.00, 'Manual Adjustment', NULL, '2025-11-24 16:27:37', '', 1),
(15, NULL, 'OUT', 2, 1, 1.00, 'invoice', 14, '2025-11-25 15:10:37', 'Sale from invoice INV-2025-012', 1),
(18, NULL, 'IN', 10, 1, 20.00, 'Manual Adjustment', NULL, '2025-11-26 08:02:46', '', 1);

--
-- Triggers `stock_transactions`
--
DROP TRIGGER IF EXISTS `after_stock_transaction_insert`;
DELIMITER $$
CREATE TRIGGER `after_stock_transaction_insert` AFTER INSERT ON `stock_transactions` FOR EACH ROW BEGIN
    IF NEW.transaction_type = 'IN' THEN
        INSERT INTO stock_balance (product_id, warehouse_id, quantity)
        VALUES (NEW.product_id, NEW.warehouse_id, NEW.quantity)
        ON DUPLICATE KEY UPDATE quantity = quantity + NEW.quantity;
    ELSEIF NEW.transaction_type = 'OUT' THEN
        UPDATE stock_balance 
        SET quantity = quantity - NEW.quantity
        WHERE product_id = NEW.product_id AND warehouse_id = NEW.warehouse_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_name` varchar(50) NOT NULL,
  `plan_price` decimal(10,2) NOT NULL,
  `billing_cycle` enum('monthly','annual') DEFAULT 'monthly',
  `status` enum('trial','active','cancelled','expired') DEFAULT 'trial',
  `trial_ends_at` datetime DEFAULT NULL,
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `razorpay_subscription_id` varchar(100) DEFAULT NULL,
  `razorpay_customer_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `plan_name`, `plan_price`, `billing_cycle`, `status`, `trial_ends_at`, `current_period_start`, `current_period_end`, `razorpay_subscription_id`, `razorpay_customer_id`, `created_at`, `updated_at`) VALUES
(1, 3, 'Starter', 4999.00, 'monthly', 'trial', '2025-12-09 09:30:24', '2025-11-25 09:30:24', '2025-12-25 09:30:24', NULL, NULL, '2025-11-25 04:00:24', '2025-11-25 04:00:24'),
(2, 8, 'Professional', 9999.00, 'monthly', 'trial', '2025-12-09 18:49:53', '2025-11-25 18:49:53', '2025-12-25 18:49:53', NULL, NULL, '2025-11-25 13:19:53', '2025-11-25 13:19:53'),
(3, 9, 'Professional', 9999.00, 'monthly', 'trial', '2025-12-09 19:03:42', '2025-11-25 19:03:42', '2025-12-25 19:03:42', NULL, NULL, '2025-11-25 13:33:42', '2025-11-25 13:33:42');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_name` varchar(50) NOT NULL,
  `plan_code` varchar(20) NOT NULL,
  `monthly_price` decimal(10,2) NOT NULL,
  `annual_price` decimal(10,2) NOT NULL,
  `max_users` int(11) NOT NULL,
  `storage_gb` int(11) NOT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_name` (`plan_name`),
  UNIQUE KEY `plan_code` (`plan_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `plan_name`, `plan_code`, `monthly_price`, `annual_price`, `max_users`, `storage_gb`, `features`, `is_active`, `display_order`, `created_at`) VALUES
(1, 'Starter', 'STARTER', 4999.00, 47990.40, 3, 5, '[\"Basic inventory management\", \"Sales & purchase invoicing\", \"Basic accounting\", \"Email support\", \"5 GB storage\"]', 1, 1, '2025-11-25 03:58:17'),
(2, 'Professional', 'PROFESSIONAL', 9999.00, 95990.40, 10, 50, '[\"Advanced inventory management\", \"Multi-warehouse support\", \"Complete accounting suite\", \"HR & payroll module\", \"Priority support\", \"50 GB storage\", \"Custom reports\"]', 1, 2, '2025-11-25 03:58:17'),
(3, 'Enterprise', 'ENTERPRISE', 19999.00, 191990.40, 999999, 999999, '[\"Unlimited users\", \"All Professional features\", \"Multi-company support\", \"Advanced analytics\", \"API access\", \"24/7 dedicated support\", \"Unlimited storage\", \"Custom integrations\", \"On-premise deployment option\"]', 1, 3, '2025-11-25 03:58:17');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `supplier_code` varchar(20) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `payment_terms` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_code` (`supplier_code`),
  KEY `idx_code` (`supplier_code`),
  KEY `idx_suppliers_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `company_id`, `supplier_code`, `company_name`, `contact_person`, `email`, `phone`, `mobile`, `website`, `gstin`, `pan`, `credit_limit`, `payment_terms`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'SUPP001', 'Rajeev Ayurveda', 'Aman', 'amanr@gmail.com', '', '88878788776', NULL, '09HJNBK8898J1I1', 'HJNBK8898J', 0.00, 0, 1, '2025-11-23 09:29:05', '2025-11-25 04:15:03');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_addresses`
--

DROP TABLE IF EXISTS `supplier_addresses`;
CREATE TABLE IF NOT EXISTS `supplier_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `address_type` enum('Billing','Shipping','Both') DEFAULT 'Both',
  `address_line1` varchar(200) DEFAULT NULL,
  `address_line2` varchar(200) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'India',
  `postal_code` varchar(10) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_addresses`
--

INSERT INTO `supplier_addresses` (`id`, `supplier_id`, `address_type`, `address_line1`, `address_line2`, `city`, `state`, `country`, `postal_code`, `is_default`) VALUES
(1, 1, 'Both', 'MAUJAMPUR JAITRA', '', 'DHAMPUR', 'UTTAR PRADESH', 'India', '246761', 1);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'maintenance_mode', '0', '2025-11-25 04:48:22', '2025-11-25 14:05:33');

-- --------------------------------------------------------

--
-- Table structure for table `tax_rates`
--

DROP TABLE IF EXISTS `tax_rates`;
CREATE TABLE IF NOT EXISTS `tax_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `tax_name` varchar(50) NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tax_rates_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tax_rates`
--

INSERT INTO `tax_rates` (`id`, `company_id`, `tax_name`, `tax_rate`, `description`, `is_active`, `created_at`) VALUES
(1, 1, 'GST 0%', 0.00, 'Zero rated GST', 1, '2025-11-23 04:28:44'),
(2, 1, 'GST 0.25%', 0.25, 'GST at 0.25%', 1, '2025-11-23 04:28:44'),
(3, 1, 'GST 3%', 3.00, 'GST at 3%', 1, '2025-11-23 04:28:44'),
(4, 1, 'GST 5%', 5.00, 'GST at 5%', 1, '2025-11-23 04:28:44'),
(5, 1, 'GST 12%', 12.00, 'GST at 12%', 1, '2025-11-23 04:28:44'),
(6, 1, 'GST 18%', 18.00, 'GST at 18%', 1, '2025-11-23 04:28:44'),
(7, 1, 'GST 28%', 28.00, 'GST at 28%', 1, '2025-11-23 04:28:44');

-- --------------------------------------------------------

--
-- Table structure for table `units_of_measure`
--

DROP TABLE IF EXISTS `units_of_measure`;
CREATE TABLE IF NOT EXISTS `units_of_measure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `type` enum('Weight','Length','Volume','Quantity','Other') DEFAULT 'Quantity',
  PRIMARY KEY (`id`),
  KEY `idx_units_of_measure_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units_of_measure`
--

INSERT INTO `units_of_measure` (`id`, `company_id`, `name`, `symbol`, `type`) VALUES
(1, 1, 'Piece', 'Pcs', 'Quantity'),
(2, 1, 'Kilogram', 'Kg', 'Weight'),
(3, 1, 'Gram', 'g', 'Weight'),
(4, 1, 'Liter', 'L', 'Volume'),
(5, 1, 'Meter', 'm', 'Length'),
(6, 1, 'Box', 'Box', 'Quantity'),
(7, 1, 'Dozen', 'Dzn', 'Quantity'),
(8, 1, 'Set', 'Set', 'Quantity');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `onboarding_completed` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email_verification` (`email_verification_token`),
  KEY `idx_users_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `company_id`, `username`, `email`, `company_name`, `password_hash`, `full_name`, `is_active`, `email_verification_token`, `email_verified`, `last_login`, `created_at`, `updated_at`, `onboarding_completed`) VALUES
(1, 1, 'admin', 'admin@tigererp.com', '', '$2y$12$8VPm.EJoFaI330m1N.e/lOad7dvaF3NeN9BdTYD59pe/zpG66/dve', 'System Administrator', 1, NULL, 0, '2025-11-25 15:01:54', '2025-11-22 15:41:13', '2025-11-25 15:01:54', 0),
(3, 1, 'amans_1764043059', 'amans@gmail.com', 'AD Herbals', '$2y$10$zPB2oNBFvHli333iZ9NzieooqZa5BQsQscaYT.ytxigdic3aqpDQe', 'Aman', 1, '99fd995f2e8c2827e8d464d2375940e6dd5288fab15558c250d141607335dcf5', 0, NULL, '2025-11-25 03:57:40', '2025-11-25 04:15:03', 1),
(4, 3, 'user_1764044178', 'test_isolation_1764044178@example.com', 'Test Company 1764044178', '$2y$12$WnLE1KacJy.AOdLsHlhouOKG8DQRZcT7m4HNqKtSix00OLqgGdVPS', 'Test User', 1, NULL, 0, NULL, '2025-11-25 04:16:18', '2025-11-25 04:16:18', 0),
(5, 4, 'user_1764044195', 'test_isolation_1764044195@example.com', 'Test Company 1764044195', '$2y$12$0c44xQEjokMP/Xr8./p1ZeI1D7X6v7TQ3Svt/rroJbbOoP0HVLd8u', 'Test User', 1, NULL, 0, NULL, '2025-11-25 04:16:35', '2025-11-25 04:16:35', 0),
(6, 5, 'user_1764044212', 'test_isolation_1764044212@example.com', 'Test Company 1764044212', '$2y$12$7G9KSl4xdbvqycT3vNhpfO/q1FD.srn8nlMdIBT7wJRcEjuMUB2DG', 'Test User', 1, NULL, 0, NULL, '2025-11-25 04:16:52', '2025-11-25 04:16:52', 0),
(7, 6, 'user_1764044231', 'test_isolation_1764044231@example.com', 'Test Company 1764044231', '$2y$12$mMAAJ2OyhCxz8MT/3tjSxO37gm9hcbBPBnElAP7GFEHqSq3W4qOva', 'Test User', 1, NULL, 0, NULL, '2025-11-25 04:17:11', '2025-11-25 04:17:11', 0),
(8, 9, 'aman_1764076784', 'aman@gmail.com', 'AD Herbals', '$2y$10$Yd3MCd5rkJYUhpxLlmEmGOVeNWeulROzVfed4DRy7ZAenR1Xo4ty.', 'Aman Rajput', 1, 'c042eed03308af55677451578257f712f7151017b84fee5f50960c4491aaf509', 0, NULL, '2025-11-25 13:19:44', '2025-11-25 13:19:56', 1),
(9, 11, 'sp_1764077610', 'sp@akash.com', 'Akash Logistics', '$2y$10$E4DxbIN8l25xUiYTNcqNKeEJkdnH26uKO6H90PzS83edvP9ZmY27C', 'SP Singh', 1, 'fc006e6305c1b4111eba6842abe4d5e375345825103bf7799da7a01691eacb83', 0, NULL, '2025-11-25 13:33:30', '2025-11-25 13:33:43', 1),
(10, 11, 'pragya@akash.com', 'pragya@akash.com', 'akash', '$2y$10$.DJdQqJ59YkIxZCrrDryGeiGGe8xHiFv9HrliM3VLAG12g4McDxMC', 'PRAGYA', 1, NULL, 0, '2025-11-25 14:07:00', '2025-11-25 14:04:57', '2025-11-25 14:07:00', 0),
(11, 1, 'pulkit@hawk', 'pulkit@hawk.com', 'Hawk ERP Solutions', '$2y$10$L59sm84KI1lvaaZ9G5vjleUWGq1AmQ1YXIQE4Ewv9RnR96P6qvfam', 'pulkit', 1, NULL, 0, '2025-11-25 14:14:48', '2025-11-25 14:14:30', '2025-11-25 14:14:48', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_module_access`
--

DROP TABLE IF EXISTS `user_module_access`;
CREATE TABLE IF NOT EXISTS `user_module_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_module` (`user_id`,`module`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_module_access`
--

INSERT INTO `user_module_access` (`id`, `user_id`, `module`, `created_at`) VALUES
(1, 11, 'sales', '2025-11-25 14:14:30');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_at`) VALUES
(1, 1, '2025-11-22 15:41:13'),
(3, 4, '2025-11-25 03:57:40'),
(4, 2, '2025-11-25 04:16:18'),
(5, 2, '2025-11-25 04:16:35'),
(6, 2, '2025-11-25 04:16:52'),
(7, 2, '2025-11-25 04:17:11'),
(8, 2, '2025-11-25 13:19:44'),
(9, 2, '2025-11-25 13:33:30'),
(10, 4, '2025-11-25 14:04:57'),
(11, 4, '2025-11-25 14:14:30');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_employee_details`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `vw_employee_details`;
CREATE TABLE IF NOT EXISTS `vw_employee_details` (
`id` int(11)
,`employee_code` varchar(20)
,`full_name` varchar(101)
,`email` varchar(100)
,`phone` varchar(20)
,`department` varchar(100)
,`designation` varchar(100)
,`manager_name` varchar(101)
,`status` enum('Active','Inactive','Terminated','Resigned')
,`date_of_joining` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_purchase_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `vw_purchase_summary`;
CREATE TABLE IF NOT EXISTS `vw_purchase_summary` (
`id` int(11)
,`bill_number` varchar(50)
,`bill_date` date
,`supplier` varchar(200)
,`total_amount` decimal(12,2)
,`paid_amount` decimal(12,2)
,`balance_amount` decimal(12,2)
,`status` enum('Draft','Submitted','Paid','Partially Paid','Overdue')
,`days_overdue` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_sales_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `vw_sales_summary`;
CREATE TABLE IF NOT EXISTS `vw_sales_summary` (
`id` int(11)
,`invoice_number` varchar(50)
,`invoice_date` date
,`customer` varchar(200)
,`total_amount` decimal(12,2)
,`paid_amount` decimal(12,2)
,`balance_amount` decimal(12,2)
,`status` enum('Draft','Sent','Paid','Partially Paid','Overdue','Cancelled')
,`days_overdue` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_stock_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `vw_stock_summary`;
CREATE TABLE IF NOT EXISTS `vw_stock_summary` (
`id` int(11)
,`product_code` varchar(50)
,`product_name` varchar(200)
,`category` varchar(100)
,`warehouse` varchar(100)
,`quantity` decimal(10,2)
,`reserved_quantity` decimal(10,2)
,`available_quantity` decimal(10,2)
,`reorder_level` decimal(10,2)
,`stock_status` varchar(9)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_customer_sales`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `v_customer_sales`;
CREATE TABLE IF NOT EXISTS `v_customer_sales` (
`id` int(11)
,`customer_code` varchar(20)
,`company_name` varchar(200)
,`customer_segment` enum('VIP','Premium','Regular','New')
,`total_orders` bigint(21)
,`total_revenue` decimal(34,2)
,`total_paid` decimal(34,2)
,`outstanding_balance` decimal(34,2)
,`last_purchase_date` date
,`avg_order_value` decimal(16,6)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_product_sales`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `v_product_sales`;
CREATE TABLE IF NOT EXISTS `v_product_sales` (
`id` int(11)
,`product_code` varchar(50)
,`name` varchar(200)
,`category_id` int(11)
,`total_quantity_sold` decimal(32,2)
,`total_sales` decimal(42,4)
,`times_sold` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_sales_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `v_sales_summary`;
CREATE TABLE IF NOT EXISTS `v_sales_summary` (
`period` varchar(7)
,`total_invoices` bigint(21)
,`total_sales` decimal(34,2)
,`total_tax` decimal(34,2)
,`total_revenue` decimal(34,2)
,`total_collected` decimal(34,2)
,`total_outstanding` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  KEY `idx_warehouses_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `company_id`, `name`, `code`, `address`, `city`, `state`, `country`, `postal_code`, `manager_id`, `is_active`, `created_at`) VALUES
(1, 1, 'BJOB', 'WH001', 'Near SDM Court', 'Dhampur', 'Uttar Pradesh', 'India', '246761', NULL, 1, '2025-11-23 09:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `work_orders`
--

DROP TABLE IF EXISTS `work_orders`;
CREATE TABLE IF NOT EXISTS `work_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wo_number` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `bom_id` int(11) DEFAULT NULL,
  `quantity_to_produce` decimal(10,2) NOT NULL,
  `quantity_produced` decimal(10,2) DEFAULT 0.00,
  `warehouse_id` int(11) NOT NULL,
  `planned_start_date` date DEFAULT NULL,
  `planned_end_date` date DEFAULT NULL,
  `actual_start_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `status` enum('Draft','Released','In Progress','Completed','Cancelled') DEFAULT 'Draft',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `wo_number` (`wo_number`),
  KEY `bom_id` (`bom_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `vw_employee_details`
--
DROP TABLE IF EXISTS `vw_employee_details`;

DROP VIEW IF EXISTS `vw_employee_details`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_employee_details`  AS SELECT `e`.`id` AS `id`, `e`.`employee_code` AS `employee_code`, concat(`e`.`first_name`,' ',`e`.`last_name`) AS `full_name`, `e`.`email` AS `email`, `e`.`phone` AS `phone`, `d`.`name` AS `department`, `des`.`title` AS `designation`, concat(`mgr`.`first_name`,' ',`mgr`.`last_name`) AS `manager_name`, `e`.`status` AS `status`, `e`.`date_of_joining` AS `date_of_joining` FROM (((`employees` `e` left join `departments` `d` on(`e`.`department_id` = `d`.`id`)) left join `designations` `des` on(`e`.`designation_id` = `des`.`id`)) left join `employees` `mgr` on(`e`.`reporting_to` = `mgr`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_purchase_summary`
--
DROP TABLE IF EXISTS `vw_purchase_summary`;

DROP VIEW IF EXISTS `vw_purchase_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_purchase_summary`  AS SELECT `pi`.`id` AS `id`, `pi`.`bill_number` AS `bill_number`, `pi`.`bill_date` AS `bill_date`, `s`.`company_name` AS `supplier`, `pi`.`total_amount` AS `total_amount`, `pi`.`paid_amount` AS `paid_amount`, `pi`.`balance_amount` AS `balance_amount`, `pi`.`status` AS `status`, to_days(curdate()) - to_days(`pi`.`due_date`) AS `days_overdue` FROM (`purchase_invoices` `pi` join `suppliers` `s` on(`pi`.`supplier_id` = `s`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_sales_summary`
--
DROP TABLE IF EXISTS `vw_sales_summary`;

DROP VIEW IF EXISTS `vw_sales_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_sales_summary`  AS SELECT `i`.`id` AS `id`, `i`.`invoice_number` AS `invoice_number`, `i`.`invoice_date` AS `invoice_date`, `c`.`company_name` AS `customer`, `i`.`total_amount` AS `total_amount`, `i`.`paid_amount` AS `paid_amount`, `i`.`balance_amount` AS `balance_amount`, `i`.`status` AS `status`, to_days(curdate()) - to_days(`i`.`due_date`) AS `days_overdue` FROM (`invoices` `i` join `customers` `c` on(`i`.`customer_id` = `c`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_stock_summary`
--
DROP TABLE IF EXISTS `vw_stock_summary`;

DROP VIEW IF EXISTS `vw_stock_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_stock_summary`  AS SELECT `p`.`id` AS `id`, `p`.`product_code` AS `product_code`, `p`.`name` AS `product_name`, `pc`.`name` AS `category`, `w`.`name` AS `warehouse`, `sb`.`quantity` AS `quantity`, `sb`.`reserved_quantity` AS `reserved_quantity`, `sb`.`available_quantity` AS `available_quantity`, `p`.`reorder_level` AS `reorder_level`, CASE WHEN `sb`.`available_quantity` <= `p`.`reorder_level` THEN 'Low Stock' ELSE 'In Stock' END AS `stock_status` FROM (((`stock_balance` `sb` join `products` `p` on(`sb`.`product_id` = `p`.`id`)) join `warehouses` `w` on(`sb`.`warehouse_id` = `w`.`id`)) left join `product_categories` `pc` on(`p`.`category_id` = `pc`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_customer_sales`
--
DROP TABLE IF EXISTS `v_customer_sales`;

DROP VIEW IF EXISTS `v_customer_sales`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_customer_sales`  AS SELECT `c`.`id` AS `id`, `c`.`customer_code` AS `customer_code`, `c`.`company_name` AS `company_name`, `c`.`customer_segment` AS `customer_segment`, count(`i`.`id`) AS `total_orders`, sum(`i`.`total_amount`) AS `total_revenue`, sum(`i`.`paid_amount`) AS `total_paid`, sum(`i`.`balance_amount`) AS `outstanding_balance`, max(`i`.`invoice_date`) AS `last_purchase_date`, avg(`i`.`total_amount`) AS `avg_order_value` FROM (`customers` `c` left join `invoices` `i` on(`c`.`id` = `i`.`customer_id` and `i`.`status` <> 'Cancelled')) GROUP BY `c`.`id`, `c`.`customer_code`, `c`.`company_name`, `c`.`customer_segment` ;

-- --------------------------------------------------------

--
-- Structure for view `v_product_sales`
--
DROP TABLE IF EXISTS `v_product_sales`;

DROP VIEW IF EXISTS `v_product_sales`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_product_sales`  AS SELECT `p`.`id` AS `id`, `p`.`product_code` AS `product_code`, `p`.`name` AS `name`, `p`.`category_id` AS `category_id`, sum(`ii`.`quantity`) AS `total_quantity_sold`, sum(`ii`.`quantity` * `ii`.`unit_price`) AS `total_sales`, count(distinct `ii`.`invoice_id`) AS `times_sold` FROM ((`products` `p` left join `invoice_items` `ii` on(`p`.`id` = `ii`.`product_id`)) left join `invoices` `i` on(`ii`.`invoice_id` = `i`.`id` and `i`.`status` <> 'Cancelled')) GROUP BY `p`.`id`, `p`.`product_code`, `p`.`name`, `p`.`category_id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_sales_summary`
--
DROP TABLE IF EXISTS `v_sales_summary`;

DROP VIEW IF EXISTS `v_sales_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_sales_summary`  AS SELECT date_format(`invoices`.`invoice_date`,'%Y-%m') AS `period`, count(0) AS `total_invoices`, sum(`invoices`.`subtotal`) AS `total_sales`, sum(`invoices`.`tax_amount`) AS `total_tax`, sum(`invoices`.`total_amount`) AS `total_revenue`, sum(`invoices`.`paid_amount`) AS `total_collected`, sum(`invoices`.`balance_amount`) AS `total_outstanding` FROM `invoices` WHERE `invoices`.`status` <> 'Cancelled' GROUP BY date_format(`invoices`.`invoice_date`,'%Y-%m') ORDER BY date_format(`invoices`.`invoice_date`,'%Y-%m') DESC ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD CONSTRAINT `bank_accounts_ibfk_1` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_of_accounts` (`id`);

--
-- Constraints for table `bill_of_materials`
--
ALTER TABLE `bill_of_materials`
  ADD CONSTRAINT `bill_of_materials_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `bom_items`
--
ALTER TABLE `bom_items`
  ADD CONSTRAINT `bom_items_ibfk_1` FOREIGN KEY (`bom_id`) REFERENCES `bill_of_materials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bom_items_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD CONSTRAINT `chart_of_accounts_ibfk_1` FOREIGN KEY (`account_type_id`) REFERENCES `account_types` (`id`),
  ADD CONSTRAINT `chart_of_accounts_ibfk_2` FOREIGN KEY (`parent_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_notes`
--
ALTER TABLE `customer_notes`
  ADD CONSTRAINT `customer_notes_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_notes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `departments_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `designations`
--
ALTER TABLE `designations`
  ADD CONSTRAINT `designations_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_ibfk_3` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_ibfk_4` FOREIGN KEY (`reporting_to`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_salary_structure`
--
ALTER TABLE `employee_salary_structure`
  ADD CONSTRAINT `employee_salary_structure_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_salary_structure_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `payroll_components` (`id`);

--
-- Constraints for table `goods_received_notes`
--
ALTER TABLE `goods_received_notes`
  ADD CONSTRAINT `goods_received_notes_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  ADD CONSTRAINT `goods_received_notes_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `goods_received_notes_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `grn_items`
--
ALTER TABLE `grn_items`
  ADD CONSTRAINT `grn_items_ibfk_1` FOREIGN KEY (`grn_id`) REFERENCES `goods_received_notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grn_items_ibfk_2` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`),
  ADD CONSTRAINT `grn_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`),
  ADD CONSTRAINT `journal_entries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD CONSTRAINT `journal_entry_lines_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `journal_entry_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`);

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`source_id`) REFERENCES `lead_sources` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `lead_statuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_applications_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  ADD CONSTRAINT `leave_applications_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `opportunities`
--
ALTER TABLE `opportunities`
  ADD CONSTRAINT `opportunities_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `opportunities_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `opportunities_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments_made`
--
ALTER TABLE `payments_made`
  ADD CONSTRAINT `payments_made_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `payments_made_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments_received`
--
ALTER TABLE `payments_received`
  ADD CONSTRAINT `payments_received_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `payments_received_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD CONSTRAINT `payment_allocations_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments_received` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_allocations_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_made_allocations`
--
ALTER TABLE `payment_made_allocations`
  ADD CONSTRAINT `payment_made_allocations_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments_made` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_made_allocations_ibfk_2` FOREIGN KEY (`bill_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_reminders`
--
ALTER TABLE `payment_reminders`
  ADD CONSTRAINT `payment_reminders_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_details`
--
ALTER TABLE `payroll_details`
  ADD CONSTRAINT `payroll_details_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_details_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `payroll_components` (`id`);

--
-- Constraints for table `production_entries`
--
ALTER TABLE `production_entries`
  ADD CONSTRAINT `production_entries_ibfk_1` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`),
  ADD CONSTRAINT `production_entries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`uom_id`) REFERENCES `units_of_measure` (`id`);

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD CONSTRAINT `purchase_invoices_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_invoices_ibfk_2` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_invoices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  ADD CONSTRAINT `purchase_invoice_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `quotations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quotation_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `sales_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `sales_orders_ibfk_2` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD CONSTRAINT `sales_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `sales_targets`
--
ALTER TABLE `sales_targets`
  ADD CONSTRAINT `sales_targets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_balance`
--
ALTER TABLE `stock_balance`
  ADD CONSTRAINT `stock_balance_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_balance_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `stock_transactions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_addresses`
--
ALTER TABLE `supplier_addresses`
  ADD CONSTRAINT `supplier_addresses_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_module_access`
--
ALTER TABLE `user_module_access`
  ADD CONSTRAINT `user_module_access_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD CONSTRAINT `warehouses_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `work_orders`
--
ALTER TABLE `work_orders`
  ADD CONSTRAINT `work_orders_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `work_orders_ibfk_2` FOREIGN KEY (`bom_id`) REFERENCES `bill_of_materials` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `work_orders_ibfk_3` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `work_orders_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
