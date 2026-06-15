-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 22, 2026 at 09:17 PM
-- Server version: 8.0.45-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `contacts_db`
--

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(5, 'admin', '$2y$10$2EtoskmMDIEl6ZdwnX5PHe7C9iB6M.A7v6kFFxcXCD.8CoduRquda'),
(7, 'admin2', '$2y$10$mbus9M.yALBBvx6GJzQgEu45qg2r3ADqn2DJloERStvvR..pghCEK');

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `target_table`, `target_id`, `created_at`) VALUES
(1, 5, 'update', 'admins', 5, '2026-05-22 13:32:15'),
(2, 5, 'update', 'admins', 5, '2026-05-22 13:32:49'),
(3, 5, 'create', 'admins', 6, '2026-05-22 13:48:18'),
(4, 5, 'delete', 'admins', 6, '2026-05-22 14:17:00'),
(5, 5, 'delete', 'contacts', 3, '2026-05-22 15:04:35'),
(6, 5, 'insert', 'admins', 7, '2026-05-22 15:23:35');

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `country_code`, `phone`, `image_path`) VALUES
(4, 'Ana Ferreira', 'ana.ferreira@example.com', '49', '15123456789', '284756.png'),
(5, 'Tiago Rodrigues', 'tiago.rodrigues@example.com', '351', '916578902', '918273.png'),
(6, 'Inês Almeida', 'ines.almeida@example.com', '351', '917689013', '465829.png'),
(7, 'Rafael Azevedo', 'rafaelazevedo@uab.pt', '351', '933345678', '731945.png'),
(8, 'Sofia Gomes', 'sofia.gomes@example.com', '351', '919801235', '256781.png'),
(9, 'Carlos Pinto', 'carlos.pinto@example.com', '351', '920912346', '684920.png'),
(10, 'Leonor Dias', 'leonor.dias@example.com', '351', '921023457', '147382.png'),
(11, 'Miguel Lopes', 'miguel.lopes@example.com', '351', '922134568', '593174.png'),
(12, 'Beatriz Neves', 'beatriz.neves@example.com', '351', '923245679', '820561.png');

--
-- Dumping data for table `contact_socials`
--

INSERT INTO `contact_socials` (`id`, `contact_id`, `social_id`, `value`) VALUES
(10, 4, 1, '/in/ana/'),
(12, 4, 3, '/x'),
(13, 5, 1, '@linkedin'),
(15, 5, 3, '@twitter'),
(16, 6, 1, '@linkedin'),
(18, 6, 3, '@twitter'),
(19, 7, 1, '/in/rafael-azevedo-84ab5434a/'),
(22, 8, 1, '@linkedin'),
(24, 8, 3, '@twitter'),
(28, 10, 1, '@linkedin'),
(30, 10, 3, '@twitter'),
(41, 10, 4, '@leonor'),
(31, 11, 1, '@linkedin'),
(33, 11, 3, '@twitter'),
(40, 11, 4, '@instagram'),
(34, 12, 1, '@linkedin'),
(36, 12, 3, '@twitter'),
(39, 12, 4, '@instagram');

--
-- Dumping data for table `socials`
--

INSERT INTO `socials` (`id`, `name`) VALUES
(4, 'Instagram'),
(1, 'LinkedIn'),
(3, 'Twitter');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
