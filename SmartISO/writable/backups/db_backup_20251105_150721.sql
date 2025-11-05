-- Database Backup
-- Generated: 2025-11-05T15:07:21+00:00
-- Database: smartiso

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Structure for table `configurations`
--

DROP TABLE IF EXISTS `configurations`;
CREATE TABLE `configurations` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `config_description` varchar(255) DEFAULT NULL,
  `config_type` enum('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `configurations`
--

INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('1', 'session_timeout', '0', 'Session timeout in minutes', 'integer', '2025-08-12 12:50:00', '2025-08-30 17:50:32');
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('2', 'system_name', 'SmartISO', 'System name displayed in the application', 'string', '2025-08-12 12:50:00', '2025-08-12 12:50:00');
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('3', 'enable_registration', '1', 'Allow new user registration (1 = enabled, 0 = disabled)', 'boolean', '2025-08-12 12:50:00', '2025-08-12 12:50:00');
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('4', 'system_timezone', 'Asia/Singapore', 'System timezone setting (GMT+8)', 'string', '2025-08-12 12:50:00', '2025-08-12 12:50:00');
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('5', 'auto_create_schedule_on_approval', '1', 'Auto-create a pending schedule when a submission is approved and assigned to service staff', 'boolean', '2025-09-07 17:09:10', '2025-09-07 17:09:10');
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('6', 'auto_create_schedule_on_submit', '1', 'Automatically create schedule row when a submission is created', 'boolean', '2025-09-07 12:15:52', '2025-09-07 22:56:34');
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('7', 'auto_backup_enabled', '1', 'Enable automatic database backups', 'boolean', '2025-11-05 22:00:52', '2025-11-05 22:28:22');
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('8', 'backup_time', '23:00', 'Scheduled time for automatic database backups', 'string', '2025-11-05 22:15:04', '2025-11-05 22:58:03');
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('9', 'admin_can_approve', '1', 'Allow global admins to act as approvers (1 = enabled, 0 = disabled)', 'boolean', '2025-11-05 14:40:26', '2025-11-05 22:44:17');

--
-- Structure for table `dbpanel`
--

DROP TABLE IF EXISTS `dbpanel`;
CREATE TABLE `dbpanel` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `panel_name` varchar(100) NOT NULL,
  `department_id` int(11) unsigned DEFAULT NULL,
  `office_id` int(11) unsigned DEFAULT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_label` varchar(100) NOT NULL,
  `field_type` varchar(20) NOT NULL COMMENT 'input, dropdown, textarea, datepicker',
  `bump_next_field` tinyint(1) NOT NULL DEFAULT 0,
  `required` tinyint(1) DEFAULT 0,
  `width` int(2) DEFAULT 6,
  `field_role` varchar(20) NOT NULL DEFAULT 'requestor',
  `code_table` varchar(100) DEFAULT NULL COMMENT 'For dropdown options - table name to fetch options',
  `length` int(5) DEFAULT NULL,
  `field_order` int(5) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `default_value` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `panel_name_field_name` (`panel_name`,`field_name`),
  KEY `dbpanel_department_fk` (`department_id`),
  KEY `dbpanel_office_fk` (`office_id`),
  CONSTRAINT `dbpanel_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `dbpanel_office_fk` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=624 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dbpanel`
--

INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('610', 'Test', NULL, NULL, 'date_of_request', 'Date Of Request', 'datepicker', '1', '0', '12', 'requestor', '', '0', '1', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('611', 'Test', NULL, NULL, 'request_no', 'Request No', 'input', '1', '0', '12', 'requestor', '', '0', '2', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('612', 'Test', NULL, NULL, 'requested_by', 'Requested By', 'input', '1', '0', '12', 'requestor', '', '0', '3', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('613', 'Test', NULL, NULL, 'department', 'Department', 'input', '1', '0', '12', 'requestor', '', '0', '4', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('614', 'Test', NULL, NULL, 'date_received', 'Date Received', 'datepicker', '1', '0', '12', 'requestor', '', '0', '5', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('615', 'Test', NULL, NULL, 'date_acted', 'Date Acted', 'datepicker', '1', '0', '12', 'requestor', '', '0', '6', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('616', 'Test', NULL, NULL, 'types_of_equipment', 'Types Of Equipment', 'input', '1', '0', '12', 'requestor', '', '0', '7', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('617', 'Test', NULL, NULL, 'date_of_last_repair', 'Date Of Last Repair', 'datepicker', '1', '0', '12', 'requestor', '', '0', '8', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('618', 'Test', NULL, NULL, 'nature_of_last_repair', 'Nature Of Last Repair', 'input', '1', '0', '12', 'requestor', '', '0', '9', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('619', 'Test', NULL, NULL, 'under_warranty', 'Under Warranty', 'checkboxes', '1', '0', '12', 'requestor', '', '0', '10', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '[{\"label\":\"Yes\",\"sub_field\":\"yes\"},{\"label\":\"No\",\"sub_field\":\"no\"},{\"label\":\"Test\",\"sub_field\":\"test\"},{\"label\":\"Testss\",\"sub_field\":\"test\"},{\"label\":\"ASDfas\",\"sub_field\":\"asdasd\"},{\"label\":\"ASdasd\",\"sub_field\":\"asd\"},{\"label\":\"Asdas\",\"sub_field\":\"asd\"}]');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('620', 'Test', NULL, NULL, 'description_of_property', 'Description Of Property', 'checkboxes', '1', '1', '12', 'requestor', '', '0', '11', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '[{\"label\":\"1\",\"sub_field\":\"1\"},{\"label\":\"2\",\"sub_field\":\"2\"},{\"label\":\"3\",\"sub_field\":\"3\"},{\"label\":\"4\",\"sub_field\":\"4\"},{\"label\":\"5\",\"sub_field\":\"5\"}]');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('621', 'Test', NULL, NULL, 'problems_encountered', 'Problems Encountered', 'input', '1', '0', '12', 'requestor', '', '0', '12', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('622', 'Test', NULL, NULL, 'action_taken', 'Action Taken', 'input', '1', '0', '12', 'requestor', '', '0', '13', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');
INSERT INTO `dbpanel` (`id`, `panel_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`) VALUES ('623', 'Test', NULL, NULL, 'comments', 'Comments', 'input', '1', '0', '12', 'requestor', '', '0', '14', '2025-11-05 21:54:16', '2025-11-05 21:54:16', '');

--
-- Structure for table `department_office_backup`
--

DROP TABLE IF EXISTS `department_office_backup`;
CREATE TABLE `department_office_backup` (
  `department_id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  PRIMARY KEY (`department_id`,`office_id`),
  KEY `idx_office` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_office_backup`
--

INSERT INTO `department_office_backup` (`department_id`, `office_id`) VALUES ('12', '4');
INSERT INTO `department_office_backup` (`department_id`, `office_id`) VALUES ('18', '4');
INSERT INTO `department_office_backup` (`department_id`, `office_id`) VALUES ('19', '4');
INSERT INTO `department_office_backup` (`department_id`, `office_id`) VALUES ('20', '4');

--
-- Structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `code`, `description`, `created_at`, `updated_at`) VALUES ('12', 'ADM', 'Administration', '2025-09-07 21:43:31', '2025-09-08 03:16:52');
INSERT INTO `departments` (`id`, `code`, `description`, `created_at`, `updated_at`) VALUES ('18', 'FIN', 'Finance', '2025-09-09 22:07:20', '2025-09-09 22:07:20');
INSERT INTO `departments` (`id`, `code`, `description`, `created_at`, `updated_at`) VALUES ('19', 'FIN2', 'Finance2', '2025-09-09 22:27:29', '2025-09-09 22:27:29');
INSERT INTO `departments` (`id`, `code`, `description`, `created_at`, `updated_at`) VALUES ('20', 'FIN3', 'Finance3', '2025-09-09 22:27:38', '2025-09-09 22:27:38');
INSERT INTO `departments` (`id`, `code`, `description`, `created_at`, `updated_at`) VALUES ('21', 'GEN', 'General Department', '2025-10-09 23:16:43', '2025-10-09 23:16:43');
INSERT INTO `departments` (`id`, `code`, `description`, `created_at`, `updated_at`) VALUES ('22', 'IT', 'Information Technology', '2025-10-09 23:16:43', '2025-10-09 23:16:43');
INSERT INTO `departments` (`id`, `code`, `description`, `created_at`, `updated_at`) VALUES ('23', 'HR', 'Human Resources', '2025-10-09 23:16:43', '2025-10-09 23:16:43');
INSERT INTO `departments` (`id`, `code`, `description`, `created_at`, `updated_at`) VALUES ('24', 'ENG', 'Engineering', '2025-10-09 23:16:43', '2025-10-09 23:16:43');
INSERT INTO `departments` (`id`, `code`, `description`, `created_at`, `updated_at`) VALUES ('25', 'MKT', 'Marketing', '2025-10-09 23:16:43', '2025-10-09 23:16:43');

--
-- Structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE `feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `rating` tinyint(1) NOT NULL COMMENT '1-5 star rating',
  `comments` text DEFAULT NULL,
  `service_quality` tinyint(1) DEFAULT NULL COMMENT '1-5 star rating for service quality',
  `timeliness` tinyint(1) DEFAULT NULL COMMENT '1-5 star rating for timeliness',
  `staff_professionalism` tinyint(1) DEFAULT NULL COMMENT '1-5 star rating for staff professionalism',
  `overall_satisfaction` tinyint(1) DEFAULT NULL COMMENT '1-5 star rating for overall satisfaction',
  `suggestions` text DEFAULT NULL,
  `status` enum('pending','reviewed','addressed') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`),
  KEY `user_id` (`user_id`),
  KEY `submission_id_user_id` (`submission_id`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `submission_id`, `user_id`, `rating`, `comments`, `service_quality`, `timeliness`, `staff_professionalism`, `overall_satisfaction`, `suggestions`, `status`, `created_at`, `updated_at`) VALUES ('3', '19', '3', '5', 'Test', '4', '3', '4', '4', 'Test', 'pending', '2025-10-06 22:08:44', '2025-10-06 22:08:44');

--
-- Structure for table `form_signatories`
--

DROP TABLE IF EXISTS `form_signatories`;
CREATE TABLE `form_signatories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `order_position` int(5) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_signatories_form_id_foreign` (`form_id`),
  KEY `form_signatories_user_id_foreign` (`user_id`),
  CONSTRAINT `form_signatories_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `form_signatories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_signatories`
--

INSERT INTO `form_signatories` (`id`, `form_id`, `user_id`, `order_position`, `created_at`, `updated_at`) VALUES ('2', '1', '4', '1', '2025-09-08 02:03:33', '2025-09-08 02:03:33');

--
-- Structure for table `form_submission_data`
--

DROP TABLE IF EXISTS `form_submission_data`;
CREATE TABLE `form_submission_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) unsigned NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_value` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `submission_id_field_name` (`submission_id`,`field_name`)
) ENGINE=InnoDB AUTO_INCREMENT=288 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_submission_data`
--

INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('1', '1', 'REQUEST_DATE', '2025-03-31', '2025-03-25 13:21:29', '2025-03-25 13:21:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('2', '1', 'REQUEST_NO', 'Test', '2025-03-25 13:21:29', '2025-03-25 13:21:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('3', '1', 'REQUESTED_BY', 'TESt', '2025-03-25 13:21:29', '2025-03-25 13:21:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('4', '2', 'REQUEST_DATE', '2025-04-26', '2025-04-24 12:49:25', '2025-04-24 12:49:25');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('5', '2', 'REQUEST_NO', 'Test', '2025-04-24 12:49:25', '2025-04-24 12:49:25');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('6', '2', 'REQUESTED_BY', 'Test', '2025-04-24 12:49:25', '2025-04-24 12:49:25');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('7', '3', 'REQUEST_DATE', '2025-04-04', '2025-04-24 14:20:38', '2025-04-24 14:20:38');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('8', '3', 'REQUEST_NO', 'Test', '2025-04-24 14:20:38', '2025-04-24 14:20:38');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('9', '3', 'REQUESTED_BY', 'test', '2025-04-24 14:20:38', '2025-04-24 14:20:38');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('10', '4', 'REQUEST_DATE', '2025-04-16', '2025-04-26 14:26:19', '2025-04-26 14:26:19');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('11', '4', 'REQUEST_NO', 'Test', '2025-04-26 14:26:19', '2025-04-26 14:26:19');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('12', '4', 'REQUESTED_BY', 'Test', '2025-04-26 14:26:19', '2025-04-26 14:26:19');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('13', '4', 'TEST', 'Test', '2025-04-26 15:35:21', '2025-04-26 15:35:21');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('142', '19', 'date_of_request', '2025-09-24', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('143', '19', 'request_no', '1', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('144', '19', 'requested_by', '2', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('145', '19', 'department', '3', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('146', '19', 'date_received', '2025-09-25', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('147', '19', 'date_acted', '2025-09-18', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('148', '19', 'types_of_equipment', '4', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('149', '19', 'date_of_last_repair', '2025-09-05', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('150', '19', 'nature_of_last_repair', '5', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('151', '19', 'under_warranty', '[\"yes\",\"no\"]', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('152', '19', 'description_of_property', '6', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('153', '19', 'problems_encountered', '7', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('154', '19', 'action_taken', '8', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('155', '19', 'comments', '9', '2025-10-06 20:47:42', '2025-10-06 20:47:42');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('156', '19', 'priority_level', 'low', '2025-10-06 20:49:42', '2025-10-06 21:33:57');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('157', '20', 'request_date', '', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('158', '20', 'request_no', '1', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('159', '20', 'requested_by', '2', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('160', '20', 'department', '3', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('161', '20', 'date_received', '2025-09-25', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('162', '20', 'date_acted', '2025-09-18', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('163', '20', 'types_of_equipment', '4', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('164', '20', 'date_of_last_repair', '2025-09-05', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('165', '20', 'nature_of_last_repair', '5', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('166', '20', 'under_warranty', '[\"yes\",\"no\"]', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('167', '20', 'p_approver_signature', '', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('168', '20', 'p_service_staff_signature', '', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('169', '20', 'description_of_property', '6', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('170', '20', 'problems_encountered', '7', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('171', '20', 'action_taken', '8', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('172', '20', 'p_requestor_signature', '', '2025-10-06 21:35:20', '2025-10-06 21:35:20');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('173', '21', 'date_of_request', '2025-09-24', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('174', '21', 'request_no', '1', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('175', '21', 'requested_by', '2', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('176', '21', 'department', '3', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('177', '21', 'date_received', '2025-09-25', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('178', '21', 'date_acted', '2025-09-18', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('179', '21', 'types_of_equipment', '4', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('180', '21', 'date_of_last_repair', '2025-09-05', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('181', '21', 'nature_of_last_repair', '5', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('182', '21', 'under_warranty', '[\"yes\",\"no\"]', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('183', '21', 'description_of_property', '6', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('184', '21', 'problems_encountered', '7', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('185', '21', 'action_taken', '8', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('186', '21', 'comments', '9', '2025-10-07 22:06:24', '2025-10-07 22:06:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('187', '22', 'date_of_request', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('188', '22', 'request_no', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('189', '22', 'requested_by', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('190', '22', 'department', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('191', '22', 'date_received', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('192', '22', 'date_acted', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('193', '22', 'types_of_equipment', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('194', '22', 'date_of_last_repair', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('195', '22', 'nature_of_last_repair', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('196', '22', 'under_warranty', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('197', '22', 'description_of_property', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('198', '22', 'problems_encountered', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('199', '22', 'action_taken', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('200', '22', 'comments', '', '2025-10-07 22:35:49', '2025-10-07 22:35:49');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('201', '23', 'date_of_request', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('202', '23', 'request_no', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('203', '23', 'requested_by', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('204', '23', 'department', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('205', '23', 'date_received', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('206', '23', 'date_acted', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('207', '23', 'types_of_equipment', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('208', '23', 'date_of_last_repair', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('209', '23', 'nature_of_last_repair', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('210', '23', 'under_warranty', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('211', '23', 'description_of_property', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('212', '23', 'problems_encountered', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('213', '23', 'action_taken', '', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('214', '23', 'comments', 'Test', '2025-10-07 22:36:16', '2025-10-07 22:36:16');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('215', '24', 'date_of_request', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('216', '24', 'request_no', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('217', '24', 'requested_by', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('218', '24', 'department', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('219', '24', 'date_received', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('220', '24', 'date_acted', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('221', '24', 'types_of_equipment', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('222', '24', 'date_of_last_repair', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('223', '24', 'nature_of_last_repair', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('224', '24', 'under_warranty', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('225', '24', 'description_of_property', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('226', '24', 'problems_encountered', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('227', '24', 'action_taken', '', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('228', '24', 'comments', 'etest', '2025-10-09 23:25:52', '2025-10-09 23:25:52');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('229', '24', 'priority_level', 'medium', '2025-10-09 23:50:09', '2025-10-09 23:50:09');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('230', '23', 'priority_level', 'medium', '2025-10-09 23:50:09', '2025-10-09 23:50:09');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('231', '20', 'priority_level', 'medium', '2025-10-09 23:50:09', '2025-10-09 23:50:09');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('232', '25', 'date_of_request', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('233', '25', 'request_no', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('234', '25', 'requested_by', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('235', '25', 'department', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('236', '25', 'date_received', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('237', '25', 'date_acted', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('238', '25', 'types_of_equipment', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('239', '25', 'date_of_last_repair', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('240', '25', 'nature_of_last_repair', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('241', '25', 'under_warranty', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('242', '25', 'description_of_property', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('243', '25', 'problems_encountered', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('244', '25', 'action_taken', '', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('245', '25', 'comments', 'Test', '2025-10-10 00:33:24', '2025-10-10 00:33:24');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('246', '26', 'date_of_request', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('247', '26', 'request_no', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('248', '26', 'requested_by', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('249', '26', 'department', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('250', '26', 'date_received', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('251', '26', 'date_acted', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('252', '26', 'types_of_equipment', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('253', '26', 'date_of_last_repair', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('254', '26', 'nature_of_last_repair', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('255', '26', 'under_warranty', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('256', '26', 'description_of_property', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('257', '26', 'problems_encountered', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('258', '26', 'action_taken', '', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('259', '26', 'comments', 'Test', '2025-10-10 00:34:22', '2025-10-10 00:34:22');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('260', '27', 'date_of_request', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('261', '27', 'request_no', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('262', '27', 'requested_by', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('263', '27', 'department', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('264', '27', 'date_received', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('265', '27', 'date_acted', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('266', '27', 'types_of_equipment', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('267', '27', 'date_of_last_repair', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('268', '27', 'nature_of_last_repair', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('269', '27', 'under_warranty', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('270', '27', 'description_of_property', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('271', '27', 'problems_encountered', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('272', '27', 'action_taken', '', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('273', '27', 'comments', 'Test', '2025-10-10 00:40:14', '2025-10-10 00:40:14');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('274', '29', 'date_of_request', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('275', '29', 'request_no', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('276', '29', 'requested_by', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('277', '29', 'department', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('278', '29', 'date_received', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('279', '29', 'date_acted', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('280', '29', 'types_of_equipment', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('281', '29', 'date_of_last_repair', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('282', '29', 'nature_of_last_repair', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('283', '29', 'under_warranty', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('284', '29', 'description_of_property', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('285', '29', 'problems_encountered', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('286', '29', 'action_taken', '', '2025-10-10 00:47:54', '2025-10-10 00:47:54');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('287', '29', 'comments', 'Test', '2025-10-10 00:47:54', '2025-10-10 00:47:54');

--
-- Structure for table `form_submissions`
--

DROP TABLE IF EXISTS `form_submissions`;
CREATE TABLE `form_submissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int(11) unsigned NOT NULL,
  `panel_name` varchar(100) NOT NULL,
  `submitted_by` int(11) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'submitted' COMMENT 'submitted, approved, rejected, completed',
  `approver_id` int(11) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_comments` text DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `signature_applied` tinyint(1) DEFAULT 0,
  `service_staff_id` int(11) unsigned DEFAULT NULL,
  `service_staff_signature_date` datetime DEFAULT NULL,
  `service_notes` text DEFAULT NULL,
  `requestor_signature_date` datetime DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completion_date` datetime DEFAULT NULL,
  `priority` enum('low','normal','high','urgent','critical') NOT NULL DEFAULT 'low',
  `reference_file` varchar(255) DEFAULT NULL,
  `reference_file_original` varchar(255) DEFAULT NULL,
  `approver_signature_date` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_id_submitted_by` (`form_id`,`submitted_by`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_submissions`
