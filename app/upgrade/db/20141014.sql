CREATE TABLE `[#DB_PREFIX#]edm_unsubscription` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `time` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]users_google` (
  `id` varchar(64) NOT NULL,
  `uid` int(11) UNSIGNED NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `locale` varchar(16) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `gender` varchar(8) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `add_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `access_token` varchar(128) DEFAULT NULL,
  `refresh_token` varchar(128) DEFAULT NULL,
  `expires_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  KEY `access_token` (`access_token`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]users_facebook` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uid` int(11) UNSIGNED NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `gender` varchar(8) DEFAULT NULL,
  `locale` varchar(16) DEFAULT NULL,
  `timezone` tinyint(3) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `access_token` varchar(255) DEFAULT NULL,
  `expires_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `add_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  KEY `access_token` (`access_token`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]users_twitter` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uid` int(11) UNSIGNED NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `screen_name` varchar(128) DEFAULT NULL,
  `location` varchar(64) DEFAULT NULL,
  `time_zone` varchar(64) DEFAULT NULL,
  `lang` varchar(16) DEFAULT NULL,
  `profile_image_url` varchar(255) DEFAULT NULL,
  `add_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `access_token` varchar(255) NOT NULL DEFAULT 'a:2:{s:11:"oauth_token";s:0:"";s:18:"oauth_token_secret";s:0:"";}',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  KEY `access_token` (`access_token`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES
('google_login_enabled', 's:1:"N";'),
('google_client_id', 's:0:"";'),
('google_client_secret', 's:0:"";'),
('facebook_login_enabled', 's:1:"N";'),
('facebook_app_id', 's:0:"";'),
('facebook_app_secret', 's:0:"";'),
('twitter_login_enabled', 's:1:"N";'),
('twitter_consumer_key', 's:0:"";'),
('twitter_consumer_secret', 's:0:"";'),
('weixin_encoding_aes_key', 's:0:"";');

ALTER TABLE `[#DB_PREFIX#]weixin_accounts` ADD `weixin_encoding_aes_key` varchar(43) DEFAULT NULL;
