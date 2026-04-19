-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2026 at 05:31 PM
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
-- Database: `fintech`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_day` int(11) NOT NULL,
  `frequency` varchar(20) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `budget_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'UNPAID',
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget`
--

CREATE TABLE `budget` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `spent_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget`
--

INSERT INTO `budget` (`id`, `name`, `amount`, `start_date`, `end_date`, `user_id`, `category`, `spent_amount`, `created_at`) VALUES
(1, 'etude', 1500.00, '2026-03-02', '2026-03-18', 3, 'Education', 1000.00, '2026-03-03 23:10:38'),
(2, 'az', 65.00, '2026-03-30', '2026-03-31', 3, 'Housing', 0.00, '2026-03-03 23:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `complaint`
--

CREATE TABLE `complaint` (
  `id` int(11) NOT NULL,
  `subject` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `complaint_date` date NOT NULL,
  `response` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaint`
--

INSERT INTO `complaint` (`id`, `subject`, `status`, `complaint_date`, `response`, `user_id`, `created_at`) VALUES
(1, 'azjdhh', 'pending', '2026-03-03', 'jazhdqskjbds', 3, '2026-03-03 17:45:10'),
(2, 'retard', 'pending', '2026-03-05', 'retard', 1, '2026-03-05 02:56:32');

-- --------------------------------------------------------

--
-- Table structure for table `contract_request`
--

CREATE TABLE `contract_request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `calculated_premium` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `created_at` datetime DEFAULT current_timestamp(),
  `boldsign_document_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contract_request`
--

INSERT INTO `contract_request` (`id`, `user_id`, `asset_id`, `package_id`, `calculated_premium`, `status`, `created_at`, `boldsign_document_id`) VALUES
(1, 1, 2, 4, 456009.90, 'APPROVED', '2026-03-02 02:22:26', NULL),
(2, 3, 3, 1, 29452.80, 'APPROVED', '2026-03-02 02:51:13', NULL),
(3, 3, 4, 3, 6252.80, 'APPROVED', '2026-03-02 03:01:34', '5d72790a-c08f-47f0-ab3c-7b8015ad79b7'),
(4, 3, 5, 1, 23944.80, 'APPROVED', '2026-03-02 04:13:14', 'aa1b9125-ba34-4dcb-93cb-a8f68c4ea340'),
(5, 3, 6, 6, 893941.44, 'SIGNED', '2026-03-02 04:21:01', '3f9ac531-e678-4b41-b975-86592f21dd11'),
(6, 3, 7, 6, 84000.00, 'APPROVED', '2026-03-02 04:30:42', 'cc66a3c9-9253-40d7-9e91-91d6f0781027'),
(7, 3, 3, 2, 166899.20, 'APPROVED', '2026-03-02 04:36:15', 'bbf86944-c7fe-427a-8ee2-26877f6e9ef0'),
(8, 3, 3, 3, 78540.80, 'APPROVED', '2026-03-02 05:10:08', '26a8c29e-acf2-44db-8aae-7a232c93627a'),
(9, 3, 3, 2, 166899.20, 'APPROVED', '2026-03-02 05:34:06', '02a94cf0-6793-4e5d-82d5-462b0c22a629'),
(10, 3, 4, 2, 13287.20, 'APPROVED', '2026-03-02 05:39:02', '4251714f-107b-438c-8177-f58091e77154'),
(11, 3, 4, 2, 13287.20, 'APPROVED', '2026-03-02 05:45:21', 'efe8caea-403b-4a2d-a0e3-f7a3ea182d06'),
(12, 3, 3, 1, 29452.80, 'APPROVED', '2026-03-02 23:24:51', '19672e4c-d63d-42c2-95b6-3ed10d8d5301'),
(13, 3, 3, 3, 78540.80, 'APPROVED', '2026-03-02 23:28:45', 'd7d39bed-d2ac-4a9d-9e77-0c00f4d6c348'),
(14, 3, 4, 3, 6252.80, 'APPROVED', '2026-03-02 23:31:20', 'fae19a3e-bba8-4ae5-94eb-e1ca26a34996'),
(15, 3, 5, 2, 135687.20, 'SIGNED', '2026-03-02 23:34:20', '68642979-3aec-4fb1-a3f9-dbf35a1af7f5'),
(16, 3, 4, 2, 13287.20, 'SIGNED', '2026-03-03 02:29:42', '8d9c337e-eff7-4770-b546-a3a9c21ae5c4'),
(17, 3, 5, 6, 89393.92, 'SIGNED', '2026-03-03 02:48:01', 'e5bac214-860b-4956-8f31-97894db6f0b9'),
(18, 3, 4, 4, 4689.60, 'SIGNED', '2026-03-03 03:07:23', '606d5b08-0d04-4a3b-acf8-0a0d037973eb'),
(19, 3, 5, 3, 63852.80, 'APPROVED', '2026-03-03 18:40:52', '98b98038-9c8a-472d-a595-67e7b05f7fe4'),
(20, 3, 3, 1, 29452.80, 'APPROVED', '2026-03-03 22:34:44', 'a5741d15-8772-408b-b909-7ac2a77606f0'),
(21, 1, 2, 1, 228004.95, 'APPROVED', '2026-03-05 12:11:40', '203089ba-e442-4b35-a9b8-c915fe4c9f4e'),
(22, 3, 3, 2, 166899.20, 'SIGNED', '2026-03-05 12:15:25', 'a451f53b-e29c-4cdd-a0a7-f8b1c1696985'),
(23, 3, 3, 1, 29452.80, 'APPROVED', '2026-03-05 13:19:35', '20f36660-2653-44fb-8c1c-59d7749b83d0'),
(24, 3, 4, 2, 13287.20, 'SIGNED', '2026-03-05 13:23:26', '9116647b-17d3-46cd-819c-14094f3dcad8');

-- --------------------------------------------------------

--
-- Table structure for table `expense`
--

CREATE TABLE `expense` (
  `id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `budget_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense`
