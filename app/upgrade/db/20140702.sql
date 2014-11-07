ALTER TABLE `[#DB_PREFIX#]favorite` DROP INDEX `answer_id`;
ALTER TABLE `[#DB_PREFIX#]favorite` CHANGE `answer_id`  `item_id` INT( 11 ) NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]favorite` ADD INDEX ( `item_id` );

ALTER TABLE `[#DB_PREFIX#]favorite` ADD `type` VARCHAR( 16 ) NOT NULL DEFAULT '', ADD INDEX ( `type` );

UPDATE `[#DB_PREFIX#]favorite` SET `type` = 'answer';

ALTER TABLE `[#DB_PREFIX#]favorite_tag` DROP INDEX `answer_id`;
ALTER TABLE `[#DB_PREFIX#]favorite_tag` CHANGE `answer_id`  `item_id` INT( 11 ) NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]favorite_tag` ADD `type` VARCHAR( 16 ) NOT NULL DEFAULT '', ADD INDEX ( `type` );

UPDATE `[#DB_PREFIX#]favorite_tag` SET `type` = 'answer';

ALTER TABLE  `[#DB_PREFIX#]favorite_tag` ADD INDEX ( `item_id` );

DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'base_url';

DELETE FROM `[#DB_PREFIX#]nav_menu` WHERE `type` = 'feature';

ALTER TABLE `[#DB_PREFIX#]topic` ADD `parent_id` INT( 10 ) NULL DEFAULT '0', ADD INDEX ( `parent_id` );
ALTER TABLE `[#DB_PREFIX#]topic` ADD `is_parent` TINYINT( 1 ) NULL DEFAULT '0', ADD INDEX ( `is_parent` );

CREATE TABLE `[#DB_PREFIX#]weixin_accounts` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `weixin_mp_token` varchar(255) NOT NULL,
  `weixin_account_role` varchar(20) DEFAULT 'base',
  `weixin_app_id` varchar(255) DEFAULT '',
  `weixin_app_secret` varchar(255) DEFAULT '',
  `wecenter_access_token` varchar(255) DEFAULT '',
  `wecenter_access_secret` varchar(255) DEFAULT '',
  `weixin_mp_menu` text,
  `weixin_subscribe_message_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `weixin_no_result_message_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `weixin_mp_token` (`weixin_mp_token`),
  KEY `weixin_account_role` (`weixin_account_role`),
  KEY `weixin_app_id` (`weixin_app_id`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8 COMMENT='微信多账号设置';

CREATE TABLE `[#DB_PREFIX#]weixin_msg` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `msg_id` bigint(20) NOT NULL,
  `group_name` varchar(255) NOT NULL DEFAULT '未分组',
  `status` varchar(15) NOT NULL DEFAULT 'unsent',
  `error_num` int(10) DEFAULT NULL,
  `main_msg` text,
  `articles_info` text,
  `questions_info` text,
  `create_time` int(10) NOT NULL,
  `filter_count` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `msg_id` (`msg_id`),
  KEY `group_name` (`group_name`),
  KEY `status` (`status`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8 COMMENT='微信群发列表';

CREATE TABLE `[#DB_PREFIX#]weibo_msg` (
  `id` bigint(20) NOT NULL,
  `created_at` int(10) NOT NULL,
  `msg_author_uid` bigint(20) NOT NULL,
  `text` varchar(255) NOT NULL,
  `access_key` varchar(32) NOT NULL,
  `has_attach` tinyint(1) NOT NULL DEFAULT '0',
  `uid` int(10) NOT NULL,
  `weibo_uid` bigint(20) NOT NULL,
  `question_id` int(11) DEFAULT NULL,
  PRIMARY KEY `id` (`id`),
  KEY `created_at` (`created_at`),
  KEY `uid` (`uid`),
  KEY `weibo_uid` (`weibo_uid`),
  KEY `question_id` (`question_id`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8 COMMENT='新浪微博消息列表';

ALTER TABLE `[#DB_PREFIX#]question` ADD `weibo_msg_id` bigint(20) DEFAULT NULL;
CREATE INDEX `weibo_msg_id` ON `[#DB_PREFIX#]question` (`weibo_msg_id`);

ALTER TABLE `[#DB_PREFIX#]users_sina` ADD `last_msg_id` bigint(20) DEFAULT NULL;
CREATE INDEX `last_msg_id` ON `[#DB_PREFIX#]users_sina` (`last_msg_id`);

ALTER TABLE `[#DB_PREFIX#]users_sina` ADD `expires_time` int(10) DEFAULT '0' COMMENT '过期时间';

ALTER TABLE `[#DB_PREFIX#]attach` MODIFY `item_id` bigint(20) DEFAULT '0' COMMENT '关联 ID';

CREATE TABLE `[#DB_PREFIX#]weixin_qr_code` (
  `scene_id` mediumint(5) NOT NULL AUTO_INCREMENT,
  `ticket` varchar(255) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `subscribe_num` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY `scene_id` (`scene_id`),
  KEY `ticket` (`ticket`),
  KEY `subscribe_num` (`subscribe_num`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8 COMMENT='微信二维码';

ALTER TABLE `[#DB_PREFIX#]weixin_reply_rule` ADD `account_id` int(10) NOT NULL DEFAULT '0';
CREATE INDEX `account_id` ON `[#DB_PREFIX#]weixin_reply_rule` (`account_id`);

ALTER TABLE `[#DB_PREFIX#]question` ADD `unverified_modify_count` int(10) NOT NULL DEFAULT '0';
CREATE INDEX `unverified_modify_count` ON `[#DB_PREFIX#]question` (`unverified_modify_count`);

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES
('weibo_msg_enabled', 's:1:"N";'),
('weibo_msg_published_user', 'a:0:"";'),
('admin_notifications', 'a:0:"";'),
('slave_mail_config', 's:0:"";');
