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
		if (is_mobile())
		{
			HTTP::redirect('/m/topic/' . $_GET['id']);
		}

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

		if (is_digits($_GET['rf']) and $_GET['rf'])
		{
			if ($from_topic = $this->model('topic')->get_topic_by_id($_GET['rf']))
			{
				$redirect_message[] = AWS_APP::lang()->_t('话题 (%s) 已与当前话题合并', $from_topic['topic_title']);
			}
		}

		if ($topic_info['seo_title'])
		{
			TPL::assign('page_title', $topic_info['seo_title']);
		}
		else
		{
			$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['url_token']);
		}

		if ($this->user_id)
		{
			$topic_info['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']);
		}

		if ($topic_info['topic_description'])
		{
			TPL::set_meta('description', $topic_info['topic_title'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($topic_info['topic_description'])), 0, 128, 'UTF-8', '...'));
		}

		$topic_info['topic_description'] = nl2br(FORMAT::parse_markdown($topic_info['topic_description']));

		TPL::assign('topic_info', $topic_info);

		TPL::assign('best_answer_users', $this->model('topic')->get_best_answer_users_by_topic_id($topic_info['topic_id'], 5));

		switch ($topic_info['model_type'])
		{
			default:
				$related_topics_ids = array();

				if ($related_topics = $this->model('topic')->related_topics($topic_info['topic_id']))
				{
					foreach ($related_topics AS $key => $val)
					{
						$related_topics_ids[$val['topic_id']] = $val['topic_id'];
					}
				}

				if ($child_topic_ids = $this->model('topic')->get_child_topic_ids($topic_info['topic_id']))
				{
					foreach ($child_topic_ids AS $key => $topic_id)
					{
						$related_topics_ids[$topic_id] = $topic_id;
					}
				}

				TPL::assign('related_topics', $related_topics);

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

				$contents_related_topic_ids = array_merge($related_topics_ids, explode(',', $contents_topic_id));

				TPL::assign('contents_related_topic_ids', implode(',', $contents_related_topic_ids));

				if ($posts_list = $this->model('posts')->get_posts_list(null, 1, get_setting('contents_per_page'), 'new', $contents_related_topic_ids))
				{
					foreach ($posts_list AS $key => $val)
					{
						if ($val['answer_count'])
						{
							$posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['published_uid']);
						}
					}
				}

				TPL::assign('posts_list', $posts_list);
				TPL::assign('all_list_bit', TPL::output('explore/ajax/list', false));

				if ($posts_list = $this->model('posts')->get_posts_list(null, 1, get_setting('contents_per_page'), null, $contents_related_topic_ids, null, null, 30, true))
				{
					foreach ($posts_list AS $key => $val)
					{
						if ($val['answer_count'])
						{
							$posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['published_uid']);
						}
					}
				}

				TPL::assign('topic_recommend_list', $posts_list);
				TPL::assign('posts_list', $posts_list);
				TPL::assign('recommend_list_bit', TPL::output('explore/ajax/list', false));

				TPL::assign('list', $this->model('topic')->get_topic_best_answer_action_list($contents_topic_id, $this->user_id, get_setting('contents_per_page')));
				TPL::assign('best_questions_list_bit', TPL::output('home/ajax/index_actions', false));

				TPL::assign('posts_list', $this->model('posts')->get_posts_list('question', 1, get_setting('contents_per_page'), 'new', explode(',', $contents_topic_id)));
				TPL::assign('all_questions_list_bit', TPL::output('explore/ajax/list', false));

				TPL::assign('posts_list', $this->model('posts')->get_posts_list('article', 1, get_setting('contents_per_page'), 'new', explode(',', $contents_topic_id)));
				TPL::assign('articles_list_bit', TPL::output('explore/ajax/list', false));

				TPL::assign('contents_topic_id', $contents_topic_id);
				TPL::assign('contents_topic_title', $contents_topic_title);

				TPL::assign('log_list', $log_list);

				TPL::assign('redirect_message', $redirect_message);

				if ($topic_info['parent_id'])
				{
					TPL::assign('parent_topic_info', $this->model('topic')->get_topic_by_id($topic_info['parent_id']));
				}

				TPL::output('topic/index');
			break;
		}
	}

	public function index_square_action()
	{
		if (is_mobile())
		{
			HTTP::redirect('/m/topic/');
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

		switch ($_GET['channel'])
		{
			case 'focus':
				if ($topics_list = $this->model('topic')->get_focus_topic_list($this->user_id, calc_page_limit($_GET['page'], 20)))
				{
					$topics_list_total_rows = $this->user_info['topic_focus_count'];
				}

				TPL::assign('topics_list', $topics_list);
			break;

            default:
			case 'hot':
				switch ($_GET['day'])
				{
					case 'month':
						$order = 'discuss_count_last_month DESC';
					break;

					case 'week':
						$order = 'discuss_count_last_week DESC';
					break;

					default:
						$order = 'discuss_count DESC';
					break;
				}

				$cache_key = 'square_hot_topic_list' . md5($order) . '_' . intval($_GET['page']);

				if (!$topics_list = AWS_APP::cache()->get($cache_key))
				{
					if ($topics_list = $this->model('topic')->get_topic_list(null, $order, 20, $_GET['page']))
					{
						$topics_list_total_rows = $this->model('topic')->found_rows();

						AWS_APP::cache()->set('square_hot_topic_list_total_rows', $topics_list_total_rows, get_setting('cache_level_low'));
					}

					AWS_APP::cache()->set($cache_key, $topics_list, get_setting('cache_level_low'));
				}
				else
				{
					$topics_list_total_rows = AWS_APP::cache()->get('square_hot_topic_list_total_rows');
				}

				TPL::assign('topics_list', $topics_list);
			break;

			case 'topic':
				if (!$topics_list = AWS_APP::cache()->get('square_parent_topics_topic_list_' . intval($_GET['topic_id']) . '_' . intval($_GET['page'])))
				{
					$topic_ids[] = intval($_GET['topic_id']);

					if ($child_topic_ids = $this->model('topic')->get_child_topic_ids($_GET['topic_id']))
					{
						$topic_ids = array_merge($child_topic_ids, $topic_ids);
					}

					if ($topics_list = $this->model('topic')->get_topic_list('topic_id IN(' . implode(',', $topic_ids) . ') AND merged_id = 0', 'discuss_count DESC', 20, $_GET['page']))
					{
						$topics_list_total_rows = $this->model('topic')->found_rows();

						AWS_APP::cache()->set('square_parent_topics_topic_list_' . intval($_GET['topic_id']) . '_total_rows', $topics_list_total_rows, get_setting('cache_level_low'));
					}

					AWS_APP::cache()->set('square_parent_topics_topic_list_' . intval($_GET['topic_id']) . '_' . intval($_GET['page']), $topics_list, get_setting('cache_level_low'));
				}
				else
				{
					$topics_list_total_rows = AWS_APP::cache()->get('square_parent_topics_topic_list_' . intval($_GET['topic_id']) . '_total_rows');
				}

				TPL::assign('topics_list', $topics_list);
			break;
		}

		TPL::assign('parent_topics', $this->model('topic')->get_parent_topics());

		TPL::assign('new_topics', $this->model('topic')->get_topic_list(null, 'topic_id DESC', 10));

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/topic/channel-' . $_GET['channel'] . '__topic_id-' . $_GET['topic_id']),
			'total_rows' => $topics_list_total_rows,
			'per_page' => 20
		))->create_links());

		$this->crumb(AWS_APP::lang()->_t('话题广场'), '/topic/');

		TPL::output('topic/square');
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

		TPL::import_js('js/fileupload.js');

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		TPL::output('topic/edit');
	}

	public function manage_action()
	{
		if (! $topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'), '/');
		}

		$this->crumb(AWS_APP::lang()->_t('话题管理'), '/topic/manage/' . $topic_info['topic_id']);
		$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['topic_id']);

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

		TPL::assign('topic_info', $topic_info);

		if (!$topic_info['is_parent'])
		{
			TPL::assign('parent_topics', $this->model('topic')->get_parent_topics());
		}

		TPL::output('topic/manage');
	}
}