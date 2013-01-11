INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('weixin_mp_token', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('cache_dir', 's:0:"";');
DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'cache_open';