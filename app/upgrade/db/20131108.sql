ALTER TABLE `[#DB_PREFIX#]article` ENGINE = MYISAM;
ALTER TABLE `[#DB_PREFIX#]article` ADD `title_fulltext` VARCHAR( 255 ) NULL, ADD FULLTEXT ( `title_fulltext` );
ALTER TABLE `[#DB_PREFIX#]article_vote` ADD `item_uid` INT( 10 ) NULL DEFAULT '0', ADD INDEX ( `item_uid` );