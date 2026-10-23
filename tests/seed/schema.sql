-- Baseline schema for the password_manager database.
--
-- Verbatim from the pre-migration dump (password_manager.sql on the
-- `pre-migration` branch), minus its data rows. The schema is frozen for the
-- migration (spec #1): no changes are allowed here — table definitions,
-- indexes and constraints are copied as-is.
--
-- Repeatable: drops and recreates every table. Data comes from seed.sql,
-- generated from tests/seed/plaintexts.php via tests/tools/generate-seed.php.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `access_list`;
DROP TABLE IF EXISTS `account`;
DROP TABLE IF EXISTS `account_media`;
DROP TABLE IF EXISTS `feature`;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS `access_rights`;

CREATE TABLE `access_list` (
  `access_level` tinyint(3) UNSIGNED NOT NULL,
  `id_feature` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `access_rights` (
  `access_level` tinyint(3) UNSIGNED NOT NULL,
  `status_access` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `account` (
  `id_account` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `username_email` varchar(256) NOT NULL,
  `password` varchar(256) NOT NULL,
  `id_media` int(11) NOT NULL,
  `key_str` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `account_media` (
  `id_media` int(11) NOT NULL,
  `logo` varchar(256) DEFAULT NULL,
  `name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `feature` (
  `id_feature` tinyint(3) UNSIGNED NOT NULL,
  `feature_name` varchar(32) NOT NULL,
  `url` varchar(64) DEFAULT '#'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `email` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `password` varchar(128) NOT NULL,
  `registered` tinyint(1) NOT NULL,
  `access_level` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `access_list`
  ADD KEY `fk_ar_access_level` (`access_level`),
  ADD KEY `fk_ftr_id_feature` (`id_feature`);

ALTER TABLE `access_rights`
  ADD PRIMARY KEY (`access_level`);

ALTER TABLE `account`
  ADD PRIMARY KEY (`id_account`),
  ADD KEY `fk_usr_id_user` (`id_user`),
  ADD KEY `fk_am_id_media` (`id_media`);

ALTER TABLE `account_media`
  ADD PRIMARY KEY (`id_media`);

ALTER TABLE `feature`
  ADD PRIMARY KEY (`id_feature`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD KEY `fk_ar2_access_level` (`access_level`);

ALTER TABLE `access_rights`
  MODIFY `access_level` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `account`
  MODIFY `id_account` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `account_media`
  MODIFY `id_media` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

ALTER TABLE `feature`
  MODIFY `id_feature` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `access_list`
  ADD CONSTRAINT `fk_ar_access_level` FOREIGN KEY (`access_level`) REFERENCES `access_rights` (`access_level`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ftr_id_feature` FOREIGN KEY (`id_feature`) REFERENCES `feature` (`id_feature`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `account`
  ADD CONSTRAINT `fk_am_id_media` FOREIGN KEY (`id_media`) REFERENCES `account_media` (`id_media`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usr_id_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user`
  ADD CONSTRAINT `fk_ar2_access_level` FOREIGN KEY (`access_level`) REFERENCES `access_rights` (`access_level`);

SET FOREIGN_KEY_CHECKS = 1;
