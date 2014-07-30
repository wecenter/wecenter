INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('receiving_mail_config', 'a:6:{s:7:"enabled";s:1:"N";s:6:"server";s:0:"";s:3:"ssl";s:1:"N";s:4:"port";s:0:"";s:8:"username";s:0:"";s:8:"password";s:0:"";}');

CREATE TABLE `[#DB_PREFIX#]received_email` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) NOT NULL,
  `date` int(10) NOT NULL,
  `from` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text,
  `question_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`),
  KEY `date` (`date`),
  KEY `question_id` (`question_id`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8 COMMENT='已导入邮件列表';

ALTER TABLE `[#DB_PREFIX#]question` ADD `received_email_id` int(10) DEFAULT NULL;
CREATE INDEX `received_email_id` ON `[#DB_PREFIX#]question` (`received_email_id`);
