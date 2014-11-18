ALTER TABLE `[#DB_PREFIX#]weixin_accounts` DROP COLUMN `wecenter_access_token`, DROP COLUMN `wecenter_access_secret`;

DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'wecenter_access_token';
DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'wecenter_access_secret';
DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'wecenter_mp_notification_once';