--

INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('19', '1', 'CRSRF_3', '3', 'completed', '4', '2025-10-06 21:40:32', 'Test', NULL, '1', '5', '2025-10-06 21:41:03', '', '2025-10-06 21:41:03', '0', NULL, 'normal', NULL, NULL, '2025-10-06 21:40:32', NULL, '2025-10-06 20:47:42', '2025-10-06 21:41:03');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('20', '1', '', '3', 'pending_service', '4', '2025-10-09 23:27:08', 'TEst', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'normal', NULL, NULL, '2025-10-09 23:27:08', NULL, '2025-10-06 21:35:20', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('21', '1', '', '3', 'pending_service', '4', '2025-10-10 00:22:52', '', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'normal', NULL, NULL, '2025-10-10 00:22:52', NULL, '2025-10-07 22:06:23', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('22', '1', '', '3', 'pending_service', '4', '2025-10-10 00:32:48', '', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-10 00:32:48', NULL, '2025-10-07 22:35:49', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('23', '1', '', '3', 'pending_service', '4', '2025-10-09 23:41:49', 'Test', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-09 23:41:49', NULL, '2025-10-07 22:36:16', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('24', '1', '', '3', 'pending_service', '4', '2025-10-09 23:26:24', 'Auto-approved with service staff assignment', NULL, '0', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-09 23:26:24', NULL, '2025-10-09 23:25:52', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('25', '1', '', '3', 'pending_service', '4', '2025-10-10 00:33:39', 'Auto-approved with service staff assignment', NULL, '0', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-10 00:33:39', NULL, '2025-10-10 00:33:24', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('26', '1', '', '3', 'pending_service', '4', '2025-10-10 00:37:35', '', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-10 00:37:35', NULL, '2025-10-10 00:34:22', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('27', '1', '', '3', 'pending_service', '4', '2025-10-10 00:47:29', '', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-10 00:47:29', NULL, '2025-10-10 00:40:14', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('28', '1', '', '3', 'submitted', NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, NULL, NULL, '2025-10-10 00:43:31', '2025-10-10 00:43:31');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('29', '1', '', '3', 'submitted', NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, NULL, NULL, '2025-10-10 00:47:54', '2025-10-27 23:36:44');

--
-- Structure for table `forms`
--

DROP TABLE IF EXISTS `forms`;
CREATE TABLE `forms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `office_id` int(11) unsigned DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `panel_name` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `fk_forms_office_id` (`office_id`),
  CONSTRAINT `fk_forms_office_id` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forms`
--

INSERT INTO `forms` (`id`, `code`, `description`, `office_id`, `department_id`, `panel_name`, `created_at`, `updated_at`) VALUES ('1', 'CRSRF', 'Computer Repair Service Request Forms', '1', '12', NULL, '2025-03-25 13:15:12', '2025-10-27 23:36:44');
INSERT INTO `forms` (`id`, `code`, `description`, `office_id`, `department_id`, `panel_name`, `created_at`, `updated_at`) VALUES ('8', 'FORM1', 'Test', '2', '19', NULL, '2025-10-06 22:11:56', '2025-10-06 22:12:06');
INSERT INTO `forms` (`id`, `code`, `description`, `office_id`, `department_id`, `panel_name`, `created_at`, `updated_at`) VALUES ('9', 'FORM2123', 'Test', NULL, '22', NULL, '2025-10-23 00:13:42', '2025-10-23 00:13:42');

--
-- Structure for table `import_jobs`
--

DROP TABLE IF EXISTS `import_jobs`;
CREATE TABLE `import_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `form_code` varchar(191) NOT NULL,
  `file_path` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `result_json` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('1', '2023-09-01-000001', 'App\\Database\\Migrations\\CreateFormsTable', 'default', 'App', '1742908364', '1');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('2', '2023_11_10_000002', 'App\\Database\\Migrations\\CreateNotificationsTable', 'default', 'App', '1742908364', '1');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('3', '2025-03-20-062829', 'App\\Database\\Migrations\\CreateDepartmentsTable', 'default', 'App', '1742908364', '1');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('4', '2025-03-20-062923', 'App\\Database\\Migrations\\CreateUsersTable', 'default', 'App', '1742908364', '1');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('5', '2025-03-20-155219', 'App\\Database\\Migrations\\CreateDbpanelTable', 'default', 'App', '1742908364', '1');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('6', '2025-03-20-160040', 'App\\Database\\Migrations\\CreateFormSubmissionsTable', 'default', 'App', '1742908364', '1');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('7', '2025-03-21-031722', 'App\\Database\\Migrations\\AddFieldsToDbpanel', 'default', 'App', '1742908364', '1');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('8', '2025-03-21-081225', 'App\\Database\\Migrations\\AddSignatureToUsers', 'default', 'App', '1742908364', '1');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('9', '2025-03-21-081247', 'App\\Database\\Migrations\\EnhanceFormSubmissionsForWorkflow', 'default', 'App', '1742908364', '1');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('20', '2025-03-24-073055', 'App\\Database\\Migrations\\CreateFormSignatories', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('21', '2025-03-24-084906', 'App\\Database\\Migrations\\AddServiceColumnsToFormSubmissions', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('22', '2025-03-24-095340', 'App\\Database\\Migrations\\AddApprovalColumnsToFormSubmissions', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('23', '2025-04-26-135354', 'App\\Database\\Migrations\\AddFieldRoleToDbpanel', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('24', '2025-07-31-050130', 'App\\Database\\Migrations\\AddPanelNameToForms', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('25', '2025-08-01-011555', 'App\\Database\\Migrations\\CreateConfigurationsTable', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('26', '2025-08-01-021830', 'App\\Database\\Migrations\\AddProfileImageToUsers', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('27', '2025-08-01-022901', 'App\\Database\\Migrations\\AddTimezoneConfiguration', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('28', '2025-08-01-062200', 'App\\Database\\Migrations\\FixFieldTypes', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('29', '2025-08-01-070000', 'App\\Database\\Migrations\\AddPriorityAndReferenceFeatures', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('30', '2025-08-11-161828', 'App\\Database\\Migrations\\CreateOfficesTable', 'default', 'App', '1755003000', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('35', '2025-08-11-161950', 'App\\Database\\Migrations\\CreateSchedulesTable', 'default', 'App', '1755003598', '3');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('36', '2025-08-11-161959', 'App\\Database\\Migrations\\CreateFeedbackTable', 'default', 'App', '1755003598', '3');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('37', '2025-08-11-162016', 'App\\Database\\Migrations\\UpdateUsersTableForOffices', 'default', 'App', '1755003598', '3');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('38', '2025-08-12-202000', 'App\\Database\\Migrations\\EnsureApproverIdExists', 'default', 'App', '1755003598', '3');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('39', '2025-08-11-161835', 'App\\Database\\Migrations\\CreateOfficesTable_161835', 'default', 'App', '1756068263', '4');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('40', '2025-08-25-000001', 'App\\Database\\Migrations\\AddPriorityToSchedules', 'default', 'App', '1756068263', '4');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('41', '2025-08-25-000002', 'App\\Database\\Migrations\\AddOfficeIdToFormsTable', 'default', 'App', '1756499975', '5');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('42', '2025-09-01-000000', 'App\\Database\\Migrations\\UpdateFieldRoleDefault', 'default', 'App', '1756589215', '6');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('43', '2025-09-02-000000', 'App\\Database\\Migrations\\AddDefaultValueToDbpanel', 'default', 'App', '1756589215', '6');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('44', '2025-08-31-062000', 'App\\Database\\Migrations\\AddDefaultValueToDbpanel', 'default', 'App', '1756589329', '7');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('45', '2025-08-30-000000', 'App\\Database\\Migrations\\AddEtaAndPriorityLevelToSchedules', 'default', 'App', '1756597173', '8');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('46', '2025-09-02-000000', 'App\\Database\\Migrations\\AddDefaultValueToDbpanel_v2', 'default', 'App', '1757161346', '9');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('47', '2025-09-06-000001', 'App\\Database\\Migrations\\AddDepartmentIdToFormsTable', 'default', 'App', '1757161346', '9');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('48', '2025-09-06-100000', 'App\\Database\\Migrations\\AddDepartmentIdToOfficesTable', 'default', 'App', '1757161346', '9');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('49', '2025-09-06-110000', 'App\\Database\\Migrations\\AddOfficeIdToFormsAndBackfill', 'default', 'App', '1757161346', '9');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('50', '2025-10-07-000000', 'App\\Database\\Migrations\\UpdatePriorityDefaultToLow', 'default', 'App', '1759846545', '10');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('51', '2025-10-22-000000', 'App\\Database\\Migrations\\AddDepartmentAdminUserType', 'default', 'App', '1761137072', '11');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('52', '2025-10-26-083000', 'App\\Database\\Migrations\\AddDepartmentOfficeToDbpanel', 'default', 'App', '1761489894', '12');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('53', '2025-10-26-090000', 'App\\Database\\Migrations\\AddManualScheduleFlag', 'default', 'App', '1761492583', '13');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('54', '2025-11-05-000001', 'App\\Database\\Migrations\\AddAdminCanApproveConfig', 'default', 'App', '1762353626', '14');

--
-- Structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `submission_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('3', '3', '10', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-08-30 17:51:02');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('4', '4', '11', 'New Service Request Requires Approval', 'A new QSGF request has been submitted by a user and requires your approval.', '1', '2025-08-31 17:50:35');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('5', '3', '11', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-08-31 18:10:17');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('6', '3', '9', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-08-31 20:46:26');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('7', '4', '12', 'New Service Request Requires Approval', 'A new QSGF request has been submitted by a user and requires your approval.', '1', '2025-08-31 21:29:15');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('11', '4', '14', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-09-05 00:00:30');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('12', '5', '14', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '1', '2025-09-05 00:01:43');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('13', '4', '15', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-09-05 01:52:19');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('14', '3', '15', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-09-05 01:59:17');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('15', '5', '15', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '1', '2025-09-05 02:08:17');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('16', '4', '16', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-09-05 02:34:41');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('17', '5', '16', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '1', '2025-09-05 02:34:56');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('18', '4', '17', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-09-06 23:36:15');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('19', '5', '17', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '1', '2025-09-06 23:36:55');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('20', '4', '18', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-09-07 16:40:32');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('21', '4', '19', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-10-06 20:47:42');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('22', '4', '20', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-10-06 21:35:20');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('23', '3', '19', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-10-06 21:40:32');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('24', '5', '19', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '1', '2025-10-06 21:40:32');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('25', '4', '21', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-10-07 22:06:23');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('26', '4', '22', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-10-07 22:35:49');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('27', '4', '23', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-10-07 22:36:16');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('28', '4', '24', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-10-09 23:25:52');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('29', '5', '24', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '1', '2025-10-09 23:26:24');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('30', '3', '20', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-10-09 23:27:08');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('31', '5', '20', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '1', '2025-10-09 23:27:08');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('32', '3', '23', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-10-09 23:41:49');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('33', '5', '23', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '1', '2025-10-09 23:41:49');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('34', '3', '21', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-10-10 00:22:52');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('35', '5', '21', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-10-10 00:22:52');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('36', '3', '22', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-10-10 00:32:48');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('37', '5', '22', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-10-10 00:32:48');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('38', '4', '25', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-10-10 00:33:24');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('39', '5', '25', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-10-10 00:33:39');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('40', '4', '26', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-10-10 00:34:22');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('41', '3', '26', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-10-10 00:37:35');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('42', '5', '26', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-10-10 00:37:35');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('43', '4', '27', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-10-10 00:40:14');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('44', '4', '28', 'New Service Request Requires Approval', 'A new test request has been submitted and requires your approval.', '1', '2025-10-10 00:43:49');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('45', '3', '27', 'Request Approved', 'Your service request has been approved and will be scheduled.', '1', '2025-10-10 00:47:29');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('46', '5', '27', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-10-10 00:47:29');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('47', '4', '29', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-10-10 00:47:54');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('48', '4', '28', 'URGENT: New Service Request Requires Approval', 'A new CRSRF test request has been submitted and urgently requires your approval.', '1', '2025-10-10 00:51:04');

--
-- Structure for table `offices`
--

DROP TABLE IF EXISTS `offices`;
CREATE TABLE `offices` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `description` varchar(255) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `code`, `description`, `department_id`, `active`, `created_at`, `updated_at`) VALUES ('1', 'ADM', 'Administration Office', '12', '0', '2025-08-12 15:07:01', '2025-09-07 21:43:37');
INSERT INTO `offices` (`id`, `code`, `description`, `department_id`, `active`, `created_at`, `updated_at`) VALUES ('2', 'IT', 'Information Technology Office', '19', '0', '2025-08-12 15:07:01', '2025-09-09 23:47:32');
INSERT INTO `offices` (`id`, `code`, `description`, `department_id`, `active`, `created_at`, `updated_at`) VALUES ('3', 'HR', 'Human Resources Office', '12', '0', '2025-08-12 15:07:01', '2025-09-08 03:17:50');
INSERT INTO `offices` (`id`, `code`, `description`, `department_id`, `active`, `created_at`, `updated_at`) VALUES ('4', 'FIN', 'Finance Office', '12', '0', '2025-08-12 15:07:01', '2025-09-07 21:43:37');

--
-- Structure for table `priority_configurations`
--

DROP TABLE IF EXISTS `priority_configurations`;
CREATE TABLE `priority_configurations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `priority_level` varchar(20) NOT NULL,
  `priority_weight` int(3) unsigned NOT NULL,
  `priority_color` varchar(7) NOT NULL DEFAULT '#6c757d',
  `description` text DEFAULT NULL,
  `sla_hours` int(5) unsigned DEFAULT NULL COMMENT 'Service Level Agreement in hours',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `priority_level` (`priority_level`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `priority_configurations`
--

INSERT INTO `priority_configurations` (`id`, `priority_level`, `priority_weight`, `priority_color`, `description`, `sla_hours`, `created_at`, `updated_at`) VALUES ('1', 'low', '1', '#28a745', 'Low priority - routine requests', '168', '2025-08-12 12:50:00', '2025-08-12 12:50:00');
INSERT INTO `priority_configurations` (`id`, `priority_level`, `priority_weight`, `priority_color`, `description`, `sla_hours`, `created_at`, `updated_at`) VALUES ('2', 'normal', '2', '#6c757d', 'Normal priority - standard requests', '72', '2025-08-12 12:50:00', '2025-08-12 12:50:00');
INSERT INTO `priority_configurations` (`id`, `priority_level`, `priority_weight`, `priority_color`, `description`, `sla_hours`, `created_at`, `updated_at`) VALUES ('3', 'high', '3', '#ffc107', 'High priority - important requests', '24', '2025-08-12 12:50:00', '2025-08-12 12:50:00');
INSERT INTO `priority_configurations` (`id`, `priority_level`, `priority_weight`, `priority_color`, `description`, `sla_hours`, `created_at`, `updated_at`) VALUES ('4', 'urgent', '4', '#fd7e14', 'Urgent priority - time-sensitive requests', '4', '2025-08-12 12:50:00', '2025-08-12 12:50:00');
INSERT INTO `priority_configurations` (`id`, `priority_level`, `priority_weight`, `priority_color`, `description`, `sla_hours`, `created_at`, `updated_at`) VALUES ('5', 'critical', '5', '#dc3545', 'Critical priority - emergency requests', '1', '2025-08-12 12:50:00', '2025-08-12 12:50:00');

--
-- Structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) unsigned NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 60,
  `location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `assigned_staff_id` int(11) unsigned DEFAULT NULL,
  `completion_notes` text DEFAULT NULL,
  `priority` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `eta_days` int(3) DEFAULT NULL,
  `estimated_date` date DEFAULT NULL,
  `priority_level` varchar(16) DEFAULT NULL,
  `is_manual_schedule` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 if schedule was manually set (not auto-generated from priority), 0 otherwise',
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`),
  KEY `assigned_staff_id` (`assigned_staff_id`),
  KEY `scheduled_date_scheduled_time` (`scheduled_date`,`scheduled_time`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('3', '19', '2025-10-21', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-10-06 20:47:42', '2025-10-26 23:21:57', '5', '2025-10-28', 'medium', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('4', '20', '2025-10-24', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-10-06 21:35:20', '2025-10-27 21:32:37', '0', '2025-10-24', 'medium', '1');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('5', '21', '2025-10-22', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-10-07 22:06:24', '2025-10-27 20:55:37', '5', '2025-10-29', 'medium', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('6', '22', '2025-11-21', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-10-07 22:35:49', '2025-10-27 21:05:07', '5', '2025-11-28', 'medium', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('7', '23', '2025-10-07', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-10-07 22:36:16', '2025-10-07 22:36:16', '7', '2025-10-14', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('8', '24', '2025-10-09', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-10-09 23:25:52', '2025-10-09 23:25:52', '7', '2025-10-16', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('9', '25', '2025-10-10', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-10-10 00:33:24', '2025-10-10 00:33:24', '7', '2025-10-17', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('10', '26', '2025-10-10', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-10-10 00:34:22', '2025-10-10 00:34:22', '7', '2025-10-17', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('11', '27', '2025-10-10', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-10-10 00:40:14', '2025-10-10 00:40:14', '7', '2025-10-17', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('12', '29', '2025-10-10', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-10-10 00:47:54', '2025-10-10 00:47:54', '7', '2025-10-17', 'low', '0');

--
-- Structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department_id` int(11) unsigned DEFAULT NULL,
  `office_id` int(11) unsigned DEFAULT NULL,
  `user_type` enum('admin','requestor','approving_authority','service_staff','superuser','department_admin') DEFAULT 'requestor',
  `signature` varchar(255) DEFAULT NULL COMMENT 'Path to user signature file',
  `profile_image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `users_department_id_foreign` (`department_id`),
  KEY `fk_users_office` (`office_id`),
  CONSTRAINT `fk_users_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`),
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('1', 'chesspiecedum2+user1@gmail.com', 'admin', '$2y$10$Zp8lR0APZb52eiNkuOPobOtI4ndKpBUpbvVmKCJNCIj40jfex4dIa', 'System Administrator', NULL, '1', 'superuser', NULL, NULL, '1', NULL, NULL, '2025-03-25 13:12:44', '2025-03-25 13:12:44', NULL);
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('2', 'chesspiecedum2+user2@gmail.com', 'admin_user', '$2y$10$hwHypfnPwSifjiVAQLu9N.FlbafkczravEwE/6WFvhlIKzgEdmgmG', 'Admin User', '12', NULL, 'admin', NULL, NULL, '1', NULL, NULL, '2025-03-25 13:12:44', '2025-11-05 23:00:28', '2025-11-05 23:00:28');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('3', 'chesspiecedum2+user3@gmail.com', 'requestor_user', '$2y$10$SR.JDa3J7sJKUHGFCdtuBuK91hdhwqcF.0geYeUhdS4Z9GGMySICe', 'Requestor User', '12', NULL, 'requestor', 'uploads/signatures/1757156429_f64ca1641555c24e9597.jpg', NULL, '1', NULL, NULL, '2025-03-25 13:12:44', '2025-11-05 22:58:30', '2025-11-05 22:58:30');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('4', 'chesspiecedum2+user4@gmail.com', 'approver_user', '$2y$10$UwETgMzxBL8nYWiOTSgXMOq8or7bu/qivp3KWR4Yv.8yJF/hPwZz2', 'Approving Authority User', NULL, '3', 'approving_authority', 'uploads/signatures/1745679693_fe4b03e6434b1e2f310b.png', NULL, '1', NULL, NULL, '2025-03-25 13:12:44', '2025-11-05 22:46:22', '2025-11-05 22:46:22');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('5', 'chesspiecedum2+user5@gmail.com', 'service_user', '$2y$10$pmOycYaryl2aXRquiYbRsOHiXqnQxvLjK7IFBwTCuACBsGXTsKwAi', 'Service Staff User', NULL, '4', 'service_staff', 'uploads/signatures/1757173058_44990d47bd91fa7802f6.png', NULL, '1', NULL, NULL, '2025-03-25 13:12:44', '2025-10-10 00:23:05', '2025-10-10 00:23:05');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('7', 'chesspiecedum2+user7@gmail.com', 'player1', '$2y$10$lZQIuNBVVr6x.GzTwmSi5.bnOOSwgLtSlSA4ITezNvOSf9.8AqbVS', 'Ralph Jayson E Diaz', '24', NULL, 'requestor', NULL, NULL, '1', NULL, NULL, '2025-10-09 23:23:21', '2025-10-09 23:23:28', '2025-10-09 23:23:28');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('8', 'chesspiecedum2+user8@gmail.com', 'Susedge', '$2y$10$Gdzdu2vmlvxb5xFO9hp2wOELBUPM2ovR0b7EEtGfpzsRm4BzqoNLy', 'Ralph Jayson Diaz', '25', '1', 'requestor', NULL, NULL, '1', NULL, NULL, '2025-10-10 00:18:13', '2025-10-10 00:18:23', '2025-10-10 00:18:23');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('9', 'chesspiecedum2+user9@gmail.com', 'dept_admin_it', '$2y$10$7LNiEyDlJDD8PVLw.KWlo.zNynv0AoES4IdhcABHWPJ59yFwagWOm', 'IT Department Admin', '22', '2', 'department_admin', NULL, NULL, '1', NULL, NULL, '2025-10-22 12:53:42', '2025-11-05 22:56:02', '2025-11-05 22:56:02');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('10', 'chesspiecedum2+user10@gmail.com', 'it_requestor_1', '$2y$10$icA3p9/0LzAUh6oUNKwkju3c/S.ZXp/FbZLJ5uF7NeF.ZzbDa1W7.', 'IT Requestor One', '22', '2', 'requestor', NULL, NULL, '1', NULL, NULL, '2025-10-22 14:11:26', '2025-10-22 14:11:26', NULL);
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('11', 'chesspiecedum2+user11@gmail.com', 'it_requestor_2', '$2y$10$Zqc8uZavgvZ8801DSAE0ROKnAvXy0n/dlr2FUoD9Wgz4XF35QAinG', 'IT Requestor Two', '22', '2', 'requestor', NULL, NULL, '1', NULL, NULL, '2025-10-22 14:11:27', '2025-10-22 14:11:27', NULL);
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('12', 'chesspiecedum2+user12@gmail.com', 'it_approver', '$2y$10$zBUv5g3mnjlGDa0K5D1pweAMdKcavNvOrWJea6Sp/vR82lhc/2wri', 'IT Approving Authority', '22', '2', 'approving_authority', NULL, NULL, '1', NULL, NULL, '2025-10-22 14:11:27', '2025-10-22 14:11:27', NULL);
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('13', 'chesspiecedum2+user13@gmail.com', 'it_service', '$2y$10$OfVLFblBVPsqvLNDC3a0HutZO1p21PaisD/psLq7ss0P/2EbjRI7K', 'IT Service Staff', '22', '2', 'service_staff', NULL, NULL, '1', NULL, NULL, '2025-10-22 14:11:27', '2025-10-22 14:11:27', NULL);

--
-- Structure for table `users_email_backup`
--

DROP TABLE IF EXISTS `users_email_backup`;
CREATE TABLE `users_email_backup` (
  `user_id` int(11) NOT NULL,
  `original_email` varchar(255) DEFAULT NULL,
  `backup_date` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_email_backup`
--

INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('1', 'admin@smartiso.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('2', 'admin@example.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('3', 'requestor@example.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('4', 'approver@example.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('5', 'service@example.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('7', 'ralphjaysondiaz11@gmail.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('8', 'chesspiecedum1@gmail.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('9', 'dept_admin@example.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('10', 'it_requestor1@example.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('11', 'it_requestor2@example.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('12', 'it_approver@example.com', '2025-10-23 00:32:16');
INSERT INTO `users_email_backup` (`user_id`, `original_email`, `backup_date`) VALUES ('13', 'it_service@example.com', '2025-10-23 00:32:16');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
