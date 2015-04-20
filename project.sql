CREATE TABLE `[#DB_PREFIX#]payment` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `amount` double NOT NULL,
  `time` int(10) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `order_id` bigint(16) NOT NULL,
  `terrace_id` varchar(64) DEFAULT NULL,
  `payment_id` varchar(16) DEFAULT '',
  `source` varchar(16) NOT NULL DEFAULT '',
  `extra_param` text,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `terrace_id` (`terrace_id`),
  KEY `time` (`time`),
  KEY `payment_id` (`payment_id`),
  KEY `order_id` (`order_id`),
  KEY `source` (`source`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]product_order` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `payment_order_id` bigint(16) NOT NULL DEFAULT '0',
  `product_id` int(10) NOT NULL,
  `project_id` int(10) NOT NULL,
  `project_title` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `shipping_address` varchar(255) DEFAULT NULL,
  `shipping_name` varchar(64) DEFAULT NULL,
  `shipping_province` varchar(64) DEFAULT NULL,
  `shipping_city` varchar(64) DEFAULT NULL,
  `shipping_mobile` varchar(16) DEFAULT NULL,
  `shipping_zipcode` int(10) DEFAULT NULL,
  `add_time` int(10) NOT NULL DEFAULT '0',
  `refund_time` int(10) NOT NULL DEFAULT '0',
  `shipping_time` int(10) NOT NULL DEFAULT '0',
  `is_donate` tinyint(1) NOT NULL DEFAULT '0',
  `track_no` varchar(32) NOT NULL DEFAULT '',
  `track_branch` varchar(64) NOT NULL DEFAULT '',
  `note` text,
  `payment_time` int(10) NOT NULL DEFAULT '0',
  `product_title` varchar(255) NOT NULL DEFAULT  '',
  `cancel_time` int(10) NOT NULL DEFAULT '0',
  `has_attach` tinyint(1) NOT NULL DEFAULT '0',
  `address` varchar(255) DEFAULT NULL,
  `project_type` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `product_id` (`product_id`),
  KEY `project_id` (`project_id`),
  KEY `payment_order_id` (`payment_order_id`),
  KEY `track_no` (`track_no`),
  KEY `is_donate` (`is_donate`),
  KEY `refund_time` (`refund_time`),
  KEY `add_time` (`add_time`),
  KEY `payment_time` (`payment_time`),
  KEY `cancel_time` (`cancel_time`),
  KEY `project_type` (`project_type`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]project` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `open_notify` int( 10 ) NOT NULL DEFAULT '0',
  `close_notify` int( 10 ) NOT NULL DEFAULT '0',
  `uid` int(10) NOT NULL DEFAULT '0',
  `category_id` int(10) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `country` varchar(16) NOT NULL,
  `province` varchar(16) NOT NULL,
  `city` varchar(16) DEFAULT NULL,
  `summary` text,
  `description` text,
  `start_time` int(10) NOT NULL DEFAULT '0',
  `end_time` int(10) NOT NULL DEFAULT '0',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `contact` text,
  `paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sponsored_users` int(10) NOT NULL DEFAULT '0',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `views` int(10) NOT NULL DEFAULT '0',
  `add_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `has_attach` tinyint(1) NOT NULL DEFAULT '0',
  `topic_id` int(10) NOT NULL DEFAULT '0',
  `discuss_count` int(10) NOT NULL DEFAULT '0',
  `status` varchar(16) DEFAULT NULL,
  `like_count` int(10) NOT NULL DEFAULT '0',
  `video_link` varchar(255) NOT NULL DEFAULT '',
  `project_type` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `open_notify` (`open_notify`),
  KEY `close_notify` (`close_notify`),
  KEY `category_id` (`category_id`),
  KEY `title` (`title`),
  KEY `country` (`country`),
  KEY `province` (`province`),
  KEY `city` (`city`),
  KEY `start_time` (`start_time`),
  KEY `end_time` (`end_time`),
  KEY `amount` (`amount`),
  KEY `paid` (`paid`),
  KEY `approved` (`approved`),
  KEY `uid` (`uid`),
  KEY `add_time` (`add_time`),
  KEY `views` (`views`),
  KEY `update_time` (`update_time`),
  KEY `discuss_count` (`discuss_count`),
  KEY `sponsored_users` (`sponsored_users`),
  KEY `status` (`status`),
  KEY `like_count` (`like_count`),
  KEY `project_type` (`project_type`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]project_product` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock` int(10) NOT NULL DEFAULT '0',
  `description` text,
  `project_id` int(10) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `amount` (`amount`),
  KEY `project_id` (`project_id`),
  KEY `title` (`title`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]project_like` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `project_id` int(10) NOT NULL,
  `uid` int(10) NOT NULL,
  `add_time` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `uid` (`uid`),
  KEY `add_time` (`add_time`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('project_enabled', 's:1:"N";');

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('alipay_partner', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('alipay_key', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('alipay_seller_email', 's:0:"";');

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('alipay_sign_type', 's:3:"MD5";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('alipay_input_charset', 's:5:"utf-8";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('alipay_transport', 's:5:"https";');

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('alipay_private_key', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('alipay_ali_public_key', 's:0:"";');
