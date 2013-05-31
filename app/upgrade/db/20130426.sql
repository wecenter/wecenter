DELETE FROM `[#DB_PREFIX#]system_setting` WHERE `varname` = 'online_interval';

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('weixin_subscribe_message', 's:85:"请问需要什么帮助吗? 您可以通过输入 "help, 帮助" 获得更多支持!";');

ALTER TABLE `[#DB_PREFIX#]feature` DROP `is_scope`;