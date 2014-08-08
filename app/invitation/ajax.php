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
	var $per_page = 10;

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function invitation_list_action()
	{
		$limit = intval($_GET['page']) * $this->per_page . ', ' . $this->per_page;

		if ($invitation_list = $this->model('invitation')->get_invitation_list($this->user_id, $limit))
		{
			foreach ($invitation_list as $key => $val)
			{
				if ($val['active_status'] == 1)
				{
					$uids[$val['active_uid']] = $val['active_uid'];
				}
			}

			if ($uids)
			{
				if ($user_infos = $this->model('account')->get_user_info_by_uids($uids))
				{
					foreach ($invitation_list as $key => $val)
					{
						if ($val['active_status'] == '1')
						{
							$invitation_list[$key]['user_info'] = $user_infos[$val['active_uid']];
						}
					}
				}
			}
		}

		TPL::assign('invitation_list', $invitation_list);

		TPL::output('invitation/ajax/invitation_list');
	}

	public function invite_action()
	{
		if (!$this->user_info['email'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('当前帐号没有提供 Email, 此功能不可用')));
		}

		if (! H::valid_email($_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的邮箱')));
		}

		if ($this->user_info['invitation_available'] < 1)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经没有可使用的邀请名额')));
		}

		if ($uid = $this->model('account')->check_email($_POST['email']))
		{
			if ($uid == $this->user_id)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你不能邀请自己')));
			}

			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('此邮箱已在本站注册帐号')));
		}

		// 若再次填入已邀请过的邮箱，则再发送一次邀请邮件
		if ($invitation_info = $this->model('invitation')->get_active_invitation_by_email($_POST['email']))
		{
			if ($invitation_info['active_status'] == 0)
			{
				if ($invitation_info['uid'] == $this->user_id)
				{
					$this->model('invitation')->send_invitation_email($invitation_info['invitation_id']);

					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('重发邀请成功')));
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('此邮箱已接收过本站发出的邀请')));
				}
			}
		}

		$invitation_code = $this->model('invitation')->get_unique_invitation_code();

		if ($invitation_id = $this->model('invitation')->add_invitation($this->user_id, $invitation_code, $_POST['email'], time(), ip2long($_SERVER['REMOTE_ADDR'])))
		{
			$this->model('invitation')->send_invitation_email($invitation_id);

			H::ajax_json_output(AWS_APP::RSM(null, 1, null));
		}
	}

	public function invite_resend_action()
	{
		$this->model('invitation')->send_invitation_email($_GET['invitation_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function invite_cancel_action()
	{
		if (! $_GET['invitation_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('邀请记录不存在')));
		}

		if (! $this->model('invitation')->get_invitation_by_id($_GET['invitation_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('邀请记录不存在')));
		}

		$this->model('invitation')->cancel_invitation_by_id($_GET['invitation_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}