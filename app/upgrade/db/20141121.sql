INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('enable_help_center', 's:1:"N";');

ALTER TABLE `[#DB_PREFIX#]users_qq`
DROP COLUMN `name`,
DROP INDEX `type`,
DROP COLUMN `type`,
DROP COLUMN `oauth_token_secret`,
DROP COLUMN `location`,
CHANGE `nick` `nickname` varchar(64) DEFAULT NULL,
ADD `refresh_token` varchar(64) DEFAULT NULL,
ADD `expires_time` int(10) DEFAULT NULL,
ADD `figureurl` varchar(255) DEFAULT NULL;
