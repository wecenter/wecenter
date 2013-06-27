CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]weixin_reply_rule` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `image_file` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `description` text COLLATE utf8_unicode_ci,
  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `keyword` (`keyword`),
  KEY `enabled` (`enabled`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('auto_create_social_topics', 's:1:"N";');

ALTER TABLE `[#DB_PREFIX#]answer` ADD `publish_source` VARCHAR( 16 ) NULL, ADD INDEX ( `publish_source` );

CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]weixin_publish_rule` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `image_file` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `description` text COLLATE utf8_unicode_ci,
  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `topics` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `item_id` int(10) NOT NULL,
  `publish_type` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `keyword` (`keyword`),
  KEY `enabled` (`enabled`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;