ALTER TABLE `[#DB_PREFIX#]users` ADD `common_email` VARCHAR( 255 ) NULL DEFAULT NULL COMMENT '常用邮箱';
ALTER TABLE `[#DB_PREFIX#]users` ADD `url_token` VARCHAR( 32 ) NULL DEFAULT NULL COMMENT '个性网址';
ALTER TABLE `[#DB_PREFIX#]users` ADD `url_token_update` INT( 10 ) NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]users` ADD INDEX (  `url_token` );

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('today_topics', 's:0:"";');