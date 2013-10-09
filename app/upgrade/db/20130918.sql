INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('new_question_force_add_topic', 's:1:"N";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('unfold_question_comments', 's:1:"N";');

CREATE TABLE `[#DB_PREFIX#]article` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text,
  `comments` int(10) DEFAULT '0',
  `views` int(10) DEFAULT '0',
  `add_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`,`comments`,`views`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;