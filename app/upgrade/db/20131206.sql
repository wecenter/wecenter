DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'invite_reg_only';

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('register_type', 's:4:"open";');

CREATE TABLE `[#DB_PREFIX#]weixin_login` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `token` int(10) NOT NULL,
  `uid` int(10) DEFAULT NULL,
  `session_id` varchar(32) NOT NULL,
  `expire` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `token` (`token`),
  KEY `expire` (`expire`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

RENAME TABLE `[#DB_PREFIX#]active_tbl` TO `[#DB_PREFIX#]active_data`;
ALTER TABLE `[#DB_PREFIX#]active_data` ADD INDEX ( `active_type_code` );
ALTER TABLE `[#DB_PREFIX#]active_data` ADD INDEX ( `uid` );
ALTER TABLE `[#DB_PREFIX#]active_data` DROP `active_type`;
ALTER TABLE `[#DB_PREFIX#]active_data` DROP `active_values`;
ALTER TABLE `[#DB_PREFIX#]active_data` DROP `active_expire`;

ALTER TABLE `[#DB_PREFIX#]question` CHANGE `question_content_fulltext` `question_content_fulltext` TEXT NULL DEFAULT NULL;
ALTER TABLE `[#DB_PREFIX#]article` CHANGE `title_fulltext` `title_fulltext` TEXT NULL DEFAULT NULL;

ALTER TABLE `[#DB_PREFIX#]users_weixin` CHANGE `expires_in` `expires_in` INT( 10 ) NULL;
ALTER TABLE `[#DB_PREFIX#]users_weixin` CHANGE `access_token` `access_token` VARCHAR( 255 ) NULL;
ALTER TABLE `[#DB_PREFIX#]users_weixin` CHANGE `refresh_token` `refresh_token` VARCHAR( 255 ) NULL;

ALTER TABLE `[#DB_PREFIX#]users_weixin` CHANGE `scope` `scope` VARCHAR( 64 ) NULL, CHANGE  `headimgurl`  `headimgurl` VARCHAR( 255 ) NULL, CHANGE `nickname` `nickname` VARCHAR( 64 ) NULL, CHANGE `sex` `sex` TINYINT( 1 ) NULL DEFAULT  '0', CHANGE `province` `province` VARCHAR( 32 ) NULL, CHANGE `city` `city` VARCHAR( 32 ) NULL, CHANGE `country` `country` VARCHAR( 32 ) NULL;