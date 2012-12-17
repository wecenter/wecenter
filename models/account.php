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


if (!defined('IN_ANWSION'))
{
	die;
}

class account_class extends AWS_MODEL
{	
	/**
	 * 未读系统通知
	 */
	const NOTIFICATION_UNREAD = 'notification_unread';
	
	/**
	 * 未读短信息
	 */
	const NOTICE_UNREAD = 'notice_unread';
	
	/**
	 * 回复问题数量
	 */
	const ANSWER_COUNT = 'answer_count';
	
	/**
	 * 受邀请参与问题计数
	 */
	const INVITE_COUNT = 'invite_count';

	function get_source_hash($email)
	{
		return H::encode_hash(array(
			'email' => $email
		));
	}

	/**
	 * 检查用户名是否已经存在
	 * @param $username
	 * @return rows 
	 */
	
	function check_username($username)
	{
		return $this->fetch_one('users', 'uid', "user_name = '" . $this->quote(trim($username)) . "' OR url_token = '" . $this->quote(trim($username)) . "'");
	}
	
	/**
	 * 检查用户名中是否包含敏感词或用户信息保留字
	 * @param unknown_type $username
	 * @return boolean
	 */
	function check_username_sensitive_words($username)
	{
		if (H::sensitive_word_exists($username, '', true))
		{
			return true;
		}
		
		if (!get_setting('censoruser'))
		{
			return false;
		}
		
		if ($censorusers = explode("\n", get_setting('censoruser')))
		{
			foreach ($censorusers as $name)
			{
				$name = trim($name);
				
				if (!$name)
				{
					continue;
				}
				
				if (preg_match('/(' . $name . ')/is', $username))
				{
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * 检查用户名是否已经存在
	 * @param $username
	 * @return rows 
	 */
	
	function check_uid($uid)
	{		
		return $this->fetch_one('users', 'uid', 'uid = ' . intval($uid));
	}

	/**
	 * 检查电子邮件地址是否已经存在
	 * @param $email
	 * @return int
	 */
	function check_email($email)
	{
		if (! H::valid_email($email))
		{
			return TRUE;
		}
		
		return $this->fetch_one('users', 'uid', "email = '" . $this->quote($email) . "'");
	}

	/**
	 * 正式表用户登录检查,错误返回FALSE,正确返回用户数据
	 * @param $username
	 * @param $password
	 * @return
	 */
	function check_login($username, $password)
	{		
		if (!$username OR !$password)
		{
			return false;
		}
		
		if (H::valid_email($username))
		{
			$user_info = $this->get_user_info_by_email($username);
		}
		
		if (! $user_info)
		{
			if (! $user_info = $this->get_user_info_by_username($username))
			{
				return false;
			}
		}
		
		if (! $this->check_password($password, $user_info['password'], $user_info['salt']))
		{
			return false;
		}
		else
		{
			return $user_info;
		}
	
	}
	
	function check_hash_login($username, $password_md5)
	{
		if (!$username OR !$password_md5)
		{
			return false;
		}
		
		if (H::valid_email($username))
		{
			$user_info = $this->get_user_info_by_email($username);
		}
		
		if (! $user_info)
		{
			if (! $user_info = $this->get_user_info_by_username($username))
			{
				return false;
			}
		}
		
		if ( $password_md5 != $user_info['password'])
		{
			return false;
		}
		else
		{
			return $user_info;
		}
	
	}

	/**
	 * 检验密码是否和数据库里面的密码相同
	 *
	 * @param string $password		新密码
	 * @param string $db_password   数据库密码
	 * @param string $salt			混淆码
	 * @return bool
	 */
	function check_password($password, $db_password, $salt)
	{
		$password = compile_password($password, $salt);
		
		if ($password == $db_password)
		{
			return true;
		}
		
		return false;
	
	}

	/**
	 * 通过用户名获取用户信息
	 * @param $username		用户名或邮箱地址
	 * @return
	 */
	function get_user_info_by_username($username, $attrb = false)
	{
		if ($uid = $this->fetch_one('users', 'uid', "user_name = '" . $this->quote($username) . "'"))
		{
			return $this->get_user_info_by_uid($uid, $attrb);
		}
	}

	/**
	 * 通过用户邮箱获取用户信息
	 * @param $email 用邮箱地址
	 * @return row
	 */
	function get_user_info_by_email($email)
	{
		if ($uid = $this->fetch_one('users', 'uid', "email = '" . $this->quote($email) . "'"))
		{
			return $this->get_user_info_by_uid($uid, $attrb);
		}
	}
	
	
	function get_user_info_by_url_token($url_token, $attrb = false)
	{
		if ($uid = $this->fetch_one('users', 'uid', "url_token = '" . $this->quote($url_token) . "'"))
		{
			return $this->get_user_info_by_uid($uid, $attrb);
		}
	}

	/**
	 * 通过用户 uid 获取用户信息
	 * @param $username
	 * @return
	 */
	function get_user_info_by_uid($uid, $attrib = false, $var_cache = true)
	{
		if (! $uid)
		{
			return false;
		}
		
		if ($var_cache)
		{
			static $users_info;
			
			if ($users_info[$uid . '_attrib'])
			{
				return $users_info[$uid . '_attrib'];
			}
			else if ($users_info[$uid])
			{
				return $users_info[$uid];
			}
		}
		
		if ($attrib)
		{
			$sql = "SELECT MEM.*, MEB.* FROM " . $this->get_table('users') . " AS MEM LEFT JOIN " . $this->get_table('users_attrib') . " AS MEB ON MEM.uid = MEB.uid WHERE MEM.uid = " . intval($uid);
		}
		else
		{
			$sql = "SELECT * FROM " . $this->get_table('users') . " WHERE uid = " . intval($uid);
		}
		
		if (! $user_info = $this->query_row($sql))
		{
			return false;
		}
		
		if (!$user_info['url_token'] AND $user_info['user_name'])
		{
			$user_info['url_token'] = urlencode($user_info['user_name']);
		}
		
		if ($attrib)
		{
			$users_info[$uid . '_attrib'] = $user_info;
		}
		else
		{
			$users_info[$uid] = $user_info;
		}
		
		return $user_info;
	}

	/**
	 * 通过指量用户 uid 返回指量用户数据
	 * 
	 * @param arrary $uids 用户 IDS
	 * @param bool	 $attrib   是否返回附加表数据
	 */
	function get_user_info_by_uids($uids, $attrib = false)
	{
		if (! is_array($uids) OR sizeof($uids) == 0)
		{
			return false;
		}
		
		array_walk_recursive($uids, 'intval_string');
		
		if ($attrib)
		{
			$sql = "SELECT MEM.*, MEB.* FROM " . $this->get_table('users') . " AS MEM LEFT JOIN " . $this->get_table('users_attrib') . " AS MEB ON MEM.uid = MEB.uid WHERE MEM.uid IN(" . implode(',', array_unique($uids)) . ")";
		}
		else
		{
			$sql = "SELECT * FROM " . $this->get_table('users') . " WHERE uid IN(" . implode(',', array_unique($uids)) . ")";
		}
		
		if ($user_info = $this->query_all($sql))
		{
			foreach($user_info as $key => $val)
			{
				if (!$val['url_token'])
				{
					$val['url_token'] = urlencode($val['user_name']);
				}
				
				$data[$val['uid']] = $val;
			}
		}
		
		return $data;
	}
	
	/**
	 * 通过用户 uid 获取用户信息
	 * @param $username
	 * @return
	 */
	function get_email_setting_by_uid($uid)
	{
		if (! $uid)
		{
			return false;
		}
		
		return $this->fetch_row('users_email_setting', 'uid = ' . intval($uid));
	}

	/**
	 * 根据用户ID获取用户通知设置
	 * @param $uid
	 */
	function get_notification_setting_by_uid($uid)
	{
		$setting = $this->fetch_row('users_notification_setting', 'uid = ' . intval($uid));
		
		if (empty($setting))
		{
			return array('data' => array());
		}
		
		$setting['data'] = unserialize($setting['data']);
		
		if (empty($setting['data']))
		{
			$setting['data'] = array();
		}
		
		return $setting;
	}

	/**
	 * 编辑邀请名额
	 * 
	 * @param int $uid
	 * @param int $value 正数为加 负数为减
	 */
	function edit_invitation_available($uid, $value)
	{
		$uid = intval($uid);
		$value = intval($value);
		
		if (! $uid OR !$value)
		{
			return false;
		}
		
		//增加
		if ($value >= 1)
		{
			return $this->query("UPDATE " . $this->get_table('users') . " SET invitation_available = invitation_available + " . $value . " WHERE uid = " . $uid);
		}
		else if ($value < 1)
		{
			$value = $value * - 1;
			
			return $this->query("UPDATE " . $this->get_table('users') . " SET invitation_available = invitation_available - " . $value . " WHERE uid = " . $uid);
		}
		else
		{
			return false;
		}
	}
	
	function insert_user($username, $password, $email, $sex = 0, $mobile = null)
	{
		if ($username == '')
		{
			return false;
		}
		
		$salt = fetch_salt(4);
		
		if ($uid = $this->insert('users', array(
			'user_name' => htmlspecialchars($username),
			'password' => compile_password($password, $salt),
			'salt' => $salt,
			'email' => htmlspecialchars($email),
			'sex' => intval($sex),
			'mobile' => htmlspecialchars($mobile),
			'reg_time' => time(),
			'reg_ip' => ip2long(fetch_ip())
		)))
		{
			$this->insert('users_attrib', array(
				'uid' => $uid
			));
			
			$this->insert('users_email_setting', array(
				'uid' => $uid
			));
			
			$this->update_notification_setting_fields(null, $uid);
			
			//$this->model('search_index')->push_index('user', $username, $uid);
		}
		
		return $uid;
	}
	
	function user_register($user_name, $password, $email, $email_valid = false)
	{
		if ($uid = $this->insert_user($user_name, $password, $email))
		{
			if ($def_focus_uids_str = get_setting('def_focus_uids'))
			{
				$def_focus_uids = explode(',', $def_focus_uids_str);
				
				foreach ($def_focus_uids as $key => $val)
				{
					$this->model('follow')->user_follow_add($uid, $val);
				}
			}
			
			$group_id = (get_setting('register_email_reqire') == 'N' || $email_valid) ? 4 : 3;
			
			$this->update_users_fields(array(
				'valid_email' => intval($email_valid),
				'group_id' => $group_id,
				'reputation_group' => 5,
				'invitation_available' => get_setting('newer_invitation_num'),
				'is_first_login' => 1
			), $uid);
			
			$this->model('integral')->process($uid, 'REGISTER', get_setting('integral_system_config_register'), '初始资本');
			
			if ($email_valid)
			{
				$this->welcome_message($uid, $user_name, $email);
			}
		}
		
		return $uid;
	}
	
	function welcome_message($uid, $user_name, $email)
	{
		if (get_setting('welcome_message_email'))
		{
			load_class('core_mail')->send_mail(null, get_setting('site_name'), $email, $user_name, '欢迎来到 ' . get_setting('site_name'), str_replace(array('{username}', '{time}', '{sitename}'), array($user_name, date('Y-m-d H:i:s', time()), get_setting('site_name')), nl2br(get_setting('welcome_message_email'))));
		}
			
		if (get_setting('welcome_message_pm'))
		{
			$this->model('message')->send_message($uid, $uid, null, str_replace(array('{username}', '{time}', '{sitename}'), array($user_name, date('Y-m-d H:i:s', time()), get_setting('site_name')), get_setting('welcome_message_pm')), 0, 0);
		}
	}
	
	/**
	 * 更新用户状态或字段
	 * @param $update_data 字段
	 * @param $userid 用户id
	 * @return  
	 */
	function update_users_fields($update_data, $uid)
	{
		return $this->update('users', $update_data, 'uid = ' . intval($uid));
	}
	
	function update_user_name($user_name, $uid)
	{
		$this->update('users', array(
			'user_name' => htmlspecialchars($user_name),
		), 'uid = ' . intval($uid));
		
		//return $this->model('search_index')->push_index('user', $user_name, $uid);
		
		return true;
	}

	/**
	 * 更新用户附加表状态或字段
	 * @param $update_data 字段
	 * @param $userid	用户id
	 * @return 
	 */
	function update_users_attrib_fields($update_data, $uid)
	{
		return $this->update('users_attrib', $update_data, 'uid = ' . intval($uid));
	}

	/**
	 * 更改用户密码
	 *
	 * @param  $oldpassword 旧密码
	 * @param  $password 新密码
	 * @param  $userid 用户id
	 * @param  $salt 混淆码
	 */
	function update_user_password($oldpassword, $password, $userid, $salt)
	{
		if ($salt == '')
		{
			return false;
		}
		
		$userid = intval($userid);
		
		if (! $userid)
		{
			return false;
		}
		
		$oldpassword = compile_password($oldpassword, $salt);
		
		if ($this->count('users', "uid = " . $userid . " AND password = '" . $this->quote($oldpassword) . "'") != 1)
		{
			return false;
		}
		
		return $this->update_user_password_ingore_oldpassword($password, $userid, $salt);
	
	}

	/**
	 * 更改用户不用旧密码密码
	 *
	 * @param $password
	 * @param $userid
	 */
	function update_user_password_ingore_oldpassword($password, $uid, $salt)
	{
		if (!$salt OR !$password OR !$uid)
		{
			return false;
		}
		
		$update_data['password'] = compile_password($password, $salt);
		$update_data['salt'] = $salt;
		
		$this->update('users', $update_data, 'uid = ' . intval($uid));
		
		return true;
	}

	function clean_first_login($uid)
	{
		if (! $this->update('users', array(
			'is_first_login' => 0
		), 'uid = ' . intval($uid)))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * 更新用户接收EMAIL设置
	 * 
	 * @param  $update_data	更新数组
	 * @param  $userid	UID
	 * 
	 * @return bool
	 */
	function update_email_setting_fields($update_data, $uid)
	{
		if (!$uid)
		{
			return false;
		}
		
		if ($this->fetch_row('users_email_setting', 'uid = ' . intval($uid)))
		{
			$this->update('users_email_setting', $update_data, 'uid = ' . intval($uid));
			
			return true;
		}
		else
		{
			$update_data['uid'] = intval($uid);
			
			return $this->insert('users_email_setting', $update_data);
		}
	}

	/**
	 * 更新用户最后登录时间
	 * @param  $userid 用户id
	 * @param  $login_time 登录时间戳(默认为当前时间,可为空)
	 */
	function update_user_last_login($uid, $login_time = 0)
	{
		if (! $uid)
		{
			return false;
		}
		
		if (!$login_time)
		{
			$login_time = time();
		}
		
		$update_data['last_login'] = intval($login_time);
		$update_data['last_ip'] = ip2long(fetch_ip());
		
		return $this->update('users', $update_data, 'uid = ' . intval($uid));
	}

	/**
	 * 更新用户通知设置
	 * 
	 * @param  $update_data	更新数组
	 * @param  $userid	UID
	 * 
	 * @return bool
	 */
	function update_notification_setting_fields($update_data, $uid)
	{
		$user_setting = $this->fetch_row('users_notification_setting', 'uid = ' . intval($uid));
		
		if (empty($user_setting))
		{
			$update_data['uid'] = intval($uid);
			
			$this->insert('users_notification_setting', $update_data);
		}
		else
		{
			$this->update('users_notification_setting', $update_data, 'uid = ' . intval($uid));
		}
		
		return true;
	}

	/**
	 * 修改用户所属的统计数值  [未读系统通知,未读短信息,粉丝数,观众数,问我数量,问题总数,回复问题数量,
	 * 编辑过的数量,话题数量,比赛数量
	 * @param int $state_type
	 * @param int $state_num
	 * 
	 * @return boolean
	 */
	public function increase_user_statistics($state_type, $state_num = 1, $uid = null)
	{
		if (!$uid)
		{
			return false;
		}
		
		$state_array = array(
			self::ANSWER_COUNT, 
			self::NOTICE_UNREAD, 
			self::NOTIFICATION_UNREAD, 
			self::INVITE_COUNT, 
		);
		
		if (! in_array($state_type, $state_array))
		{
			return false;
		}
		
		//未读通知
		if ($state_type == self::NOTIFICATION_UNREAD)
		{
			return $this->update('users', array(
				$state_type => $this->model('notify')->get_notifications_unread_num($uid)
			), 'uid = ' . intval($uid));
		}
		
		//未读私信
		if ($state_type == self::NOTICE_UNREAD)
		{
			return $this->update('users', array(
				$state_type => $this->model('message')->get_message_unread_num($uid)
			), 'uid = ' . intval($uid));
		}
		
		//回复计数
		if ($state_type == self::ANSWER_COUNT)
		{
			$question_answer_count = $this->count('answer', 'uid = ' . intval($uid));
			
			return $this->update('users', array(
				$state_type => $question_answer_count
			), 'uid = ' . intval($uid));
		}
		
		//受邀请参与问题计数
		if ($state_type == self::INVITE_COUNT)
		{
			$count = $this->count('question_invite', 'recipients_uid = ' . intval($uid));
			
			return $this->update('users', array(
				$state_type => $count
			), 'uid = ' . intval($uid));
		}
				
		return $this->update('users', array(
			$state_type => ($state_type + $state_num)
		), 'uid = ' . intval($uid));
	}

	/**
	 * 设置登录时候的COOKIE信息
	 *
	 * @param  $userid
	 * @param  $username
	 * @param  $password
	 * 
	 * @return true
	 */
	function setcookie_login($uid, $user_name, $password, $salt, $expire = null, $hash_password = true)
	{
		if (! $uid)
		{
			return false;
		}
		
		if (! $expire)
		{
			HTTP::set_cookie('_user_login', get_login_cookie_hash($user_name, $password, $salt, $uid, $hash_password));
		}
		else
		{
			$expire = time() + $expire;
			
			HTTP::set_cookie('_user_login', get_login_cookie_hash($user_name, $password, $salt, $uid, $hash_password), $expire);
		}
		
		return true;
	}

	/**
	 * 设置退出时候的COOKIE信息
	 * @param $userid
	 * @param $username
	 * @param $password
	 * @param $expire
	 * @return
	 */
	function setcookie_logout()
	{
		HTTP::set_cookie('_user_login', '', time() - 3600);
	}
	
	public function logout()
	{
		$this->setcookie_logout();
		$this->setsession_logout();
	}

	function setsession_logout()
	{
		if (isset($_SESSION['client_info']))
		{
			unset($_SESSION['client_info']);
		}
		
		if (isset($_SESSION['permission']))
		{
			unset($_SESSION['permission']);
		}
	}

	function set_admin_login($uid)
	{
		$_SESSION['admin_login'] = H::encode_hash(array('uid' => $uid, 'UA' => $_SERVER['HTTP_USER_AGENT'], 'ip' => $_SERVER['REMOTE_ADDR']));
	}
	
	function admin_logout()
	{
		if (isset($_SESSION['admin_login']))
		{
			unset($_SESSION['admin_login']);
		}
	}
	
	/**
	 * 检查用户名的字符
	 * @param $username
	 * @return
	 */
	function check_username_char($username)
	{
		$flag = false;
		
		$length = strlen(iconv('UTF-8', 'gb2312', $username));
		
		$length_min = intval(get_setting('username_length_min'));
		
		$length_max = intval(get_setting('username_length_max'));
		
		if ($length < $length_min || $length > $length_max)
		{
			$flag = true;
		}
		
		switch(get_setting('username_rule'))
		{
			case '1' :
				if (!preg_match('/^[\x{4e00}-\x{9fa5}_a-zA-Z0-9]+$/u', $username) || $flag)
				{
					return '请输入 ' . ceil ($length_min / 2) . ' - ' . floor($length_max / 2) . ' 个汉字或 ' . $length_min . ' - ' . $length_max . ' 个字母、数字';
				}
				break;
				
			case '2' :
				if (!preg_match("/^[a-zA-Z0-9_]+$/i", $username) || $flag)
				{
					return '请输入 ' . $length_min . ' - ' . $length_max . ' 个字母、数字或下划线';
				}
				break;
				
			case '3' :
				if (!preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $username) || $flag)
				{
					return '请输入 ' . ceil ($length_min / 2) . ' - ' . floor($length_max / 2) . ' 个汉字';
				}
				break;
		}
		
		return false;
	}

	/**
	 * 
	 * 根据where条件批量获取用户
	 * @param string $where
	 * @param int    $limit
	 * 
	 * @return array
	 */
	public function get_user_list($where = '', $limit = 10, $orderby = 'uid ASC')
	{
		if ($where)
		{
			$where = ' WHERE forbidden = 0 AND group_id <> 3 AND ' . $where;
		}
		
		return $this->query_all("SELECT uid FROM " . $this->get_table('users') . $where . " ORDER BY " . $orderby . " LIMIT " . $limit);
	}

	/**
	 * 
	 * @param string $where
	 * @param int    $limit
	 * 
	 * @return array
	 */
	public function get_users_list($where, $limit = 10, $attrib = false, $exclude_self = true, $orderby = 'uid DESC')
	{
		if ($attrib)
		{
			if ($where)
			{
				$where = ' WHERE MEM.forbidden = 0 AND MEM.group_id <> 3 AND (' . $where . ')';
			}
			else 
			{
				$where = ' WHERE MEM.forbidden = 0 AND MEM.group_id <> 3';
			}
			
			if ($exclude_self)
			{
				if ($where)
				{
					$where .= " AND MEM.uid <> " . USER::get_client_uid();
				}
				else
				{
					$where = " WHERE MEM.uid <> " . USER::get_client_uid();
				}
			}
			
			$result = $this->query_all("SELECT MEM.*, MEB.* FROM " . $this->get_table('users') . " MEM LEFT JOIN " . $this->get_table('users_attrib') . " AS MEB ON MEM.uid = MEB.uid " . $where . " ORDER BY MEM.{$orderby}", $limit);
		}
		else
		{
			if ($exclude_self)
			{
				if ($where)
				{
					$where .= ' AND forbidden = 0 AND group_id <> 3 AND uid <> ' . USER::get_client_uid();
				}
				else
				{
					$where = ' forbidden = 0 AND group_id <> 3 AND uid <> ' . USER::get_client_uid();
				}
			}
			
			$result = $this->fetch_all('users', $where, $orderby, $limit);
		}
		
		if ($result)
		{
			foreach ($result AS $key => $val)
			{
				if (!$val['url_token'] AND $val['user_name'])
				{
					$result[$key]['url_token'] = urlencode($val['user_name']);
				}
			}
		}
		
		return $result;
	}
	
	public function get_users_list_by_search($count = false, $search_data = null)
	{
		$where = array();
		$page = 0;
		$per_page = 0;
		$sort_key = 'uid';
		$order = 'DESC';
		
		if (is_array($search_data))
		{
			extract($search_data);
		}
		
		if ($user_name)
		{
			$where[] = "user_name LIKE '%" . $this->quote($user_name) . "%'";
		}
		
		if ($email)
		{
			$where[] = "email = '" . $this->quote($email) . "'";
		}
		
		if ($group_id)
		{
			$where[] = 'group_id = ' . intval($group_id);
		}
		
		if ($reg_date)
		{
			$reg_time = intval(strtotime($reg_date));
			
			$where[] = 'reg_time BETWEEN ' . $reg_time . ' AND ' . ($reg_time + 86400);
		}
		
		if ($last_login_date)
		{
			$last_login_time = intval(strtotime($last_login_date));
			
			$where[] = 'last_login BETWEEN ' . $last_login_time . ' AND ' . ($last_login_time + 86400);
		}
		
		if ($ip)
		{
			if (preg_match('/.*\.\\*$/i', $ip))
			{
				$ip_base = ip2long(str_replace('*', '0', $ip));

				$where[] = 'last_ip BETWEEN ' . $ip_base . ' AND ' . ($ip_base + 255);
			}
			else
			{
				$where[] = 'last_ip = ' . ip2long($ip);
			}
		}
		
		if ($integral_min || $integral_min == '0')
		{
			$where[] = 'integral >= ' . intval($integral_min);
		}
		
		if ($integral_max || $integral_max == '0')
		{
			$where[] = 'integral <= ' . intval($integral_max);
		}
		
		if ($reputation_min || $reputation_min == '0')
		{
			$where[] = 'reputation >= ' . intval($reputation_min);
		}
		
		if ($reputation_max || $reputation_max == '0')
		{
			$where[] = 'reputation <= ' . intval($reputation_max);
		}
		
		if ($answer_count_min || $answer_count_min == '0')
		{
			$where[] = 'answer_count >= ' . intval($answer_count_min);
		}
		
		if ($answer_count_max || $answer_count_max == '0')
		{
			$where[] = 'answer_count <= ' . intval($answer_count_max);
		}
		
		if ($job_id)
		{
			$where[] = 'job_id = ' . intval($job_id);
		}
		
		if ($province)
		{
			$where[] = "province = '" . $this->quote($province) . "'";
		}
		
		if ($city)
		{
			$where[] = "city = '" . $this->quote($city) . "'";
		}
		
		if ($birthday)
		{
			$birthday_time = intval(strtotime($birthday));
			
			$where[] = 'last_login BETWEEN ' . $birthday_time . ' AND ' . ($birthday_time + 86400);
		}
		
		if ($signature)
		{
			$attrib_list = $this->fetch_all('users_attrib', "signature LIKE '%" . $this->quote($signature) . "%'");
			
			$where[] = 'uid IN (' . implode(',', array_merge(array(0), fetch_array_value($attrib_list, 'uid'))) . ')';
		}
		
		if ($common_email)
		{
			$where[] = "common_email = '" . $this->quote($common_email) . "'";
		}
		
		if ($mobile)
		{
			$where[] = 'mobile = ' . $this->quote($mobile);
		}
		
		if ($qq)
		{
			$attrib_list = $this->fetch_all('users_attrib', 'qq = ' . intval($qq));
			
			$where[] = 'uid IN (' . implode(',', array_merge(array(0), fetch_array_value($attrib_list, 'uid'))) . ')';
		}
		
		if ($homepage)
		{
			$attrib_list = $this->fetch_all('users_attrib', "homepage LIKE '%" . $this->quote($homepage) . "%'");
			
			$where[] = 'uid IN (' . implode(',', array_merge(array(0), fetch_array_value($attrib_list, 'uid'))) . ')';
		}
		
		if ($school_name)
		{
			$edu_list = $this->fetch_all('education_experience', "school_name LIKE '%" . $this->quote($school_name) . "%'");
			
			$where[] = 'uid IN (' . implode(',', array_merge(array(0), fetch_array_value($edu_list, 'uid'))) . ')';
		}
		
		if ($departments)
		{
			$edu_list = $this->fetch_all('education_experience', "departments LIKE '%" . $this->quote($departments) . "%'");
			
			$where[] = 'uid IN (' . implode(',', array_merge(array(0), fetch_array_value($edu_list, 'uid'))) . ')';
		}
		
		if ($company_name)
		{
			$work_list = $this->fetch_all('work_experience', "company_name LIKE '%" . $this->quote($company_name) . "%' AND job_id = " .  intval($company_job_id));
			
			$where[] = 'uid IN (' . implode(',', array_merge(array(0), fetch_array_value($work_list, 'uid'))) . ')';
		}
		
		if ($count)
		{
			return $this->count('users', implode(' AND ', $where));
		}
		
		if ($user_list = $this->fetch_page('users', implode(' AND ', $where), $sort_key . ' ' . $order, $page, $per_page))
		{
			foreach($user_list as $key => $val)
			{
				if (!$val['url_token'])
				{
					$user_list[$key]['url_token'] = rawurlencode($val['user_name']);
				}
			}
			return $user_list;
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * 批量获取多个话题关注的用户列表
	 * @param  $topics_array
	 */
	public function get_users_list_by_topic_focus($topic_ids)
	{	
		if ( !is_array($topic_ids) OR sizeof($topic_ids))
		{
			return false;
		}
		
		array_walk_recursive($topic_ids, 'intval_string');
		
		return $this->query_all("SELECT DISTINCT uid, topic_id FROM " . $this->get_table('topic_focus') . " WHERE topic_id IN(" . implode(",", $topic_ids) . ")");
	}

	/**
	 * 
	 * 根据where条件获取用户数量
	 * @param string $where
	 * @param int    $limit
	 * 
	 * @return array
	 */
	public function get_user_count($where = '')
	{
		return $this->count('users', $where);
	}

	/**
	 * 获取个人动态
	 */
	function get_user_actions($uid, $limit = 10, $actions = false, $this_uid = 0, $distint = true)
	{
		$this_uid = intval($this_uid);
		
		$action_question = ACTION_LOG::ADD_QUESTION;
		
		if (strstr($actions, ','))
		{
			$action_question = explode(',', $actions);
			
			array_walk_recursive($action_question, 'intval_string');
			
			$action_question = implode(',', $action_question);
		}
		else if ($actions)
		{
			$action_question = intval($actions);
		}
			
		if (!$uid)
		{
			$where[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_action IN(" . $this->quote($action_question) . "))";
		}
		else
		{
			$where[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND uid = " . intval($uid) . " AND associate_action IN(" . $this->quote($action_question) . "))";
		}
		
		if ($this_uid == $uid)
		{
			$show_anonymous = true;
		}
	
		if ($distint)
		{
			$action_list = ACTION_LOG::get_actions_distint_by_where(implode($where, ' OR '), $limit, null, $show_anonymous);
		}
		else
		{
			$action_list = ACTION_LOG::get_action_by_where(implode($where, ' OR '), $limit, $show_anonymous);
		}
		
		// 重组信息
		foreach ($action_list as $key => $val)
		{
			$users_ids[] = $val['uid'];
						
			switch ($val['associate_type'])
			{
				case ACTION_LOG::CATEGORY_QUESTION:
					$question_ids[] = $val['associate_id'];
				break;	
			}
		}
		
		if ($users_ids)
		{
			$action_list_users = $this->get_user_info_by_uids($users_ids, true);
		}
		
		if ($question_ids)
		{
			$action_questions_info = $this->model('question')->get_question_info_by_ids($question_ids);
			
			if ($this_uid)
			{
				$action_questions_focus = $this->model('question')->has_focus_questions($question_ids, $this_uid);
			}
			else if ($uid)
			{
				$action_questions_focus = $this->model('question')->has_focus_questions($question_ids, $uid);
			}
		}
		
		foreach ($action_list as $key => $val)
		{
			$action_list[$key]['user_info'] = $action_list_users[$val['uid']];
			
			switch ($val['associate_type'])
			{
				case ACTION_LOG::CATEGORY_QUESTION :
					$question_info = $action_questions_info[$val['associate_id']];
					
					if (in_array($val['associate_action'], array(
						ACTION_LOG::ADD_TOPIC,
						ACTION_LOG::MOD_TOPIC,
						ACTION_LOG::MOD_TOPIC_DESCRI,
						ACTION_LOG::MOD_TOPIC_PIC,
						ACTION_LOG::DELETE_TOPIC,
						ACTION_LOG::ADD_TOPIC_FOCUS,
						ACTION_LOG::DELETE_TOPIC_FOCUS,
						ACTION_LOG::ADD_TOPIC_PARENT,
						ACTION_LOG::DELETE_TOPIC_PARENT
					)) AND $val['associate_attached'])
					{
						$topic_info = $this->model('topic')->get_topic_by_id($val['associate_attached']);
					}
					
					if (in_array($val['associate_action'], array(
						ACTION_LOG::ADD_QUESTION
					)) AND $question_info['has_attach'])
					{
						$question_info['attachs'] = $this->model('publish')->get_attach('question', $question_info['question_id'], 'min'); //获取附件
					}
					
					if ($val['uid'])
					{			
						$question_info['last_action_str'] = ACTION_LOG::format_action_str($val['associate_action'], $val['uid'], $action_list_users[$val['uid']]['user_name'], $question_info, $topic_info);
					}
					
					if (in_array($val['associate_action'], array(
						ACTION_LOG::ANSWER_QUESTION
					)) AND $question_info['answer_count'] > 0)
					{
						$answer_list = $this->model('answer')->get_answer_by_id($val['associate_attached']);
					}
					else
					{
						$answer_list = null;
					}
					
					if (! empty($answer_list))
					{
						$user_info = $this->get_user_info_by_uid($answer_list['uid'], true);
						
						$answer_list['user_name'] = $user_info['user_name'];
						$answer_list['url_token'] = $user_info['url_token'];
						$answer_list['signature'] = $user_info['signature'];
						$answer_list['answer_content'] = strip_ubb($answer_list['answer_content']);
						$question_info['answer_info'] = $answer_list;
						
						if ($answer_list['has_attach'])
						{
							$answer_list['attachs'] = $this->model('publish')->get_attach('answer', $val['associate_attached'], 'min');
						}
					}
					
					$action_list[$key]['has_focus'] = $action_questions_focus[$question_info['question_id']];
					
					//还原到单个数组ROW里面
					if ($question_info)
					{
						foreach ($question_info as $qkey => $qval)
						{
							if ($qkey == 'add_time')
							{
								continue;
							}
							
							$action_list[$key][$qkey] = $qval;
						}
					}
					
					//$action_list[$key]['topics'] = $action_questions_topics[$question_info['question_id']];
					break;
			}
		}
		
		return $action_list;
	}

	public function get_user_recommend_v2($uid, $limit = 10)
	{
		if ($friends = $this->model('follow')->get_user_friends($uid, 100))
		{
			foreach ($friends as $key => $val)
			{
				$follow_uids[] = $val['friend_uid'];
				
				$follow_users_array[$val['friend_uid']] = $val;
			}
		}
		
		if (! $follow_uids)
		{
			return $this->get_users_list(false, $limit, true);
		}
		
		$users_ids = array();
		
		if ($users_focus = $this->query_all("SELECT DISTINCT friend_uid, fans_uid FROM " . $this->get_table('user_follow') . " WHERE fans_uid IN(" . implode($follow_uids, ',') . ") ORDER BY follow_id DESC LIMIT " . $limit))
		{
			foreach ($users_focus as $key => $val)
			{
				$users_ids[] = $val['friend_uid'];
				
				if (! isset($users_ids_rtype[$val['friend_uid']]))
				{
					$users_ids_rtype[$val['friend_uid']] = array(
						'type' => 'friend', 
						'fans_uid' => $val['fans_uid']
					); //推荐类型
				}
			}
		}
		
		//取我关注的话题
		if ($my_focus_topics = $this->model('topic')->get_focus_topic_list($uid, null))
		{
			foreach ($my_focus_topics as $key => $val)
			{
				$my_focus_topics_ids[] = $val['topic_id'];
				$my_focus_topics_array[$val['topic_id']] = $val;
			}
		}
		
		if ($my_focus_topics_ids)
		{
			$uids = $this->get_users_list_by_topic_focus($my_focus_topics_ids);
		}
		
		if ($uids)
		{
			foreach ($uids as $key => $val)
			{
				if (in_array($val['uid'], $users_ids))
				{
					continue;
				}
				
				$users_ids[$val['uid']] = $val['uid'];
				
				if (! isset($users_ids_rtype[$val['friend_uid']]))
				{
					$users_ids_rtype[$val['uid']] = array(
						"type" => "topic", 
						"topic_id" => $val['topic_id']
					);
				}
			}
		}
		
		if (! $users_ids)
		{
			return $this->get_users_list("MEM.uid NOT IN (" . implode($follow_uids, ',') . ")", $limit, true);
		}

		$users = $this->query_all("SELECT MEM.*, MEB.signature 
			FROM " . $this->get_table('users') . " MEM						
			LEFT JOIN " . $this->get_table('users_attrib') . " AS MEB 						
			ON MEM.uid = MEB.uid
			WHERE  (MEM.group_id <> 3 AND MEM.forbidden = 0) AND MEM.uid IN(" . implode($users_ids, ',') . ") AND MEM.uid NOT IN (" . implode($follow_uids, ',') . ") AND MEM.uid <> " . $uid . " ORDER BY MEM.uid DESC LIMIT " . $limit);
		
		foreach ($users as $key => $val)
		{
			$users[$key]['rtype'] = $users_ids_rtype[$val['uid']];
			
			if ($users_ids_rtype[$val['uid']]['type'] == "friend")
			{
				$users[$key]['friend_users'] = $follow_users_array[$users[$key]['rtype']['fans_uid']];
			}
			else if ($users_ids_rtype[$val['uid']]['type'] == "topic")
			{
				$users[$key]['topic_info'] = $my_focus_topics_array[$users[$key]['rtype']['topic_id']];
			}
			
			if (!$val['url_token'])
			{
				$users[$key]['url_token'] = urlencode($val['user_name']);
			}
		}
		
		return $users;
	}
	
	/**
	 * 根据职位ID获取职位信息
	 */
	function get_jobs_by_id($id)
	{
		if (!$id)
		{
			return false;
		}
		
		static $jobs_info;
		
		if (!$jobs_info[$id])
		{
			$jobs_info[$id] = $this->fetch_row('jobs', 'id = ' . intval($id));
		}
		
		return $jobs_info[$id];
	}

	/**
	 * 获取头像目录文件地址
	 * @param  $uid
	 * @param  $size
	 * @param  $return_type 0=返回全部 1=返回目录(a/b/c/) 2=返回文件名
	 * @return string
	 */
	function get_avatar($uid, $size = 'min', $return_type = 0)
	{
		$size = in_array($size, array(
			'max', 
			'mid', 
			'min', 
			'50', 
			'150'
		)) ? $size : 'real';
		
		$uid = abs(intval($uid));
		$uid = sprintf("%09d", $uid);
		$dir1 = substr($uid, 0, 3);
		$dir2 = substr($uid, 3, 2);
		$dir3 = substr($uid, 5, 2);
		
		if ($return_type == 1)
		{
			return $dir1 . '/' . $dir2 . '/' . $dir3 . '/';
		}
		
		if ($return_type == 2)
		{
			return substr($uid, - 2) . '_avatar_' . $size . '.jpg';
		}
		
		return $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . '_avatar_' . $size . '.jpg';
	}
	
	/**
	 * 删除用户头像
	 * @param unknown_type $uid
	 * @return boolean
	 */
	function delete_avatar($uid)
	{
		if (!$uid)
		{
			return false;
		}
			
		foreach( AWS_APP::config()->get('image')->avatar_thumbnail as $key => $val)
		{
			@unlink(get_setting('upload_dir').'/avatar/' . $this->get_avatar($uid, $key, 1) . $this->get_avatar($uid, $key, 2));
		}
		
		return $this->update_users_fields(array('avatar_file' => ''), $uid);
	}
	
	function update_thanks_count($uid)
	{
		$counter = $this->sum('answer', 'thanks_count', 'uid = ' . intval($uid));
		$counter += $this->sum('question', 'thanks_count', 'published_uid = ' . intval($uid));
		
		return $this->update('users', array(
			'thanks_count' => $counter
		), "uid = " . intval($uid));
	}
	
	// 获取活跃用户 (非垃圾用户)
	function get_activity_random_users($limit = 10, $extra_info = false, $uid_not_in = array())
	{
		// 好友 & 粉丝 > 5, 回复 > 5, 根据登陆时间, 倒序
		if (sizeof($uid_not_in) > 0)
		{
			$not_in_query = ' AND uid NOT IN(' . implode($uid_not_in, ',') . ')';
		}
		
		if ($extra_info)
		{
			$sql = "SELECT uid FROM " . $this->get_table('users') . " WHERE fans_count > 5 AND friend_count > 5 AND answer_count > 1 " . $not_in_query . " ORDER BY last_login DESC LIMIT " . $limit;
			
			if (! $rs = $this->query_all($sql))
			{
				return false;
			}
			
			foreach ($rs as $key => $val)
			{
				$user_id_array[] = $val['uid'];
			}
			
			if ($user_id_array)
			{
				return $this->get_user_info_by_uids($user_id_array, true);
			}
			
			return false;
		}
		
		return $this->fetch_all('users', "fans_count > 5 AND friend_count > 5 AND answer_count > 1 " . $not_in_query, 'last_login DESC', $limit);
	}
	
	function add_group($group_name, $reputation_lower, $reputation_higer, $reputation_factor)
	{
		$data = array(
			'type' => 1,
			'custom' => 1,
			'group_name' => $group_name,
			'reputation_lower' => $reputation_lower,
			'reputation_higer' => $reputation_higer,
			'reputation_factor' => $reputation_factor,
		);
	
		return $this->insert('users_group', $data);
	}
	
	function delete_group($group_id)
	{
		return $this->delete('users_group', 'group_id = ' . intval($group_id));
	}
	
	function update_group($group_id, $data)
	{
		return $this->update('users_group', $data, 'group_id = ' . intval($group_id));
	}
	
	function get_group_by_id($group_id, $field = null)
	{
		if (!$group_id)
		{
			return false;
		}
		
		static $groups;
		
		if (isset($groups[$group_id]))
		{
			if ($field)
			{
				return $groups[$group_id][$field];
			}
			else
			{
				return $groups[$group_id];
			}
		}
		
		$group = $this->fetch_row('users_group', 'group_id = ' . intval($group_id));
		
		if ($group['permission'])
		{
			$group['permission'] = unserialize($group['permission']);
		}

		$groups[$group_id] = $group;
		
		if ($field)
		{
			return $group[$field];
		}
		
		return $group;
	}
	
	function get_user_group_list($type = 0)
	{
		$group = array();
		
		if ($users_groups = $this->fetch_all('users_group', 'type = ' . intval($type)))
		{
			foreach ($users_groups as $key => $val)
			{
				$group[$val['group_id']] = $val;
			}
		}
		
		return $group;
	}
	
	function get_user_group_by_reputation($reputation, $field = null)
	{
		if ($mem_groups = $this->get_user_group_list(1))
		{
			foreach ($mem_groups as $key => $val)
			{
				if ((intval($reputation) >= intval($val['reputation_lower'])) && (intval($reputation) < intval($val['reputation_higer'])))
				{
					$group = $val;
					break;
				}
			}		
		}
		else	// 若会员组为空，则返回为普通会员组
		{
			$system_groups = $this->get_user_group_list(0);
			
			$group = $system_groups[4];
		}
		
		if ($field)
		{
			return $group[$field];
		}
		
		return $group;
	}
	
	function update_user_reputation_group($uid)
	{
		$user_info = $this->get_user_info_by_uid($uid);
		
		$reputation_group = $this->get_user_group_by_reputation($user_info['reputation'], 'group_id');
		
		if ($reputation_group != $user_info['reputation_group'])
		{
			return $this->update_users_fields(array(
				'reputation_group' => $reputation_group
			), $uid);
		}
		
		return false;
	}
	
	function get_user_group($group_id, $reputation_group = 0)
	{
		if ($group_id == 4)
		{
			$group_info = $this->model('account')->get_group_by_id($reputation_group);
		}
		
		if (!$group_info)
		{
			return $this->model('account')->get_group_by_id($group_id);
		}
		
		return $group_info;
	}
	
	function check_url_token($url_token, $uid)
	{
		return $this->count('users', "(url_token = '" . $this->quote($url_token) . "' OR user_name = '" . $this->quote($url_token) . "') AND uid != " . intval($uid));
	}
	
	function update_url_token($url_token, $uid)
	{
		return $this->update('users', array(
			'url_token' => $url_token,
			'url_token_update' => time()
		), 'uid = ' . intval($uid));
	}
	
	function forbidden_user($uid, $status, $admin_uid)
	{
		if (!$uid)
		{
			return false;
		}
		
		$this->model('account')->update_users_fields(array(
			'forbidden' => intval($status)
		), intval($uid));
		
		return $this->insert('users_forbidden', array(
			'uid' => intval($uid),
			'status' => intval($status), 
			'admin_uid' => $admin_uid,
			'add_time' => time(),
		));
	}
	
	function get_forbidden_user_list($count = false, $order = 'uid DESC', $limit = 10)
	{
		if ($count)
		{
			return $this->count('users', 'forbidden = 1');
		}
		
		if ($user_list = $this->fetch_all('users', 'forbidden = 1', $order, $limit))
		{
			$uids = fetch_array_value($user_list, 'uid');
			
			$users_forbidden = $this->fetch_all('users_forbidden', 'uid IN (' . implode(',', $uids) . ')', 'id DESC');
			
			$admin_uids = fetch_array_value($users_forbidden, 'admin_uid');
			
			$admin_user = $this->get_user_info_by_uids($admin_uids);
			
			$forbidden_log = array();
			
			foreach($users_forbidden as $key => $log)
			{
				if (!isset($forbidden_log[$log['uid']]))
				{
					$log['admin_info'] = $admin_user[$log['admin_uid']];
					
					$forbidden_log[$log['uid']] = $log;
				} 
			}
			
			foreach ($user_list as $key => $user)
			{
				$user_list[$key]['forbidden_log'] = $forbidden_log[$user['uid']];
			}
			
			return $user_list;
		}
		else
		{
			return array();
		}
	}
	
	public function set_default_timezone($time_zone, $uid)
	{
		return $this->update('users', array(
			'default_timezone' => htmlspecialchars($time_zone)
		), 'uid = ' . intval($uid));
	}
	
	public function send_delete_message($published_uid, $question_content, $question_detail)
	{
		$message = AWS_APP::lang()->_t('你发表的问题 %s 已被管理员删除', $question_content);
		$meesage .= "\r\n----- " . AWS_APP::lang()->_t('问题内容') . " -----\r\n" . $question_detail;
		$meesage .= "\r\n-----------------------------\r\n";
		$meesage .= AWS_APP::lang()->_t('如有疑问, 请联系管理员');

		$this->model('message')->send_message($this->user_id, $question_info['published_uid'], null, $message, 0, 0);
				
		$this->model('email')->action_email(email_class::TYPE_QUESTION_DEL, $published_uid, get_js_url('/inbox/'), array(
			'question_title' => $question_content,
			'question_detail' => $question_detail,
		));
		
		return true;
	}
}
