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

class setting extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('设置'), '/account/setting/');

		TPL::import_css('css/user-setting.css');
	}

	public function index_action()
	{
		HTTP::redirect('/account/setting/profile/');
	}

	public function profile_action()
	{
		$this->crumb(AWS_APP::lang()->_t('基本资料'), '/account/setting/profile/');

		for ($i = date('Y'); $i > 1900; $i--)
		{
			$birthday_y[$i] = $i;
		}

		TPL::assign('birthday_y', $birthday_y);

		for ($tmp_i = 1; $tmp_i <= 31; $tmp_i ++)
		{
			$birthday_d[$tmp_i] = $tmp_i;
		}

		TPL::assign('birthday_d', $birthday_d);

		TPL::assign('job_list', $this->model('work')->get_jobs_list());

		TPL::assign('education_experience_list', $this->model('education')->get_education_experience_list($this->user_id));

		$jobs_list = $this->model('work')->get_jobs_list();

		if ($work_experience_list = $this->model('work')->get_work_experience_list($this->user_id))
		{
			foreach ($work_experience_list as $key => $val)
			{
				$work_experience_list[$key]['job_name'] = $jobs_list[$val['job_id']];
			}
		}

		TPL::assign('work_experience_list', $work_experience_list);

		TPL::import_js('js/fileupload.js');

		TPL::output('account/setting/profile');
	}

	public function privacy_action()
	{
		$this->crumb(AWS_APP::lang()->_t('隐私/提醒'), '/account/setting/privacy');

		TPL::assign('notification_settings', $this->model('account')->get_notification_setting_by_uid($this->user_id));
		TPL::assign('notify_actions', $this->model('notify')->notify_action_details);

		TPL::output('account/setting/privacy');
	}

	public function openid_action()
	{
		$this->crumb(AWS_APP::lang()->_t('账号绑定'), '/account/setting/openid/');

		if (get_setting('qq_login_enabled') == 'Y')
		{
			TPL::assign('qq', $this->model('openid_qq')->get_qq_user_by_uid($this->user_id));
		}

		if (get_setting('sina_weibo_enabled') == 'Y')
		{
			TPL::assign('sina_weibo', $this->model('openid_weibo_oauth')->get_weibo_user_by_uid($this->user_id));
		}

		if (get_setting('weixin_app_id'))
		{
			TPL::assign('weixin', $this->model('openid_weixin_weixin')->get_user_info_by_uid($this->user_id));
		}

		if (get_setting('google_login_enabled') == 'Y')
		{
			TPL::assign('google', $this->model('openid_google')->get_google_user_by_uid($this->user_id));
		}

		if (get_setting('facebook_login_enabled') == 'Y')
		{
			TPL::assign('facebook', $this->model('openid_facebook')->get_facebook_user_by_uid($this->user_id));
		}

		if (get_setting('twitter_login_enabled') == 'Y')
		{
			TPL::assign('twitter', $this->model('openid_twitter')->get_twitter_user_by_uid($this->user_id));
		}

		TPL::output('account/setting/openid');
	}

	public function integral_action()
	{
		$this->crumb(AWS_APP::lang()->_t('我的积分'), '/account/setting/integral/');

		TPL::output('account/setting/integral');
	}

	public function security_action()
	{
		$this->crumb(AWS_APP::lang()->_t('安全设置'), '/account/setting/security/');

		TPL::output('account/setting/security');
	}

	public function verify_action()
	{
		$this->crumb(AWS_APP::lang()->_t('申请认证'), '/account/setting/verify/');

		TPL::assign('verify_apply', $this->model('verify')->fetch_apply($this->user_id));

		TPL::output('account/setting/verify');
	}
}
