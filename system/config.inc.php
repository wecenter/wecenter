<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/

// 定义 Cookies 作用域
define('G_COOKIE_DOMAIN','');

// 定义 Cookies 前缀
define('G_COOKIE_PREFIX','csc_');

// 定义应用加密 KEY
define('G_SECUKEY','xnksahihodgq');
define('G_COOKIE_HASH_KEY', 'odtpwdhowspnjfi');

define('G_INDEX_SCRIPT', '?/');

define('X_UA_COMPATIBLE', 'Chrome=1,IE=edge');

// GZIP 压缩输出页面
define('G_GZIP_COMPRESS', FALSE);

// Session 存储类型 (db, file)
define('G_SESSION_SAVE', 'db');

// Session 文件存储路径
define('G_SESSION_SAVE_PATH', '');

// Lucene 支持
define('G_LUCENE_SUPPORT', FALSE);