--

INSERT INTO `expense` (`id`, `amount`, `category`, `expense_date`, `description`, `budget_id`, `created_at`) VALUES
(1, 1000.00, 'Food', '2026-03-10', 'dinners', 1, '2026-03-02 15:03:54');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_package`
--

CREATE TABLE `insurance_package` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `asset_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `coverage_details` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `risk_multiplier` decimal(5,2) NOT NULL DEFAULT 1.00,
  `duration_months` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insurance_package`
--

INSERT INTO `insurance_package` (`id`, `name`, `asset_type`, `description`, `coverage_details`, `base_price`, `risk_multiplier`, `duration_months`, `is_active`, `created_at`) VALUES
(1, 'Professional Car Cover', 'car', 'Comprehensive insurance for professional drivers and company vehicles.', 'Covers accidents, theft, fire, and third-party liability up to 300,000 TND.', 1000.00, 1.50, 12, 1, '2026-03-02 02:20:43'),
(2, 'Fleet Premium Cover', 'car', 'Insurance designed for corporate fleets with full coverage and roadside assistance.', 'Accidents, theft, fire, natural disasters, and roadside support included for up to 20 vehicles.', 5000.00, 1.70, 12, 1, '2026-03-02 02:20:43'),
(3, 'Executive Car Protection', 'car', 'Premium protection plan for high-value company cars and executive vehicles.', 'Covers comprehensive damages, theft, fire, natural disasters, and personal liability up to 500,000 TND.', 2500.00, 1.60, 12, 1, '2026-03-02 02:20:43'),
(4, 'Professional Home Cover', 'home', 'Comprehensive insurance for professional or high-value residential properties.', 'Covers fire, theft, flood, natural disasters, and third-party liability up to 500,000 TND.', 2000.00, 1.50, 12, 1, '2026-03-02 02:21:00'),
(5, 'Corporate Property Protection', 'home', 'Insurance designed for company-owned residential or rental properties with full coverage.', 'Covers fire, theft, natural disasters, liability, and property damage up to 1,000,000 TND.', 5000.00, 1.70, 12, 1, '2026-03-02 02:21:00'),
(6, 'Executive Residence Plan', 'home', 'Premium plan for executive or luxury residential homes with extensive protection.', 'Covers all-risk damages including fire, flood, theft, earthquake, liability, and temporary relocation costs up to 750,000 TND.', 3500.00, 1.60, 12, 1, '2026-03-02 02:21:00');

-- --------------------------------------------------------

--
-- Table structure for table `insured_asset`
--

CREATE TABLE `insured_asset` (
  `id` int(11) NOT NULL,
  `reference` varchar(150) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `location` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `declared_value` decimal(12,2) NOT NULL,
  `approved_value` decimal(12,2) DEFAULT NULL,
  `manufacture_date` date NOT NULL,
  `brand` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insured_asset`
