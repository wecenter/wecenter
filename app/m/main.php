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

define('IN_MOBILE', true);

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		if ($_GET['ignore_ua_check'] == 'FALSE')
		{
			HTTP::set_cookie('_ignore_ua_check', 'FALSE');
		}

		if (!is_mobile())
		{
			switch ($_GET['act'])
			{
				default:
					HTTP::redirect('/');
				break;

				case 'home':
					HTTP::redirect('/home/');
				break;

				case 'login':
					HTTP::redirect('/account/login/');
				break;

				case 'question':
					HTTP::redirect('/question/' . $_GET['id']);
				break;

				case 'register':
					HTTP::redirect('/account/register/');
				break;

				case 'topic':
					HTTP::redirect('/topic/' . $_GET['id']);
				break;

				case 'people':
					HTTP::redirect('/people/' . $_GET['id']);
				break;

				case 'article':
					HTTP::redirect('/article/' . $_GET['id']);
				break;
			}
		}

		if (!$this->user_id AND !$this->user_info['permission']['visit_site'] AND $_GET['act'] != 'login' AND $_GET['act'] != 'register')
		{
			HTTP::redirect('/m/login/url-' . base64_encode($_SERVER['REQUEST_URI']));
		}

		switch ($_GET['act'])
		{
			default:
				if (!$this->user_id)
				{
					HTTP::redirect('/m/login/url-' . base64_encode($_SERVER['REQUEST_URI']));
				}
			break;

			case 'index':
			case 'explore':
			case 'login':
			case 'question':
			case 'register':
			case 'topic':
			case 'search':
			case 'people':
			case 'article':
			case 'find_password':
			case 'find_password_success':
			case 'find_password_modify':
				// Public page..
			break;
		}

		TPL::import_clean();

		TPL::import_css(array(
			'mobile/css/mobile.css'
		));

		TPL::import_js(array(
			'js/jquery.2.js',
			'js/jquery.form.js',
			'mobile/js/framework.js',
			'mobile/js/aws-mobile.js',
			'mobile/js/app.js',
			'mobile/js/aw-mobile-template.js'
		));
	}

	public function home_action()
	{
		if (!$this->user_id)
		{
			HTTP::redirect('/m/');
		}

		$this->crumb(AWS_APP::lang()->_t('动态'), '/m/');

		TPL::output('m/home');
	}


	public function focus_action()
	{
		$this->crumb(AWS_APP::lang()->_t('我关注的问题'), '/m/focus/');

		TPL::output('m/focus');
	}

	public function invite_action()
	{
		$this->crumb(AWS_APP::lang()->_t('邀请我回答的问题'), '/m/invite/');

		TPL::output('m/invite');
	}

	public function inbox_action()
	{
		if ($_GET['dialog_id'])
		{
			if (!$dialog = $this->model('message')->get_dialog_by_id($_GET['dialog_id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定的站内信不存在'), '/m/inbox/');
			}

			if ($dialog['recipient_uid'] != $this->user_id AND $dialog['sender_uid'] != $this->user_id)
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定的站内信不存在'), '/m/inbox/');
			}

			$this->model('message')->set_message_read($_GET['dialog_id'], $this->user_id);

			if ($list = $this->model('message')->get_message_by_dialog_id($_GET['dialog_id']))
			{
				if ($dialog['sender_uid'] != $this->user_id)
				{
					$recipient_user = $this->model('account')->get_user_info_by_uid($dialog['sender_uid']);
				}
				else
				{
					$recipient_user = $this->model('account')->get_user_info_by_uid($dialog['recipient_uid']);
				}

				foreach ($list as $key => $value)
				{
					$value['message'] = FORMAT::parse_links($value['message']);
					$value['user_name'] = $recipient_user['user_name'];
					$value['url_token'] = $recipient_user['url_token'];

					$list_data[] = $value;
				}
			}

			$this->crumb(AWS_APP::lang()->_t('私信对话') . ': ' . $recipient_user['user_name'], '/m/inbox/dialog_id-' . intval($_GET['dialog_id']));

			TPL::assign('body_class', 'active');

			TPL::assign('list', $list_data);

			TPL::assign('recipient_user', $recipient_user);

			TPL::output('m/inbox_read');
		}
		else
		{
			$this->crumb(AWS_APP::lang()->_t('私信'), '/m/inbox/');

			TPL::output('m/inbox');
		}
	}

	public function inbox_new_action()
	{
		TPL::assign('body_class', 'active');

		$this->crumb(AWS_APP::lang()->_t('新私信'), '/m/inbox_new/');

		if ($_GET['uid'])
		{
			if ($dialog_info = $this->model('message')->get_dialog_by_user($_GET['uid'], $this->user_id))
			{
				HTTP::redirect('/m/inbox/dialog_id-' . $dialog_info['id']);
			}

			TPL::assign('user', $this->model('account')->get_user_info_by_uid($_GET['uid']));
		}

		TPL::output('m/inbox_new');
	}

	public function question_action()
	{
		TPL::assign('body_class', 'active');

		if (!$this->user_id AND !$this->user_info['permission']['visit_question'])
		{
			HTTP::redirect('/m/login/url-' . base64_encode(get_js_url($_SERVER['QUERY_STRING'])));
		}

		if (! isset($_GET['id']))
		{
			HTTP::redirect('/m/explore/');
		}

		if ($_GET['notification_id'])
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}

		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在或已被删除'), '/m/explore/');
		}

		$question_info['redirect'] = $this->model('question')->get_redirect($question_info['question_id']);

		if ($question_info['redirect']['target_id'])
		{
			$target_question = $this->model('question')->get_question_info_by_id($question_info['redirect']['target_id']);
		}

		if (is_digits($_GET['rf']) and $_GET['rf'])
		{
			if ($from_question = $this->model('question')->get_question_info_by_id($_GET['rf']))
			{
				$redirect_message[] = AWS_APP::lang()->_t('从问题') . ' <a href="' . get_js_url('/m/question/' . $_GET['rf'] . '?rf=false') . '">' . $from_question['question_content'] . '</a> ' . AWS_APP::lang()->_t('跳转而来');
			}
		}

		if ($question_info['redirect'] and ! $_GET['rf'])
		{
			if ($target_question)
			{
				HTTP::redirect('/m/question/' . $question_info['redirect']['target_id'] . '?rf=' . $question_info['question_id']);
			}
			else
			{
				$redirect_message[] = AWS_APP::lang()->_t('重定向目标问题已被删除, 将不再重定向问题');
			}
		}
		else if ($question_info['redirect'])
		{
			if ($target_question)
			{
				$message = AWS_APP::lang()->_t('此问题将跳转至') . ' <a href="' . get_js_url('/m/question/' . $question_info['redirect']['target_id'] . '?rf=' . $question_info['question_id']) . '">' . $target_question['question_content'] . '</a>';

				if ($this->user_id AND ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR (!$this->question_info['lock'] AND $this->user_info['permission']['redirect_question'])))
				{
					$message .= '&nbsp; (<a href="javascript:;" onclick="AWS.ajax_request(G_BASE_URL + \'/question/ajax/redirect/\', \'item_id=' . $question_info['question_id'] . '\');">' . AWS_APP::lang()->_t('撤消重定向') . '</a>)';
				}

				$redirect_message[] = $message;
			}
			else
			{
				$redirect_message[] = AWS_APP::lang()->_t('重定向目标问题已被删除, 将不再重定向问题');
			}
		}

		if ($question_info['has_attach'])
		{
			$question_info['attachs'] = $this->model('publish')->get_attach('question', $question_info['question_id'], 'min');
			$question_info['attachs_ids'] = FORMAT::parse_attachs($question_info['question_detail'], true);
		}

		$this->model('question')->update_views($question_info['question_id']);

		if (get_setting('answer_unique') == 'Y')
		{
			if ($this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id))
			{
				TPL::assign('user_answered', TRUE);
			}
		}

		$question_info['question_detail'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($question_info['question_detail'])));

		TPL::assign('question_id', $question_info['question_id']);
		TPL::assign('question_info', $question_info);
		TPL::assign('question_focus', $this->model('question')->has_focus_question($question_info['question_id'], $this->user_id));
		TPL::assign('question_topics', $this->model('topic')->get_topics_by_item_id($question_info['question_id'], 'question'));

		$this->crumb($question_info['question_content'], '/m/question/' . $question_info['question_id']);

		TPL::assign('redirect_message', $redirect_message);

		if ($this->user_id)
		{
			TPL::assign('question_thanks', $this->model('question')->get_question_thanks($question_info['question_id'], $this->user_id));

			TPL::assign('invite_users', $this->model('question')->get_invite_users($question_info['question_id']));

			//TPL::assign('user_follow_check', $this->model("follow")->user_follow_check($this->user_id, $question_info['published_uid']));

			if ($this->user_info['draft_count'] > 0)
			{
				TPL::assign('draft_content', $this->model('draft')->get_data($question_info['question_id'], 'answer', $this->user_id));
			}
		}

		if (isset($_GET['answer_id']) and (! $this->user_id OR $_GET['single']))
		{
			$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . intval($_GET['answer_id']));
		}
		else if (! $this->user_id AND !$this->user_info['permission']['answer_show'])
		{
			if ($question_info['best_answer'])
			{
				$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . intval($question_info['best_answer']));
			}
			else
			{
				$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, null, 'agree_count DESC');
			}
		}
		else
		{
			$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], calc_page_limit($_GET['page'], 20), null, 'agree_count DESC, against_count ASC, add_time ASC');
		}

		// 最佳回复预留
		$answers[0] = '';

		if (! is_array($answer_list))
		{
			$answer_list = array();
		}

		$answer_ids = array();
		$answer_uids = array();

		foreach ($answer_list as $answer)
		{
			$answer_ids[] = $answer['answer_id'];
			$answer_uids[] = $answer['uid'];

			if ($answer['has_attach'])
			{
				$has_attach_answer_ids[] = $answer['answer_id'];
			}
		}

		if (!in_array($question_info['best_answer'], $answer_ids) AND intval($_GET['page']) < 2)
		{
			$answer_list = array_merge($this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . $question_info['best_answer']), $answer_list);
		}

		if ($answer_ids)
		{
			$answer_agree_users = $this->model('answer')->get_vote_user_by_answer_ids($answer_ids);

			$answer_vote_status = $this->model('answer')->get_answer_vote_status($answer_ids, $this->user_id);

			$answer_users_rated_thanks = $this->model('answer')->users_rated('thanks', $answer_ids, $this->user_id);
			$answer_users_rated_uninterested = $this->model('answer')->users_rated('uninterested', $answer_ids, $this->user_id);
			$answer_attachs = $this->model('publish')->get_attachs('answer', $has_attach_answer_ids, 'min');
		}

		foreach ($answer_list as $answer)
		{
			if ($answer['has_attach'])
			{
				$answer['attachs'] = $answer_attachs[$answer['answer_id']];

				$answer['insert_attach_ids'] = FORMAT::parse_attachs($answer['answer_content'], true);
			}

			$answer['user_rated_thanks'] = $answer_users_rated_thanks[$answer['answer_id']];
			$answer['user_rated_uninterested'] = $answer_users_rated_uninterested[$answer['answer_id']];

			$answer['answer_content'] = $this->model('question')->parse_at_user(FORMAT::parse_attachs(FORMAT::parse_markdown($answer['answer_content'])));

			$answer['agree_users'] = $answer_agree_users[$answer['answer_id']];
			$answer['agree_status'] = $answer_vote_status[$answer['answer_id']];

			if ($question_info['best_answer'] == $answer['answer_id'] AND intval($_GET['page']) < 2)
			{
				$answers[0] = $answer;
			}
			else
			{
				$answers[] = $answer;
			}
		}

		if (! $answers[0])
		{
			unset($answers[0]);
		}

		if (get_setting('answer_unique') == 'Y')
		{
			if ($this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id))
			{
				TPL::assign('user_answered', TRUE);
			}
		}

		TPL::assign('answers_list', $answers);

		TPL::assign('attach_access_key', md5($this->user_id . time()));

		TPL::assign('human_valid', human_valid('answer_valid_hour'));

		$question_related_list = $this->model('question')->get_related_question_list($question_info['question_id'], $question_info['question_content']);

		TPL::assign('question_related_list', $question_related_list);

		TPL::assign('question_related_links', $this->model('related')->get_related_links('question', $question_info['question_id']));

		if ($this->user_id)
		{
			if ($question_topics)
			{
				foreach ($question_topics AS $key => $val)
				{
					$question_topic_ids[] = $val['topic_id'];
				}
			}

			if ($helpful_users = $this->model('topic')->get_helpful_users_by_topic_ids($question_topic_ids, 12))
			{
				foreach ($helpful_users AS $key => $val)
				{
					if ($val['user_info']['uid'] == $this->user_id)
					{
						unset($helpful_users[$key]);
					}
					else
					{
						$helpful_users[$key]['has_invite'] = $this->model('question')->has_question_invite($question_info['question_id'], $val['user_info']['uid'], $this->user_id);
					}
				}

				TPL::assign('helpful_users', $helpful_users);
			}
		}

		$total_page = $question_info['answer_count'] / 20;

		if ($total_page > intval($total_page))
		{
			$total_page = intval($total_page) + 1;
		}

		if (!$_GET['page'])
		{
			$_GET['page'] = 1;
		}

		if ($_GET['page'] < $total_page)
		{
			$_GET['page'] = $_GET['page'] + 1;

			TPL::assign('next_page', $_GET['page']);
		}

		TPL::import_js(array(
			'js/fileupload.js'
		));

		TPL::output('m/question');
	}

	public function login_action()
	{
		$url = base64_decode($_GET['url']);

		if ($this->user_id)
		{
			if ($url)
			{
				header('Location: ' . $url);
			}
			else
			{
				HTTP::redirect('/m/');
			}
		}

		if ($url)
		{
			$return_url = $url;
		}
		else if (strstr($_SERVER['HTTP_REFERER'], '/m/'))
		{
			$return_url = $_SERVER['HTTP_REFERER'];
		}
		else
		{
			$return_url = get_js_url('/m/');
		}

		if (in_weixin() AND get_setting('weixin_app_id') AND get_setting('weixin_account_role') == 'service')
		{
			HTTP::redirect($this->model('openid_weixin_weixin')->redirect_url($return_url));
		}

		TPL::assign('body_class', 'explore-body');
		TPL::assign('return_url', strip_tags($return_url));

		$this->crumb(AWS_APP::lang()->_t('登录'), '/m/login/');

		TPL::output('m/login');
	}

	public function register_action()
	{
		if (($this->user_id AND !$_GET['weixin_id']) OR $this->user_info['weixin_id'])
		{
			if ($url)
			{
				header('Location: ' . $url);
			}
			else
			{
				HTTP::redirect('/m/');
			}
		}

		if (get_setting('register_type') == 'close')
		{
			H::redirect_msg(AWS_APP::lang()->_t('本站目前关闭注册'));
		}
		else if (get_setting('register_type') == 'invite' AND !$_GET['icode'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('本站只能通过邀请注册'));
		}
		else if (get_setting('register_type') == 'weixin')
		{
			H::redirect_msg(AWS_APP::lang()->_t('本站只能通过微信注册'));
		}

		if ($_GET['icode'])
		{
			if ($this->model('invitation')->check_code_available($_GET['icode']))
			{
				TPL::assign('icode', $_GET['icode']);
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('邀请码无效或已经使用，请使用新的邀请码'), '/');
			}
		}

		$this->crumb(AWS_APP::lang()->_t('注册'), '/m/register/');

		TPL::assign('body_class', 'explore-body');

		TPL::output('m/register');
	}

	public function find_password_action()
	{
		$this->crumb(AWS_APP::lang()->_t('找回密码'), '/m/find_password/');

		TPL::output('m/find_password');
	}

	public function find_password_success_action()
	{
		TPL::assign('email', AWS_APP::session()->find_password);

		$this->crumb(AWS_APP::lang()->_t('找回密码'), '/m/find_password_success/');

		TPL::output('m/find_password_success');
	}

	public function find_password_modify_action()
	{
		if (!$active_code_row = $this->model('active')->get_active_code($_GET['key'], 'FIND_PASSWORD'))
		{
			H::redirect_msg(AWS_APP::lang()->_t('链接已失效'), '/');
		}

		if ($active_code_row['active_time'] OR $active_code_row['active_ip'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('链接已失效'), '/');
		}

		TPL::output('m/find_password_modify');
	}

	public function index_action()
	{
		if (!$this->user_id AND !$this->user_info['permission']['visit_explore'])
		{
			HTTP::redirect('/m/login/url-' . base64_encode(get_js_url($_SERVER['QUERY_STRING'])));
		}

		$this->crumb(AWS_APP::lang()->_t('发现'), '/m/');

		TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('explore'));

		TPL::assign('sidebar_hot_topics', $this->model('module')->sidebar_hot_topics($_GET['category']));

		if ($_GET['feature_id'])
		{
			TPL::assign('feature_info', $this->model('feature')->get_feature_by_id($_GET['feature_id']));
		}

		if ($_GET['category'])
		{
			$category_info = $this->model('system')->get_category_info($_GET['category']);

			TPL::assign('category_info', $category_info);
		}

		if (! $_GET['sort_type'] AND !$_GET['is_recommend'])
		{
			$_GET['sort_type'] = 'new';
		}

		if ($_GET['sort_type'] == 'hot')
		{
			$posts_list = $this->model('posts')->get_hot_posts(null, $category_info['id'], null, $_GET['day'], $_GET['page'], get_setting('contents_per_page'));
		}
		else
		{
			$posts_list = $this->model('posts')->get_posts_list(null, $_GET['page'], get_setting('contents_per_page'), $_GET['sort_type'], null, $category_info['id'], $_GET['answer_count'], $_GET['day'], $_GET['is_recommend']);
		}

		TPL::assign('posts_list', $posts_list);

		TPL::import_js(array(
			'mobile/js/iscroll.js',
		));

		TPL::output('m/index');
	}

	public function explore_action()
	{
		HTTP::redirect('/m/');
	}

	public function people_action()
	{
		if (isset($_GET['notification_id']))
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}

		//if ((is_digits($_GET['id']) AND intval($_GET['id']) == $this->user_id AND $this->user_id) OR ($this->user_id AND !$_GET['id']))
		if ($this->user_id AND !$_GET['id'])
		{
			$user = $this->user_info;
		}
		else
		{
			if (is_digits($_GET['id']))
			{
				if (!$user = $this->model('account')->get_user_info_by_uid($_GET['id'], TRUE))
				{
					$user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE);
				}
			}
			else if ($user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE))
			{

			}
			else
			{
				$user = $this->model('account')->get_user_info_by_url_token($_GET['id'], TRUE);
			}

			if (!$user)
			{
				H::redirect_msg(AWS_APP::lang()->_t('用户不存在'), '/m/');
			}

			if (urldecode($user['url_token']) != $_GET['id'])
			{
				HTTP::redirect('/m/people/' . $user['url_token']);
			}

			$this->model('people')->update_views($user['uid']);
		}

		TPL::assign('user', $user);

		TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $user['uid']));

		TPL::assign('reputation_topics', $this->model('people')->get_user_reputation_topic($user['uid'], $user['reputation'], 12));
		TPL::assign('fans_list', $this->model('follow')->get_user_fans($user['uid'], 20));
		TPL::assign('friends_list', $this->model('follow')->get_user_friends($user['uid'], 20));
		TPL::assign('focus_topics', $this->model('topic')->get_focus_topic_list($user['uid'], 8));

		TPL::assign('user_actions_questions', $this->model('actions')->get_user_actions($user['uid'], 5, ACTION_LOG::ADD_QUESTION, $this->user_id));
		TPL::assign('user_actions_answers', $this->model('actions')->get_user_actions($user['uid'], 5, ACTION_LOG::ANSWER_QUESTION, $this->user_id));

		$this->crumb(AWS_APP::lang()->_t('%s 的个人主页', $user['user_name']), '/m/people/' . $user['url_token']);

		$job_info = $this->model('account')->get_jobs_by_id($user['job_id']);

		TPL::assign('job_name', $job_info['job_name']);

		if ($user['weibo_visit'])
		{
			if ($users_sina = $this->model('openid_weibo_oauth')->get_weibo_user_by_uid($user['uid']))
			{
				TPL::assign('sina_weibo_url', 'http://www.weibo.com/' . $users_sina['id']);
			}
		}

		TPL::output('m/people');
	}

	public function people_square_action()
	{
		if (!$_GET['page'])
		{
			$_GET['page'] = 1;
		}

		$this->crumb(AWS_APP::lang()->_t('用户列表'), '/m/people/');

		if ($_GET['feature_id'])
		{
			if ($helpful_users = $this->model('topic')->get_helpful_users_by_topic_ids($this->model('feature')->get_topics_by_feature_id($_GET['feature_id']), get_setting('contents_per_page'), 4))
			{
				foreach ($helpful_users AS $key => $val)
				{
					$users_list[$key] = $val['user_info'];
					$users_list[$key]['experience'] = $val['experience'];


					foreach ($val['experience'] AS $exp_key => $exp_val)
					{
						$users_list[$key]['total_agree_count'] += $exp_val['agree_count'];
					}
				}
			}
		}
		else
		{
			$where = array();

			if ($_GET['group_id'])
			{
				$where[] = 'group_id = ' . intval($_GET['group_id']);
			}

			$users_list = $this->model('account')->get_users_list(implode('', $where), calc_page_limit($_GET['page'], get_setting('contents_per_page')), true, false, 'reputation DESC');

			$where[] = 'forbidden = 0 AND group_id <> 3';

			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_js_url('/m/people/group_id-' . $_GET['group_id']),
				'total_rows' => $this->model('account')->get_user_count(implode(' AND ', $where)),
				'per_page' => get_setting('contents_per_page'),
				'num_links' => 1
			))->create_links());
		}

		if ($users_list)
		{
			foreach ($users_list as $key => $val)
			{
				if ($val['reputation'])
				{
					$reputation_users_ids[] = $val['uid'];
					$users_reputations[$val['uid']] = $val['reputation'];
				}

				$uids[] = $val['uid'];
			}

			if (!$_GET['feature_id'])
			{
				$reputation_topics = $this->model('people')->get_users_reputation_topic($reputation_users_ids, $users_reputations, 4);

				foreach ($users_list as $key => $val)
				{
					$users_list[$key]['reputation_topics'] = $reputation_topics[$val['uid']];
				}
			}

			if ($uids AND $this->user_id)
			{
				$users_follow_check = $this->model('follow')->users_follow_check($this->user_id, $uids);
			}

			foreach ($users_list as $key => $val)
			{
				$users_list[$key]['focus'] = $users_follow_check[$val['uid']];
			}

			TPL::assign('users_list', array_values($users_list));
		}

		if (!$_GET['group_id'])
		{
			TPL::assign('feature_list', $this->model('feature')->get_enabled_feature_list());
		}

		TPL::assign('custom_group', $this->model('account')->get_user_group_list(0, 1));

		TPL::output('m/people_square');
	}

	public function search_action()
	{
		if ($_POST['q'])
		{
			HTTP::redirect('/m/search/q-' . base64_encode($_POST['q']));
		}

		$keyword = htmlspecialchars(base64_decode($_GET['q']));

		$this->crumb($keyword, 'm/search/q-' . urlencode($keyword));

		TPL::assign('body_class', 'active');

		TPL::assign('keyword', $keyword);

		TPL::assign('split_keyword', implode(' ', $this->model('system')->analysis_keyword($keyword)));

		TPL::output('m/search');
	}

	public function topic_square_action()
	{
		$this->crumb(AWS_APP::lang()->_t('话题广场'), '/m/topic/');

		TPL::assign('topics_hot_list', $this->model('topic')->get_topic_list(null, 'discuss_count DESC', 5));

		TPL::output('m/topic_square');
	}

	public function topic_action()
	{
		if (is_digits($_GET['id']))
		{
			if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
			{
				$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']);
			}
		}
		else if (!$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']))
		{
			$topic_info = $this->model('topic')->get_topic_by_url_token($_GET['id']);
		}

		if (!$topic_info)
		{
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'), '/m/');
		}

		if ($topic_info['merged_id'])
		{
			if ($this->model('topic')->get_topic_by_id($topic_info['merged_id']))
			{
				HTTP::redirect('/m/topic/' . $topic_info['merged_id'] . '?rf=' . $topic_info['topic_id']);
			}
			else
			{
				$this->model('topic')->remove_merge_topic($topic_info['topic_id'], $topic_info['merged_id']);
			}
		}

		if (urldecode($topic_info['url_token']) != $_GET['id'])
		{
			HTTP::redirect('/m/topic/' . $topic_info['url_token'] . '?rf=' . $_GET['rf']);
		}

		if (is_digits($_GET['rf']) and $_GET['rf'])
		{
			if ($from_topic = $this->model('topic')->get_topic_by_id($_GET['rf']))
			{
				$redirect_message[] = AWS_APP::lang()->_t('话题 (%s) 已与当前话题合并', $from_topic['topic_title']);
			}
		}

		if ($merged_topics = $this->model('topic')->get_merged_topic_ids($topic_info['topic_id']))
		{
			foreach ($merged_topics AS $key => $val)
			{
				$merged_topic_ids[] = $val['source_id'];
			}

			$contents_topic_id = $topic_info['topic_id'] . ',' . implode(',', $merged_topic_ids);

			if ($merged_topics_info = $this->model('topic')->get_topics_by_ids($merged_topic_ids))
			{
				foreach($merged_topics_info AS $key => $val)
				{
					$contents_topic_title[] = $val['topic_title'];
				}
			}

			$contents_topic_title = $topic_info['topic_title'] . ',' . implode(',', $contents_topic_title);
		}
		else
		{
			$contents_topic_id = $topic_info['topic_id'];
			$contents_topic_title = $topic_info['topic_title'];
		}

		TPL::assign('contents_topic_id', $contents_topic_id);
		TPL::assign('contents_topic_title', $contents_topic_title);

		if ($this->user_id)
		{
			$topic_info['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']);
		}

		TPL::assign('topic_info', $topic_info);

		$this->crumb(AWS_APP::lang()->_t('话题'), '/m/topic/');
		$this->crumb($topic_info['topic_title'], '/m/topic/' . $topic_info['topic_title']);

		TPL::assign('redirect_message', $redirect_message);

		TPL::assign('best_answer_users', $this->model('topic')->get_best_answer_users_by_topic_id($topic_info['topic_id'], 5));

		TPL::output('m/topic');
	}

	public function notifications_action()
	{
		$this->crumb(AWS_APP::lang()->_t('通知'), '/m/notifications/');

		TPL::output('m/notifications');
	}

	public function draft_action()
	{
		$this->crumb(AWS_APP::lang()->_t('草稿'), '/m/draft/');

		TPL::output('m/draft');
	}

	public function settings_action()
	{
		$this->crumb(AWS_APP::lang()->_t('设置'), '/m/settings/');

		TPL::assign('notification_settings', $this->model('account')->get_notification_setting_by_uid($this->user_id));
		TPL::assign('notify_actions', $this->model('notify')->notify_action_details);

		TPL::output('m/settings');
	}

	public function publish_action()
	{
		if ($_GET['id'])
		{
			if (!$question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定问题不存在'));
			}

			if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_question'])
			{
				if ($question_info['published_uid'] != $this->user_id)
				{
					H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个问题'), '/m/question/' . $_GET['id']);
				}
			}

			TPL::assign('question_info', $question_info);
		}
		else if (!$this->user_info['permission']['publish_question'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布问题'));
		}
		else if ($this->is_post() AND $_POST['question_detail'])
		{
			TPL::assign('question_info', array(
				'question_content' => $_POST['question_content'],
				'question_detail' => $_POST['question_detail']
			));

			$question_info['category_id'] = $_POST['category_id'];
		}
		else if ($_GET['weixin_media_id'])
		{
			$weixin_pic_url = AWS_APP::cache()->get('weixin_pic_url_' . md5(base64_decode($_GET['weixin_media_id'])));

			if (!$weixin_pic_url)
			{
				H::redirect_msg(AWS_APP::lang()->_t('图片已过期或 media_id 无效'));
			}

			TPL::assign('weixin_media_id', $_GET['weixin_media_id']);

			TPL::assign('weixin_pic_url', $weixin_pic_url);
		}
		else
		{
			$draft_content = $this->model('draft')->get_data(1, 'question', $this->user_id);

			TPL::assign('question_info', array(
				'question_content' => $_POST['question_content'],
				'question_detail' => $draft_content['message']
			));
		}

		if ($this->user_info['integral'] < 0 AND get_setting('integral_system_enabled') == 'Y' AND !$_GET['id'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作'));
		}

		if (!$question_info['category_id'] AND $_GET['category_id'])
		{
			$question_info['category_id'] = $_GET['category_id'];
		}

		if (get_setting('category_enable') == 'Y')
		{
			TPL::assign('question_category_list', $this->model('system')->build_category_html('question', 0, $question_info['category_id']));
		}
		if (($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $question_info['published_uid'] == $this->user_id AND $_GET['id']) OR !$_GET['id'])
		{
			TPL::assign('attach_access_key', md5($this->user_id . time()));
		}

		TPL::import_js(array(
			'js/fileupload.js'
		));

		TPL::assign('body_class', 'active');

		TPL::assign('human_valid', human_valid('question_valid_hour'));

		TPL::output('m/publish');
	}

	public function article_action()
	{
		if ($_GET['notification_id'])
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}

		if (! $article_info = $this->model('article')->get_article_info_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('文章不存在或已被删除'), '/home/explore/');
		}

		$this->crumb($article_info['title'], '/article/' . $article_info['id']);

		if ($article_info['has_attach'])
		{
			$article_info['attachs'] = $this->model('publish')->get_attach('article', $article_info['id'], 'min');

			$article_info['attachs_ids'] = FORMAT::parse_attachs($article_info['message'], true);
		}

		$article_info['user_info'] = $this->model('account')->get_user_info_by_uid($article_info['uid'], true);

		$article_info['message'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($article_info['message'])));

		if ($this->user_id)
		{
			$article_info['vote_info'] = $this->model('article')->get_article_vote_by_id('article', $article_info['id'], null, $this->user_id);
		}

		$article_info['vote_users'] = $this->model('article')->get_article_vote_users_by_id('article', $article_info['id'], null, 10);

		TPL::assign('article_info', $article_info);

		TPL::assign('article_topics', $this->model('topic')->get_topics_by_item_id($article_info['id'], 'article'));

		if ($_GET['item_id'])
		{
			$comments[] = $this->model('article')->get_comment_by_id($_GET['item_id']);
		}
		else
		{
			$comments = $this->model('article')->get_comments($article_info['id'], $_GET['page'], 100);
		}

		if ($comments AND $this->user_id)
		{
			foreach ($comments AS $key => $val)
			{
				$comments[$key]['vote_info'] = $this->model('article')->get_article_vote_by_id('comment', $val['id'], 1, $this->user_id);
			}
		}

		$this->model('article')->update_views($article_info['id']);

		TPL::assign('comments', $comments);
		TPL::assign('comments_count', $article_info['comments']);

		TPL::assign('human_valid', human_valid('answer_valid_hour'));

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/m/article/id-' . $article_info['id']),
			'total_rows' => $article_info['comments'],
			'per_page' => 100
		))->create_links());

		TPL::output('m/article');
	}

	public function article_square_action()
	{
		$this->crumb(AWS_APP::lang()->_t('文章'), '/m/article/');

		TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('article'));

		if ($_GET['feature_id'])
		{
			TPL::assign('feature_info', $this->model('feature')->get_feature_by_id($_GET['feature_id']));
		}

		if ($_GET['category'])
		{
			TPL::assign('category_info', $this->model('system')->get_category_info($_GET['category']));
		}

		TPL::output('m/article_square');
	}

	public function nearby_people_action()
	{
		$this->crumb(AWS_APP::lang()->_t('附近的人'), '/m/nearby_people/');

		if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($this->user_id))
		{
			if (!$near_by_users = $this->model('people')->get_near_by_users($weixin_user['longitude'], $weixin_user['latitude'], $this->user_id, 20))
			{
				H::redirect_msg(AWS_APP::lang()->_t('你的附近暂时没有人'));
			}
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('请先绑定微信或打开地理位置分享'));
		}

		TPL::assign('near_by_users', $near_by_users);

		TPL::output('m/nearby_people');
	}

	public function nearby_question_action()
	{
		$this->crumb(AWS_APP::lang()->_t('附近的问题'), '/m/nearby_question/');

		if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($this->user_id))
		{
			if (!$near_by_questions = $this->model('question')->get_near_by_questions($weixin_user['longitude'], $weixin_user['latitude'], $this->user_id, 20))
			{
				H::redirect_msg(AWS_APP::lang()->_t('你的附近暂时没有问题'));
			}
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('请先绑定微信或打开地理位置分享'));
		}

		TPL::assign('near_by_questions', $near_by_questions);

		TPL::output('m/nearby_question');
	}

	public function verify_action()
	{
		$this->crumb(AWS_APP::lang()->_t('申请认证'), '/m/verify/');

		TPL::assign('body_class', 'active');

		TPL::assign('verify_apply', $this->model('verify')->fetch_apply($this->user_id));

		TPL::output('m/verify');
	}

	public function favorite_action()
	{
        $this->crumb(AWS_APP::lang()->_t('我的收藏'), '/m/favorite/');

		TPL::output('m/favorite');
	}
}
