ALTER TABLE `[#DB_PREFIX#]favorite` DROP INDEX `answer_id`;
ALTER TABLE `[#DB_PREFIX#]favorite` CHANGE  `answer_id`  `item_id` INT( 11 ) NULL DEFAULT  '0';
ALTER TABLE `[#DB_PREFIX#]favorite` ADD INDEX ( `item_id` );

ALTER TABLE `[#DB_PREFIX#]favorite` ADD `type` VARCHAR( 16 ) NOT NULL DEFAULT '', ADD INDEX ( `type` );

UPDATE `[#DB_PREFIX#]favorite` SET `type` = 'answer';

ALTER TABLE `[#DB_PREFIX#]favorite` DROP INDEX `answer_id`;
ALTER TABLE `[#DB_PREFIX#]favorite_tag` CHANGE  `answer_id`  `item_id` INT( 11 ) NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]favorite_tag` ADD `type` VARCHAR( 16 ) NOT NULL DEFAULT '', ADD INDEX ( `type` );

UPDATE `[#DB_PREFIX#]favorite_tag` SET `type` = 'answer';

ALTER TABLE  `[#DB_PREFIX#]favorite_tag` ADD INDEX ( `item_id` );