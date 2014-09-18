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

class actions_class extends AWS_MODEL
{
	public function home_activity($uid, $limit = 10)
	{
		// 我关注的话题
		if ($user_focus_topics_ids = $this->model('topic')->get_focus_topic_ids_by_uid($uid))
		{
			if ($user_focus_topics_questions_ids = $this->model('topic')->get_item_ids_by_topics_ids($user_focus_topics_ids, 'question', 1000))
			{
				if ($user_focus_topics_info = $this->model('question')->get_topic_info_by_question_ids($user_focus_topics_questions_ids))
				{
					foreach ($user_focus_topics_info AS $key => $user_focus_topics_info_by_question)
					{
						foreach ($user_focus_topics_info_by_question AS $_key => $_val)
						{
							if (!in_array($_val['topic_id'], $user_focus_topics_ids))
							{
								unset($user_focus_topics_info[$key][$_key]);
							}
						}
					}
				}
			}

			$user_focus_topics_article_ids = $this->model('topic')->get_item_ids_by_topics_ids($user_focus_topics_ids, 'article', 1000);
		}

		// 我关注的问题
		/*if ($user_focus_questions_ids = $this->model('question')->get_focus_question_ids_by_uid($uid))
		{
			// 回复问题
			$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_id IN (" . implode(',', $user_focus_questions_ids) . ") AND associate_action = " . ACTION_LOG::ANSWER_QUESTION . " AND uid <> " . $uid . ")";
		}*/

		// 我关注的话题
		if ($user_focus_topics_questions_ids)
		{
			// 回复问题, 新增问题, 赞同答案
			$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_id IN (" . implode(',', $user_focus_topics_questions_ids) . ") AND associate_action IN (" . ACTION_LOG::ANSWER_QUESTION . ", " . ACTION_LOG::ADD_QUESTION . ", " . ACTION_LOG::ADD_AGREE . ") AND uid <> " . $uid . ")";
		}

		if ($user_focus_topics_article_ids)
		{
			// 发表文章, 文章评论
			$where_in[] = "(associate_id IN (" . implode(',', $user_focus_topics_article_ids) . ") AND associate_action IN (" . ACTION_LOG::ADD_ARTICLE . ", " . ACTION_LOG::ADD_COMMENT_ARTICLE . ") AND uid <> " . $uid . ")";
		}

		// 我关注的人
		if ($user_follow_uids = $this->model('follow')->get_user_friends_ids($uid))
		{
			// 添加问题, 回复问题, 添加文章
			$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ") AND associate_action IN(" . ACTION_LOG::ADD_QUESTION . ',' . ACTION_LOG::ANSWER_QUESTION . ',' . ACTION_LOG::ADD_ARTICLE . ',' . ACTION_LOG::ADD_COMMENT_ARTICLE . '))';

			// 增加赞同, 文章评论
			$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ") AND associate_action IN(" . ACTION_LOG::ADD_AGREE .", " . ACTION_LOG::ADD_COMMENT_ARTICLE . ") AND uid <> " . $uid . ")";

			// 添加问题关注
			if ($user_focus_questions_ids)
			{
				$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ") AND associate_action = " . ACTION_LOG::ADD_REQUESTION_FOCUS . " AND associate_id NOT IN (" . implode(',', $user_focus_questions_ids) . "))";
			}
			else
			{
				$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ") AND associate_action = " . ACTION_LOG::ADD_REQUESTION_FOCUS . ")";
			}
		}
		else
		{
			$user_follow_uids = array();
		}

		// 添加问题, 添加文章
		$where_in[] = "(associate_action IN (" . ACTION_LOG::ADD_QUESTION . ", " . ACTION_LOG::ADD_ARTICLE . ") AND uid = " . $uid . ")";

