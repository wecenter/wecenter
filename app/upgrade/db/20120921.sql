ALTER TABLE `[#DB_PREFIX#]topic_question` ADD INDEX (  `question_id` );
ALTER TABLE `[#DB_PREFIX#]topic_question` ADD INDEX (  `uid` );

DROP TABLE `[#DB_PREFIX#]topic_experience`;

ALTER TABLE `[#DB_PREFIX#]category` DROP `description`;

CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]favorite` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `answer_id` int(11) DEFAULT '0',
  `time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `answer_id` (`answer_id`),
  KEY `time` (`time`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]favorite_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `title` varchar(128) DEFAULT NULL,
  `answer_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

UPDATE `[#DB_PREFIX#]system_setting` SET `varname` = 'url_param_key' WHERE `varname` = 's:1:"2";';
