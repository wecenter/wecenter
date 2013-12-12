DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'invite_reg_only';

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('register_type', 's:4:"open";');

CREATE TABLE `[#DB_PREFIX#]weixin_login` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `token` int(10) NOT NULL,
  `uid` int(10) DEFAULT NULL,
  `session_id` varchar(3)2 NOT NULL,
  `expire` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `token` (`token`),
  KEY `expire` (`expire`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;