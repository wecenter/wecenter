ALTER TABLE `[#DB_PREFIX#]question` ADD  `last_answer` INT( 11 ) NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]attach` ADD KEY ( `is_image` );
ALTER TABLE `[#DB_PREFIX#]users` ADD KEY ( `last_active` );
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('logout_search_avail', 's:1:"N";');
UPDATE `[#DB_PREFIX#]notification` SET read_flag = 1 WHERE read_flag > 0;
ALTER TABLE `[#DB_PREFIX#]notification` CHANGE `read_flag` `read_flag` TINYINT( 1 ) NULL DEFAULT '0' COMMENT '阅读状态';
