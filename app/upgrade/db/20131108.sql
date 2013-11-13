ALTER TABLE `[#DB_PREFIX#]article` ENGINE = MYISAM;
ALTER TABLE `[#DB_PREFIX#]article` ADD `title_fulltext` VARCHAR( 255 ) NULL, ADD FULLTEXT ( `title_fulltext` );