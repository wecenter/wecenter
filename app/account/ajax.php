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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

		$rule_action['actions'] = array(
			'check_username',
			'check_email',
			'register_process',
			'login_process',
			'register_agreement',
			'send_valid_mail',
			'valid_email_active',
			'request_find_password',
			'find_password_modify',
			'weixin_login_process',
			'areas_json_data'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function check_username_action()
	{
		if ($this->model('account')->check_username_char($_GET['username']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名不符合规则')));
		}

		if ($this->model('account')->check_username_sensitive_words($_GET['username']) || $this->model('account')->check_username($_GET['username']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已被注册')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function check_email_action()
	{
		if (!$_GET['email'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入邮箱地址')));
		}

		if ($this->model('account')->check_email($_GET['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('邮箱地址已被使用')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function register_process_action()
	{
		if (get_setting('register_type') == 'close')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站目前关闭注册')));
		}
		else if (get_setting('register_type') == 'invite' AND !$_POST['icode'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过邀请注册')));
		}
		else if (get_setting('register_type') == 'weixin')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过微信注册')));
		}

		if ($_POST['icode'])
		{
			if (!$invitation = $this->model('invitation')->check_code_available($_POST['icode']) AND $_POST['email'] == $invitation['invitation_email'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('邀请码无效或与邀请邮箱不一致')));
			}
		}

		if (trim($_POST['user_name']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户名')));
		}
		else if ($this->model('account')->check_username($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已经存在')));
		}
		else if ($check_rs = $this->model('account')->check_username_char($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名包含无效字符')));
		}
		else if ($this->model('account')->check_username_sensitive_words($_POST['user_name']) OR trim($_POST['user_name']) != $_POST['user_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名中包含敏感词或系统保留字')));
		}

		if ($this->model('account')->check_email($_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('E-Mail 已经被使用, 或格式不正确')));
		}

		if (strlen($_POST['password']) < 6 OR strlen($_POST['password']) > 16)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
		}

		if (! $_POST['agreement_chk'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你必需同意用户协议才能继续')));
		}

		// 检查验证码
		if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']) AND get_setting('register_seccode') == 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
		}

		if (get_setting('ucenter_enabled') == 'Y')
		{
			$result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email']);

			if (is_array($result))
			{
				$uid = $result['user_info']['uid'];
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, $result));
			}
		}
		else
		{
			$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email']);
		}


		if ($_POST['email'] == $invitation['invitation_email'])
		{
			$this->model('active')->set_user_email_valid_by_uid($uid);

			$this->model('active')->active_user_by_uid($uid);
		}

		if (isset($_POST['sex']))
		{
			$update_data['sex'] = intval($_POST['sex']);

			if ($_POST['province'])
			{
				$update_data['province'] = htmlspecialchars($_POST['province']);
				$update_data['city'] = htmlspecialchars($_POST['city']);
			}

			if ($_POST['job_id'])
			{
				$update_data['job_id'] = intval($_POST['job_id']);
			}

			$update_attrib_data['signature'] = htmlspecialchars($_POST['signature']);

			// 更新主表
			$this->model('account')->update_users_fields($update_data, $uid);

			// 更新从表
			$this->model('account')->update_users_attrib_fields($update_attrib_data, $uid);
		}

		$this->model('account')->setcookie_logout();
		$this->model('account')->setsession_logout();

		if ($_POST['icode'])
		{
			$follow_users = $this->model('invitation')->get_invitation_by_code($_POST['icode']);
		}
		else if (HTTP::get_cookie('fromuid'))
		{
			$follow_users = $this->model('account')->get_user_info_by_uid(HTTP::get_cookie('fromuid'));
		}

		if ($follow_users['uid'])
		{
			$this->model('follow')->user_follow_add($uid, $follow_users['uid']);
			$this->model('follow')->user_follow_add($follow_users['uid'], $uid);

			$this->model('integral')->process($follow_users['uid'], 'INVITE', get_setting('integral_system_config_invite'), '邀请注册: ' . $_POST['user_name'], $follow_users['uid']);
		}

		if ($_POST['icode'])
		{
			$this->model('invitation')->invitation_code_active($_POST['icode'], time(), fetch_ip(), $uid);
		}

		if (get_setting('register_valid_type') == 'N' OR (get_setting('register_valid_type') == 'email' AND get_setting('register_type') == 'invite'))
		{
			$this->model('active')->active_user_by_uid($uid);
		}

		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		if (get_setting('register_valid_type') == 'N' OR $user_info['group_id'] != 3 OR $_POST['email'] == $invitation['invitation_email'])
		{
			$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $_POST['password'], $user_info['salt']);

			if (!$_POST['_is_mobile'])
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/home/first_login-TRUE')
				), 1, null));
			}
		}
		else
		{
			AWS_APP::session()->valid_email = $user_info['email'];

			$this->model('active')->new_valid_email($uid);

			if (!$_POST['_is_mobile'])
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/account/valid_email/')
				), 1, null));
			}
		}

		if ($_POST['_is_mobile'])
		{
			if ($_POST['return_url'])
			{
				$user_info = $this->model('account')->get_user_info_by_uid($uid);

				$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $_POST['password'], $user_info['salt']);

				$return_url = strip_tags($_POST['return_url']);
			}
			else
			{
				$return_url = get_js_url('/m/');
			}

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $return_url
			), 1, null));
		}
	}

	public function login_process_action()
	{
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if (!$user_info = $this->model('ucenter')->login($_POST['user_name'], $_POST['password']))
			{
				$user_info = $this->model('account')->check_login($_POST['user_name'], $_POST['password']);
			}
		}
		else
		{
			$user_info = $this->model('account')->check_login($_POST['user_name'], $_POST['password']);
		}

		if (! $user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的帐号或密码')));
		}
		else
		{
			if ($user_info['forbidden'] == 1)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('抱歉, 你的账号已经被禁止登录')));
			}

			if (get_setting('site_close') == 'Y' AND $user_info['group_id'] != 1)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, get_setting('close_notice')));
			}

			if (get_setting('register_valid_type') == 'approval' AND $user_info['group_id'] == 3)
			{
				$url = get_js_url('/account/valid_approval/');
			}
			else
			{
				if ($_POST['net_auto_login'])
				{
					$expire = 60 * 60 * 24 * 360;
				}

				$this->model('account')->update_user_last_login($user_info['uid']);
				$this->model('account')->setcookie_logout();

				$this->model('account')->setcookie_login($user_info['uid'], $_POST['user_name'], $_POST['password'], $user_info['salt'], $expire);

				if (get_setting('register_valid_type') == 'email' AND !$user_info['valid_email'])
				{
					AWS_APP::session()->valid_email = $user_info['email'];

					$url = get_js_url('/account/valid_email/');
				}
				else if ($user_info['is_first_login'] AND !$_POST['_is_mobile'])
				{
					$url = get_js_url('/home/first_login-TRUE');
				}
				else if ($_POST['return_url'] AND !strstr($_POST['return_url'], '/logout') AND
					($_POST['_is_mobile'] AND strstr($_POST['return_url'], '/m/') OR
					strstr($_POST['return_url'], '://') AND strstr($_POST['return_url'], base_url())))
				{
					$url = strip_tags($_POST['return_url']);
				}
				else if ($_POST['_is_mobile'])
				{
					$url = get_js_url('/m/');
				}

				if (get_setting('ucenter_enabled') == 'Y')
				{
					$sync_url = get_js_url('/account/sync_login/');

					$url = ($url) ? $sync_url . 'url-' . base64_encode($url) : $sync_url;
				}
			}

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $url
			), 1, null));
		}
	}

	public function register_agreement_action()
	{
		H::ajax_json_output(AWS_APP::RSM(null, 1, nl2br(get_setting('register_agreement'))));
	}

	public function welcome_message_template_action()
	{
		TPL::assign('job_list', $this->model('work')->get_jobs_list());

		TPL::output('account/ajax/welcome_message_template');
	}

	public function welcome_get_topics_action()
	{
		if ($topics_list = $this->model('topic')->get_topic_list(null, 'RAND()', 8))
		{
			foreach ($topics_list as $key => $topic)
			{
				$topics_list[$key]['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic['topic_id']);
			}
		}
		TPL::assign('topics_list', $topics_list);

		TPL::output('account/ajax/welcome_get_topics');
	}

	public function welcome_get_users_action()
	{
		if ($welcome_recommend_users = trim(rtrim(get_setting('welcome_recommend_users'), ',')))
		{
			$welcome_recommend_users = explode(',', $welcome_recommend_users);

			$users_list = $this->model('account')->get_users_list("user_name IN('" . implode("','", $welcome_recommend_users) . "')", 6, true, true, 'RAND()');
		}

		if (!$users_list)
		{
			$users_list = $this->model('account')->get_activity_random_users(6);
		}

		if ($users_list)
		{
			foreach ($users_list as $key => $val)
			{
				$users_list[$key]['follow_check'] = $this->model('follow')->user_follow_check($this->user_id, $val['uid']);
			}
		}

		TPL::assign('users_list', $users_list);

		TPL::output('account/ajax/welcome_get_users');
	}

	public function clean_first_login_action()
	{
		$this->model('account')->clean_first_login($this->user_id);

		die('success');
	}

	public function delete_draft_action()
	{
		if (!$_POST['type'])
		{
			die;
		}

		if ($_POST['type'] == 'clean')
		{
			$this->model('draft')->clean_draft($this->user_id);
		}
		else
		{
			$this->model('draft')->delete_draft($_POST['item_id'], $_POST['type'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function save_draft_action()
	{
		if (!$_GET['item_id'] OR !$_GET['type'] OR !$_POST)
		{
			die;
		}

		$this->model('draft')->save_draft($_GET['item_id'], $_GET['type'], $this->user_id, $_POST);

		H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('已保存草稿, %s', date('H:i:s', time()))));
	}

	public function send_valid_mail_action()
	{
		if (!$this->user_id)
		{
			if ( H::valid_email(AWS_APP::session()->valid_email))
			{
				$this->user_info = $this->model('account')->get_user_info_by_email(AWS_APP::session()->valid_email);
				$this->user_id = $this->user_info['uid'];
			}
		}

		if (! H::valid_email($this->user_info['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误, 用户没有提供 E-mail')));
		}

		if ($this->user_info['valid_email'] == 1)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户邮箱已经认证')));
		}

		$this->model('active')->new_valid_email($this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('邮件发送成功')));
	}

	public function valid_email_active_action()
	{
		if (!$active_data = $this->model('active')->get_active_code($_POST['active_code'], 'VALID_EMAIL'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('激活失败, 无效的链接')));
		}

		if ($active_data['active_time'] OR $active_data['active_ip'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/account/login/'),
			), 1, null));
		}

		if (!$user_info = $this->model('account')->get_user_info_by_uid($active_data['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('激活失败, 无效的链接')));
		}

		if ($user_info['valid_email'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/account/login/'),
			), 1, null));
		}

		if ($this->model('active')->active_code_active($_POST['active_code'], 'VALID_EMAIL'))
		{
			if (AWS_APP::session()->valid_email)
			{
				unset(AWS_APP::session()->valid_email);
			}

			$this->model('active')->set_user_email_valid_by_uid($user_info['uid']);

			if (get_setting('register_valid_type') == 'email' OR get_setting('register_valid_type') == 'N')
			{
				if ($user_info['group_id'] == 3)
				{
					$this->model('active')->active_user_by_uid($user_info['uid']);
				}

				// 帐户激活成功，切换为登录状态跳转至首页
				$this->model('account')->setsession_logout();
				$this->model('account')->setcookie_logout();

				$this->model('account')->update_user_last_login($user_info['uid']);

				$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $user_info['password'], $user_info['salt'], null, false);
			}

			$this->model('account')->welcome_message($user_info['uid'], $user_info['user_name'], $user_info['email']);

			if (get_setting('register_valid_type') == 'email' OR get_setting('register_valid_type') == 'N')
			{
				$url = $user_info['is_first_login'] ? '/first_login-TRUE' : '/';

				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url($url)
				), 1, null));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('激活成功, 请等待管理员审核账户')));
			}
		}
	}

	public function request_find_password_action()
	{
		if (!H::valid_email($_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('请填写正确的邮箱地址')));
		}

		if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1',  AWS_APP::lang()->_t('请填写正确的验证码')));
		}

		if (!$user_info = $this->model('account')->get_user_info_by_email($_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('邮箱地址错误或帐号不存在')));
		}

		$this->model('active')->new_find_password($user_info['uid']);

		AWS_APP::session()->find_password = $user_info['email'];

		if (is_mobile())
		{
			$url = get_js_url('/m/find_password_success/');
		}
		else
		{
			$url = get_js_url('/account/find_password/process_success/');
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => $url
		), 1, null));
	}

	public function find_password_modify_action()
	{
		if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('请填写正确的验证码')));
		}

		$active_data = $this->model('active')->get_active_code($_POST['active_code'], 'FIND_PASSWORD');

		if ($active_data)
		{
			if ($active_data['active_time'] OR $active_data['active_ip'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('链接已失效，请重新找回密码')));
			}
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('链接已失效，请重新找回密码')));
		}

		if (!$_POST['password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('请输入密码')));
		}

		if ($_POST['password'] != $_POST['re_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('两次输入的密码不一致')));
		}

		if (! $uid = $this->model('active')->active_code_active($_POST['active_code'], 'FIND_PASSWORD'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('链接已失效，请重新找回密码')));
		}

		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		$this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $uid, $user_info['salt']);

		$this->model('active')->set_user_email_valid_by_uid($user_info['uid']);

		if ($user_info['group_id'] == 3)
		{
			$this->model('active')->active_user_by_uid($user_info['uid']);
		}

		$this->model('account')->setcookie_logout();

		$this->model('account')->setsession_logout();

		unset(AWS_APP::session()->find_password);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/account/login/'),
		), 1, AWS_APP::lang()->_t('密码修改成功, 请返回登录')));
	}

	public function avatar_upload_action()
	{
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,jpeg,png,gif',
			'upload_path' => get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($this->user_id, '', 1),
			'is_image' => TRUE,
			'max_size' => get_setting('upload_avatar_size_limit'),
			'file_name' => $this->model('account')->get_avatar($this->user_id, '', 2),
			'encrypt_name' => FALSE
		))->do_upload('aws_upload_file');

		if (AWS_APP::upload()->get_error())
		{
			switch (AWS_APP::upload()->get_error())
            {
                default:
                    die("{'error':'错误代码: " . AWS_APP::upload()->get_error() . "'}");
                break;

                case 'upload_invalid_filetype':
                    die("{'error':'文件类型无效'}");
                break;

                case 'upload_invalid_filesize':
                    die("{'error':'文件尺寸过大, 最大允许尺寸为 " . get_setting('upload_size_limit') .  " KB'}");
                break;
            }
		}

		if (! $upload_data = AWS_APP::upload()->data())
        {
            die("{'error':'上传失败, 请与管理员联系'}");
        }

		if ($upload_data['is_image'] == 1)
		{
			foreach(AWS_APP::config()->get('image')->avatar_thumbnail AS $key => $val)
			{
				$thumb_file[$key] = $upload_data['file_path'] . $this->model('account')->get_avatar($this->user_id, $key, 2);

				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => $thumb_file[$key],
					'width' => $val['w'],
					'height' => $val['h']
				))->resize();
			}
		}

		$update_data['avatar_file'] = $this->model('account')->get_avatar($this->user_id, null, 1) . basename($thumb_file['min']);

		// 更新主表
		$this->model('account')->update_users_fields($update_data, $this->user_id);

		if (!$this->model('integral')->fetch_log($this->user_id, 'UPLOAD_AVATAR'))
		{
			$this->model('integral')->process($this->user_id, 'UPLOAD_AVATAR', round((get_setting('integral_system_config_profile') * 0.2)), '上传头像');
		}

		echo htmlspecialchars(json_encode(array(
			'success' => true,
			'thumb' => get_setting('upload_url') . '/avatar/' . $this->model('account')->get_avatar($this->user_id, null, 1) . basename($thumb_file['max'])
		)), ENT_NOQUOTES);
	}

	function add_edu_action()
	{
		$school_name = htmlspecialchars($_POST['school_name']);
		$education_years = intval($_POST['education_years']);
		$departments = htmlspecialchars($_POST['departments']);

		if (!$_POST['school_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入学校名称')));
		}

		if (!$_POST['departments'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入院系')));
		}

		if ($_POST['education_years'] == AWS_APP::lang()->_t('请选择') OR !$_POST['education_years'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择入学年份')));
		}

		if (preg_match('/\//is', $_POST['school_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('学校名称不能包含 /')));
		}

		if (preg_match('/\//is', $_POST['departments']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('院系名称不能包含 /')));
		}

		if (get_setting('auto_create_social_topics') == 'Y')
		{
			$this->model('topic')->save_topic($_POST['school_name']);
			$this->model('topic')->save_topic($_POST['departments']);
		}

		$edu_id = $this->model('education')->add_education_experience($this->user_id, $school_name, $education_years, $departments);

		if (!$this->model('integral')->fetch_log($this->user_id, 'UPDATE_EDU'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_EDU', round((get_setting('integral_system_config_profile') * 0.2)), AWS_APP::lang()->_t('完善教育经历'));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'id' => $edu_id
		), 1, null));

	}

	function remove_edu_action()
	{
		$this->model('education')->del_education_experience($_POST['id'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));

	}

	function add_work_action()
	{
		if (!$_POST['company_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入公司名称')));
		}

		if (!$_POST['job_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择职位')));
		}

		if (!$_POST['start_year'] OR !$_POST['end_year'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择工作时间')));
		}

		if (preg_match('/\//is', $_POST['company_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('公司名称不能包含 /')));
		}

		if (get_setting('auto_create_social_topics') == 'Y')
		{
			$this->model('topic')->save_topic($_POST['company_name']);
		}

		$work_id = $this->model('work')->add_work_experience($this->user_id, $_POST['start_year'], $_POST['end_year'], $_POST['company_name'], $_POST['job_id']);

		if (!$this->model('integral')->fetch_log($this->user_id, 'UPDATE_WORK'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_WORK', round((get_setting('integral_system_config_profile') * 0.2)), AWS_APP::lang()->_t('完善工作经历'));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'id' => $work_id
		), 1, null));
	}

	function remove_work_action()
	{
		$this->model('work')->del_work_experience($_POST['id'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	//修改教育经历
	function edit_edu_action()
	{
		if (!$_POST['school_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入学校名称')));
		}

		if (!$_POST['departments'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入院系')));
		}

		if (!$_POST['education_years'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择入学年份')));
		}

		$update_data['school_name'] = htmlspecialchars($_POST['school_name']);
		$update_data['education_years'] = intval($_POST['education_years']);
		$update_data['departments'] = htmlspecialchars($_POST['departments']);

		if (preg_match('/\//is', $_POST['school_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('学校名称不能包含 /')));
		}

		if (preg_match('/\//is', $_POST['departments']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('院系名称不能包含 /')));
		}

		if (get_setting('auto_create_social_topics') == 'Y')
		{
			$this->model('topic')->save_topic($_POST['school_name']);
			$this->model('topic')->save_topic($_POST['departments']);
		}

		$this->model('education')->update_education_experience($update_data, $_GET['id'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	//修改工作经历
	function edit_work_action()
	{
		if (!$_POST['company_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入公司名称')));
		}

		if (!$_POST['job_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择职位')));
		}

		if (!$_POST['start_year'] OR !$_POST['end_year'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请选择工作时间')));
		}

		$update_data['job_id'] = intval($_POST['job_id']);
		$update_data['company_name'] = htmlspecialchars($_POST['company_name']);

		$update_data['start_year'] = intval($_POST['start_year']);
		$update_data['end_year'] = intval($_POST['end_year']);

		if (preg_match('/\//is', $_POST['company_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('公司名称不能包含 /')));
		}

		if (get_setting('auto_create_social_topics') == 'Y')
		{
			$this->model('topic')->save_topic($_POST['company_name']);
		}

		$this->model('work')->update_work_experience($update_data, $_GET['id'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function privacy_setting_action()
	{
		if ($notify_actions = $this->model('notify')->notify_action_details)
		{
			$notification_setting = array();

			foreach ($notify_actions as $key => $val)
			{
				if (! isset($_POST['notification_settings'][$key]) AND $val['user_setting'])
				{
					$notification_setting[] = intval($key);
				}
			}
		}

		$email_settings = array(
			'FOLLOW_ME' => 'N',
			'QUESTION_INVITE' => 'N',
			'NEW_ANSWER' => 'N',
			'NEW_MESSAGE' => 'N',
			'QUESTION_MOD' => 'N',
		);

		if ($_POST['email_settings'])
		{
			foreach ($_POST['email_settings'] AS $key => $val)
			{
				unset($email_settings[$val]);
			}
		}

		$weixin_settings = array(
			'AT_ME' => 'N',
			'NEW_ANSWER' => 'N',
			'NEW_ARTICLE_COMMENT',
			'NEW_COMMENT' => 'N',
			'QUESTION_INVITE' => 'N'
		);

		if ($_POST['weixin_settings'])
		{
			foreach ($_POST['weixin_settings'] AS $key => $val)
			{
				unset($weixin_settings[$val]);
			}
		}

		$this->model('account')->update_users_fields(array(
			'email_settings' => serialize($email_settings),
			'weixin_settings' => serialize($weixin_settings),
			'weibo_visit' => intval($_POST['weibo_visit']),
			'inbox_recv' => intval($_POST['inbox_recv'])
		), $this->user_id);

		$this->model('account')->update_notification_setting_fields($notification_setting, $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('隐私设置保存成功')));
	}

	public function profile_setting_action()
	{
		if (!$this->user_info['user_name'] OR $this->user_info['user_name'] == $this->user_info['email'] AND $_POST['user_name'])
		{
			$update_data['user_name'] = htmlspecialchars(trim($_POST['user_name']));

			if ($check_result = $this->model('account')->check_username_char($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', $check_result));
			}
		}

		if ($_POST['url_token'] AND $_POST['url_token'] != $this->user_info['url_token'])
		{
			if ($this->user_info['url_token_update'] AND $this->user_info['url_token_update'] > (time() - 3600 * 24 * 30))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你距离上次修改个性网址未满 30 天')));
			}

			if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('个性网址只允许输入英文或数字')));
			}

			if ($this->model('account')->check_url_token($_POST['url_token'], $this->user_id))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('个性网址已经被占用请更换一个')));
			}

			if (preg_match("/^[\d]+$/i", $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('个性网址不允许为纯数字')));
			}

			$this->model('account')->update_url_token($_POST['url_token'], $this->user_id);
		}

		if ($update_data['user_name'] and $this->model('account')->check_username($update_data['user_name']) and $this->user_info['user_name'] != $update_data['user_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经存在相同的姓名, 请重新填写')));
		}

		if (! H::valid_email($this->user_info['email']))
		{
			if (! H::valid_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入正确的 E-Mail 地址')));
			}

			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('邮箱已经存在, 请使用新的邮箱')));
			}

			$update_data['email'] = $_POST['email'];

			$this->model('active')->new_valid_email($this->user_id, $_POST['email']);
		}

		if ($_POST['common_email'])
		{
			if (! H::valid_email($_POST['common_email']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入正确的常用邮箱地址')));
			}

			$update_data['common_email'] = $_POST['common_email'];
		}

		$update_data['sex'] = intval($_POST['sex']);


		$update_data['province'] = htmlspecialchars($_POST['province']);

		$update_data['city'] = htmlspecialchars($_POST['city']);

		if ($_POST['birthday_y'])
		{
			$update_data['birthday'] = intval(strtotime(intval($_POST['birthday_y']) . '-' . intval($_POST['birthday_m']) . '-' . intval($_POST['birthday_d'])));
		}

		if (!$this->user_info['verified'])
		{
			$update_attrib_data['signature'] = htmlspecialchars($_POST['signature']);
		}

		$update_data['job_id'] = intval($_POST['job_id']);

		if ($_POST['signature'] AND !$this->model('integral')->fetch_log($this->user_id, 'UPDATE_SIGNATURE'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_SIGNATURE', round((get_setting('integral_system_config_profile') * 0.1)), AWS_APP::lang()->_t('完善一句话介绍'));
		}

		$update_attrib_data['qq'] = htmlspecialchars($_POST['qq']);
		$update_attrib_data['homepage'] = htmlspecialchars($_POST['homepage']);
		$update_data['mobile'] = htmlspecialchars($_POST['mobile']);

		if (($update_attrib_data['qq'] OR $update_attrib_data['homepage'] OR $update_data['mobile']) AND !$this->model('integral')->fetch_log($this->user_id, 'UPDATE_CONTACT'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_CONTACT', round((get_setting('integral_system_config_profile') * 0.1)), AWS_APP::lang()->_t('完善联系资料'));
		}

		if (get_setting('auto_create_social_topics') == 'Y')
		{
			if ($_POST['city'])
			{
				$this->model('topic')->save_topic($_POST['city']);
			}

			if ($_POST['province'])
			{
				$this->model('topic')->save_topic($_POST['province']);
			}
		}

		// 更新主表
		$this->model('account')->update_users_fields($update_data, $this->user_id);

		// 更新从表
		$this->model('account')->update_users_attrib_fields($update_attrib_data, $this->user_id);

		$this->model('account')->set_default_timezone($_POST['default_timezone'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('个人资料保存成功')));
	}

	public function modify_password_action()
	{
		if (!$_POST['old_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入当前密码')));
		}

		if ($_POST['password'] != $_POST['re_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入相同的确认密码')));
		}

		if (strlen($_POST['password']) < 6 OR strlen($_POST['password']) > 16)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
		}

		if (get_setting('ucenter_enabled') == 'Y')
		{
			if ($this->model('ucenter')->is_uc_user($this->user_info['email']))
			{
				$result = $this->model('ucenter')->user_edit($this->user_id, $this->user_info['user_name'], $_POST['old_password'], $_POST['password']);

				if ($result !== 1)
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, $result));
				}
			}
		}

		if ($this->model('account')->update_user_password($_POST['old_password'], $_POST['password'], $this->user_id, $this->user_info['salt']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('密码修改成功, 请牢记新密码')));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入正确的当前密码')));
		}
	}

	public function areas_json_data_action()
	{
		readfile(ROOT_PATH . 'static/js/areas.js');
	}

	public function integral_log_action()
	{
		if ($log = $this->model('integral')->fetch_all('integral_log', 'uid = ' . $this->user_id, 'time DESC', (intval($_GET['page']) * 10) . ', 10'))
		{
			foreach ($log AS $key => $val)
			{
				$parse_items[$val['id']] = array(
					'item_id' => $val['item_id'],
					'action' => $val['action']
				);
			}

			TPL::assign('log', $log);
			TPL::assign('log_detail', $this->model('integral')->parse_log_item($parse_items));
		}

		TPL::output('account/ajax/integral_log');
	}

	public function verify_action()
	{
		if ($this->is_post() AND !$this->user_info['verified'])
		{
			if (trim($_POST['name']) == '')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入真实姓名或企业名称')));
			}

			if (trim($_POST['reason']) == '')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入申请认证说明')));
			}

			if ($_FILES['attach']['name'])
			{
				AWS_APP::upload()->initialize(array(
					'allowed_types' => 'jpg,png,gif',
					'upload_path' => get_setting('upload_dir') . '/verify',
					'is_image' => FALSE,
					'encrypt_name' => TRUE
				))->do_upload('attach');

				if (AWS_APP::upload()->get_error())
				{
					switch (AWS_APP::upload()->get_error())
					{
						default:
							H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
						break;

						case 'upload_invalid_filetype':
							H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
						break;
					}
				}

				if (! $upload_data = AWS_APP::upload()->data())
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
				}
			}

			$this->model('verify')->add_apply($this->user_id, $_POST['name'], $_POST['reason'], $_POST['type'], array(
				'id_code' => htmlspecialchars($_POST['id_code']),
				'contact' => htmlspecialchars($_POST['contact'])
			), basename($upload_data['full_path']));

			$recipient_uid = get_setting('report_message_uid') ? get_setting('report_message_uid') : 1;

			$this->model('message')->send_message($this->user_id, $recipient_uid, AWS_APP::lang()->_t('有新的认证请求, 请登录后台查看处理: %s', get_js_url('/admin/user/verify_approval_list/')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function clean_user_recommend_cache_action()
	{
		AWS_APP::cache()->delete('user_recommend_' . $this->user_id);
	}

	public function unbinding_weixin_action()
	{
		if (! $this->user_info['email'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('当前帐号没有绑定 Email, 不允许解除绑定')));
		}

		if (get_setting('register_type') == 'weixin')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('当前系统设置不允许解除绑定')));
		}

		$this->model('openid_weixin_weixin')->weixin_unbind($this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function weixin_login_process_action()
	{
		if (!get_setting('weixin_app_id') OR !get_setting('weixin_app_secret') OR get_setting('weixin_account_role') != 'service')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('当前微信公众号暂不支持此功能')));
		}

		if ($user_info = $this->model('openid_weixin_weixin')->weixin_login_process(session_id()))
		{
			$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $user_info['password'], $user_info['salt'], null, false);

			H::ajax_json_output(AWS_APP::RSM(null, 1, null));
		}

		H::ajax_json_output(AWS_APP::RSM(null, -1, null));
	}

	public function complete_profile_action()
	{
		if ($this->user_info['email'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('当前帐号已经完善资料')));
		}

		if ($check_result = $this->model('account')->check_username_char($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', $check_result));
		}

		$update_data['user_name'] = htmlspecialchars(trim($_POST['user_name']));

		if (! H::valid_email($this->user_info['email']))
		{
			if (! H::valid_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入正确的 E-Mail 地址')));
			}

			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('邮箱已经存在, 请使用新的邮箱')));
			}

			$update_data['email'] = $_POST['email'];

			$this->model('active')->new_valid_email($this->user_id, $_POST['email']);
		}

		$this->model('account')->update_users_fields($update_data, $this->user_id);

		$this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $this->user_id, $this->user_info['salt']);

		$this->model('account')->setcookie_login($this->user_info['uid'], $update_data['user_name'], $_POST['password'], $this->user_info['salt']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}
