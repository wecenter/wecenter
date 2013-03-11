UPDATE `[#DB_PREFIX#]users` SET `email_settings` = 'a:2:{s:9:"FOLLOW_ME";s:1:"N";s:10:"NEW_ANSWER";s:1:"N";}' WHERE `email_settings` = 'Array';

DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'index_actions_day_limit';