--

INSERT INTO `insured_asset` (`id`, `reference`, `type`, `description`, `created_at`, `location`, `user_id`, `declared_value`, `approved_value`, `manufacture_date`, `brand`) VALUES
(1, '199tun5621', 'car', 'good', '2026-03-02 02:17:39', 'Mahdia - Chebba', 1, 19000.00, NULL, '2026-03-23', NULL),
(2, '653314321354', 'home', 'big house', '2026-03-02 02:22:10', 'Ben Arous - Rades', 1, 1520033.00, NULL, '2026-03-25', NULL),
(3, '155tun999', 'car', 'luxury', '2026-03-02 02:51:01', 'Mahdia - Mahdia Ville', 3, 196352.00, NULL, '2026-03-24', NULL),
(4, '152tun5632', 'car', 'good', '2026-03-02 03:01:23', 'Beja - Medjez El Bab', 3, 15632.00, NULL, '2026-03-30', NULL),
(5, '149tun566', 'car', 'luxury black car', '2026-03-02 04:13:01', 'Gabes - Ghannouch', 3, 159632.00, NULL, '2026-03-31', NULL),
(6, '6455321', 'home', 'big', '2026-03-02 04:20:45', 'Beja - Beja Ville', 3, 1596324.00, NULL, '2026-03-15', NULL),
(7, '98650009', 'home', 'small', '2026-03-02 04:30:22', 'Ben Arous - Hammam Lif', 3, 150000.00, NULL, '2026-03-17', NULL),
(8, '154tun1569', 'car', 'black nice car', '2026-03-05 13:17:27', 'Sidi Bouzid - Sidi Bouzid Ville', 3, 150000.00, NULL, '2026-03-09', 'audi a4');

-- --------------------------------------------------------

--
-- Table structure for table `insured_contract`
--

