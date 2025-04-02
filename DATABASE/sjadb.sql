-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.32-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for sjadb
CREATE DATABASE IF NOT EXISTS `sjadb` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `sjadb`;

-- Dumping structure for table sjadb.activity_log
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table sjadb.activity_log: ~0 rows (approximately)

-- Dumping structure for table sjadb.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table sjadb.notifications: ~0 rows (approximately)

-- Dumping structure for table sjadb.settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table sjadb.settings: ~1 rows (approximately)
REPLACE INTO `settings` (`id`, `status`) VALUES
	(1, 'open');

-- Dumping structure for table sjadb.stud_tbl
CREATE TABLE IF NOT EXISTS `stud_tbl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `attendance` enum('present','absent') NOT NULL DEFAULT 'absent',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table sjadb.stud_tbl: ~5 rows (approximately)
REPLACE INTO `stud_tbl` (`id`, `first_name`, `last_name`, `section`, `grade_level`, `username`, `password`, `attendance`) VALUES
	(1, 'John', 'Doe', 'Venus', 'Grade 7', 'Z01', 'mote', 'absent'),
	(2, 'Jane', 'Doe', 'Venus', 'Grade 7', 'Z02', 'mote', 'absent'),
	(3, 'Ivan', 'Dela Cruz', 'Venus', 'Grade 7', 'Z03', 'mote', 'absent'),
	(4, 'May Anne', 'Oranto', 'Venus', 'Grade 7', 'Z04', 'mote', 'absent'),
	(5, 'Mary Anne', 'Villa', 'Venus', 'Grade 7', 'Z05', 'mote', 'absent');

-- Dumping structure for table sjadb.teach_user
CREATE TABLE IF NOT EXISTS `teach_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table sjadb.teach_user: ~2 rows (approximately)
REPLACE INTO `teach_user` (`id`, `username`, `password`, `created_at`, `role`, `first_name`, `last_name`, `grade_level`, `section`) VALUES
	(1, 'admin', 'SJA2025', '2025-02-12 06:37:14', 'admin', 'Admin', 'Moderator', 'Admin', 'Admin'),
	(2, 'T01', 'mote', '2025-02-12 22:15:51', 'teacher', 'Jana', 'Doe', 'Grade 7', 'Venus');
  (3, 'T02', 'mote', '2025-02-12 23:15:51', 'student', 'Jon', 'Doe', 'Grade 7', 'Venus');


-- Dumping structure for table sjadb.violations
CREATE TABLE IF NOT EXISTS `violations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `violation_description` text NOT NULL,
  `violation_date` date NOT NULL DEFAULT curdate(),
  `violation_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  CONSTRAINT `fk_username` FOREIGN KEY (`username`) REFERENCES `stud_tbl` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table sjadb.violations: ~1 rows (approximately)
REPLACE INTO `violations` (`id`, `username`, `violation_description`, `violation_date`, `violation_time`) VALUES
	(1, 'z01', 'None', '2025-03-05', '12:53:00');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
