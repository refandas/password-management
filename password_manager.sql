-- phpMyAdmin SQL Dump
-- version 4.8.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 20, 2019 at 11:28 AM
-- Server version: 10.1.37-MariaDB
-- PHP Version: 7.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `password_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_list`
--

CREATE TABLE `access_list` (
  `access_level` tinyint(3) UNSIGNED NOT NULL,
  `id_feature` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `access_list`
--

INSERT INTO `access_list` (`access_level`, `id_feature`) VALUES
(1, 1),
(2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `access_rights`
--

CREATE TABLE `access_rights` (
  `access_level` tinyint(3) UNSIGNED NOT NULL,
  `status_access` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `access_rights`
--

INSERT INTO `access_rights` (`access_level`, `status_access`) VALUES
(1, 'admin'),
(2, 'user');

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `id_account` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `username_email` varchar(256) NOT NULL,
  `password` varchar(256) NOT NULL,
  `id_media` int(11) NOT NULL,
  `key_str` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`id_account`, `id_user`, `username_email`, `password`, `id_media`, `key_str`) VALUES
(7, 3, 'f7a6fc6b68ab44b88e72f47dbd30b4b77a40765de09edb84cb9bda979d4a89c2ee7af5dac9f2ed0954f547c4ee258455dd5740ca5fb732a01b8b00de6c83e1a4nNJP6k38Nq1Ql969rAgueK0Zu22i', '198bfdb653841a46eb2a3329213cf30f7557854fca28243d8233cec2f086892c06faa83fd4ad4e630a6dc972fd5310e6eb17ca836dc5ceb0a09ac30cdf83e5560MSqTaDJTr8fwzp2kgaQiUJtGA==', 12, 'ef1e4ac80480382390755a8858d9d8c2f8c0062ecb41e82da98a982215edbd79'),
(8, 3, 'fbafdef3948f593200b26765b692d676e89f1b97d03c7c0bb2c2d06101320cb2592f2ca10a82ea361d0a7fe9a7f02bf94c3ff19feed8d6ba94c88872b3fe6167RPeRtAzGlIvuYpkxYd2mJdC9GEXWUw==', 'd20afd8b1c16a920da289fd4a5911d443bf8fbd7ab030c7fe668df16bb40fca50dd6b774d87f4cdd9be65fcdd2e4190899e8f6dff4679d5ffc329e19104dc6c8inJ02M8g2Vo7y2NlJwAVVhjbrmO8iQ==', 1, '2d3e9a9190f8f9d9ee52b8c2e1329ae670d6b0a9af00de6d8e275f91295009bb');

-- --------------------------------------------------------

--
-- Table structure for table `account_media`
--

CREATE TABLE `account_media` (
  `id_media` int(11) NOT NULL,
  `logo` varchar(256) DEFAULT NULL,
  `name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `account_media`
--

INSERT INTO `account_media` (`id_media`, `logo`, `name`) VALUES
(1, 'fab fa-instagram', 'Instagram'),
(2, 'fab fa-twitter', 'Twitter'),
(3, 'fab fa-facebook-f', 'Facebook'),
(4, 'fab fa-google', 'Google'),
(5, 'fab fa-medium', 'Medium'),
(6, 'fab fa-quora', 'Quora'),
(7, 'fab fa-trello', 'Trello'),
(8, 'fab fa-line', 'Line'),
(9, 'fab fa-cc-visa', 'Visa'),
(10, 'fab fa-cc-mastercard', 'Mastercard'),
(11, 'fab fa-cc-amex', 'American Express'),
(12, 'fab fa-amazon', 'Amazon'),
(13, 'fab fa-apple', 'Apple'),
(14, 'fab fa-steam', 'Steam');

-- --------------------------------------------------------

--
-- Table structure for table `feature`
--

CREATE TABLE `feature` (
  `id_feature` tinyint(3) UNSIGNED NOT NULL,
  `feature_name` varchar(32) NOT NULL,
  `url` varchar(64) DEFAULT '#'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `feature`
--

INSERT INTO `feature` (`id_feature`, `feature_name`, `url`) VALUES
(1, 'user', '#'),
(2, 'account', '#');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `email` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `password` varchar(128) NOT NULL,
  `registered` tinyint(1) NOT NULL,
  `access_level` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `email`, `name`, `password`, `registered`, `access_level`) VALUES
(2, 'admin@email.com', 'Admin', '$2y$10$yOPFuKeRlpk/E30B0gQsmOzM74yxze1oOlD/kV6x5NDx4seYNBlkW', 1, 1),
(3, 'jack@email.com', 'Jack Lagunalahuna', '$2y$10$xv2anqirWjtBXJ.PmtQG1uKNJq.nnJActG3..k14Qsawp.xtlOV8y', 1, 2),
(4, 'akunbaru@email.com', 'Akun Baru', '$2y$10$WC.2PV.W43rxOCmMCd6wrujtirZeMioaHAmodEbpI7gmYNXc7RclW', 0, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_list`
--
ALTER TABLE `access_list`
  ADD KEY `fk_ar_access_level` (`access_level`),
  ADD KEY `fk_ftr_id_feature` (`id_feature`);

--
-- Indexes for table `access_rights`
--
ALTER TABLE `access_rights`
  ADD PRIMARY KEY (`access_level`);

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id_account`),
  ADD KEY `fk_usr_id_user` (`id_user`),
  ADD KEY `fk_am_id_media` (`id_media`);

--
-- Indexes for table `account_media`
--
ALTER TABLE `account_media`
  ADD PRIMARY KEY (`id_media`);

--
-- Indexes for table `feature`
--
ALTER TABLE `feature`
  ADD PRIMARY KEY (`id_feature`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD KEY `fk_ar2_access_level` (`access_level`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_rights`
--
ALTER TABLE `access_rights`
  MODIFY `access_level` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `id_account` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `account_media`
--
ALTER TABLE `account_media`
  MODIFY `id_media` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `feature`
--
ALTER TABLE `feature`
  MODIFY `id_feature` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_list`
--
ALTER TABLE `access_list`
  ADD CONSTRAINT `fk_ar_access_level` FOREIGN KEY (`access_level`) REFERENCES `access_rights` (`access_level`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ftr_id_feature` FOREIGN KEY (`id_feature`) REFERENCES `feature` (`id_feature`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `account`
--
ALTER TABLE `account`
  ADD CONSTRAINT `fk_am_id_media` FOREIGN KEY (`id_media`) REFERENCES `account_media` (`id_media`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usr_id_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_ar2_access_level` FOREIGN KEY (`access_level`) REFERENCES `access_rights` (`access_level`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
