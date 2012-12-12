ALTER TABLE `[#DB_PREFIX#]topic` ADD `topic_title_fulltext` VARCHAR( 128 ) NULL DEFAULT '';
ALTER TABLE `[#DB_PREFIX#]question` ADD `question_content_fulltext` VARCHAR( 128 ) NULL DEFAULT '';
ALTER TABLE `[#DB_PREFIX#]users` ADD `user_name_fulltext` VARCHAR( 128 ) NULL DEFAULT '';
ALTER TABLE `[#DB_PREFIX#]topic` ADD FULLTEXT (`topic_title_fulltext`);
ALTER TABLE `[#DB_PREFIX#]question` ADD FULLTEXT (`question_content_fulltext`);
ALTER TABLE `[#DB_PREFIX#]users` ADD FULLTEXT (`user_name_fulltext`);
DROP TABLE `[#DB_PREFIX#]question_keyword`;
DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'related_question_keyword_count';
