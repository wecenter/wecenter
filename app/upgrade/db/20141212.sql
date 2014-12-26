ALTER TABLE `[#DB_PREFIX#]users_group` ADD INDEX `type`, ADD INDEX `custom`;

UPDATE `[#DB_PREFIX#]system_setting` SET `weibo_msg_enabled` = 'question' WHERE `weibo_msg_enabled` = 'Y';

ALTER TABLE `[#DB_PREFIX#]weibo_msg` ADD `ticket_id` int(11) DEFAULT NULL, ADD INDEX `ticket_id`;

ALTER TABLE `[#DB_PREFIX#]receiving_email` ADD `ticket_id` int(11) DEFAULT NULL, ADD INDEX `ticket_id`;
