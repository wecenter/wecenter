ALTER TABLE `aws_article` ADD `chapter_id` int(10) DEFAULT NULL, ADD INDEX (`chapter_id`);
ALTER TABLE `aws_article` ADD `sort` tinyint(2) UNSIGNED NOT NULL DEFAULT '0', ADD INDEX (`sort`);

ALTER TABLE `aws_question` ADD `chapter_id` int(10) DEFAULT NULL, ADD INDEX (`chapter_id`);
ALTER TABLE `aws_question` ADD `sort` tinyint(2) UNSIGNED NOT NULL DEFAULT '0', ADD INDEX (`sort`);

CREATE TABLE `aws_chapter` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `url_token` varchar(32) DEFAULT NULL,
  `sort` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `url_token` (`url_token`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

UPDATE `aws_users_group` SET `permission` = 'a:9:{s:10:"visit_site";s:1:"1";s:13:"visit_explore";s:1:"1";s:12:"search_avail";s:1:"1";s:14:"visit_question";s:1:"1";s:11:"visit_topic";s:1:"1";s:13:"visit_feature";s:1:"1";s:12:"visit_people";s:1:"1";s:13:"visit_chapter";s:1:"1";s:11:"answer_show";s:1:"1";}' WHERE `group_id` = '99';
