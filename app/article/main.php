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
			$rule_action['actions'][] = 'square';
			$rule_action['actions'][] = 'index';
		}
		
		return $rule_action;
	}
	
	public function index_action()
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
			$article_info['vote_info'] = $this->model('article')->get_article_vote_by_id('article', $article_info['id'], null, $this->user_id);
		}
		
		$article_info['vote_users'] = $this->model('article')->get_article_vote_users_by_id('article', $article_info['id'], null, 10);
		
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

	public function square_action()
	{
		$this->crumb(AWS_APP::lang()->_t('文章广场'), '/article/square');
		
		if ($_GET['feature_id'])
		{
			$article_list = $this->model('article')->get_articles_list_by_topic_ids($_GET['page'], get_setting('contents_per_page'), 'add_time DESC', $this->model('feature')->get_topics_by_feature_id($_GET['feature_id']));
			
			$article_list_total = $this->model('article')->article_list_total;
		}
		else
		{
			$article_list = $this->model('article')->get_articles_list($_GET['page'], get_setting('contents_per_page'), 'add_time DESC');
			
			$article_list_total = $this->model()->found_rows();
		}
		
		if ($article_list)
		{
			foreach ($article_list AS $key => $val)
			{
				$article_ids[] = $val['id'];
				
				$article_uids[$val['uid']] = $val['uid'];
				
				$article_list[$key]['message'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($val['message'])));
			}
			
			$article_topics = $this->model('topic')->get_topics_by_item_ids($article_ids, 'article');
			$article_users_info = $this->model('account')->get_user_info_by_uids($article_uids);
			
			foreach ($article_list AS $key => $val)
			{
				$article_list[$key]['user_info'] = $article_users_info[$val['uid']];
			}
		}
		
		TPL::assign('article_list', $article_list);
		TPL::assign('article_topics', $article_topics);
		
		TPL::assign('feature_list', $this->model('feature')->get_feature_list());
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/article/square/feature_id-' . intval($_GET['feature_id'])), 
			'total_rows' => $article_list_total,
			'per_page' => get_setting('contents_per_page')
		))->create_links());
		
		TPL::output('article/square');
	}
}