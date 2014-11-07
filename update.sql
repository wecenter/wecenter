ALTER TABLE `aws_weixin_accounts` DROP COLUMN `wecenter_access_token`, DROP COLUMN `wecenter_access_secret`;

DELETE FROM `aws_system_setting` WHERE `varname` = 'wecenter_access_token';
DELETE FROM `aws_system_setting` WHERE `varname` = 'wecenter_access_secret';
