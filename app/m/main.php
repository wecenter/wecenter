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
				/*if (!$this->user_id)
				{
					HTTP::redirect('/mobile/login/url-' . base64_encode($_SERVER['REQUEST_URI']));
				}
				*/
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
			HTTP::redirect('/mobile/explore/');
		}
		
		if (! $question_id = intval($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在或已被删除'), '/mobile/explore/');
		}
		
		if (! $_GET['sort'] or $_GET['sort'] != 'ASC')
		{
			$_GET['sort'] = 'DESC';
		}
		else
		{
			$_GET['sort'] = 'ASC';
		}
		
		if ($_GET['notification_id'])
		{
			$this->model('notify')->read_notification(($_GET['notification_id']), intval($_GET['ori']));
		}
		
		if (! $question_info = $this->model("question")->get_question_info_by_id($question_id))
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在或已被删除'), '/mobile/explore/');
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
				$redirect_message[] = AWS_APP::lang()->_t('从问题') . ' <a href="' . get_js_url('/mobile/question/' . $_GET['rf'] . '?rf=false') . '">' . $from_question['question_content'] . '</a> ' . AWS_APP::lang()->_t('跳转而来');
			}
		}
		
		if ($question_info['redirect'] and ! $_GET['rf'])
		{
			if ($target_question)
			{
				HTTP::redirect('/mobile/question/' . $question_info['redirect']['target_id'] . '?rf=' . $question_info['question_id']);
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
				$message = AWS_APP::lang()->_t('此问题将跳转至') . ' <a href="' . get_js_url('mobile/question/' . $question_info['redirect']['target_id'] . '?rf=' . $question_info['question_id']) . '">' . $target_question['question_content'] . '</a>';
				
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
		
		if ($question_info['category_id'] AND get_setting('category_enable') == 'Y')
		{
			$question_info['category_info'] = $this->model('system')->get_category_info($question_info['category_id']);
		}
		
		$question_info['user_info'] = $this->model("account")->get_user_info_by_uid($question_info['published_uid'], true);
		
		$this->model('question')->update_views($question_id);
			
		if ($_GET['sort_key'] == 'add_time')
		{
			$answer_order_by = $_GET['sort_key'] . " DESC";
		}
		else
		{
			$answer_order_by = "agree_count DESC, against_count ASC, add_time ASC";
		}
			
		$answer_count = $this->model('answer')->get_answer_count_by_question_id($question_id, $answer_count_where);
			
			
		if (! $this->user_id)
		{
			if ($question_info['best_answer'])
			{
				$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_id, 1, 'AND answer.answer_id = ' . (int)$question_info['best_answer']);
			}
			else
			{
				$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_id, 1, null, 'agree_count DESC');
			}
		}
		else
		{
			if ($answer_list_where)
			{
				$answer_list_where = ' AND ' . implode(' AND ', $answer_list_where);
			}
				
			$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_id, calc_page_limit($_GET['page'], 100), $answer_list_where, $answer_order_by);
		}
			
		$answer_ids = array();
		$answer_uids = array();
			
		$answers[0] = ''; // 预留给最佳回复
		
		if (! is_array($answer_list))
		{
			$answer_list = array();
		}
			
		foreach ($answer_list as $answer)
		{
			$answer_ids[] = $answer['answer_id'];
			$answer_uids[] = $answer['uid'];
		}
		
		if (!in_array($question_info['best_answer'], $answer_ids) AND intval($_GET['page']) < 2)
		{
			$answer_list = array_merge($this->model('answer')->get_answer_list_by_question_id($question_id, 1, 'AND answer.answer_id = ' . (int)$question_info['best_answer']), $answer_list);
		}
			
		if ($answer_ids)
		{
			$answer_agree_users = $this->model('answer')->get_vote_user_by_answer_ids($answer_ids);
			$answer_vote_status = $this->model('answer')->get_answer_vote_status($answer_ids, $this->user_id);
			$answer_users_rated_thanks = $this->model('answer')->users_rated('thanks', $answer_ids, $this->user_id);
			$answer_users_rated_uninterested = $this->model('answer')->users_rated('uninterested', $answer_ids, $this->user_id);
		}
			
		foreach ($answer_list as $answer)
		{
			if ($answer['has_attach'])
			{
				$answer['attachs'] = $this->model('publish')->get_attach('answer', $answer['answer_id'], 'min');
			}
				
			$answer['user_rated_thanks'] = $answer_users_rated_thanks[$answer['answer_id']];
			$answer['user_rated_uninterested'] = $answer_users_rated_uninterested[$answer['answer_id']];
				
			if ($answer['answer_content'])
			{
				$answer['answer_content'] = FORMAT::parse_links(nl2br($answer['answer_content']));
			}
				
			$answer['agree_users'] = $answer_agree_users[$answer['answer_id']];
			$answer['agree_status'] = $answer_vote_status[$answer['answer_id']];
			
			if ($question_info['best_answer'] == $answer['answer_id'])
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
			if ($this->model('answer')->has_answer_by_uid($question_id, $this->user_id))
			{
				TPL::assign('user_answered', TRUE);
			}
		}
			
		TPL::assign('answers', $answers);
		TPL::assign('answer_count', $answer_count);
		
		$question_info['question_detail'] = FORMAT::parse_attachs(FORMAT::parse_links(nl2br(FORMAT::parse_markdown($question_info['question_detail']))));
		
		if ($this->user_id)
		{		
			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_js_url('/mobile/question/id-' . $question_id . '__sort_key-' . $_GET['sort_key'] . '__sort-' . $_GET['sort'] . '__uid-' . $_GET['uid']), 
				'total_rows' => $answer_count,
				'per_page' => 100
			))->create_links());
		}
		
		TPL::assign('question_id', $question_id);
		TPL::assign('question_info', $question_info);
		TPL::assign('question_focus', $this->model("question")->has_focus_question($question_id, $this->user_id));
		TPL::assign('question_topics', $this->model('question')->get_question_topic_by_question_id($question_id));
		
		$this->crumb($question_info['question_content'], '/mobile/question/' . $question_id);
		
		TPL::assign('human_valid', human_valid('answer_valid_hour'));
		
		TPL::assign('redirect_message', $redirect_message);
		
		TPL::output('mobile/question');
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
				HTTP::redirect('/mobile/');
			}
		}
		
		if (!$_SERVER['HTTP_REFERER'])
		{
			$return_url = get_js_url('/mobile/');
		}
		else
		{
			$return_url = $_SERVER['HTTP_REFERER'];
		}
		
		TPL::assign('r_uname', HTTP::get_cookie('r_uname'));
		TPL::assign('return_url', strip_tags($return_url));
		
		$this->crumb(AWS_APP::lang()->_t('登录'), '/mobile/login/');
		
		TPL::output("mobile/login");
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
}
