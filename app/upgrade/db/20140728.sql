INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('receiving_email_global_config', 'a:2:{s:7:"enabled";s:1:"N";s:12:"publish_user";N;}');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('wecenter_mp_notification_once', 's:1:"Y";');

CREATE TABLE `[#DB_PREFIX#]receiving_email_config` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `protocol` varchar(10) NOT NULL,
  `server` varchar(255) NOT NULL,
  `ssl` tinyint(1) NOT NULL DEFAULT '0',
  `port` smallint(5) DEFAULT NULL,
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `uid` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `server` (`server`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8 COMMENT='邮件账号列表';

CREATE TABLE `[#DB_PREFIX#]received_email` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `config_id` int(10) NOT NULL,
  `message_id` varchar(255) NOT NULL,
  `date` int(10) NOT NULL,
  `from` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text,
  `question_id` int(11) DEFAULT NULL,
  `access_key` varchar(32) NOT NULL,
  `has_attach` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `config_id` (`config_id`),
  KEY `message_id` (`message_id`),
  KEY `date` (`date`),
  KEY `question_id` (`question_id`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8 COMMENT='已导入邮件列表';

ALTER TABLE `[#DB_PREFIX#]question` ADD `received_email_id` int(10) DEFAULT NULL;
CREATE INDEX `received_email_id` ON `[#DB_PREFIX#]question` (`received_email_id`);

ALTER TABLE `[#DB_PREFIX#]topic` ADD `discuss_count_last_week` INT( 10 ) NULL DEFAULT '0', ADD INDEX ( `discuss_count_last_week` );
ALTER TABLE `[#DB_PREFIX#]topic` ADD `discuss_count_last_month` INT( 10 ) NULL DEFAULT '0', ADD INDEX ( `discuss_count_last_month` );

ALTER TABLE `[#DB_PREFIX#]topic` ADD `discuss_count_update` INT( 10 ) NULL DEFAULT '0', ADD INDEX ( `discuss_count_update` );
