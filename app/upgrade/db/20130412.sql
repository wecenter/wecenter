ALTER TABLE `[#DB_PREFIX#]question` ADD INDEX ( `agree_count` );
ALTER TABLE `[#DB_PREFIX#]question` ADD INDEX ( `against_count` );
ALTER TABLE `[#DB_PREFIX#]question` ADD INDEX ( `anonymous` );
ALTER TABLE `[#DB_PREFIX#]question` ADD INDEX ( `popular_value` );
ALTER TABLE `[#DB_PREFIX#]question` ADD INDEX ( `best_answer` );
ALTER TABLE `[#DB_PREFIX#]question` ADD INDEX ( `popular_value_update` );

ALTER TABLE `[#DB_PREFIX#]active_tbl` ADD INDEX ( `active_type` );

ALTER TABLE `[#DB_PREFIX#]answer` ADD INDEX ( `uid` );
ALTER TABLE `[#DB_PREFIX#]answer` ADD INDEX ( `uninterested_count` );
ALTER TABLE `[#DB_PREFIX#]answer` ADD INDEX ( `force_fold` );
ALTER TABLE `[#DB_PREFIX#]answer` ADD INDEX ( `anonymous` );

ALTER TABLE `[#DB_PREFIX#]answer_vote` ADD INDEX ( `vote_value` );

ALTER TABLE `[#DB_PREFIX#]category` ADD INDEX ( `title` );

ALTER TABLE `[#DB_PREFIX#]draft` ADD INDEX ( `time` );

ALTER TABLE `[#DB_PREFIX#]education_experience` ADD INDEX ( `uid` );

ALTER TABLE `[#DB_PREFIX#]feature` ADD INDEX ( `title` );
ALTER TABLE `[#DB_PREFIX#]feature` ADD INDEX ( `is_scope` );

ALTER TABLE `[#DB_PREFIX#]feature_topic` ADD INDEX ( `topic_id` );

ALTER TABLE `[#DB_PREFIX#]invitation` ADD INDEX ( `invitation_email` );
ALTER TABLE `[#DB_PREFIX#]invitation` ADD INDEX ( `active_time` );
ALTER TABLE `[#DB_PREFIX#]invitation` ADD INDEX ( `active_ip` );
ALTER TABLE `[#DB_PREFIX#]invitation` ADD INDEX ( `active_status` );

ALTER TABLE `[#DB_PREFIX#]integral_log` ADD INDEX ( `integral` );

ALTER TABLE `[#DB_PREFIX#]notice` ADD INDEX ( `sender_uid` );
ALTER TABLE `[#DB_PREFIX#]notice` ADD INDEX ( `add_time` );
ALTER TABLE `[#DB_PREFIX#]notice` ADD INDEX ( `notice_type` );

ALTER TABLE `[#DB_PREFIX#]notice_dialog` ADD INDEX ( `add_time` );

ALTER TABLE `[#DB_PREFIX#]notice_recipient` ADD INDEX ( `sender_del` );
ALTER TABLE `[#DB_PREFIX#]notice_recipient` ADD INDEX ( `recipient_del` );
ALTER TABLE `[#DB_PREFIX#]notice_recipient` ADD INDEX ( `sender_time` );
ALTER TABLE `[#DB_PREFIX#]notice_recipient` ADD INDEX ( `recipient_time` );

ALTER TABLE `[#DB_PREFIX#]notification` ADD INDEX ( `add_time` );

ALTER TABLE `[#DB_PREFIX#]question_invite` ADD INDEX ( `sender_uid` );
ALTER TABLE `[#DB_PREFIX#]question_invite` ADD INDEX ( `recipients_uid` );
ALTER TABLE `[#DB_PREFIX#]question_invite` ADD INDEX ( `add_time` );

ALTER TABLE `[#DB_PREFIX#]report` ADD INDEX ( `add_time` );
ALTER TABLE `[#DB_PREFIX#]report` ADD INDEX ( `status` );

ALTER TABLE `[#DB_PREFIX#]reputation_topic` ADD INDEX ( `topic_id` );
ALTER TABLE `[#DB_PREFIX#]reputation_topic` ADD INDEX ( `reputation` );

ALTER TABLE `[#DB_PREFIX#]related_topic` ADD INDEX ( `related_id` );

ALTER TABLE `[#DB_PREFIX#]topic` ADD INDEX ( `discuss_count` );
ALTER TABLE `[#DB_PREFIX#]topic` ADD INDEX ( `add_time` );
ALTER TABLE `[#DB_PREFIX#]topic` ADD INDEX ( `user_related` );
ALTER TABLE `[#DB_PREFIX#]topic` ADD INDEX ( `focus_count` );
ALTER TABLE `[#DB_PREFIX#]topic` ADD INDEX ( `topic_lock` );

ALTER TABLE `[#DB_PREFIX#]redirect` ADD INDEX ( `type` );

ALTER TABLE `[#DB_PREFIX#]users_online` ADD INDEX ( `last_active` );

ALTER TABLE `[#DB_PREFIX#]users_ucenter` ADD INDEX ( `uc_uid` );
ALTER TABLE `[#DB_PREFIX#]users_ucenter` ADD INDEX ( `email` );

ALTER TABLE `[#DB_PREFIX#]users_forbidden` ADD INDEX ( `uid` );