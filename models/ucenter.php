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


if (!defined('IN_ANWSION'))
{
	die;
}

class ucenter_class extends AWS_MODEL
{
	var $uc_client_path;
	var $ucenter_charset;

	function setup()
	{
		if (get_setting('ucenter_enabled') != 'Y')
		{
			throw new Zend_Exception('UCenter adapter not enabled.');
		}

		$this->uc_client_path = realpath(AWS_PATH . '../') . '/uc_client/';

		if (!file_exists($this->uc_client_path . '/client.php'))
		{
			throw new Zend_Exception('UCenter client not installed.');
		}

		if (!file_exists($this->uc_client_path . '/config.inc.php'))
		{
			throw new Zend_Exception('UCenter client config file not installed.');
		}

		require_once $this->uc_client_path . '/config.inc.php';
		require_once $this->uc_client_path . '/client.php';

		$this->ucenter_charset = strtolower(get_setting('ucenter_charset'));
	}

	function register($_username, $_password, $_email)
	{
		if ($this->ucenter_charset != 'utf-8')
		{
			$result = uc_user_register(convert_encoding($_username, 'utf-8', $this->ucenter_charset), $_password, $_email);
		}
		else
		{
			$result = uc_user_register($_username, $_password, $_email);
		}

		switch ($result)
		{
			default:
				$uid = $this->model('account')->user_register($_username, $_password, $_email);

				if (get_setting('register_valid_type') == 'N' OR (get_setting('register_valid_type') == 'email' AND get_setting('register_type') == 'invite'))
				{
					$this->model('active')->active_user_by_uid($uid);
				}

				return array(
					'user_info' => $this->model('account')->get_user_info_by_username($_username),
					'uc_uid' => $result,
					'username' => $_username,
					'email' => $_email
				);
			break;

			case -1:
				return '用户名不合法';
			break;

			case -2:
				return '用户名包含不允许注册的词语';
			break;

			case -3:
				return '用户名已经存在';
			break;

			case -4:
				return 'Email 格式有误';
			break;

			case -5:
				return 'Email 不允许注册';
			break;

			case -6:
				return '该 Email 已经被注册';
			break;
		}
	}

	function login($_username, $_password)
	{
		if (H::valid_email($_username))
		{
			// 使用 E-mail 登录
			list($uc_uid, $username, $password, $email) = uc_user_login($_username, $_password, 2);

		}

		if ($this->ucenter_charset != 'utf-8')
		{
			$username = convert_encoding($username, $this->ucenter_charset, 'UTF-8');
		}

		if (!$uc_uid)
		{
			if ($this->ucenter_charset != 'utf-8')
			{
				list($uc_uid, $username, $password, $email) = uc_user_login(convert_encoding($_username, 'utf-8', $this->ucenter_charset), $_password);

				if ($username)
				{
					$username = convert_encoding($username, $this->ucenter_charset, 'UTF-8');
				}
			}
			else
			{
				list($uc_uid, $username, $password, $email) = uc_user_login($_username, $_password);
			}
		}

		if ($username)
		{
			$username = htmlspecialchars($username);
		}

		if ($uc_uid > 0)
		{
			if ($user_info = $this->get_uc_user_info($uc_uid))
			{
				// Update password
				$this->model('account')->update_user_password_ingore_oldpassword($_password, $user_info['uid'], $user_info['salt']);

				// Update username
				if ($user_info['user_name'] != $username)
				{
					if (!$this->model('account')->check_username($username))
					{
						$this->model('account')->update_user_name($username, $user_info['uid']);

						$this->update('users_ucenter', array(
							'username' => htmlspecialchars($username),
						), 'uc_uid = ' . intval($uc_uid));
					}
				}
			}
			else
			{
				if ($site_user_info = $this->model('account')->get_user_info_by_email($email))
				{
					$this->insert('users_ucenter', array(
						'uid' => $site_user_info['uid'],
						'uc_uid' => $uc_uid,
						'username' => $username,
						'email' => $email
					));

					return false;
				}

				if ($new_user_id = $this->model('account')->user_register($username, $_password, $email, TRUE))
				{
					if ($exists_uc_id = $this->is_uc_user($email))
					{
						$this->update('users_ucenter', array(
							'username' => $username,
							'uid' => $new_user_id
						), 'uc_uid = ' . intval($exists_uc_id));
					}
					else
					{
						$this->insert('users_ucenter', array(
							'uid' => $new_user_id,
							'uc_uid' => $uc_uid,
							'username' => $username,
							'email' => $email
						));
					}

					$user_info = $this->model('account')->get_user_info_by_uid($new_user_id, true, false);
				}
			}
		}

		if (uc_check_avatar($uc_uid, 'big'))
		{
			if (!$user_info['avatar_file'])
			{
				$this->model('account')->associate_remote_avatar($user_info['uid'], UC_API . '/avatar.php?uid=' . $uc_uid . '&size=big');
			}
		}
		else
		{
			if ($user_info['avatar_file'] AND get_setting('ucenter_path'))
			{
				$avatar = get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($user_info['uid'], '');

				$uc_avatar_dir = get_setting('ucenter_path') . '/data/avatar/' . $this->model('account')->get_avatar($uc_uid, '', 1);

				if (!file_exists($uc_avatar_dir))
				{
					make_dir($uc_avatar_dir);
				}

				foreach(AWS_APP::config()->get('image')->uc_avatar_thumbnail AS $key => $val)
				{
					AWS_APP::image()->initialize(array(
						'quality' => 90,
						'source_image' => $avatar,
						'new_image' => $uc_avatar_dir . $this->model('account')->get_avatar($uc_uid, $key, 2),
						'width' => $val['w'],
						'height' => $val['h']
					))->resize();
				}
			}
		}

		return $user_info;
	}

	function get_uc_user_info($uc_uid)
	{
		$uc_user = $this->fetch_row('users_ucenter', 'uc_uid = ' . intval($uc_uid));

		return $this->model('account')->get_user_info_by_uid($uc_user['uid'], true);
	}

	function user_edit($uid, $_username, $oldpw, $newpw, $email = null)
	{
		if (!$uid)
		{
			return false;
		}

		$result = uc_user_edit($_username, $oldpw, $newpw, $email);

		switch ($result)
		{
			default:
				/*if ($new_pw)
				{
					$this->model('account')->update_user_password_ingore_oldpassword($newpw, $uid, fetch_salt(4));
				}*/

				return 1;
			break;

			case -1:
				return '旧密码不正确';
			break;

			case -4:
				return 'Email 格式有误';
			break;

			case -5:
				return 'Email 不允许注册';
			break;

			case -6:
				return '该 Email 已经被注册';
			break;

			/*case -7:
				return '没有做任何修改';
			break;*/

			case -8:
				return '该用户受保护无权限更改';
			break;
		}
	}

	function is_uc_user($email)
	{
		if (!$email)
		{
			return false;
		}

		static $uc_users;

		if ($uc_users[$email])
		{
			return $uc_users[$email];
		}

		$uc_user = $this->fetch_row('users_ucenter', "email = '" . $this->quote(trim($email)) . "'");

		$uc_users[$email] = $uc_user['uc_uid'];

		return $uc_users[$email];
	}

	function sync_login($uc_uid)
	{
		return uc_user_synlogin($uc_uid);
	}

	function sync_logout($uc_uid)
	{
		return uc_user_synlogout($uc_uid);
	}
}