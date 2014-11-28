CREATE TABLE `[#DB_PREFIX#]ticket` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `priority` enum('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
  `status` enum('pending', 'closed', 'undefined') NOT NULL DEFAULT 'pending',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `message` text,
  `time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `rating` enum('bad', 'good', 'undefined') NOT NULL DEFAULT 'undefined',
  `service` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ip` bigint(11) UNSIGNED DEFAULT NULL,
  `question_id` int(10) UNSIGNED DEFAULT NULL,
  `weibo_msg_id` bigint(20) UNSIGNED DEFAULT NULL,
  `received_email_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]ticket_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `note` varchar(255) NOT NULL,
  `time` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;
