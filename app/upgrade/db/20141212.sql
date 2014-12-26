ALTER TABLE `[#DB_PREFIX#]users_group` ADD INDEX (`type`), ADD INDEX (`custom`);

UPDATE `[#DB_PREFIX#]system_setting` SET `value` = 's:8:"question";' WHERE `varname` = 'weibo_msg_enabled' AND `value` = 's:1:"Y";';

ALTER TABLE `[#DB_PREFIX#]weibo_msg` ADD `ticket_id` int(11) DEFAULT NULL, ADD INDEX (`ticket_id`);

ALTER TABLE `[#DB_PREFIX#]received_email` ADD `ticket_id` int(11) DEFAULT NULL, ADD INDEX (`ticket_id`);
