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
		$rule_action['rule_type'] = "white";	// 黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		
		if ($this->user_info['permission']['visit_topic'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'square';
			$rule_action['actions'][] = 'index';
		}
		
		return $rule_action;
	}

	public function index_action()
	{
		if ($_GET['id'] or $_GET['title'])
		{
			$this->_topic();
		}
		else
		{
			$this->square_action();
		}
	}
	
	public function square_action()
	{
		if (is_mobile() AND HTTP::get_cookie('_ignore_ua_check') != 'TRUE')
		{
			HTTP::redirect('/m/topic_square/' . $_GET['id']);
		}
		
		if ($today_topics = rtrim(get_setting('today_topics'), ','))
		{
			if (!$today_topic = AWS_APP::cache()->get('square_today_topic_' . md5($today_topics)))
			{
				if ($today_topic = $this->model('topic')->get_topic_by_title(array_random(explode(',', $today_topics))))
				{					
					$today_topic['best_answer_users'] = $this->model('topic')->get_best_answer_users_by_topic_id($today_topic['topic_id'], 5);
					
					$today_topic['questions_list'] = $this->model('posts')->get_posts_list('question', 1, 3, 'new', explode(',', $today_topic['topic_id']));
					
					AWS_APP::cache()->set('square_today_topic_' . md5($today_topics), $today_topic, (strtotime('Tomorrow') - time()));
				}
			}
			
			TPL::assign('today_topic', $today_topic);
		}
		
		if (!$_GET['id'] AND !$this->user_id)
		{
			$_GET['id'] = 'hot';
		}
		
		switch ($_GET['id'])
		{			
			default:
			case 'focus':
				if ($topics_list = $this->model('topic')->get_focus_topic_list($this->user_id, calc_page_limit($_GET['page'], get_setting('contents_per_page'))))
				{
					$topics_list_total_rows = $this->user_info['topic_focus_count'];
					
					foreach ($topics_list AS $key => $val)
					{
						$topics_list[$key]['action_list'] = $this->model('posts')->get_posts_list('question', 1, 3, 'new', explode(',', $val['topic_id']));
					}
				}
				
				TPL::assign('topics_list', $topics_list);
			break;
			
			case 'hot':
				if (!$topics_list = AWS_APP::cache()->get('square_hot_topic_list_' . intval($_GET['page'])))
				{
					if ($topics_list = $this->model('topic')->get_topic_list(null, 'discuss_count DESC', get_setting('contents_per_page'), $_GET['page']))
					{
						$topics_list_total_rows = $this->model('topic')->found_rows();
						
						AWS_APP::cache()->set('square_hot_topic_list_total_rows', $topics_list_total_rows, get_setting('cache_level_low'));
						
						foreach ($topics_list AS $key => $val)
						{
							$topics_list[$key]['action_list'] = $this->model('posts')->get_posts_list('question', 1, 3, 'new', explode(',', $val['topic_id']));
						}
					}
					
					AWS_APP::cache()->set('square_hot_topic_list_' . intval($_GET['page']), $topics_list, get_setting('cache_level_low'));
				}
				else
				{
					$topics_list_total_rows = AWS_APP::cache()->get('square_hot_topic_list_total_rows');
				}
				
				TPL::assign('topics_list', $topics_list);
			break;
			
			case 'feature':
				if (!$topics_list = AWS_APP::cache()->get('square_feature_topic_list_' . intval($_GET['feature_id']) . '_' . intval($_GET['page'])))
				{
					if ($topic_ids = $this->model('feature')->get_topics_by_feature_id($_GET['feature_id']))
					{
						if ($topics_list = $this->model('topic')->get_topic_list('topic_id IN(' . implode(',', $topic_ids) . ')', 'discuss_count DESC', get_setting('contents_per_page'), $_GET['page']))
						{
							$topics_list_total_rows = $this->model('topic')->found_rows();
							
							AWS_APP::cache()->set('square_feature_topic_list_' . intval($_GET['feature_id']) . '_total_rows', $topics_list_total_rows, get_setting('cache_level_low'));
							
							foreach ($topics_list AS $key => $val)
							{
								$topics_list[$key]['action_list'] = $this->model('posts')->get_posts_list('question', 1, 3, 'new', explode(',', $val['topic_id']));
							}
						}
					}
					
					AWS_APP::cache()->set('square_feature_topic_list_' . intval($_GET['feature_id']) . '_' . intval($_GET['page']), $topics_list, get_setting('cache_level_low'));
				}
				else
				{
					$topics_list_total_rows = AWS_APP::cache()->get('square_feature_topic_list_' . intval($_GET['feature_id']) . '_total_rows');
				}
				
				TPL::assign('topics_list', $topics_list);
			break;
		}
		
		TPL::assign('feature_list', $this->model('feature')->get_enabled_feature_list());
		
		TPL::assign('new_topics', $this->model('topic')->get_topic_list(null, 'topic_id DESC', 10));
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/topic/square/id-' . $_GET['id'] . '__feature_id-' . $_GET['feature_id']), 
			'total_rows' => $topics_list_total_rows,
			'per_page' => get_setting('contents_per_page')
		))->create_links());
		
		$this->crumb(AWS_APP::lang()->_t('话题广场'), '/topic/');
		
		TPL::output('topic/square');
	}
	
	public function _topic()
	{
		if (is_mobile() AND HTTP::get_cookie('_ignore_ua_check') != 'TRUE')
		{
			HTTP::redirect('/m/topic/' . $_GET['id']);
		}
		
		if (is_numeric($_GET['id']))
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
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'), '/');
		}
		
		if ($topic_info['merged_id'] AND $topic_info['merged_id'] != $topic_info['topic_id'])
		{
			if ($this->model('topic')->get_topic_by_id($topic_info['merged_id']))
			{
				HTTP::redirect('/topic/' . $topic_info['merged_id'] . '?rf=' . $topic_info['topic_id']);
			}
			else
			{
				$this->model('topic')->remove_merge_topic($topic_info['topic_id'], $topic_info['merged_id']);
			}
		}
		
		if (urldecode($topic_info['url_token']) != $_GET['id'])
		{
			HTTP::redirect('/topic/' . $topic_info['url_token'] . '?rf=' . $_GET['rf']);
		}
		
		if (is_numeric($_GET['rf']) and $_GET['rf'])
		{			
			if ($from_topic = $this->model('topic')->get_topic_by_id($_GET['rf']))
			{
				$redirect_message[] = AWS_APP::lang()->_t('话题 (%s) 已与当前话题合并', $from_topic['topic_title']);
			}
		}
		
		TPL::assign('best_answer_users', $this->model('topic')->get_best_answer_users_by_topic_id($topic_info['topic_id'], 5));
		
		if ($this->user_id)
		{
			$topic_info['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']);
		}
		
		if ($topic_info['seo_title'])
		{
			TPL::assign('page_title', $topic_info['seo_title']);
		}
		else
		{
			$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['url_token']);
		}
		
		if ($topic_info['topic_description'])
		{
			TPL::set_meta('description', $topic_info['topic_title'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($topic_info['topic_description'])), 0, 128, 'UTF-8', '...'));
		}
		
		$topic_info['topic_description'] = nl2br(FORMAT::parse_markdown($topic_info['topic_description']));
		
		TPL::assign('topic_info', $topic_info);
		
		TPL::assign('related_topics', $this->model('topic')->related_topics($topic_info['topic_id']));
		
		$log_list = ACTION_LOG::get_action_by_event_id($topic_info['topic_id'], 10, ACTION_LOG::CATEGORY_TOPIC, implode(',', array(
			ACTION_LOG::ADD_TOPIC,
			ACTION_LOG::MOD_TOPIC,
			ACTION_LOG::MOD_TOPIC_DESCRI,
			ACTION_LOG::MOD_TOPIC_PIC,
			ACTION_LOG::DELETE_TOPIC,
			ACTION_LOG::ADD_RELATED_TOPIC,
			ACTION_LOG::DELETE_RELATED_TOPIC
		)), -1);
		
		$log_list = $this->model('topic')->analysis_log($log_list);
		
		$contents_topic_id = $topic_info['topic_id'];
		$contents_topic_title = $topic_info['topic_title'];
		
		if ($merged_topics = $this->model('topic')->get_merged_topic_ids($topic_info['topic_id']))
		{
			foreach ($merged_topics AS $key => $val)
			{
				$merged_topic_ids[] = $val['source_id'];
			}
			
			$contents_topic_id .= ',' . implode(',', $merged_topic_ids);
			
			if ($merged_topics_info = $this->model('topic')->get_topics_by_ids($merged_topic_ids))
			{				
				foreach($merged_topics_info AS $key => $val)
				{
					$merged_topic_title[] = $val['topic_title'];
				}
			}
			
			if ($merged_topic_title)
			{
				$contents_topic_title .= ',' . implode(',', $merged_topic_title);
			}
		}
		
		TPL::assign('list', $this->model('topic')->get_topic_best_answer_action_list($contents_topic_id, $this->user_id, get_setting('contents_per_page')));
		TPL::assign('best_questions_list_bit', TPL::output('home/ajax/index_actions', false));
		
		TPL::assign('posts_list', $this->model('posts')->get_posts_list('question', 1, get_setting('contents_per_page'), 'new', explode(',', $contents_topic_id)));
		TPL::assign('all_questions_list_bit', TPL::output('explore/ajax/list', false));
		
		TPL::assign('posts_list', $this->model('posts')->get_posts_list('article', 1, get_setting('contents_per_page'), 'new', explode(',', $contents_topic_id)));
		TPL::assign('articles_list_bit', TPL::output('explore/ajax/list', false));
		
		TPL::assign('contents_topic_id', $contents_topic_id);
		TPL::assign('contents_topic_title', $contents_topic_title);
		
		TPL::assign('log_list', $log_list);
		
		TPL::import_js('js/ajaxupload.js');
		
		TPL::assign('redirect_message', $redirect_message);
		
		TPL::assign('in_features', $this->model('feature')->get_feature_by_id($this->model('feature')->get_topic_in_feature_ids($topic_info['topic_id'])));
		
		TPL::output('topic/index');
	}
	
	public function edit_action()
	{
		if (! $topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'), '/');
		}
		
		if (!($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::redirect_msg(AWS_APP::lang()->_t('你没有权限进行此操作'));
			}
			else if ($this->model('topic')->has_lock_topic($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('已锁定的话题不能编辑'));
			}
		}
		
		$this->crumb(AWS_APP::lang()->_t('话题编辑'), '/topic/edit/' . $topic_info['topic_id']);
		$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['topic_id']);
		
		TPL::assign('topic_info', $topic_info);
		TPL::assign('related_topics', $this->model('topic')->related_topics($_GET['id']));
		
		TPL::import_js('js/ajaxupload.js');
		
		if (get_setting('advanced_editor_enable') == 'Y')
		{
			// codemirror
			TPL::import_css('js/editor/codemirror/lib/codemirror.css');
			TPL::import_js('js/editor/codemirror/lib/codemirror.js');
			TPL::import_js('js/editor/codemirror/lib/util/continuelist.js');
			TPL::import_js('js/editor/codemirror/mode/xml/xml.js');
			TPL::import_js('js/editor/codemirror/mode/markdown/markdown.js');

			// editor
			TPL::import_js('js/editor/jquery.markitup.js');
			TPL::import_js('js/editor/markdown.js');
			TPL::import_js('js/editor/sets/default/set.js');
		}
		
		TPL::output('topic/edit');
	}
	
	public function manage_action()
	{
		if (! $topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'), '/');
		}
		
		if (!($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			if (!$this->user_info['permission']['manage_topic'])
			{
				H::redirect_msg(AWS_APP::lang()->_t('你没有权限进行此操作'));
			}
			else if ($this->model('topic')->has_lock_topic($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('已锁定的话题不能编辑'));
			}
		}
		
		if ($merged_topics = $this->model('topic')->get_merged_topic_ids($topic_info['topic_id']))
		{
			foreach ($merged_topics AS $key => $val)
			{
				$merged_topic_ids[] = $val['source_id'];
			}
					
			$merged_topics_info = $this->model('topic')->get_topics_by_ids($merged_topic_ids);
		}
		
		TPL::assign('merged_topics_info', $merged_topics_info);
		
		TPL::assign('feature_list', $this->model('feature')->get_enabled_feature_list());
		TPL::assign('topic_in_features', $this->model('feature')->get_topic_in_feature_ids($topic_info['topic_id']));
		
		$this->crumb(AWS_APP::lang()->_t('话题管理'), '/topic/manage/' . $topic_info['topic_id']);
		
		$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['topic_id']);
		
		TPL::assign('topic_info', $topic_info);
		
		TPL::output('topic/manage');
	}
}