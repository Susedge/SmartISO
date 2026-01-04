-- Database Backup
-- Generated: 2026-01-04T21:56:14+08:00
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
-- Structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'Action type: create, update, delete, login, logout, view, approve, reject, etc.',
  `entity_type` varchar(50) NOT NULL COMMENT 'Entity type: user, form, submission, panel, department, office, config, etc.',
  `entity_id` int(11) unsigned DEFAULT NULL,
  `entity_name` varchar(255) DEFAULT NULL COMMENT 'Human-readable name of the entity',
  `old_values` text DEFAULT NULL COMMENT 'JSON of old values before change',
  `new_values` text DEFAULT NULL COMMENT 'JSON of new values after change',
  `description` varchar(500) DEFAULT NULL COMMENT 'Human-readable description of the action',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `entity_type` (`entity_type`),
  KEY `entity_id` (`entity_id`),
  KEY `created_at` (`created_at`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_user_time` (`user_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('1', '14', 'TAU DCO Officer', 'logout', 'session', NULL, 'tau_dco_user', NULL, NULL, 'User logged out: tau_dco_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 22:24:42');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('2', '14', 'TAU DCO Officer', 'login', 'session', NULL, 'tau_dco_user', NULL, NULL, 'User logged in: tau_dco_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 22:24:49');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('3', '2', 'Admin User', 'login', 'session', NULL, 'admin_user', NULL, NULL, 'User logged in: admin_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 13:08:43');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('4', '2', 'Admin User', 'logout', 'session', NULL, 'admin_user', NULL, NULL, 'User logged out: admin_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 13:54:31');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('5', '2', 'Admin User', 'login', 'session', NULL, 'admin_user', NULL, NULL, 'User logged in: admin_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 13:54:36');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('6', '2', 'Admin User', 'logout', 'session', NULL, 'admin_user', NULL, NULL, 'User logged out: admin_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 18:03:18');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('7', '2', 'Admin User', 'login', 'session', NULL, 'admin_user', NULL, NULL, 'User logged in: admin_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 18:03:23');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('8', '2', 'Admin User', 'logout', 'session', NULL, 'admin_user', NULL, NULL, 'User logged out: admin_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 18:06:59');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('9', '2', 'Admin User', 'login', 'session', NULL, 'admin_user', NULL, NULL, 'User logged in: admin_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 18:07:04');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('10', '2', 'Admin User', 'create', 'user', '15', 'DCO user', NULL, NULL, 'Created new user: DCO user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 21:17:24');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('11', '2', 'Admin User', 'logout', 'session', NULL, 'admin_user', NULL, NULL, 'User logged out: admin_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 21:19:52');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('12', '15', 'DCO user', 'login', 'session', NULL, 'DCO user', NULL, NULL, 'User logged in: DCO user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 21:20:00');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('13', '15', 'DCO user', 'logout', 'session', NULL, 'DCO user', NULL, NULL, 'User logged out: DCO user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 21:20:26');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('14', '2', 'Admin User', 'login', 'session', NULL, 'admin_user', NULL, NULL, 'User logged in: admin_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 21:22:45');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('15', '2', 'Admin User', 'login', 'session', NULL, 'admin_user', NULL, NULL, 'User logged in: admin_user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-04 21:55:34');

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('8', 'backup_time', '23:37', 'Scheduled time for automatic database backups', 'string', '2025-11-05 22:15:04', '2025-11-05 23:35:58');
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('9', 'admin_can_approve', '1', 'Allow global admins to act as approvers (1 = enabled, 0 = disabled)', 'boolean', '2025-11-05 14:40:26', '2025-11-05 22:44:17');
INSERT INTO `configurations` (`id`, `config_key`, `config_value`, `config_description`, `config_type`, `created_at`, `updated_at`) VALUES ('10', 'last_backup_time', '2025-11-08 23:41:54', 'Timestamp of the last successful backup', 'string', '2025-11-05 23:35:43', '2025-11-08 23:41:54');

--
-- Structure for table `dbpanel`
--

DROP TABLE IF EXISTS `dbpanel`;
CREATE TABLE `dbpanel` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `panel_name` varchar(100) NOT NULL,
  `form_name` varchar(255) DEFAULT NULL,
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
  `default_value` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `panel_name_field_name` (`panel_name`,`field_name`),
  KEY `dbpanel_department_fk` (`department_id`),
  KEY `dbpanel_office_fk` (`office_id`),
  KEY `idx_dbpanel_active` (`panel_name`,`is_active`),
  KEY `idx_form_name` (`form_name`),
  CONSTRAINT `dbpanel_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `dbpanel_office_fk` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=704 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dbpanel`
--

INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('610', 'Test', NULL, NULL, NULL, 'date_of_request', 'Date Of Request', 'datepicker', '1', '0', '12', 'requestor', '', '0', '1', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('611', 'Test', NULL, NULL, NULL, 'request_no', 'Request No', 'input', '1', '0', '12', 'requestor', '', '0', '2', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('612', 'Test', NULL, NULL, NULL, 'requested_by', 'Requested By', 'input', '1', '0', '12', 'requestor', '', '0', '3', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('613', 'Test', NULL, NULL, NULL, 'department', 'Department', 'input', '1', '0', '12', 'requestor', '', '0', '4', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('614', 'Test', NULL, NULL, NULL, 'date_received', 'Date Received', 'datepicker', '1', '0', '12', 'requestor', '', '0', '5', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('615', 'Test', NULL, NULL, NULL, 'date_acted', 'Date Acted', 'datepicker', '1', '0', '12', 'requestor', '', '0', '6', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('616', 'Test', NULL, NULL, NULL, 'types_of_equipment', 'Types Of Equipment', 'input', '1', '0', '12', 'requestor', '', '0', '7', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('617', 'Test', NULL, NULL, NULL, 'date_of_last_repair', 'Date Of Last Repair', 'datepicker', '1', '0', '12', 'requestor', '', '0', '8', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('618', 'Test', NULL, NULL, NULL, 'nature_of_last_repair', 'Nature Of Last Repair', 'input', '1', '0', '12', 'requestor', '', '0', '9', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('619', 'Test', NULL, NULL, NULL, 'under_warranty', 'Under Warranty', 'checkboxes', '1', '0', '12', 'requestor', '', '0', '10', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '[{\"label\":\"Yes\",\"sub_field\":\"yes\"},{\"label\":\"No\",\"sub_field\":\"no\"},{\"label\":\"Test\",\"sub_field\":\"test\"},{\"label\":\"Testss\",\"sub_field\":\"test\"},{\"label\":\"ASDfas\",\"sub_field\":\"asdasd\"},{\"label\":\"ASdasd\",\"sub_field\":\"asd\"},{\"label\":\"Asdas\",\"sub_field\":\"asd\"}]', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('620', 'Test', NULL, NULL, NULL, 'description_of_property', 'Description Of Property', 'checkboxes', '1', '1', '12', 'requestor', '', '0', '11', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '[{\"label\":\"1\",\"sub_field\":\"1\"},{\"label\":\"2\",\"sub_field\":\"2\"},{\"label\":\"3\",\"sub_field\":\"3\"},{\"label\":\"4\",\"sub_field\":\"4\"},{\"label\":\"5\",\"sub_field\":\"5\"}]', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('621', 'Test', NULL, NULL, NULL, 'problems_encountered', 'Problems Encountered', 'input', '1', '0', '12', 'requestor', '', '0', '12', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('622', 'Test', NULL, NULL, NULL, 'action_taken', 'Action Taken', 'input', '1', '0', '12', 'requestor', '', '0', '13', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('623', 'Test', NULL, NULL, NULL, 'comments', 'Comments', 'input', '1', '0', '12', 'requestor', '', '0', '14', '2025-11-05 21:54:16', '2025-12-01 22:27:53', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('625', 'Testttt', NULL, NULL, NULL, 'location_building_office', 'Location Building Office', 'input', '0', '0', '12', 'requestor', '', '0', '1', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('626', 'Testttt', NULL, NULL, NULL, 'requisition_no', 'Requisition No', 'input', '0', '0', '12', 'requestor', '', '0', '2', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('627', 'Testttt', NULL, NULL, NULL, 'nature', 'Nature', 'checkboxes', '0', '0', '12', 'requestor', '', '0', '3', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '[{\"label\":\"Electrical\",\"sub_field\":\"electrical\"},{\"label\":\"Plumbing\",\"sub_field\":\"plumbing\"},{\"label\":\"Carpentry And Masonry\",\"sub_field\":\"carpentry_and_masonry\"},{\"label\":\"Painting Hauling Houskeeping\",\"sub_field\":\"painting_hauling_houskeeping\"}]', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('628', 'Testttt', NULL, NULL, NULL, 'repair', 'Repair', 'checkboxes', '0', '0', '12', 'requestor', '', '0', '4', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '[{\"label\":\"Lightings And Switches\",\"sub_field\":\"lightings_and_switches\"},{\"label\":\"Fixtures\",\"sub_field\":\"fixtures\"},{\"label\":\"Furniture\",\"sub_field\":\"furniture\"},{\"label\":\"Outlets\",\"sub_field\":\"outlets\"},{\"label\":\"Pipelines\",\"sub_field\":\"pipelines\"},{\"la', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('629', 'Testttt', NULL, NULL, NULL, 'other', 'Other', 'checkboxes', '0', '0', '12', 'requestor', '', '0', '5', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '[{\"label\":\"Painting\",\"sub_field\":\"painting\"},{\"label\":\"Termite Proofing\",\"sub_field\":\"termite_proofing\"},{\"label\":\"Hauling\",\"sub_field\":\"hauling\"},{\"label\":\"Grasscutting Cleaning\",\"sub_field\":\"grasscutting_cleaning\"},{\"label\":\"Clearing Demolition\",\"sub_fi', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('630', 'Testttt', NULL, NULL, NULL, 'others_specify', 'Others  Specify ', 'input', '0', '0', '12', 'requestor', '', '0', '6', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('631', 'Testttt', NULL, NULL, NULL, 'requested_by', 'Requested By', 'input', '0', '0', '12', 'requestor', '', '0', '7', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('632', 'Testttt', NULL, NULL, NULL, 'received_by', 'Received By', 'input', '0', '0', '12', 'requestor', '', '0', '8', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('633', 'Testttt', NULL, NULL, NULL, 'requested_by_date', 'Requested By Date', 'datepicker', '0', '0', '12', 'requestor', '', '0', '9', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('634', 'Testttt', NULL, NULL, NULL, 'received_by_date', 'Received By Date', 'datepicker', '0', '0', '12', 'requestor', '', '0', '10', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('635', 'Testttt', NULL, NULL, NULL, 'date_inspected', 'Date Inspected', 'datepicker', '0', '0', '12', 'requestor', '', '0', '11', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('636', 'Testttt', NULL, NULL, NULL, 'inspected_by', 'Inspected By', 'input', '0', '0', '12', 'requestor', '', '0', '12', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('637', 'Testttt', NULL, NULL, NULL, 'services', 'Services', 'checkboxes', '0', '0', '12', 'requestor', '', '0', '13', '2025-11-10 20:57:01', '2025-11-10 20:57:01', '[{\"label\":\"Bom Estimates\",\"sub_field\":\"bom_estimates\"},{\"label\":\"Supply Of Labor\",\"sub_field\":\"supply_of_labor\"},{\"label\":\"Assistance Only\",\"sub_field\":\"assistance_only\"}]', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('691', 'Testtt', NULL, NULL, NULL, 'location_building_office', 'Location Building Office', 'input', '0', '0', '12', 'requestor', '', '0', '1', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('692', 'Testtt', NULL, NULL, NULL, 'requisition_no', 'Requisition No', 'input', '0', '0', '12', 'requestor', '', '0', '2', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('693', 'Testtt', NULL, NULL, NULL, 'nature', 'Nature', 'checkboxes', '0', '0', '12', 'requestor', '', '0', '3', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '[{\"label\":\"Electrical\",\"sub_field\":\"electrical\"},{\"label\":\"Plumbing\",\"sub_field\":\"plumbing\"},{\"label\":\"Carpentry And Masonry\",\"sub_field\":\"carpentry_and_masonry\"},{\"label\":\"Painting Hauling Houskeeping\",\"sub_field\":\"painting_hauling_houskeeping\"}]', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('694', 'Testtt', NULL, NULL, NULL, 'repair', 'Repair', 'checkboxes', '0', '0', '12', 'requestor', '', '0', '4', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '[{\"label\":\"Lightings And Switches\",\"sub_field\":\"lightings_and_switches\"},{\"label\":\"Fixtures\",\"sub_field\":\"fixtures\"},{\"label\":\"Furniture\",\"sub_field\":\"furniture\"},{\"label\":\"Outlets\",\"sub_field\":\"outlets\"},{\"label\":\"Pipelines\",\"sub_field\":\"pipelines\"},{\"label\":\"Walls And Partitions\",\"sub_field\":\"walls_and_partitions\"},{\"label\":\"Fans\",\"sub_field\":\"fans\"},{\"label\":\"Sanitary Drainage\",\"sub_field\":\"sanitary_drainage\"},{\"label\":\"Ceilings\",\"sub_field\":\"ceilings\"},{\"label\":\"Aircons\",\"sub_field\":\"aircons\"},{\"label\":\"Water Pumps Sources\",\"sub_field\":\"water_pumps_sources\"},{\"label\":\"Trusses And Roofs\",\"sub_field\":\"trusses_and_roofs\"},{\"label\":\"Electrical Lines\",\"sub_field\":\"electrical_lines\"},{\"label\":\"Open Closed Canals\",\"sub_field\":\"open_closed_canals\"},{\"label\":\"Floors And Slabs\",\"sub_field\":\"floors_and_slabs\"}]', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('695', 'Testtt', NULL, NULL, NULL, 'other', 'Other', 'checkboxes', '0', '0', '12', 'requestor', '', '0', '5', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '[{\"label\":\"Painting\",\"sub_field\":\"painting\"},{\"label\":\"Termite Proofing\",\"sub_field\":\"termite_proofing\"},{\"label\":\"Hauling\",\"sub_field\":\"hauling\"},{\"label\":\"Grasscutting Cleaning\",\"sub_field\":\"grasscutting_cleaning\"},{\"label\":\"Clearing Demolition\",\"sub_field\":\"clearing_demolition\"}]', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('696', 'Testtt', NULL, NULL, NULL, 'others_specify', 'Others  Specify ', 'input', '0', '0', '12', 'requestor', '', '0', '6', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('697', 'Testtt', NULL, NULL, NULL, 'requested_by', 'Requested By', 'input', '0', '0', '12', 'requestor', '', '0', '7', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('698', 'Testtt', NULL, NULL, NULL, 'received_by', 'Received By', 'input', '0', '0', '12', 'requestor', '', '0', '8', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('699', 'Testtt', NULL, NULL, NULL, 'requested_by_date', 'Requested By Date', 'datepicker', '0', '0', '12', 'requestor', '', '0', '9', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('700', 'Testtt', NULL, NULL, NULL, 'received_by_date', 'Received By Date', 'datepicker', '0', '0', '12', 'requestor', '', '0', '10', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('701', 'Testtt', NULL, NULL, NULL, 'date_inspected', 'Date Inspected', 'datepicker', '0', '0', '12', 'requestor', '', '0', '11', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('702', 'Testtt', NULL, NULL, NULL, 'inspected_by', 'Inspected By', 'input', '0', '0', '12', 'requestor', '', '0', '12', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '', '1');
INSERT INTO `dbpanel` (`id`, `panel_name`, `form_name`, `department_id`, `office_id`, `field_name`, `field_label`, `field_type`, `bump_next_field`, `required`, `width`, `field_role`, `code_table`, `length`, `field_order`, `created_at`, `updated_at`, `default_value`, `is_active`) VALUES ('703', 'Testtt', NULL, NULL, NULL, 'services', 'Services', 'checkboxes', '0', '0', '12', 'requestor', '', '0', '13', '2025-11-10 21:19:26', '2025-11-10 21:19:26', '[{\"label\":\"Bom Estimates\",\"sub_field\":\"bom_estimates\"},{\"label\":\"Supply Of Labor\",\"sub_field\":\"supply_of_labor\"},{\"label\":\"Assistance Only\",\"sub_field\":\"assistance_only\"}]', '1');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_signatories`
--

INSERT INTO `form_signatories` (`id`, `form_id`, `user_id`, `order_position`, `created_at`, `updated_at`) VALUES ('2', '1', '4', '1', '2025-09-08 02:03:33', '2025-09-08 02:03:33');
INSERT INTO `form_signatories` (`id`, `form_id`, `user_id`, `order_position`, `created_at`, `updated_at`) VALUES ('3', '1', '2', '2', '2025-11-12 22:11:29', '2025-11-12 22:11:29');
INSERT INTO `form_signatories` (`id`, `form_id`, `user_id`, `order_position`, `created_at`, `updated_at`) VALUES ('4', '1', '9', '3', '2025-11-12 22:53:14', '2025-11-12 22:53:14');

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
) ENGINE=InnoDB AUTO_INCREMENT=398 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('288', '30', 'date_of_request', '2025-09-24', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('289', '30', 'request_no', '1', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('290', '30', 'requested_by', '2', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('291', '30', 'department', '3', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('292', '30', 'date_received', '2025-09-25', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('293', '30', 'date_acted', '2025-09-18', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('294', '30', 'types_of_equipment', '4', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('295', '30', 'date_of_last_repair', '2025-09-05', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('296', '30', 'nature_of_last_repair', '5', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('297', '30', 'under_warranty', '[\"yes\",\"no\"]', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('298', '30', 'description_of_property', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('299', '30', 'problems_encountered', '7', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('300', '30', 'action_taken', '8', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('301', '30', 'comments', '9', '2025-11-12 21:10:29', '2025-11-12 21:10:29');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('302', '31', 'date_of_request', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('303', '31', 'request_no', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('304', '31', 'requested_by', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('305', '31', 'department', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('306', '31', 'date_received', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('307', '31', 'date_acted', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('308', '31', 'types_of_equipment', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('309', '31', 'date_of_last_repair', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('310', '31', 'nature_of_last_repair', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('311', '31', 'under_warranty', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('312', '31', 'description_of_property', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('313', '31', 'problems_encountered', 'Test', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('314', '31', 'action_taken', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('315', '31', 'comments', '', '2025-11-12 22:12:15', '2025-11-12 22:12:15');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('316', '30', 'priority_level', 'medium', '2025-11-18 21:17:40', '2025-11-18 21:17:40');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('317', '29', 'priority_level', 'medium', '2025-11-18 21:17:40', '2025-11-18 21:17:40');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('318', '28', 'priority_level', 'medium', '2025-11-18 21:17:40', '2025-11-18 21:17:40');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('319', '27', 'priority_level', 'medium', '2025-11-18 21:17:40', '2025-11-18 21:17:40');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('320', '26', 'priority_level', 'medium', '2025-11-18 21:17:40', '2025-11-18 21:17:40');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('321', '25', 'priority_level', 'medium', '2025-11-18 21:17:40', '2025-11-18 21:17:40');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('322', '22', 'priority_level', 'medium', '2025-11-18 21:17:40', '2025-11-18 21:17:40');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('323', '21', 'priority_level', 'medium', '2025-11-18 21:17:40', '2025-11-18 21:17:40');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('324', '31', 'priority_level', 'medium', '2025-11-18 21:18:17', '2025-11-18 21:18:17');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('325', '32', 'date_of_request', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('326', '32', 'request_no', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('327', '32', 'requested_by', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('328', '32', 'department', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('329', '32', 'date_received', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('330', '32', 'date_acted', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('331', '32', 'types_of_equipment', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('332', '32', 'date_of_last_repair', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('333', '32', 'nature_of_last_repair', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('334', '32', 'under_warranty', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('335', '32', 'description_of_property', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('336', '32', 'problems_encountered', ' ', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('337', '32', 'action_taken', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('338', '32', 'comments', '', '2025-11-23 21:36:02', '2025-11-23 21:36:02');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('339', '33', 'date_of_request', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('340', '33', 'request_no', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('341', '33', 'requested_by', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('342', '33', 'department', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('343', '33', 'date_received', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('344', '33', 'date_acted', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('345', '33', 'types_of_equipment', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('346', '33', 'date_of_last_repair', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('347', '33', 'nature_of_last_repair', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('348', '33', 'under_warranty', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('349', '33', 'description_of_property', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('350', '33', 'problems_encountered', 'Test', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('351', '33', 'action_taken', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('352', '33', 'comments', '', '2025-11-23 21:37:00', '2025-11-23 21:37:00');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('353', '34', 'date_of_request', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('354', '34', 'request_no', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('355', '34', 'requested_by', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('356', '34', 'department', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('357', '34', 'date_received', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('358', '34', 'date_acted', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('359', '34', 'types_of_equipment', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('360', '34', 'date_of_last_repair', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('361', '34', 'nature_of_last_repair', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('362', '34', 'under_warranty', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('363', '34', 'description_of_property', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('364', '34', 'problems_encountered', ' Tezt', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('365', '34', 'action_taken', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('366', '34', 'comments', '', '2025-11-23 21:42:51', '2025-11-23 21:42:51');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('367', '35', 'date_of_request', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('368', '35', 'request_no', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('369', '35', 'requested_by', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('370', '35', 'department', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('371', '35', 'date_received', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('372', '35', 'date_acted', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('373', '35', 'types_of_equipment', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('374', '35', 'date_of_last_repair', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('375', '35', 'nature_of_last_repair', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('376', '35', 'under_warranty', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('377', '35', 'description_of_property', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('378', '35', 'problems_encountered', 'Terst', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('379', '35', 'action_taken', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('380', '35', 'comments', '', '2025-11-23 22:08:11', '2025-11-23 22:08:11');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('381', '36', 'date_of_request', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('382', '36', 'request_no', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('383', '36', 'requested_by', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('384', '36', 'department', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('385', '36', 'date_received', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('386', '36', 'date_acted', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('387', '36', 'types_of_equipment', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('388', '36', 'date_of_last_repair', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('389', '36', 'nature_of_last_repair', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('390', '36', 'under_warranty', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('391', '36', 'description_of_property', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('392', '36', 'problems_encountered', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('393', '36', 'action_taken', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('394', '36', 'comments', '', '2025-11-23 22:27:30', '2025-11-23 22:27:30');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('395', '33', 'priority_level', 'low', '2025-11-24 02:36:08', '2025-11-24 02:36:08');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('396', '34', 'priority_level', 'low', '2025-11-24 17:49:28', '2025-11-24 17:49:28');
INSERT INTO `form_submission_data` (`id`, `submission_id`, `field_name`, `field_value`, `created_at`, `updated_at`) VALUES ('397', '35', 'priority_level', 'low', '2025-11-24 17:50:11', '2025-11-24 17:50:11');

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
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_submissions`
--

INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('19', '1', 'CRSRF_3', '3', 'completed', '4', '2025-10-06 21:40:32', 'Test', NULL, '1', '5', '2025-10-06 21:41:03', '', '2025-10-06 21:41:03', '0', NULL, 'normal', NULL, NULL, '2025-10-06 21:40:32', NULL, '2025-10-06 20:47:42', '2025-10-06 21:41:03');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('20', '1', '', '3', 'pending_service', '4', '2025-10-09 23:27:08', 'TEst', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-09 23:27:08', NULL, '2025-10-06 21:35:20', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('21', '1', '', '3', 'pending_service', '4', '2025-10-10 00:22:52', '', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-10 00:22:52', NULL, '2025-10-07 22:06:23', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('22', '1', '', '3', 'pending_service', '4', '2025-10-10 00:32:48', '', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-10 00:32:48', NULL, '2025-10-07 22:35:49', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('23', '1', '', '3', 'pending_service', '4', '2025-10-09 23:41:49', 'Test', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-09 23:41:49', NULL, '2025-10-07 22:36:16', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('24', '1', '', '3', 'pending_service', '4', '2025-10-09 23:26:24', 'Auto-approved with service staff assignment', NULL, '0', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-09 23:26:24', NULL, '2025-10-09 23:25:52', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('25', '1', '', '3', 'pending_service', '4', '2025-10-10 00:33:39', 'Auto-approved with service staff assignment', NULL, '0', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-10 00:33:39', NULL, '2025-10-10 00:33:24', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('26', '1', '', '3', 'pending_service', '4', '2025-10-10 00:37:35', '', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-10 00:37:35', NULL, '2025-10-10 00:34:22', '2025-10-27 23:36:44');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('27', '1', '', '3', 'cancelled', '4', '2025-10-10 00:47:29', '', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-10-10 00:47:29', NULL, '2025-10-10 00:40:14', '2025-11-25 11:24:01');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('28', '1', '', '3', 'completed', '4', '2025-11-17 15:33:01', 'Test', NULL, '1', '5', '2025-11-17 15:34:19', '', '2025-11-17 15:34:19', '0', '2025-11-17 15:34:19', 'low', NULL, NULL, '2025-11-17 15:33:01', NULL, '2025-10-10 00:43:31', '2025-11-17 15:34:19');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('29', '1', '', '3', 'pending_service', '4', '2025-11-18 20:36:29', 'Test', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-11-18 20:36:29', NULL, '2025-10-10 00:47:54', '2025-11-18 20:36:34');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('30', '1', 'Test', '3', 'completed', '4', '2025-11-12 21:11:54', '', NULL, '1', '5', '2025-11-17 15:30:13', '', '2025-11-17 15:30:13', '0', '2025-11-17 15:30:13', 'low', NULL, NULL, '2025-11-12 21:11:54', NULL, '2025-11-12 21:10:25', '2025-11-17 15:30:13');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('31', '1', 'Test', '3', 'completed', '4', '2025-11-18 21:18:02', 'Test', NULL, '1', '5', '2025-11-24 14:49:21', '', NULL, '1', '2025-11-24 14:49:21', 'low', NULL, NULL, '2025-11-18 21:18:02', NULL, '2025-11-12 22:12:03', '2025-11-24 14:49:21');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('32', '1', 'Test', '3', 'pending_service', NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, NULL, NULL, '2025-11-23 21:35:44', '2025-11-24 01:19:01');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('33', '1', 'Test', '3', 'completed', '4', '2025-11-24 02:35:47', 'Test', NULL, '1', '5', '2025-11-24 14:26:29', '', '2025-11-24 14:26:29', '0', '2025-11-24 14:26:29', 'low', NULL, NULL, '2025-11-24 02:35:47', NULL, '2025-11-23 21:36:47', '2025-11-24 14:26:29');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('34', '1', 'Test', '3', 'pending_service', '4', '2025-11-24 17:49:13', 'Test', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-11-24 17:49:13', NULL, '2025-11-23 21:42:37', '2025-11-24 17:49:24');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('35', '1', 'Test', '3', 'pending_service', '4', '2025-11-24 17:49:50', '', NULL, '1', '5', NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, '2025-11-24 17:49:50', NULL, '2025-11-23 22:07:57', '2025-11-24 17:50:01');
INSERT INTO `form_submissions` (`id`, `form_id`, `panel_name`, `submitted_by`, `status`, `approver_id`, `approved_at`, `approval_comments`, `rejected_reason`, `signature_applied`, `service_staff_id`, `service_staff_signature_date`, `service_notes`, `requestor_signature_date`, `completed`, `completion_date`, `priority`, `reference_file`, `reference_file_original`, `approver_signature_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES ('36', '1', 'Test', '3', 'submitted', NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, '0', NULL, 'low', NULL, NULL, NULL, NULL, '2025-11-23 22:27:15', '2025-11-23 22:27:15');

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
  `header_image` varchar(255) DEFAULT NULL,
  `revision_no` varchar(20) DEFAULT '00',
  `effectivity_date` date DEFAULT NULL,
  `dco_approved` tinyint(1) DEFAULT 0,
  `dco_approved_by` int(11) unsigned DEFAULT NULL,
  `dco_approved_at` datetime DEFAULT NULL,
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

INSERT INTO `forms` (`id`, `code`, `description`, `office_id`, `department_id`, `panel_name`, `header_image`, `revision_no`, `effectivity_date`, `dco_approved`, `dco_approved_by`, `dco_approved_at`, `created_at`, `updated_at`) VALUES ('1', 'CRSRF', 'Computer Repair Service Request Forms', '1', '12', 'Test', 'TAU-header.png', '00', NULL, '0', NULL, NULL, '2025-03-25 13:15:12', '2025-12-27 18:19:55');
INSERT INTO `forms` (`id`, `code`, `description`, `office_id`, `department_id`, `panel_name`, `header_image`, `revision_no`, `effectivity_date`, `dco_approved`, `dco_approved_by`, `dco_approved_at`, `created_at`, `updated_at`) VALUES ('8', 'FORM1', 'Test', '2', '19', NULL, 'TAU-header.png', '00', NULL, '0', NULL, NULL, '2025-10-06 22:11:56', '2025-10-06 22:12:06');
INSERT INTO `forms` (`id`, `code`, `description`, `office_id`, `department_id`, `panel_name`, `header_image`, `revision_no`, `effectivity_date`, `dco_approved`, `dco_approved_by`, `dco_approved_at`, `created_at`, `updated_at`) VALUES ('9', 'FORM2123', 'Test', NULL, '22', NULL, 'TAU-header.png', '00', NULL, '0', NULL, NULL, '2025-10-23 00:13:42', '2025-10-23 00:13:42');

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
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('55', '2025-11-10-130700', 'App\\Database\\Migrations\\ChangeDefaultValueToText', 'default', 'App', '1762780572', '15');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('56', '2025-11-25-000001', 'App\\Database\\Migrations\\DedupeSchedulesAndAddUniqueConstraint', 'default', 'App', '1764598997', '16');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('57', '2025-12-01-000001', 'App\\Database\\Migrations\\AddHeaderImageToForms', 'default', 'App', '1764598997', '16');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('58', '2025-12-01-000001', 'App\\Database\\Migrations\\AddPanelActiveStatus', 'default', 'App', '1764598997', '16');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('59', '2025-12-03-000001', 'App\\Database\\Migrations\\AddDcoFieldsToForms', 'default', 'App', '1764762604', '17');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('60', '2025-12-08-000001', 'App\\Database\\Migrations\\CreateAuditLogsTable', 'default', 'App', '1765722273', '18');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('61', '2025-12-08-000002', 'App\\Database\\Migrations\\CreateStaffAvailabilityTable', 'default', 'App', '1765722273', '18');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('62', '2025-12-27-132211', 'App\\Database\\Migrations\\AddTauDcoUserType', 'default', 'App', '1766841756', '19');

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
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('49', '4', '30', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by a user and requires your approval.', '1', '2025-11-12 21:10:25');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('50', '3', '30', 'Request Approved', 'Your service request has been approved and will be scheduled.', '0', '2025-11-12 21:11:54');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('51', '5', '30', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-11-12 21:11:57');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('52', '4', '31', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-12 22:12:03');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('53', '2', '31', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '1', '2025-11-12 22:12:07');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('54', '1', '31', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-12 22:12:11');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('55', '3', '28', 'Request Approved', 'Your service request has been approved and will be scheduled.', '0', '2025-11-17 15:33:01');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('56', '5', '28', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-11-17 15:33:04');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('57', '3', '29', 'Request Approved', 'Your service request has been approved and will be scheduled.', '0', '2025-11-18 20:36:29');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('58', '5', '29', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-11-18 20:36:34');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('59', '3', '31', 'Request Approved', 'Your service request has been approved and will be scheduled.', '0', '2025-11-18 21:18:02');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('60', '5', '31', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-11-18 21:18:06');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('61', '4', '32', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:35:44');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('62', '2', '32', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:35:49');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('63', '9', '32', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:35:54');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('64', '1', '32', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:35:58');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('65', '4', '33', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:36:47');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('66', '2', '33', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:36:50');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('67', '9', '33', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '1', '2025-11-23 21:36:54');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('68', '1', '33', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:36:57');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('69', '4', '34', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:42:37');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('70', '2', '34', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:42:40');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('71', '9', '34', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:42:45');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('72', '1', '34', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 21:42:48');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('73', '4', '35', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 22:07:57');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('74', '2', '35', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '1', '2025-11-23 22:08:01');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('75', '9', '35', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '1', '2025-11-23 22:08:04');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('76', '1', '35', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 22:08:08');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('77', '4', '36', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 22:27:15');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('78', '2', '36', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '1', '2025-11-23 22:27:19');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('79', '9', '36', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 22:27:23');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('80', '1', '36', 'New Service Request Requires Approval', 'A new CRSRF request has been submitted by Requestor User and requires approval.', '0', '2025-11-23 22:27:26');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('81', '3', '33', 'Request Approved', 'Your service request has been approved and will be scheduled.', '0', '2025-11-24 02:35:47');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('82', '5', '33', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-11-24 02:35:52');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('83', '3', '31', 'Service Completed', 'Your service request has been completed successfully. You can now provide feedback about your experience.', '0', '2025-11-24 14:49:21');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('84', '4', '31', 'Request Completed', 'Submission #31 has been completed by service staff.', '0', '2025-11-24 14:49:26');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('85', '9', '31', 'Request Completed', 'Submission #31 has been completed.', '0', '2025-11-24 14:49:29');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('86', '5', '31', 'Request Completed', 'You marked Submission #31 as completed.', '0', '2025-11-24 14:49:33');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('87', '3', '34', 'Request Approved', 'Your service request has been approved and will be scheduled.', '0', '2025-11-24 17:49:13');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('88', '1', '34', 'Request Approved', 'Submission #34 has been approved.', '0', '2025-11-24 17:49:17');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('89', '2', '34', 'Request Approved', 'Submission #34 has been approved.', '0', '2025-11-24 17:49:21');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('90', '5', '34', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-11-24 17:49:24');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('91', '3', '35', 'Request Approved', 'Your service request has been approved and will be scheduled.', '0', '2025-11-24 17:49:50');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('92', '1', '35', 'Request Approved', 'Submission #35 has been approved.', '0', '2025-11-24 17:49:54');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('93', '2', '35', 'Request Approved', 'Submission #35 has been approved.', '1', '2025-11-24 17:49:58');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('94', '5', '35', 'New Service Assignment', 'You have been assigned to process a CRSRF service request. Please review and complete the service.', '0', '2025-11-24 17:50:01');
INSERT INTO `notifications` (`id`, `user_id`, `submission_id`, `title`, `message`, `read`, `created_at`) VALUES ('95', '4', '27', 'Request Cancelled', 'A service request (CRSRF) has been cancelled by the requestor.', '0', '2025-11-25 11:24:01');

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

INSERT INTO `offices` (`id`, `code`, `description`, `department_id`, `active`, `created_at`, `updated_at`) VALUES ('1', 'ADM', 'Administration Office', '12', '1', '2025-08-12 15:07:01', '2025-09-07 21:43:37');
INSERT INTO `offices` (`id`, `code`, `description`, `department_id`, `active`, `created_at`, `updated_at`) VALUES ('2', 'IT', 'Information Technology Office', '19', '1', '2025-08-12 15:07:01', '2025-09-09 23:47:32');
INSERT INTO `offices` (`id`, `code`, `description`, `department_id`, `active`, `created_at`, `updated_at`) VALUES ('3', 'HR', 'Human Resources Office', '12', '1', '2025-08-12 15:07:01', '2025-09-08 03:17:50');
INSERT INTO `offices` (`id`, `code`, `description`, `department_id`, `active`, `created_at`, `updated_at`) VALUES ('4', 'FIN', 'Finance Office', '12', '1', '2025-08-12 15:07:01', '2025-09-07 21:43:37');

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
  UNIQUE KEY `uk_schedules_submission_id` (`submission_id`),
  KEY `submission_id` (`submission_id`),
  KEY `assigned_staff_id` (`assigned_staff_id`),
  KEY `scheduled_date_scheduled_time` (`scheduled_date`,`scheduled_time`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('3', '19', '2025-10-06', '09:00:00', '60', '', 'Auto-created schedule on submit', 'completed', '5', NULL, '0', '2025-10-06 20:47:42', '2025-10-26 23:21:57', '5', '2025-10-11', 'medium', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('4', '20', '2025-10-09', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', '5', NULL, '0', '2025-10-06 21:35:20', '2025-10-27 21:32:37', '7', '2025-10-16', 'low', '1');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('5', '21', '2025-10-10', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', '5', NULL, '0', '2025-10-07 22:06:24', '2025-11-24 01:19:16', '3', '2025-10-15', 'high', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('6', '22', '2025-10-10', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', '5', NULL, '0', '2025-10-07 22:35:49', '2025-10-27 21:05:07', '7', '2025-10-17', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('7', '23', '2025-10-09', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', '5', NULL, '0', '2025-10-07 22:36:16', '2025-10-07 22:36:16', '7', '2025-10-16', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('8', '24', '2025-10-09', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', '5', NULL, '0', '2025-10-09 23:25:52', '2025-10-09 23:25:52', '7', '2025-10-16', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('9', '25', '2025-10-10', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', '5', NULL, '0', '2025-10-10 00:33:24', '2025-10-10 00:33:24', '7', '2025-10-17', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('10', '26', '2025-10-10', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', '5', NULL, '0', '2025-10-10 00:34:22', '2025-10-10 00:34:22', '7', '2025-10-17', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('11', '27', '2025-10-10', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', '5', NULL, '0', '2025-10-10 00:40:14', '2025-10-10 00:40:14', '7', '2025-10-17', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('12', '29', '2025-11-18', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', '5', NULL, '0', '2025-10-10 00:47:54', '2025-11-24 01:18:41', '5', '2025-11-25', 'medium', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('13', '30', '2025-11-19', '00:05:00', '60', '', 'Auto-created schedule on submit', 'completed', '5', NULL, '0', '2025-11-12 21:10:29', '2025-11-24 02:22:51', '3', '2025-11-24', 'high', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('15', '28', '2025-11-17', '09:04:00', '60', '', 'Auto-created schedule on approval via signForm', 'completed', '5', NULL, '0', '2025-11-17 15:33:08', '2025-11-24 21:45:16', '3', '2025-11-20', 'high', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('16', '32', '2025-11-23', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-11-23 21:36:02', '2025-11-24 01:19:01', '5', '2025-11-28', 'medium', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('18', '34', '2025-11-24', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-11-23 21:42:51', '2025-11-24 17:49:24', '7', '2025-12-01', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('19', '35', '2025-11-24', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-11-23 22:08:11', '2025-11-24 17:50:01', '7', '2025-12-01', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('20', '36', '2025-11-23', '09:00:00', '60', '', 'Auto-created schedule on submit', 'pending', NULL, NULL, '0', '2025-11-23 22:27:30', '2025-11-23 22:27:30', '7', '2025-11-30', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('21', '31', '2025-11-30', '09:04:00', '60', '', 'Created from calendar', 'confirmed', '5', NULL, '0', '2025-11-24 00:29:19', '2025-11-24 02:34:31', '7', '2025-12-07', 'low', '0');
INSERT INTO `schedules` (`id`, `submission_id`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `location`, `notes`, `status`, `assigned_staff_id`, `completion_notes`, `priority`, `created_at`, `updated_at`, `eta_days`, `estimated_date`, `priority_level`, `is_manual_schedule`) VALUES ('22', '33', '2025-11-28', '09:04:00', '60', '', 'Created from calendar', 'confirmed', '5', NULL, '0', '2025-11-24 02:36:19', '2025-11-24 02:39:14', '0', '2025-11-28', 'medium', '1');

--
-- Structure for table `staff_availability`
--

DROP TABLE IF EXISTS `staff_availability`;
CREATE TABLE `staff_availability` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) unsigned NOT NULL,
  `date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `availability_type` enum('available','busy','leave','holiday') NOT NULL DEFAULT 'available',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `date` (`date`),
  KEY `idx_staff_date` (`staff_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `user_type` enum('admin','requestor','approving_authority','service_staff','superuser','department_admin','tau_dco') DEFAULT 'requestor',
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('1', 'chesspiecedum2+user1@gmail.com', 'admin', '$2y$10$Zp8lR0APZb52eiNkuOPobOtI4ndKpBUpbvVmKCJNCIj40jfex4dIa', 'System Administrator', NULL, '1', 'superuser', NULL, NULL, '1', NULL, NULL, '2025-03-25 13:12:44', '2025-03-25 13:12:44', NULL);
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('2', 'chesspiecedum2+admin@gmail.com', 'admin_user', '$2y$10$hwHypfnPwSifjiVAQLu9N.FlbafkczravEwE/6WFvhlIKzgEdmgmG', 'Admin User', '12', NULL, 'admin', 'uploads/signatures/1762956782_28fa73e97230e682f8d3.jpg', NULL, '1', NULL, NULL, '2025-03-25 13:12:44', '2026-01-04 21:55:33', '2026-01-04 21:55:33');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('3', 'chesspiecedum2+user3@gmail.com', 'requestor_user', '$2y$10$SR.JDa3J7sJKUHGFCdtuBuK91hdhwqcF.0geYeUhdS4Z9GGMySICe', 'Requestor User', '12', NULL, 'requestor', 'uploads/signatures/1757156429_f64ca1641555c24e9597.jpg', NULL, '1', NULL, NULL, '2025-03-25 13:12:44', '2025-11-25 11:23:34', '2025-11-25 11:23:34');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('4', 'chesspiecedum2+user4@gmail.com', 'approver_user', '$2y$10$UwETgMzxBL8nYWiOTSgXMOq8or7bu/qivp3KWR4Yv.8yJF/hPwZz2', 'Approving Authority User', NULL, '3', 'approving_authority', 'uploads/signatures/1745679693_fe4b03e6434b1e2f310b.png', NULL, '1', NULL, NULL, '2025-03-25 13:12:44', '2025-11-24 23:09:01', '2025-11-24 23:09:01');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('5', 'chesspiecedum2+user5@gmail.com', 'service_user', '$2y$10$pmOycYaryl2aXRquiYbRsOHiXqnQxvLjK7IFBwTCuACBsGXTsKwAi', 'Service Staff User', '25', '4', 'service_staff', 'uploads/signatures/1757173058_44990d47bd91fa7802f6.png', NULL, '1', NULL, NULL, '2025-03-25 13:12:44', '2025-11-25 09:31:01', '2025-11-25 09:31:01');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('7', 'chesspiecedum2+user7@gmail.com', 'player1', '$2y$10$lZQIuNBVVr6x.GzTwmSi5.bnOOSwgLtSlSA4ITezNvOSf9.8AqbVS', 'Ralph Jayson E Diaz', '24', NULL, 'requestor', NULL, NULL, '1', NULL, NULL, '2025-10-09 23:23:21', '2025-10-09 23:23:28', '2025-10-09 23:23:28');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('8', 'chesspiecedum2+user8@gmail.com', 'Susedge', '$2y$10$Gdzdu2vmlvxb5xFO9hp2wOELBUPM2ovR0b7EEtGfpzsRm4BzqoNLy', 'Ralph Jayson Diaz', '25', '1', 'requestor', NULL, NULL, '1', NULL, NULL, '2025-10-10 00:18:13', '2025-10-10 00:18:23', '2025-10-10 00:18:23');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('9', 'chesspiecedum2+user9@gmail.com', 'dept_admin_it', '$2y$10$7LNiEyDlJDD8PVLw.KWlo.zNynv0AoES4IdhcABHWPJ59yFwagWOm', 'IT Department Admin', '12', '2', 'department_admin', NULL, NULL, '1', NULL, NULL, '2025-10-22 12:53:42', '2025-11-24 21:36:51', '2025-11-24 21:36:51');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('10', 'chesspiecedum2+user10@gmail.com', 'it_requestor_1', '$2y$10$icA3p9/0LzAUh6oUNKwkju3c/S.ZXp/FbZLJ5uF7NeF.ZzbDa1W7.', 'IT Requestor One', '22', '2', 'requestor', NULL, NULL, '1', NULL, NULL, '2025-10-22 14:11:26', '2025-10-22 14:11:26', NULL);
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('11', 'chesspiecedum2+user11@gmail.com', 'it_requestor_2', '$2y$10$Zqc8uZavgvZ8801DSAE0ROKnAvXy0n/dlr2FUoD9Wgz4XF35QAinG', 'IT Requestor Two', '22', '2', 'requestor', NULL, NULL, '1', NULL, NULL, '2025-10-22 14:11:27', '2025-10-22 14:11:27', NULL);
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('12', 'chesspiecedum2+user12@gmail.com', 'it_approver', '$2y$10$zBUv5g3mnjlGDa0K5D1pweAMdKcavNvOrWJea6Sp/vR82lhc/2wri', 'IT Approving Authority', '22', '2', 'approving_authority', NULL, NULL, '1', NULL, NULL, '2025-10-22 14:11:27', '2025-10-22 14:11:27', NULL);
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('13', 'chesspiecedum2+user13@gmail.com', 'it_service', '$2y$10$OfVLFblBVPsqvLNDC3a0HutZO1p21PaisD/psLq7ss0P/2EbjRI7K', 'IT Service Staff', '22', '2', 'service_staff', NULL, NULL, '1', NULL, NULL, '2025-10-22 14:11:27', '2025-10-22 14:11:27', NULL);
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('14', 'dco@tau.edu.ph', 'tau_dco_user', '$2y$10$r9qfjbOwDqQJU.3YE2MtNeJOVvrsaTIECWZz2JjCYhCKjtvBzPq22', 'TAU DCO Officer', NULL, NULL, 'tau_dco', NULL, NULL, '1', NULL, NULL, '2025-12-03 11:50:12', '2025-12-14 22:24:49', '2025-12-14 22:24:49');
INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `full_name`, `department_id`, `office_id`, `user_type`, `signature`, `profile_image`, `active`, `reset_token`, `reset_expires`, `created_at`, `updated_at`, `last_login`) VALUES ('15', 'chesspiecedum1@gmail.com', 'DCO user', '$2y$10$rSjZLB8NK/OvAZIbxWTZjugdL0zo5ihchhTS4.M.JePjv9Y7SY07W', 'DCO user', NULL, NULL, 'tau_dco', NULL, NULL, '1', NULL, NULL, '2025-12-27 21:17:24', '2025-12-27 21:20:00', '2025-12-27 21:20:00');

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
