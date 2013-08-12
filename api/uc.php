<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|   
+---------------------------------------------------------------------------
*/

define('UC_CLIENT_VERSION', '1.6.0');
define('UC_CLIENT_RELEASE', '20110501');

define('API_DELETEUSER', 0);
define('API_RENAMEUSER', 0);
define('API_GETTAG', 0);
define('API_SYNLOGIN', 1);
define('API_SYNLOGOUT', 1);
define('API_UPDATEPW', 0);
define('API_UPDATEBADWORDS', 1);
define('API_UPDATEHOSTS', 1);
define('API_UPDATEAPPS', 1);
define('API_UPDATECLIENT', 1);
define('API_UPDATECREDIT', 0);
define('API_GETCREDIT', 0);
define('API_GETCREDITSETTINGS', 0);
define('API_UPDATECREDITSETTINGS', 0);
define('API_ADDFEED', 0);
define('API_RETURN_SUCCEED', '1');
define('API_RETURN_FAILED', '-1');
define('API_RETURN_FORBIDDEN', '1');

require_once ('../system/init.php');

if (!defined('ROOT_PATH'))
{
	define('ROOT_PATH', realpath('../') . '/');
}

if (!defined('IN_UC')) {

	error_reporting(0);
	
	require_once (ROOT_PATH . 'uc_client/config.inc.php');
	require_once (ROOT_PATH . 'uc_client/client.php');

	$code = @$_GET['code'];
	parse_str(uc_authcode($code, 'DECODE', UC_KEY), $get);

	$timestamp = time();
	if($timestamp - $get['time'] > 3600) {
		exit('Authracation has expiried');
	}
	if(empty($get)) {
		exit('Invalid Request');
	}
	
	$action = $get['action'];
	
	$post = uc_unserialize(file_get_contents('php://input'));
	
	if(in_array($get['action'], array('test', 'updatebadwords', 'updatehosts', 'updateapps', 'updateclient', 'synlogin', 'synlogout'))) {
		$uc_note = new uc_note();
		exit($uc_note->$get['action']($get, $post));
	} else {
		exit(API_RETURN_FAILED);
	}

} else {
	exit;
}

class uc_note {

	var $db = '';
	var $tablepre = '';

	function _serialize($arr, $htmlon = 0) {
		return uc_serialize($arr, $htmlon);
	}

	function uc_note() {
		require_once (AWS_PATH . '/config/database.php');

		$this->tablepre = $config['prefix'];

		$this->db = Zend_Db::factory($config['driver'] , $config['master']);
		$this->db->query("SET NAMES " . $config['charset']);
	}

	function test($get, $post) {
		return API_RETURN_SUCCEED;
	}

	function updatebadwords($get, $post) {
		if(!API_UPDATEBADWORDS) {
			return API_RETURN_FORBIDDEN;
		}

		$data = array();
		if(is_array($post)) {
			foreach($post as $k => $v) {
				$data['findpattern'][$k] = $v['findpattern'];
				$data['replace'][$k] = $v['replacement'];
			}
		}
		$cachefile = ROOT_PATH.'/uc_client/data/cache/badwords.php';
		$fp = fopen($cachefile, 'w');
		$s = "<?php\r\n";
		$s .= '$_CACHE[\'badwords\'] = '.var_export($data, TRUE).";\r\n";
		fwrite($fp, $s);
		fclose($fp);

		return API_RETURN_SUCCEED;
	}

	function updatehosts($get, $post) {
		if(!API_UPDATEHOSTS) {
			return API_RETURN_FORBIDDEN;
		}

		$cachefile = ROOT_PATH.'/uc_client/data/cache/hosts.php';
		$fp = fopen($cachefile, 'w');
		$s = "<?php\r\n";
		$s .= '$_CACHE[\'hosts\'] = '.var_export($post, TRUE).";\r\n";
		fwrite($fp, $s);
		fclose($fp);

		return API_RETURN_SUCCEED;
	}

	function updateapps($get, $post) {
		if(!API_UPDATEAPPS) {
			return API_RETURN_FORBIDDEN;
		}

		$UC_API = '';
		if($post['UC_API']) {
			$UC_API = $post['UC_API'];
			unset($post['UC_API']);
		}

		$cachefile = ROOT_PATH.'/uc_client/data/cache/apps.php';
		
		$s = "<?php\r\n";
		$s .= '$_CACHE[\'apps\'] = '.var_export($post, TRUE).";\r\n";
		
		file_put_contents($cachefile, $s);

		if($UC_API && is_really_writable(ROOT_PATH.'/uc_client/config.inc.php')) {
			if(preg_match('/^https?:\/\//is', $UC_API)) {
				$configfile = trim(file_get_contents(ROOT_PATH.'/uc_client/config.inc.php'));
				$configfile = substr($configfile, -2) == '?>' ? substr($configfile, 0, -2) : $configfile;
				$configfile = preg_replace("/define\('UC_API',\s*'.*?'\);/i", "define('UC_API', '".addslashes($UC_API)."');", $configfile);
				if($fp = @fopen(ROOT_PATH.'/uc_client/config.inc.php', 'w')) {
					@fwrite($fp, trim($configfile));
					@fclose($fp);
				}
			}
		}
		return API_RETURN_SUCCEED;
	}

	function updateclient($get, $post) {
		if(!API_UPDATECLIENT) {
			return API_RETURN_FORBIDDEN;
		}

		$cachefile = ROOT_PATH.'/uc_client/data/cache/settings.php';
		$fp = fopen($cachefile, 'w');
		$s = "<?php\r\n";
		$s .= '$_CACHE[\'settings\'] = '.var_export($post, TRUE).";\r\n";
		fwrite($fp, $s);
		fclose($fp);

		return API_RETURN_SUCCEED;
	}
	
	function synlogin($get, $post) {		
		if(!API_SYNLOGIN) {
			return API_RETURN_FORBIDDEN;
		}

		header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');

		$cookietime = time() + 31536000;
		
		$uid = intval($get['uid']);
		
		if ($uc_info = $this->db->fetchRow("SELECT * FROM {$this->tablepre}users_ucenter WHERE uc_uid = " . $uid))
		{
			if ($user_info = $this->db->fetchRow("SELECT * FROM {$this->tablepre}users WHERE uid = " . $uc_info['uid']))
			{
				HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false), $cookietime);
			}
		}
	}

	function synlogout($get, $post) {
		if(!API_SYNLOGOUT) {
			return API_RETURN_FORBIDDEN;
		}

		header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');

		HTTP::set_cookie("_user_login", '', -31536000);
	}
}