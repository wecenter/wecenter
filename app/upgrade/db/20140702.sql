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