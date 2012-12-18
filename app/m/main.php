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
		if (preg_match('/iPad/i', $_SERVER['HTTP_USER_AGENT']))
		{
			HTTP::redirect('/');
		}
		
		switch ($_GET['act'])
		{
			default:
				if (!$this->user_id)
				{
					HTTP::redirect('/m/login/url-' . base64_encode($_SERVER['REQUEST_URI']));
				}
			break;
			
			case 'login':
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
		
		if (!$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], calc_page_limit($_GET['page'], 20), null, "agree_count DESC, against_count ASC, add_time ASC"))
		{
			$answer_list = array();
		}
			
		foreach ($answer_list as $key => $answer)
		{
			if ($answer['has_attach'])
			{
				$answer_list[$key]['attachs'] = $this->model('publish')->get_attach('answer', $answer['answer_id'], 'min');
			}
				
			if ($answer['answer_content'])
			{
				$answer_list[$key]['answer_content'] = FORMAT::parse_links(nl2br($answer['answer_content']));
			}
		}
		
		TPL::assign('answers_list', $answer_list);
		
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
		$this->crumb(AWS_APP::lang()->_t('发现'), '/m/explore/');
			
		TPL::output('m/explore');
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
				H::redirect_msg(AWS_APP::lang()->_t('用户不存在'), '/m/');
			}
			
			if (urldecode($user['url_token']) != $_GET['id'])
			{
				HTTP::redirect('/m/people/' . $user['url_token']);
			}
			
			$this->model('people')->update_views_count($user['uid']);
		}
		
		TPL::assign('user', $user);
		
		TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $user['uid']));
		
		$this->crumb(AWS_APP::lang()->_t('%s 的个人主页', $user['user_name']), '/mobile/people/' . $user['url_token']);
		
		TPL::output('m/people');
	}
	
	public function new_pm_action()
	{		
		TPL::output("mobile/pm_new");
	}
	
	public function search_action()
	{
		$this->crumb(AWS_APP::lang()->_t('搜索'), '/m/search/');
		
		TPL::output('m/search');
	}
	
	public function search_result_action()
	{
		$keyword = htmlspecialchars($_POST['q']);
		
		$this->crumb(AWS_APP::lang()->_t('搜索'), '/m/search/');
		
		$this->crumb($keyword, '/m/search/');
		
		if (!$keyword)
		{
			HTTP::redirect('/m/search/');	
		}
		
		TPL::assign('search_type', htmlspecialchars($_POST['search_type']));
		
		TPL::assign('keyword', $keyword);
		TPL::assign('split_keyword', implode(' ', $this->model('system')->analysis_keyword($keyword)));
		
		TPL::output('m/search_result');
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
	
	public function users_list_action()
	{
		switch ($_GET['tag'])
		{
			case 'follows':
				$users_list = $this->model('follow')->get_user_friends($_GET['uid'], calc_page_limit($_GET['page'], 50));
			break;
			
			case 'fans':
				$users_list = $this->model('follow')->get_user_fans($_GET['uid'], calc_page_limit($_GET['page'], 50));
			break;
		}
		
		$total_page = $this->model('follow')->found_rows() / 50;
		
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
		
		TPL::assign('users_list', $users_list);
		
		TPL::output('m/users_list');
	}
	
	public function user_actions_action()
	{
		TPL::assign('distint', intval($_GET['distint']));
		TPL::assign('uid', intval($_GET['uid']));
		TPL::assign('actions', htmlspecialchars(addslashes($_GET['actions'])));
		
		TPL::output('m/user_actions');
	}
}
