ALTER TABLE `[#DB_PREFIX#]feature` ADD `seo_title` VARCHAR( 255 ) NULL;

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('question_helpful_users_limit', 's:1:"3";');