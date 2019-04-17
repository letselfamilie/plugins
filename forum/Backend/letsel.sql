-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 26, 2019 at 01:12 PM
-- Server version: 8.0.14
-- PHP Version: 7.3.2RC1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `letsel`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `cat_name` char(50) NOT NULL,
  PRIMARY KEY (`cat_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`cat_name`) VALUES
('domestic violence'),
('natural disasters'),
('robbery'),
('traffic accident');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
CREATE TABLE IF NOT EXISTS `favorites` (
  `user_id` mediumint(8) UNSIGNED NOT NULL,
  `topic_id` mediumint(8) UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`,`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`user_id`, `topic_id`) VALUES
(1, 1),
(1, 2),
(2, 3),
(3, 2),
(4, 1),
(5, 4);

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

DROP TABLE IF EXISTS `likes`;
CREATE TABLE IF NOT EXISTS `likes` (
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` mediumint(8) UNSIGNED NOT NULL,
  PRIMARY KEY (`post_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`post_id`, `user_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 3),
(3, 3),
(5, 2),
(5, 4),
(6, 4);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `post_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `response_to` int(10) UNSIGNED DEFAULT NULL,
  `topic_id` mediumint(8) UNSIGNED NOT NULL,
  `user_id` mediumint(8) UNSIGNED NOT NULL,
  `post_message` text NOT NULL,
  `is_anonym` bit(1) NOT NULL,
  `create_timestamp` timestamp NOT NULL,
  PRIMARY KEY (`post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `response_to`, `topic_id`, `user_id`, `post_message`, `is_anonym`, `create_timestamp`) VALUES
(1, 3, 1, 1, 'Really sorry man', b'0', '2019-03-24 13:02:54'),
(2, NULL, 1, 2, 'Car owners are required by law to carry liability insurance that covers personal injuries and property damage in the event of a car accident. There are four categories of car insurance: tort liability, no-fault, choice no-fault, and add-on.', b'0', '2019-01-10 12:19:40'),
(3, 4, 2, 3, 'The first place to look for liability is one or more of the drivers involved in a crash. A driver whose unsafe actions caused the accident can be held liable under a theory of ordinary negligence.', b'1', '2019-03-05 15:12:28'),
(4, 1, 4, 4, 'Resilience is about reducing the impact of flooding, should water get inside your property. The aim is to ensure that damage is minimised and you can get back in to your home or business as quickly as possible.', b'0', '2019-03-17 16:35:54'),
(5, 1, 3, 4, 'You should apply to embassy of your country', b'0', '2019-02-09 11:20:02'),
(6, NULL, 4, 5, 'Older properties (listed & non-listed) often need different approaches to ensure the integrity of the build. Many everyday practices can be damaging and create historic problems for the futures of such buildings.', b'1', '2019-01-05 07:07:09');

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

DROP TABLE IF EXISTS `topics`;
CREATE TABLE IF NOT EXISTS `topics` (
  `topic_id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `topic_name` char(100) NOT NULL,
  `cat_name` char(50) NOT NULL,
  `user_id` mediumint(8) UNSIGNED NOT NULL,
  `is_anonym` bit(1) NOT NULL,
  `create_timestamp` timestamp NOT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `topics`
--

INSERT INTO `topics` (`topic_id`, `topic_name`, `cat_name`, `user_id`, `is_anonym`, `create_timestamp`) VALUES
(1, 'What if my car is hit by an uninsured driver?', 'traffic accident', 1, b'1', '2019-01-01 16:35:54'),
(2, 'Whom to Sue After a Car Accident', 'traffic accident', 2, b'0', '2019-01-18 18:00:23'),
(3, 'Tourist have been robbed by men he met in pub', 'robbery', 3, b'1', '2018-06-10 12:24:10'),
(4, 'Protecting your property during flood', 'natural disasters', 4, b'1', '2018-12-05 08:37:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `login` char(30) NOT NULL,
  `first_name` char(30) NOT NULL,
  `surname` char(30) NOT NULL,
  `password` char(32) NOT NULL,
  `email` char(50) NOT NULL,
  `photo` varchar(100) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `login`, `first_name`, `surname`, `password`, `email`, `photo`) VALUES
(1, 'JulieTKaufman', 'Julie', 'Kaufman', 'yail4mi2Qu', 'bertram1981@gmail.com', '/Backend/user_photos/53.jpg'),
(2, 'IkeJNunez', 'Ike', 'Nunez', 'he1Zaij2', 'esta1997@yahoo.com', '/Backend/user_photos/80.jpg'),
(3, 'IronNuge', 'Donald', 'Johnson', 'meeriz7Sah', 'gerardo.jenki@gmail.com', '/Backend/user_photos/39.jpg'),
(4, 'MrsHudson22121', 'Judy', 'Markham', 'Sie0aez3sek', 'laurel_pour@hotmail.com', '/Backend/user_photos/50.jpg'),
(5, 'melany', 'Randy', 'Stallworth', 'dooWaph8ich', 'weldon1971@yahoo.com', '/Backend/user_photos/38.jpg');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
