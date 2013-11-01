ALTER TABLE `[#DB_PREFIX#]article_vote` ADD `reputation_factor` INT( 10 ) NULL DEFAULT '0';
ALTER TABLE `[#DB_PREFIX#]reputation_topic` DROP `best_answer_count`;