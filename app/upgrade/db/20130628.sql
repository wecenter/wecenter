ALTER TABLE `[#DB_PREFIX#]feature` ADD `seo_title` VARCHAR( 255 ) NULL;

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('question_helpful_users_limit', 's:1:"3";');

ALTER TABLE `[#DB_PREFIX#]users` CHANGE `user_name` `user_name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户名';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `email` `email` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'EMAIL';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `mobile` `mobile` VARCHAR( 16 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户手机';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `password` `password` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户密码';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `salt` `salt` VARCHAR( 16 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户附加混淆码';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `avatar_file` `avatar_file` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '头像文件';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `sex` `sex` TINYINT( 1 ) NULL DEFAULT NULL COMMENT '性别';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `birthday` `birthday` INT( 10 ) NULL DEFAULT NULL COMMENT '生日';
ALTER TABLE `[#DB_PREFIX#]users` DROP `country`;
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `province` `province` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '省';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `city` `city` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '市';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `job_id` `job_id` INT( 10 ) NULL DEFAULT '0' COMMENT '职业ID';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `reg_time` `reg_time` INT( 10 ) NULL DEFAULT NULL COMMENT '注册时间';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `online_time` `online_time` INT( 10 ) NULL DEFAULT '0' COMMENT '在线时间';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `last_active` `last_active` INT( 10 ) NULL DEFAULT NULL COMMENT '最后活跃时间';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `invite_count` `invite_count` INT( 10 ) NOT NULL DEFAULT '0' COMMENT '邀请我回答数量';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `question_count` `question_count` INT( 10 ) NOT NULL DEFAULT '0' COMMENT '问题数量';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `answer_count` `answer_count` INT( 10 ) NOT NULL DEFAULT '0' COMMENT '回答数量';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `topic_focus_count` `topic_focus_count` INT( 10 ) NOT NULL DEFAULT '0' COMMENT '关注话题数量';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `invitation_available` `invitation_available` INT( 10 ) NOT NULL DEFAULT '0' COMMENT '邀请数量';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `group_id` `group_id` INT( 10 ) NULL DEFAULT '0' COMMENT '用户组';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `reputation_group` `reputation_group` INT( 10 ) NULL DEFAULT '0' COMMENT '威望对应组';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `forbidden` `forbidden` TINYINT( 1 ) NULL DEFAULT '0' COMMENT '是否禁止用户';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `valid_email` `valid_email` TINYINT( 1 ) NULL DEFAULT '0' COMMENT '邮箱验证';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `agree_count` `agree_count` INT( 10 ) NULL DEFAULT '0' COMMENT '赞同数量';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `thanks_count` `thanks_count` INT( 10 ) NULL DEFAULT '0' COMMENT '感谢数量';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `views_count` `views_count` INT( 10 ) NULL DEFAULT '0' COMMENT '个人主页查看数量';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `reputation` `reputation` INT( 10 ) NULL DEFAULT '0' COMMENT '威望';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `reputation_update_time` `reputation_update_time` INT( 10 ) NULL DEFAULT '0' COMMENT '威望更新';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `integral` `integral` INT( 10 ) NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]users` CHANGE `inbox_recv` `inbox_recv` TINYINT( 1 ) NOT NULL DEFAULT '0' COMMENT '0-所有人可以发给我,1-我关注的人';

CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]weixin_fake_id` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `type` varchar(16) NOT NULL,
  `fake_id` bigint(10) NOT NULL,
  `item_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`item_id`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

DROP TABLE `[#DB_PREFIX#]admin_group`;

DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'recommend_topics_number';
DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'focus_topics_list_per_page';

ALTER TABLE `[#DB_PREFIX#]verify_apply` ADD `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, ADD `data` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, ADD  `status` TINYINT( 1 ) NULL DEFAULT '0', ADD INDEX ( `name` , `status` );
ALTER TABLE `[#DB_PREFIX#]verify_apply` ADD `type` VARCHAR( 16 ) NULL DEFAULT '', ADD INDEX ( `type` ) ;