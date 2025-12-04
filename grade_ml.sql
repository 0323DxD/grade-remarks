-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 02:42 PM
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
-- Database: `grade_ml`
--

-- --------------------------------------------------------

--
-- Table structure for table `predictions`
--

CREATE TABLE `predictions` (
  `id` int(11) NOT NULL,
  `numeric_grade` float NOT NULL,
  `attendance_pct` float NOT NULL,
  `assignment_avg` float NOT NULL,
  `exam_score` float NOT NULL,
  `predicted_remark` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `predictions`
--

INSERT INTO `predictions` (`id`, `numeric_grade`, `attendance_pct`, `assignment_avg`, `exam_score`, `predicted_remark`, `created_at`) VALUES
(1, 34, 78, 43, 79, 'Needs Improvement', '2025-12-01 12:07:33'),
(3, 34, 78, 43, 79, 'Needs Improvement', '2025-12-01 12:15:50'),
(4, 89, 100, 98, 89, 'Excellent', '2025-12-01 12:50:39'),
(6, 90, 87, 89, 67, 'Very Good', '2025-12-01 12:58:01'),
(7, 56, 89, 78, 98, 'Very Good', '2025-12-01 12:58:27'),
(9, 76, 87, 44, 67, 'Satisfactory', '2025-12-01 12:59:10'),
(10, 45, 34, 65, 88, 'Needs Improvement', '2025-12-01 13:06:16'),
(11, 65, 87, 44, 89, 'Satisfactory', '2025-12-01 13:06:31');

-- --------------------------------------------------------

--
-- Table structure for table `training_data`
--

CREATE TABLE `training_data` (
  `id` int(11) NOT NULL,
  `numeric_grade` float NOT NULL,
  `attendance_pct` float NOT NULL,
  `assignment_avg` float NOT NULL,
  `exam_score` float NOT NULL,
  `remark` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_data`
--

INSERT INTO `training_data` (`id`, `numeric_grade`, `attendance_pct`, `assignment_avg`, `exam_score`, `remark`) VALUES
(1, 98, 98, 97, 99, 'Excellent'),
(2, 95, 96, 94, 96, 'Excellent'),
(3, 92, 95, 91, 93, 'Excellent'),
(4, 88, 90, 85, 89, 'Very Good'),
(5, 85, 88, 82, 86, 'Very Good'),
(6, 82, 85, 80, 82, 'Very Good'),
(7, 78, 80, 77, 76, 'Satisfactory'),
(8, 75, 78, 74, 73, 'Satisfactory'),
(9, 72, 75, 70, 71, 'Satisfactory'),
(10, 68, 70, 65, 66, 'Needs Improvement'),
(11, 62, 65, 60, 61, 'Needs Improvement'),
(12, 55, 60, 50, 56, 'Needs Improvement');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `predictions`
--
ALTER TABLE `predictions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `training_data`
--
ALTER TABLE `training_data`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `predictions`
--
ALTER TABLE `predictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `training_data`
--
ALTER TABLE `training_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
