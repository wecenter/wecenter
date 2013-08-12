ALTER TABLE `[#DB_PREFIX#]weixin_reply_rule` ADD `event_key` VARCHAR( 32 ) NULL DEFAULT '', ADD INDEX ( `event_key` );

DROP TABLE `[#DB_PREFIX#]edm_unsubscription`;