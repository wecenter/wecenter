CREATE TABLE `[#DB_PREFIX#]weixin_third_party_api` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `account_id` int(10) NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `rank` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  KEY `enabled` (`enabled`),
  KEY `rank` (`rank`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8 COMMENT='微信第三方接入';

ALTER TABLE `[#DB_PREFIX#]weixin_message` MODIFY `action` text;

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('last_sent_valid_email_id', 'i:0;');

DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` IN ('qq_t_enabled', 'qq_app_key', 'qq_app_secret');
DELETE FROM `[#DB_PREFIX#]users_qq` WHERE `type` = 'weibo';
