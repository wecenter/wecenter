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
  'source' enum('local', 'weibo', 'weixin', 'email') NOT NULL DEFAULT 'local',
  `question_id` int(10) UNSIGNED DEFAULT NULL,
  `weibo_msg_id` bigint(20) UNSIGNED DEFAULT NULL,
  `received_email_id` int(10) UNSIGNED DEFAULT NULL,
  `reply_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `priority` (`priority`),
  KEY `status` (`status`),
  KEY `uid` (`uid`),
  KEY `time` (`time`),
  KEY `rating` (`rating`),
  KEY `service` (`service`),
  KEY `source` (`source`),
  KEY `question_id` (`question_id`),
  KEY `weibo_msg_id` (`weibo_msg_id`),
  KEY `received_email_id` (`received_email_id`),
  KEY `reply_time` (`reply_time`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]ticket_reply` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `message` text,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ip` bigint(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `uid` (`uid`),
  KEY `time` (`time`)
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

CREATE TABLE `[#DB_PREFIX#]ticket_invite` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `sender_uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `recipient_uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `sender_uid` (`sender_uid`),
  KEY `recipient_uid` (`recipient_uid`),
  KEY `time` (`time`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

INSERT INTO `[#DB_PREFIX#]users_group` (`group_id`, `type`, `custom`, `group_name`, `reputation_lower`, `reputation_higer`, `reputation_factor`, `permission`) VALUES (10, 0, 0, '客服组', 0, 0, 4, 'a:15:{s:16:"publish_question";s:1:"1";s:21:"publish_approval_time";a:2:{s:5:"start";s:0:"";s:3:"end";s:0:"";}s:13:"edit_question";s:1:"1";s:10:"edit_topic";s:1:"1";s:12:"manage_topic";s:1:"1";s:12:"create_topic";s:1:"1";s:17:"redirect_question";s:1:"1";s:13:"upload_attach";s:1:"1";s:11:"publish_url";s:1:"1";s:15:"publish_article";s:1:"1";s:12:"edit_article";s:1:"1";s:19:"edit_question_topic";s:1:"1";s:15:"publish_comment";s:1:"1";s:10:"is_service";s:1:"1";s:14:"publish_ticket";s:1:"1";}');

UPDATE `[#DB_PREFIX#]users_group` SET `permission` = 'a:17:{s:16:"is_administortar";s:1:"1";s:12:"is_moderator";s:1:"1";s:16:"publish_question";s:1:"1";s:21:"publish_approval_time";a:2:{s:5:"start";s:0:"";s:3:"end";s:0:"";}s:13:"edit_question";s:1:"1";s:10:"edit_topic";s:1:"1";s:12:"manage_topic";s:1:"1";s:12:"create_topic";s:1:"1";s:17:"redirect_question";s:1:"1";s:13:"upload_attach";s:1:"1";s:11:"publish_url";s:1:"1";s:15:"publish_article";s:1:"1";s:12:"edit_article";s:1:"1";s:19:"edit_question_topic";s:1:"1";s:15:"publish_comment";s:1:"1";s:10:"is_service";s:1:"1";s:14:"publish_ticket";s:1:"1";}' WHERE `group_id` = 1;

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('ticket_enabled', 's:1:"N";');
