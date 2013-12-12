DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'invite_reg_only';

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('register_type', 's:4:"open";');