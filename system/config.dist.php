<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

// 定义 Cookies 作用域
define('G_COOKIE_DOMAIN','');

// 定义 Cookies 前缀
define('G_COOKIE_PREFIX','{G_COOKIE_PREFIX}');

// 定义应用加密 KEY
define('G_SECUKEY','{G_SECUKEY}');
define('G_COOKIE_HASH_KEY', '{G_COOKIE_HASH_KEY}');

define('G_INDEX_SCRIPT', '?/');

define('X_UA_COMPATIBLE', 'IE=edge,Chrome=1');

// GZIP 压缩输出页面
define('G_GZIP_COMPRESS', FALSE);

// Session 存储类型 (db, file)
define('G_SESSION_SAVE', 'db');

// Session 文件存储路径
define('G_SESSION_SAVE_PATH', '');