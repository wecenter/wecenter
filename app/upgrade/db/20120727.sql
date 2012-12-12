INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('qq_login_enabled', 's:1:"N";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('qq_login_app_id', '');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('qq_login_app_key', '');
ALTER TABLE `[#DB_PREFIX#]users_qq` ADD `type` VARCHAR( 20 ) NULL DEFAULT NULL COMMENT '类别' AFTER `uid`;
UPDATE `[#DB_PREFIX#]users_qq` SET type = 'weibo';
ALTER TABLE `[#DB_PREFIX#]users` DROP `district`;
ALTER TABLE `[#DB_PREFIX#]work_experience` DROP `district`;