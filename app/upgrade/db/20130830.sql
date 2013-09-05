ALTER TABLE `[#DB_PREFIX#]users` ADD `recent_topics` TEXT NULL;
ALTER TABLE `[#DB_PREFIX#]weixin_reply_rule` DROP `is_subscribe`;

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('weixin_subscribe_message_key', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('weixin_no_result_message_key', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('weixin_mp_menu', 'a:0:{}');

ALTER TABLE `[#DB_PREFIX#]weixin_reply_rule` ADD `sort_status` INT( 10 ) NULL DEFAULT '0', ADD INDEX ( `sort_status` ) ;