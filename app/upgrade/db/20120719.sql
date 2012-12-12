ALTER TABLE `[#DB_PREFIX#]reputation_category` ADD `thanks_count` INT(10) NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]reputation_category` ADD `agree_count` INT(10) NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]reputation_category` ADD `question_count` INT(10) NULL DEFAULT '0';