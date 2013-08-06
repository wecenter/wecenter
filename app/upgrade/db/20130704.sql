ALTER TABLE `[#DB_PREFIX#]users` CHANGE `verified` `verified` VARCHAR( 32 ) NULL DEFAULT NULL;
UPDATE `[#DB_PREFIX#]users` SET `verified` = 'personal' WHERE `verified` = '1';
UPDATE `[#DB_PREFIX#]users` SET `verified` = '' WHERE `verified` = '0';