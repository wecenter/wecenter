CREATE TABLE `[#DB_PREFIX#]weixin_third_party_access_rule` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `account_id` int(10) NOT NULL DEFAULT '0',
  `keyword` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `enabled` tinyint(1) DEFAULT '0',
  `rank` tinyint(2) UNSIGNED DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  KEY `keyword` (`keyword`),
  KEY `enabled` (`enabled`),
  KEY `rank` (`rank`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8 COMMENT='微信第三方接入';

ALTER TABLE `[#DB_PREFIX#]weixin_message` MODIFY `action` text;
