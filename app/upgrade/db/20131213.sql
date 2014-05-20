DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'register_email_reqire';

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('register_valid_type', 's:5:"email";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('wecenter_access_token', 's:0:"";');

ALTER TABLE `[#DB_PREFIX#]notice` DROP `notice_title`;
ALTER TABLE `[#DB_PREFIX#]notice` DROP `notice_type`;
ALTER TABLE `[#DB_PREFIX#]notice` CHANGE `notice_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE `[#DB_PREFIX#]notice` CHANGE `sender_uid` `uid` INT( 11 ) NULL DEFAULT NULL COMMENT '发送者 ID';
ALTER TABLE `[#DB_PREFIX#]notice` CHANGE `notice_content` `message` TEXT NULL DEFAULT NULL COMMENT '内容';
ALTER TABLE `[#DB_PREFIX#]notice_dialog` DROP `last_notice_id`;
ALTER TABLE `[#DB_PREFIX#]notice_dialog` CHANGE `last_time` `update_time` INT( 11 ) NULL DEFAULT NULL COMMENT '最后更新时间';
ALTER TABLE `[#DB_PREFIX#]notice_dialog` DROP `all_count`;
ALTER TABLE `[#DB_PREFIX#]notice_dialog` CHANGE `dialog_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT COMMENT '对话 ID';
ALTER TABLE `[#DB_PREFIX#]notice` ADD `sender_remove` TINYINT( 1 ) NULL DEFAULT '0', ADD `recipient_remove` TINYINT( 1 ) NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]notice` ADD INDEX ( `uid` );
ALTER TABLE `[#DB_PREFIX#]notice` ADD INDEX ( `add_time` );
ALTER TABLE `[#DB_PREFIX#]notice` ADD INDEX ( `sender_remove` );
ALTER TABLE `[#DB_PREFIX#]notice` ADD INDEX ( `recipient_remove` );

RENAME TABLE `[#DB_PREFIX#]notice` TO `[#DB_PREFIX#]inbox`;
RENAME TABLE `[#DB_PREFIX#]notice_dialog` TO `[#DB_PREFIX#]inbox_dialog`;

ALTER TABLE `[#DB_PREFIX#]inbox` ADD `receipt` INT( 10 ) NULL DEFAULT '0';

ALTER TABLE `[#DB_PREFIX#]inbox` ADD INDEX ( `receipt` );

DROP TABLE `[#DB_PREFIX#]notice_recipient`;

ALTER TABLE `[#DB_PREFIX#]users` CHANGE `notice_unread` `inbox_unread` INT( 11 ) NOT NULL DEFAULT '0' COMMENT '未读短信息';

UPDATE `[#DB_PREFIX#]users` SET `group_id` = 4 WHERE `group_id` = 3 AND `valid_email` = 1;

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('wecenter_access_secret', 's:0:"";');

UPDATE `[#DB_PREFIX#]system_setting` SET `value` = 's:0:"";' WHERE `varname` IN('weixin_app_id', 'weixin_app_secret');