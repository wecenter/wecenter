ALTER TABLE  `[#DB_PREFIX#]notification` ADD INDEX (  `sender_uid` );
ALTER TABLE  `[#DB_PREFIX#]notification` ADD INDEX (  `model_type` );
ALTER TABLE  `[#DB_PREFIX#]notification` ADD INDEX (  `source_id` );
ALTER TABLE  `[#DB_PREFIX#]notification` ADD INDEX (  `action_type` );