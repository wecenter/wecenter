CREATE TABLE `[#DB_PREFIX#]ticket` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `priority` enum('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
  `status` enum('pending', 'closed') NOT NULL DEFAULT 'pending',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `message` text,
  `time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `rating` enum('valid', 'invalid', 'undefined') NOT NULL DEFAULT 'undefined',
  `service` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ip` bigint(11) UNSIGNED DEFAULT NULL,
  `question_id` int(10) UNSIGNED DEFAULT NULL,
  `weibo_msg_id` bigint(20) UNSIGNED DEFAULT NULL,
  `received_email_id` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `priority` (`priority`),
  KEY `status` (`status`),
  KEY `uid` (`uid`),
  KEY `time` (`time`),
  KEY `rating` (`rating`),
  KEY `service` (`service`),
  KEY `question_id` (`question_id`),
  KEY `weibo_msg_id` (`weibo_msg_id`),
  KEY `received_email_id` (`received_email_id`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]ticket_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `action` varchar(255) NOT NULL,
  `data` text,
  `time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `uid` (`uid`),
  KEY `action` (`action`),
  KEY `time` (`time`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;
