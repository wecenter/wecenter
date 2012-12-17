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
		switch ($_GET['act'])
		{
			default:
				if (!$this->user_id)
				{
					HTTP::redirect('/m/login/url-' . base64_encode($_SERVER['REQUEST_URI']));
				}
			break;
			
			case 'login':
			case 'explore':
			case 'question':
			case 'topic':
			break;
		}
		
		TPL::import_clean();
		
		TPL::import_css(array(
			'js/mobile/jquery.mobile.css',
			'js/mobile/mobile.css',
		));
		
		TPL::import_js(array(
			'js/jquery.js',
			'js/jquery.form.js',
			'js/mobile/jquery.mobile.js',
			'js/mobile/mobile.js',
		));
	}
	
	public function index_action()
	{
		$this->crumb(AWS_APP::lang()->_t('首页'), '/mobile/');
		
		TPL::output('m/index');
	}
	
	public function question_action()
	{
		if (! isset($_GET['id']))
		{
			HTTP::redirect('/m/explore/');
		}
		
		if (! $question_id = intval($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在或已被删除'), '/m/explore/');
		}
		
		if ($_GET['notification_id'])
		{
			$this->model('notify')->read_notification(($_GET['notification_id']), intval($_GET['ori']));
		}
		
		if (! $question_info = $this->model("question")->get_question_info_by_id($question_id))
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在或已被删除'), '/m/explore/');
		}
		
		$question_info['redirect'] = $this->model("question")->get_redirect($question_info['question_id']);
		
		if ($question_info['redirect']['target_id'])
		{
			$target_question = $this->model("question")->get_question_info_by_id($question_info['redirect']['target_id']);
		}
		
		if (is_numeric($_GET['rf']) and $_GET['rf'])
		{
			if ($from_question = $this->model("question")->get_question_info_by_id($_GET['rf']))
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
				
				if ($this->user_id && ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR (!$this->question_info['lock'] AND $this->user_info['permission']['redirect_question'])))
				{
					$message .= '&nbsp; (<a href="javascript:;" onclick="' . addslashes('ajax_request(G_BASE_URL + \'/question/ajax/redirect/\', \'item_id=' . $question_id . '\');') . '">' . AWS_APP::lang()->_t('撤消重定向') . '</a>)';
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
		
		$this->model('question')->update_views($question_id);
		
		if (get_setting('answer_unique') == 'Y')
		{
			if ($this->model('answer')->has_answer_by_uid($question_id, $this->user_id))
			{
				TPL::assign('user_answered', TRUE);
			}
		}
		
		$question_info['question_detail'] = FORMAT::parse_attachs(FORMAT::parse_links(nl2br(FORMAT::parse_markdown($question_info['question_detail']))));
		
		TPL::assign('question_id', $question_id);
		TPL::assign('question_info', $question_info);
		TPL::assign('question_focus', $this->model("question")->has_focus_question($question_id, $this->user_id));
		TPL::assign('question_topics', $this->model('question')->get_question_topic_by_question_id($question_id));
		
		$this->crumb($question_info['question_content'], '/m/question/' . $question_id);
		
		TPL::assign('redirect_message', $redirect_message);
		
		TPL::output('m/question');
	}
	
	public function add_answer_action()
	{
		if (!$_GET['question_id'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在或已被删除'), '/m/explore/');
		}
		
		TPL::assign('question_id', intval($_GET['question_id']));
		
		TPL::output('m/add_answer');
	}
	
	public function add_comment_action()
	{
		if (!$_GET['question_id'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在或已被删除'), '/m/explore/');
		}
		
		TPL::assign('question_id', intval($_GET['question_id']));
		
		TPL::output('m/add_comment');
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
		
		if (!$_SERVER['HTTP_REFERER'])
		{
			$return_url = get_js_url('/m/');
		}
		else
		{
			$return_url = $_SERVER['HTTP_REFERER'];
		}
		
		TPL::assign('r_uname', HTTP::get_cookie('r_uname'));
		TPL::assign('return_url', strip_tags($return_url));
		
		$this->crumb(AWS_APP::lang()->_t('登录'), '/m/login/');
		
		TPL::output('m/login');
	}
	
	public function explore_action()
	{
		if (!$this->user_info['permission']['visit_explore'])
		{
			if (!$this->user_id)
			{
				HTTP::redirect('/mobile/login/url-' . base64_encode($_SERVER['REQUEST_URI']));
			}
		}
		
		//if (get_setting('category_enable') == 'Y')
		//{
			$content_category = $this->model('module')->content_category();
			
			TPL::assign('content_category', $content_category);
		//}
		
		if ($_GET['category'] and $category_info = $this->model('system')->get_category_info($_GET['category']))
		{
			TPL::assign('category_info', $category_info);
			
			$this->crumb($category_info['title'], '/mobile/explore/?category=' . $category_info['id']);
		}
		
		TPL::output("mobile/explore");
	}
	
	public function publish_action()
	{
		if (!$this->user_info['permission']['publish_question'] AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布问题'));
		}
		
		if (get_setting('category_enable') == 'Y')
		{
			TPL::assign('question_category_list', $this->model('system')->build_category_html('question', 0, $question_info['category_id']));
		}
		
		TPL::assign('human_valid', human_valid('question_valid_hour'));
		
		TPL::assign('back_url', get_js_url('/mobile/explore/'));
		
		TPL::output('mobile/publish');
	}
	
	public function people_action()
	{
		if (isset($_GET['notification_id']))
		{
			$this->model('notify')->read_notification($_GET['notification_id']);
		}
		
		//if ((is_numeric($_GET['id']) AND intval($_GET['id']) == $this->user_id AND $this->user_id) OR ($this->user_id AND !$_GET['id']))
		if ($this->user_id AND !$_GET['id'])
		{
			$user = $this->user_info;
		}
		else
		{
			if (is_numeric($_GET['id']))
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
				H::redirect_msg(AWS_APP::lang()->_t('用户不存在'), '/mobile/');
			}
			
			if (urldecode($user['url_token']) != $_GET['id'])
			{
				HTTP::redirect('/mobile/people/' . $user['url_token']);
			}
			
			$this->model('people')->update_views_count($user['uid']);
		}
		
		TPL::assign('reputation_topics', $this->model('people')->get_user_reputation_topic($user['uid'], $user['reputation'], 5));
		
		TPL::assign('user', $user);
		
		$job_info = $this->model('account')->get_jobs_by_id($user['job_id']);
		
		TPL::assign('job_name', $job_info['job_name']);
		
		TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $user['uid']));
		
		$this->crumb(AWS_APP::lang()->_t('%s 的个人主页', $user['user_name']), '/mobile/people/' . $user['url_token']);
		
		TPL::output('mobile/people');
	}
	
	public function topic_action()
	{
		if (is_numeric($_GET['id']))
		{
			if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
			{
				$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']);
			}
		}
		else if ($topic_info = $this->model('topic')->get_topic_by_title($_GET['id']))
		{
			
		}
		else
		{
			$topic_info = $this->model('topic')->get_topic_by_url_token($_GET['id']);
		}
		
		if (!$topic_info)
		{
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'), '/mobile/');
		}
		
		if (urldecode($topic_info['url_token']) != $_GET['id'])
		{
			HTTP::redirect('/mobile/topic/' . $topic_info['url_token']);
		}
		
		$topic_info['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']);
		
		TPL::assign('topic_info', $topic_info);
		
		$this->crumb($topic_info['topic_title'], '/mobile/topic/' . rawurlencode($topic_info['topic_title']));
		
		TPL::output('mobile/topic');
	}
	
	public function inbox_action()
	{
		if ($_GET['dialog_id'])
		{
			$dialog_id = intval($_GET['dialog_id']);
			
			if (!$dialog_id)
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定的站内信不存在'), '/mobile/inbox/');
			}
			
			$this->model('message')->read_message($dialog_id);
			
			$list = $this->model('message')->get_message_by_dialog_id($dialog_id, 100);
			
			if (empty($list['list_one']))
			{
				HTTP::redirect("/mobile/inbox/");
			}
			
			if (! empty($list))
			{
				if ($list['list_one'][0]['sender_uid'] != $this->user_id)
				{
					$recipient_user = $this->model('account')->get_user_info_by_uid($list['list_one'][0]['sender_uid']);
				}
				else
				{
					$recipient_user = $this->model('account')->get_user_info_by_uid($list['list_one'][0]['recipient_uid']);
				}
				
				if ($list['list'])
				{
					foreach ($list['list'] as $key => $value)
					{
						$value['notice_content'] = FORMAT::parse_links($value['notice_content']);
						$value['user_name'] = $recipient_user['user_name'];
						$value['url_token'] = $recipient_user['url_token'];
						
						$list_data[] = $value;
					}
				}
			}
			
			$this->crumb(AWS_APP::lang()->_t('私信对话') . ': ' . $recipient_user['user_name'], '/mobile/inbox/dialog_id-' . $dialog_id);
			
			TPL::assign('list', $list_data);
			TPL::assign('recipient_user', $recipient_user);
			
			TPL::assign('back_url', get_js_url('/mobile/inbox/'));
			
			TPL::output("mobile/inbox_read_message");
		}
		else
		{
			$this->crumb(AWS_APP::lang()->_t('私信'), '/mobile/inbox/');
		
			TPL::output("mobile/inbox");
		}
	}
	
	public function new_pm_action()
	{
		TPL::assign('back_url', get_js_url('/mobile/inbox/'));
		
		TPL::output("mobile/pm_new");
	}
	
	public function notifications_action()
	{
		$this->crumb(AWS_APP::lang()->_t('通知'), '/mobile/notifications/');
		
		TPL::output("mobile/notifications");
	}
	
	public function search_action()
	{
		if ($_POST['q'])
		{
			HTTP::redirect('/mobile/search/q-' . rawurlencode($_POST['q']));
		}
		else if ($_GET['q'])
		{
			$this->crumb(AWS_APP::lang()->_t('搜索'), '/mobile/search/');
			
			TPL::assign('keyword', htmlspecialchars($_GET['q']));
			
			TPL::output("mobile/search");
		}
		else
		{
			HTTP::redirect('/mobile/');
		}
	}
	
	public function comments_list_action()
	{
		$comments_list = $this->model('question')->get_question_comments($_GET['question_id']);
		
		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments_list, 'uid'));
		
		foreach ($comments_list as $key => $val)
		{
			$comments_list[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments_list[$key]['message']));
			
			$comments_list[$key]['user_name'] = $user_infos[$val['uid']]['user_name'];
			$comments_list[$key]['url_token'] = $user_infos[$val['uid']]['url_token'];
		}
		
		TPL::assign('comments_list', $comments_list);
		
		TPL::output('m/comments_list');
	}
}
