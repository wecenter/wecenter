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

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';
		
		if ($this->user_info['permission']['visit_question'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'index'
			);
		}
		
		return $rule_action;
	}
	
	public function index_action()
	{
		if ($_GET['id'] or $_GET['title'])
		{
			$this->_square();
		}
		else
		{
			$this->square_action();
		}
	}

	public function square_action()
	{
		if ($today_topics = rtrim(get_setting('today_topics'), ','))
		{
			if (!$today_topic = AWS_APP::cache()->get('square_today_topic_' . md5($today_topics)))
			{
				if ($today_topic = $this->model('topic')->get_topic_by_title(array_random(explode(',', $today_topics))))
				{					
					$today_topic['best_answer_users'] = $this->model('topic')->get_best_answer_users($today_topic['topic_id'], 0, 5);
					
					$today_topic['questions_list'] = $this->model('question')->get_questions_list(0, 3, 'new', $today_topic['topic_id']);
					
					AWS_APP::cache()->set('square_today_topic_' . md5($today_topics), $today_topic, (strtotime('Tomorrow') - time()));
				}
			}
			
			TPL::assign('today_topic', $today_topic);
		}

		TPL::assign('feature_list', $this->model('feature')->get_feature_list());
		
		TPL::assign('new_topics', $this->model('topic')->get_topic_list(null, 'topic_id DESC', 10));
		
		TPL::output('article/square');
	}

	public function _atricle()
	{
		if (! isset($_GET['id']))
		{
			HTTP::redirect('/home/explore/');
		}
		
		if ($_GET['notification_id'])
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}
		
		if (is_mobile() AND HTTP::get_cookie('_ignore_ua_check') != 'TRUE')
		{
			HTTP::redirect('/m/article/' . $_GET['id']);
		}
		
		if (! $article_info = $this->model('article')->get_article_info_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('文章不存在或已被删除'), '/home/explore/');
		}
				
		if ($article_info['has_attach'])
		{
			$article_info['attachs'] = $this->model('publish')->get_attach('article', $article_info['id'], 'min');
			
			$article_info['attachs_ids'] = FORMAT::parse_attachs($article_info['message'], true);
		}
		
		$article_info['user_info'] = $this->model('account')->get_user_info_by_uid($article_info['uid'], true);
		
		$article_info['message'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($article_info['message'])));
		
		if ($this->user_id)
		{
			$article_info['vote_info'] = $this->model('article')->get_article_vote_by_id('article', $article_info['id'], $this->user_id);
		}
		
		$article_info['vote_users'] = $this->model('article')->get_article_vote_users_by_id('article', $article_info['id'], 10);
		
		TPL::assign('article_info', $article_info);
		
		TPL::assign('article_topics', $this->model('topic')->get_topics_by_item_id($article_info['id'], 'article'));
		
		TPL::assign('reputation_topics', $this->model('people')->get_user_reputation_topic($article_info['user_info']['uid'], $user['reputation'], 5));
				
		$this->crumb($article_info['title'], '/article/' . $article_info['id']);
		
		TPL::assign('human_valid', human_valid('answer_valid_hour'));
		
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
				$comments[$key]['vote_info'] = $this->model('article')->get_article_vote_by_id('comment', $val['id'], $this->user_id);
			}
		}
		
		if ($this->user_id)
		{
			TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $article_info['uid']));
		}
		
		TPL::assign('comments', $comments);
		TPL::assign('comments_count', $article_info['comments']);
		
		TPL::assign('human_valid', human_valid('answer_valid_hour'));
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/article/id-' . $article_info['id']), 
			'total_rows' => $article_info['comments'],
			'per_page' => 100
		))->create_links());

		TPL::set_meta('keywords', implode(',', $this->model('system')->analysis_keyword($article_info['title'])));
		
		TPL::set_meta('description', $article_info['title'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($article_info['message'])), 0, 128, 'UTF-8', '...'));
		
		TPL::assign('attach_access_key', md5($this->user_id . time()));
		
		TPL::output('article/index');
	}
}