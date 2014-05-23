ALTER TABLE `[#DB_PREFIX#]users_qq` ADD INDEX ( `type` );
ALTER TABLE `[#DB_PREFIX#]users_qq` ADD INDEX ( `add_time` ) ;
ALTER TABLE `[#DB_PREFIX#]users_qq` ADD INDEX ( `access_token` ) ;
ALTER TABLE `[#DB_PREFIX#]users_qq` ADD `openid` VARCHAR( 128 ) NULL DEFAULT '' AFTER  `type` , ADD INDEX ( `openid` );

UPDATE `[#DB_PREFIX#]users_qq` SET `openid` = `name` WHERE `type` = 'qq';
UPDATE `[#DB_PREFIX#]users_qq` SET `name` = '' WHERE `type` = 'qq';

ALTER TABLE `[#DB_PREFIX#]users_sina` ADD INDEX ( `access_token` );