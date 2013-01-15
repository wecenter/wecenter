INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('weixin_mp_token', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('cache_dir', 's:0:"";');
DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'cache_open';
ALTER TABLE `[#DB_PREFIX#]users` ENGINE = [#DB_ENGINE#];
ALTER TABLE `[#DB_PREFIX#]topic` ENGINE = [#DB_ENGINE#];

UPDATE `[#DB_PREFIX#]system_setting` SET `value` = 's:1:"0";' WHERE `varname` = 'auto_question_lock_day';
ALTER TABLE `[#DB_PREFIX#]feature` ADD `is_scope` TINYINT( 1 ) NOT NULL DEFAULT '0';