		if ($questions_uninterested_ids = $this->model('question')->get_question_uninterested($uid))
		{
			$where = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_id NOT IN (" . implode(',', $questions_uninterested_ids) . "))";
		}
		else
		{
			$where = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . ")";
		}

		if ($where_in AND $where)
		{
			$where .= ' AND (' . implode(' OR ', $where_in) . ')';
		}
		else if ($where_in)
		{
			$where = implode(' OR ', $where_in);
		}
		
		if (! $action_list = ACTION_LOG::get_actions_fresh_by_where($where, $limit))
		{
			return false;
		}
		
		foreach ($action_list as $key => $val)
		{
			if (in_array($val['associate_action'], array(
				ACTION_LOG::ADD_ARTICLE,
				ACTION_LOG::ADD_AGREE_ARTICLE,
				ACTION_LOG::ADD_COMMENT_ARTICLE
			)))
			{
				$action_list_article_ids[] = $val['associate_id'];
				
				if ($val['associate_action'] == ACTION_LOG::ADD_COMMENT_ARTICLE AND $val['associate_attached'])
				{
					$action_list_article_comment_ids[] = $val['associate_attached'];
				}
			}
			else
			{
				$action_list_question_ids[] = $val['associate_id'];

				if (in_array($val['associate_action'], array(
					ACTION_LOG::ANSWER_QUESTION,
					ACTION_LOG::ADD_AGREE
				)) AND $val['associate_attached'])
				{
					$action_list_answer_ids[] = $val['associate_attached'];
				}
			}

			if (! $action_list_uids[$val['uid']])
			{
				$action_list_uids[$val['uid']] = $val['uid'];
			}

		}

		if ($action_list_question_ids)
		{
			$question_infos = $this->model('question')->get_question_info_by_ids($action_list_question_ids);
		}

		if ($action_list_answer_ids)
		{
			$answer_infos = $this->model('answer')->get_answers_by_ids($action_list_answer_ids);
			$answer_attachs = $this->model('publish')->get_attachs('answer', $action_list_answer_ids, 'min');
		}

		if ($action_list_uids)
		{
			$user_info_lists = $this->model('account')->get_user_info_by_uids($action_list_uids, true);
		}

		if ($action_list_article_ids)
		{
			$article_infos = $this->model('article')->get_article_info_by_ids($action_list_article_ids);
		}
		
		if ($action_list_article_comment_ids)
		{
			$article_comment = $this->model('article')->get_comments_by_ids($action_list_article_comment_ids);
		}

		// 重组信息
		foreach ($action_list as $key => $val)
		{
			$action_list[$key]['user_info'] = $user_info_lists[$val['uid']];

			if ($user_focus_topics_info[$val['associate_id']] AND !in_array($action_list[$key]['uid'], $user_follow_uids) AND $action_list[$key]['uid'] != $uid)
			{
				$topic_info = end($user_focus_topics_info[$val['associate_id']]);
			}
			else
			{
				unset($topic_info);
			}

			switch ($val['associate_action'])
			{
				case ACTION_LOG::ADD_ARTICLE:
				case ACTION_LOG::ADD_AGREE_ARTICLE:
				case ACTION_LOG::ADD_COMMENT_ARTICLE:
					$article_info = $article_infos[$val['associate_id']];

					$action_list[$key]['title'] = $article_info['title'];
					$action_list[$key]['link'] = get_js_url('/article/' . $article_info['id']);

					$action_list[$key]['article_info'] = $article_info;
					
					if ($val['associate_action'] == ACTION_LOG::ADD_COMMENT_ARTICLE)
					{
						$action_list[$key]['comment_info'] = $article_comment[$val['associate_attached']];
					}
				break;

				default:
					$question_info = $question_infos[$val['associate_id']];

					$action_list[$key]['title'] = $question_info['question_content'];
					$action_list[$key]['link'] = get_js_url('/question/' . $question_info['question_id']);

					// 是否关注
					if ($user_focus_questions_ids)
					{
						if (in_array($question_info['question_id'], $user_focus_questions_ids))
						{
							$question_info['has_focus'] = TRUE;
						}
					}

					// 对于回复问题的
					if ($answer_infos[$val['associate_attached']] AND in_array($val['associate_action'], array(
						ACTION_LOG::ANSWER_QUESTION,
						ACTION_LOG::ADD_AGREE
					)))
					{
						$action_list[$key]['answer_info'] = $answer_infos[$val['associate_attached']];
					}

					$action_list[$key]['question_info'] = $question_info;

					// 处理回复
					if ($action_list[$key]['answer_info']['answer_id'])
					{
						if ($action_list[$key]['answer_info']['anonymous'] AND $val['associate_action'] == ACTION_LOG::ANSWER_QUESTION)
						{
							unset($action_list[$key]);

							continue;
						}

						$final_list_answer_ids[] = $action_list[$key]['answer_info']['answer_id'];

						if ($action_list[$key]['answer_info']['has_attach'])
						{
							$action_list[$key]['answer_info']['attachs'] = $answer_attachs[$action_list[$key]['answer_info']['answer_id']];
						}
					}
				break;
			}

			if ($action_list[$key])
			{
				$action_list[$key]['last_action_str'] = ACTION_LOG::format_action_data($val['associate_action'], $val['uid'], $user_info_lists[$val['uid']]['user_name'], $question_info, $topic_info);
			}
		}

		if ($final_list_answer_ids)
		{
			$answer_agree_users = $this->model('answer')->get_vote_user_by_answer_ids($final_list_answer_ids);
			$answer_vote_status = $this->model('answer')->get_answer_vote_status($final_list_answer_ids, $uid);
		}

		foreach ($action_list as $key => $val)
		{
			if (isset($action_list[$key]['answer_info']['answer_id']))
			{
				$answer_id = $action_list[$key]['answer_info']['answer_id'];

				if (isset($answer_agree_users[$answer_id]))
				{
					$action_list[$key]['answer_info']['agree_users'] = $answer_agree_users[$answer_id];
				}

				if (isset($answer_vote_status[$answer_id]))
				{
					$action_list[$key]['answer_info']['agree_status'] = $answer_vote_status[$answer_id];
				}
			}
		}

		return $action_list;
	}

	public function get_user_actions($uid, $limit = 10, $actions = false, $this_uid = 0)
	{
		$cache_key = 'user_actions_' . md5($uid . $limit . $actions . $this_uid);

		if ($user_actions = AWS_APP::cache()->get($cache_key))
		{
			return $user_actions;
		}

		$associate_action = ACTION_LOG::ADD_QUESTION;

		if (strstr($actions, ','))
		{
			$associate_action = explode(',', $actions);

			array_walk_recursive($associate_action, 'intval_string');

			$associate_action = implode(',', $associate_action);
		}
		else if ($actions)
		{
			$associate_action = intval($actions);
		}

		if (!$uid)
		{
			$where[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_action IN(" . $this->quote($associate_action) . "))";
		}
		else
		{
			$where[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND uid = " . intval($uid) . " AND associate_action IN(" . $this->quote($associate_action) . "))";
		}

		if ($this_uid == $uid)
		{
			$show_anonymous = true;
		}

		$action_list = ACTION_LOG::get_action_by_where(implode($where, ' OR '), $limit, $show_anonymous);

		// 重组信息
		foreach ($action_list as $key => $val)
		{
			$uids[] = $val['uid'];

			switch ($val['associate_type'])
			{
				case ACTION_LOG::CATEGORY_QUESTION:
					if (in_array($val['associate_action'], array(
						ACTION_LOG::ADD_ARTICLE,
						ACTION_LOG::ADD_COMMENT_ARTICLE
					)))
					{
						$article_ids[] = $val['associate_id'];
					}
					else
					{
						$question_ids[] = $val['associate_id'];
					}

					if (in_array($val['associate_action'], array(
						ACTION_LOG::ADD_TOPIC,
						ACTION_LOG::MOD_TOPIC,
						ACTION_LOG::MOD_TOPIC_DESCRI,
						ACTION_LOG::MOD_TOPIC_PIC,
						ACTION_LOG::DELETE_TOPIC,
						ACTION_LOG::ADD_TOPIC_FOCUS
					)) AND $val['associate_attached'])
					{
						$associate_topic_ids[] = $val['associate_attached'];
					}
				break;
			}
		}

		if ($uids)
		{
			$action_list_users = $this->model('account')->get_user_info_by_uids($uids, true);
		}

		if ($question_ids)
		{
			$action_questions_info = $this->model('question')->get_question_info_by_ids($question_ids);
		}

		if ($associate_topic_ids)
		{
			$associate_topics = $this->model('topic')->get_topics_by_ids($associate_topic_ids);
		}

		if ($article_ids)
		{
			$action_articles_info = $this->model('article')->get_article_info_by_ids($article_ids);
		}

		foreach ($action_list as $key => $val)
		{
			$action_list[$key]['user_info'] = $action_list_users[$val['uid']];

			switch ($val['associate_type'])
			{
				case ACTION_LOG::CATEGORY_QUESTION :
					switch ($val['associate_action'])
					{
						case ACTION_LOG::ADD_ARTICLE:
						case ACTION_LOG::ADD_COMMENT_ARTICLE:
							$article_info = $action_articles_info[$val['associate_id']];

							$action_list[$key]['title'] = $article_info['title'];
							$action_list[$key]['link'] = get_js_url('/article/' . $article_info['id']);

							$action_list[$key]['article_info'] = $article_info;

							$action_list[$key]['last_action_str'] = ACTION_LOG::format_action_data($val['associate_action'], $val['uid'], $action_list_users[$val['uid']]['user_name']);
						break;

						default:
							$question_info = $action_questions_info[$val['associate_id']];

							$action_list[$key]['title'] = $question_info['question_content'];
							$action_list[$key]['link'] = get_js_url('/question/' . $question_info['question_id']);

							if (in_array($val['associate_action'], array(
								ACTION_LOG::ADD_TOPIC,
								ACTION_LOG::MOD_TOPIC,
								ACTION_LOG::MOD_TOPIC_DESCRI,
								ACTION_LOG::MOD_TOPIC_PIC,
								ACTION_LOG::DELETE_TOPIC,
								ACTION_LOG::ADD_TOPIC_FOCUS
							)) AND $val['associate_attached'])
							{
								$topic_info = $associate_topics[$val['associate_attached']];
							}
							else
							{
								unset($topic_info);
							}

							if (in_array($val['associate_action'], array(
								ACTION_LOG::ADD_QUESTION
							)) AND $question_info['has_attach'])
							{
								$question_info['attachs'] = $question_attachs[$question_info['question_id']];
							}

							if ($val['uid'])
							{
								$action_list[$key]['last_action_str'] = ACTION_LOG::format_action_data($val['associate_action'], $val['uid'], $action_list_users[$val['uid']]['user_name'], $question_info, $topic_info);
							}

							if (in_array($val['associate_action'], array(
								ACTION_LOG::ANSWER_QUESTION
							)) AND $question_info['answer_count'])
							{
								if ($answer_list = $this->model('answer')->get_answer_by_id($val['associate_attached']))
								{
									$action_list[$key]['answer_info'] = $answer_list;
								}
							}

							$action_list[$key]['question_info'] = $question_info;
						break;
					}
				break;
			}
		}

		AWS_APP::cache()->set($cache_key, $action_list, get_setting('cache_level_normal'));

		return $action_list;
	}
}