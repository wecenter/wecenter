ALTER TABLE `aws_article` ADD `chapter_id` int(10) DEFAULT NULL, ADD INDEX (`chapter_id`);
ALTER TABLE `aws_article` ADD `sort` tinyint(2) UNSIGNED NOT NULL DEFAULT '0', ADD INDEX (`sort`);

ALTER TABLE `aws_question` ADD `chapter_id` int(10) DEFAULT NULL, ADD INDEX (`chapter_id`);
ALTER TABLE `aws_question` ADD `sort` tinyint(2) UNSIGNED NOT NULL DEFAULT '0', ADD INDEX (`sort`);

CREATE TABLE `aws_chapter` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `url_token` varchar(32) DEFAULT NULL,
  `sort` tinyint(2) UNSIGNED NOT NULL DEFAULT '0'
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `url_token` (`url_token`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
