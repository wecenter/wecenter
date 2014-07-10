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

if (!defined('IN_ANWSION'))
{
	die;
}

class admin_class extends AWS_MODEL
{
	public function fetch_menu_list($select_id)
	{
		$admin_menu = (array)AWS_APP::config()->get('admin_menu');

		if (empty($admin_menu))
		{
			return false;
		}

		foreach($admin_menu as $m_id => $menu)
		{
			if ($menu['children'])
			{
				foreach($menu['children'] as $c_id => $c_menu)
				{
					if ($select_id == $c_menu['id'])
					{
						$admin_menu[$m_id]['children'][$c_id]['select'] = true;
						$admin_menu[$m_id]['select'] = true;
					}
				}
			}
		}

		return $admin_menu;
	}

	public function set_admin_login($uid)
	{
		AWS_APP::session()->admin_login = H::encode_hash(array(
			'uid' => $uid,
			'UA' => $_SERVER['HTTP_USER_AGENT'],
			'ip' => fetch_ip()
		));
	}

	public function admin_logout()
	{
		if (isset(AWS_APP::session()->admin_login))
		{
			unset(AWS_APP::session()->admin_login);
		}
	}

	public function notifications_crond()
	{
		$last_version = json_decode(curl_get_contents('http://wenda.wecenter.com/api/version_check.php'), true);

		$admin_notifications = get_setting('admin_notifications');

		$notifications = array(
								// 内容审核
								'answer_approval' => $this->count('approval', "type = 'answer'"),
								'question_approval' => $this->count('approval', "type = 'question'"),
								'article_approval' => $this->count('approval', "type = 'article'"),
								'article_comment_approval' => $this->count('approval', "type = 'article_comment'"),
								'weibo_msg_approval' => $this->count('weibo_msg', 'question_id IS NULL'),
								'unverified_modify_count' => $this->count('question', "unverified_modify IS NOT NULL OR unverified_modify <> 'a:0:{}'"),

								// 用户举报
								'user_report' => $this->count('report', 'status = 0'),

								// 注册审核
								'register_approval' => $this->count('users', 'group_id = 3'),

								// 认证审核
								'verify_approval' => $this->count('verify_apply', 'status = 0'),

								// 程序更新
								'last_version' => $last_version['version'],
								'last_version_build_day' => $last_version['build_day'],

								// 新浪微博 Access Token 更新
								'sina_users' => $admin_notifications['sina_users']
							);

		$this->model('setting')->set_vars(array('admin_notifications' => $notifications));
	}
}
