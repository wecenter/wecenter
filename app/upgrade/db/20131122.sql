CREATE TABLE `[#DB_PREFIX#]users_weixin` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `openid` varchar(255) NOT NULL,
  `expires_in` int(10) NOT NULL,
  `access_token` varchar(255) NOT NULL,
  `refresh_token` varchar(255) NOT NULL,
  `scope` varchar(64) NOT NULL,
  `headimgurl` varchar(255) NOT NULL,
  `nickname` varchar(64) NOT NULL,
  `sex` tinyint(1) NOT NULL DEFAULT '0',
  `province` varchar(32) NOT NULL,
  `city` varchar(32) NOT NULL,
  `country` varchar(32) NOT NULL,
  `add_time` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  KEY `openid` (`openid`),
  KEY `expires_in` (`expires_in`),
  KEY `scope` (`scope`),
  KEY `sex` (`sex`),
  KEY `province` (`province`),
  KEY `city` (`city`),
  KEY `country` (`country`),
  KEY `add_time` (`add_time`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

ALTER TABLE `[#DB_PREFIX#]users` DROP `weixin_id`;
DROP TABLE `[#DB_PREFIX#]weixin_valid`;
DROP TABLE `[#DB_PREFIX#]weixin_fake_id`;
DROP TABLE `[#DB_PREFIX#]weixin_publish_rule`;
ALTER TABLE `[#DB_PREFIX#]weixin_reply_rule` DROP `event_key`;

CREATE TABLE `[#DB_PREFIX#]weixin_list_image` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `file_location` varchar(255) NOT NULL,
  `access_key` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `access_key` (`access_key`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;