CREATE TABLE `insured_contract` (
  `id` int(11) NOT NULL,
  `asset_ref` varchar(150) NOT NULL,
  `boldsign_document_id` varchar(255) NOT NULL,
  `status` enum('NOT_SIGNED','SIGNED','REJECTED') NOT NULL DEFAULT 'NOT_SIGNED',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `signed_at` timestamp NULL DEFAULT NULL,
  `local_file_path` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insured_contract`
--

INSERT INTO `insured_contract` (`id`, `asset_ref`, `boldsign_document_id`, `status`, `created_at`, `signed_at`, `local_file_path`) VALUES
(1, '152tun5632', '8d9c337e-eff7-4770-b546-a3a9c21ae5c4', 'SIGNED', '2026-03-03 00:30:57', '2026-03-03 00:30:57', NULL),
(2, '149tun566', 'e5bac214-860b-4956-8f31-97894db6f0b9', 'SIGNED', '2026-03-03 00:49:22', '2026-03-03 00:49:22', NULL),
(3, '152tun5632', '606d5b08-0d04-4a3b-acf8-0a0d037973eb', 'SIGNED', '2026-03-03 01:08:14', '2026-03-03 01:08:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loan`
--

CREATE TABLE `loan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan`
--

INSERT INTO `loan` (`id`, `user_id`, `amount`, `interest_rate`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 3, 10000.00, 1.50, '2026-03-02', '2026-03-16', 'active', '2026-03-02 15:13:38'),
(2, 1, 1000.00, 1.60, '2026-03-09', '2028-03-09', 'active', '2026-03-04 04:26:00'),
(3, 3, 1000.00, 1.50, '2026-03-01', '2027-03-25', 'active', '2026-03-05 13:41:28');

-- --------------------------------------------------------

--
-- Table structure for table `repayment`
--

CREATE TABLE `repayment` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_type` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `monthly_payment` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repayment`
--

INSERT INTO `repayment` (`id`, `loan_id`, `amount`, `payment_date`, `payment_type`, `status`, `monthly_payment`) VALUES
(1, 2, 120.00, '2026-03-04', 'early', 'paid', 42.36),
(2, 2, 190.00, '2026-03-04', 'monthly', 'late', 42.36),
(3, 2, 586.00, '2026-03-04', 'early', 'late', 42.36),
(4, 1, 150.00, '2026-03-05', 'monthly', 'late', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `permissions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`id`, `role_name`, `permissions`) VALUES
(1, 'admin', NULL),
(2, 'user', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `reference_type` varchar(30) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'TND'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`id`, `user_id`, `amount`, `type`, `status`, `description`, `created_at`, `reference_type`, `reference_id`, `currency`) VALUES
(1, 3, 500.00, 'DEBIT', 'FAILED', 'electromenager', '2026-03-02 15:25:48', 'LOAN', NULL, 'TND'),
(2, 3, 500.00, 'DEBIT', 'FAILED', 'electromenager', '2026-03-02 15:26:07', 'LOAN', NULL, 'TND'),
(3, 3, 500.00, 'DEBIT', 'FAILED', 'electromenager', '2026-03-02 15:28:01', 'LOAN', NULL, 'TND'),
(4, 3, 500.00, 'DEBIT', 'FAILED', 'electromenager', '2026-03-02 15:31:17', 'LOAN', NULL, 'TND'),
(5, 3, 250.00, 'CREDIT', 'FAILED', 'TV', '2026-03-02 15:44:59', 'CONTRACT', NULL, 'TND'),
(6, 3, 1000.00, 'DEBIT', 'FAILED', 'Payment via Paymee', '2026-03-02 15:51:35', 'LOAN', NULL, 'TND'),
(7, 3, 1000.00, 'DEBIT', 'FAILED', 'Payment via Paymee', '2026-03-02 15:52:35', 'LOAN', NULL, 'TND'),
(8, 3, 200.00, 'CREDIT', 'FAILED', 'uazd', '2026-03-03 15:51:39', 'LOAN', NULL, 'TND'),
(9, 3, 2000.00, 'CREDIT', 'FAILED', 'srfgefdsfd', '2026-03-03 15:56:46', 'CONTRACT', NULL, 'TND'),
(10, 3, 1000.00, 'DEBIT', 'FAILED', 'qsqsd', '2026-03-03 17:45:54', 'LOAN', NULL, 'USD'),
(11, 3, 13625.00, 'DEBIT', 'FAILED', 'ergh', '2026-03-03 22:32:07', 'CONTRACT', NULL, 'TND'),
(12, 1, 31321.00, 'DEBIT', 'FAILED', 'zef', '2026-03-05 02:50:03', 'LOAN', NULL, 'USD'),
(13, 1, 31321.00, 'DEBIT', 'FAILED', 'zef', '2026-03-05 02:50:42', 'LOAN', NULL, 'USD'),
(14, 1, 31321.00, 'DEBIT', 'FAILED', 'zef', '2026-03-05 02:51:56', 'LOAN', NULL, 'USD'),
(15, 1, 56.00, 'DEBIT', 'FAILED', 'dnj', '2026-03-05 02:55:46', 'LOAN', NULL, 'TND'),
(16, 3, 1000.00, 'DEBIT', 'FAILED', 'pay contract', '2026-03-05 13:31:30', 'CONTRACT', NULL, 'TND');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 2,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `phone` varchar(30) DEFAULT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `google_account` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `face_registered` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `email`, `password_hash`, `role_id`, `created_at`, `updated_at`, `is_verified`, `phone`, `verification_code`, `google_account`, `last_login`, `face_registered`) VALUES
(1, 'mohamed tlili', 'admin@gmail.com', '1234', 1, '2026-03-02 01:52:46', '2026-03-05 13:10:41', 1, '22222222', NULL, 0, '2026-03-05 13:10:41', 0),
(2, 'admin', 'yasmine912003@gmail.com', '1234', 1, '2026-03-02 01:53:32', '2026-03-05 13:09:18', 0, '99999999', NULL, 0, '2026-03-05 13:09:18', 0),
(3, 'mohamed wassim tlili', 'mohamedwassim.tlili@gmail.com', '937377f056160fc4b15e0b770c67136a5f03c15205b4d3bf918268fefa2c6d0a', 2, '2026-03-02 02:50:29', '2026-03-05 13:48:47', 0, '51 112 994', '525893', 0, '2026-03-05 13:23:14', 0),
(4, 'yasmine', 'comitedesclubsesb@gmail.com', '91b4d142823f7d20c5f08df69122de43f35f057a988d9619f6d3138485c9a203', 1, '2026-03-03 23:22:10', '2026-03-04 02:21:01', 1, '22222222', NULL, 0, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget`
--
ALTER TABLE `budget`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `complaint`
--
ALTER TABLE `complaint`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contract_request`
--
ALTER TABLE `contract_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `expense`
--
ALTER TABLE `expense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `budget_id` (`budget_id`);

--
-- Indexes for table `insurance_package`
--
ALTER TABLE `insurance_package`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `insured_asset`
--
ALTER TABLE `insured_asset`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_reference` (`reference`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `insured_contract`
--
ALTER TABLE `insured_contract`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_document_id` (`boldsign_document_id`),
  ADD KEY `idx_asset_ref` (`asset_ref`);

--
-- Indexes for table `loan`
--
ALTER TABLE `loan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `repayment`
--
ALTER TABLE `repayment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget`
--
ALTER TABLE `budget`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `complaint`
--
ALTER TABLE `complaint`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contract_request`
--
ALTER TABLE `contract_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `expense`
--
ALTER TABLE `expense`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `insurance_package`
--
ALTER TABLE `insurance_package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `insured_asset`
--
ALTER TABLE `insured_asset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `insured_contract`
--
ALTER TABLE `insured_contract`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `loan`
--
ALTER TABLE `loan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `repayment`
--
ALTER TABLE `repayment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget`
--
ALTER TABLE `budget`
  ADD CONSTRAINT `budget_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `complaint`
--
ALTER TABLE `complaint`
  ADD CONSTRAINT `complaint_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contract_request`
--
ALTER TABLE `contract_request`
  ADD CONSTRAINT `contract_request_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `insured_asset` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contract_request_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `insurance_package` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expense`
--
ALTER TABLE `expense`
  ADD CONSTRAINT `expense_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budget` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `insured_asset`
--
ALTER TABLE `insured_asset`
  ADD CONSTRAINT `insured_asset_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `insured_contract`
--
ALTER TABLE `insured_contract`
  ADD CONSTRAINT `fk_asset` FOREIGN KEY (`asset_ref`) REFERENCES `insured_asset` (`reference`) ON DELETE CASCADE;

--
-- Constraints for table `loan`
--
ALTER TABLE `loan`
  ADD CONSTRAINT `loan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `repayment`
--
ALTER TABLE `repayment`
  ADD CONSTRAINT `repayment_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
