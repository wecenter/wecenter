ALTER TABLE `[#DB_PREFIX#]users` ENGINE = [#DB_ENGINE#];
ALTER TABLE `[#DB_PREFIX#]topic` ENGINE = [#DB_ENGINE#];

UPDATE `[#DB_PREFIX#]system_setting` SET `value` = 's:1:"0";' WHERE `varname` = 'auto_question_lock_day';
ALTER TABLE `[#DB_PREFIX#]feature` ADD `is_scope` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]reputation_topic` ADD KEY (`uid`);
DROP TABLE `[#DB_PREFIX#]cache`;