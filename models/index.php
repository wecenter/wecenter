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

class index_class extends AWS_MODEL
{
	function get_index_focus($uid, $limit = 10)
	{
		$user_focus_questions_ids = $this->model('question')->get_focus_question_ids_by_uid($uid);
				
		// 我关注的话题		
		if ($user_focus_topics_ids = $this->model('topic')->get_focus_topic_ids_by_uid($uid))
		{			
			if ($user_focus_topics_questions_ids = $this->model('topic')->get_question_ids_by_topics_ids($user_focus_topics_ids, 1000))
			{
				$user_focus_topics_by_questions_ids = $this->model('question')->get_focus_questions_topic($user_focus_topics_questions_ids, $user_focus_topics_ids);
			}
		}
		
		// 我关注的人
		$user_follow_uids = $this->model('follow')->get_user_friends_ids($uid);
		
		// 我关注的问题
		// 我关注的话题的回复
		// 我关注的话题添加的问题
		// 我关注的人添加问题, 回复问题, 赞成回答, 增加话题
		// 我关注的人关注了问题
		// 关注的话题的回复添加了赞同
		if ($user_focus_questions_ids)
		{
			// 回复问题, 添加话题
			/*$where_in[] = "(associate_id IN (" . implode(',', $user_focus_questions_ids) . ")
			AND associate_action IN (" . ACTION_LOG::ANSWER_QUESTION . ',' . ACTION_LOG::ADD_TOPIC . ") AND uid <> " . $uid . ")";*/
			
			// 回复问题
			$where_in[] = "(associate_id IN (" . implode(',', $user_focus_questions_ids) . ")
			AND associate_action = " . ACTION_LOG::ANSWER_QUESTION . " AND uid <> " . $uid . ")";
			
			// 添加问题
			$where_in[] = "(associate_id IN (" . implode(',', $user_focus_questions_ids) . ")
			AND associate_action = " . ACTION_LOG::ADD_QUESTION . " AND uid = " . $uid . ")";
		}
		
		if ($user_focus_topics_questions_ids)
		{
			// 回复问题
			$where_in[] = "(associate_id IN (" . implode(',', $user_focus_topics_questions_ids) . ")
			AND associate_action = " . ACTION_LOG::ANSWER_QUESTION . " AND uid <> " . $uid . ")";
			
			// 添加话题
			/*if ($user_focus_topics_ids)
			{
				$where_in[] = "(associate_id IN (" . implode(',', $user_focus_topics_questions_ids) . ")
				AND associate_attached IN (" . implode(',', $user_focus_topics_ids) . ")
				AND associate_action = " . ACTION_LOG::ADD_TOPIC . " AND uid <> " . $uid . ")";
			}
			else
			{
				$where_in[] = "(associate_id IN (" . implode(',', $user_focus_topics_questions_ids) . ")
				AND associate_action = " . ACTION_LOG::ADD_TOPIC . " AND uid <> " . $uid . ")";
			}*/
			
			// 关注的话题的回复添加了赞同
			/*if (sizeof($user_focus_topics_questions_ids) > 0)
			{
				$where_in[] = "(associate_id IN (" . implode(',', $user_focus_topics_questions_ids) . ")
				AND associate_action = " . ACTION_LOG::ADD_AGREE . " AND uid <> " . $uid . ")";
			}*/
		}
		
		if ($user_follow_uids)
		{
			// 添加问题, 回复问题, 增加赞同, 添加话题
			/*$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ")
			AND associate_action IN(" . ACTION_LOG::ADD_QUESTION . ',' . ACTION_LOG::ANSWER_QUESTION . ',' . ACTION_LOG::ADD_AGREE . ',' . ACTION_LOG::ADD_TOPIC ."))";*/
			
			// 添加问题, 回复问题, 添加话题
			$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ")
			AND associate_action IN(" . ACTION_LOG::ADD_QUESTION . ',' . ACTION_LOG::ANSWER_QUESTION . ',' . ACTION_LOG::ADD_TOPIC . "))";
			
			// 增加赞同
			$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ")
			AND associate_action IN(" . ACTION_LOG::ADD_AGREE .") AND uid <> " . $uid . ")";
			
			// 添加问题关注
			if ($user_focus_questions_ids)
			{
				$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ")
				AND associate_action = " . ACTION_LOG::ADD_REQUESTION_FOCUS . "
				AND associate_id NOT IN (" . implode(',', $user_focus_questions_ids) . "))";
			}
			else
			{
				$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ")
				AND associate_action = " . ACTION_LOG::ADD_REQUESTION_FOCUS . ")";
			}
		}
		
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
			$action_list_question_ids[] = $val['associate_id'];
			
			if (in_array($val['associate_action'], array(
				ACTION_LOG::ANSWER_QUESTION, 
				ACTION_LOG::ADD_AGREE
			)) AND $val['associate_attached'])
			{
				$action_list_answer_ids[] = $val['associate_attached'];
			}
			
			if (in_array($val['associate_action'], array(
				ACTION_LOG::ADD_TOPIC
			)) AND $val['associate_attached'])
			{
				$action_list_topic_ids[] = $val['associate_attached'];
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
		
		if ($action_list_topic_ids)
		{
			$topic_infos = $this->model('topic')->get_topics_by_ids($action_list_topic_ids);
		}
		
		if ($action_list_uids)
		{
			$user_info_lists = $this->model('account')->get_user_info_by_uids($action_list_uids, true);
		}
		
		// 重组信息		
		foreach ($action_list as $key => $val)
		{
			$action_list[$key]['user_info'] = $user_info_lists[$val['uid']];
			
			if (in_array($val['associate_action'], array(
				ACTION_LOG::ADD_TOPIC
			)))
			{
				$topic_info = $topic_infos[$val['associate_attached']];
			}
			else
			{
				unset($topic_info);
			}
			
			if (isset($user_focus_topics_by_questions_ids[$val['associate_id']]) AND ! $topic_info)
			{
				$topic_info = $user_focus_topics_by_questions_ids[$val['associate_id']];
			}
			
			$question_info = $question_infos[$val['associate_id']];
			
			// 是否关注
			if ($user_focus_questions_ids)
			{
				if (in_array($question_info['question_id'], $user_focus_questions_ids))
				{
					$question_info['has_focus'] = TRUE;
				}
			}
			
			$question_info['last_action_str'] = ACTION_LOG::format_action_str($val['associate_action'], $val['uid'], $user_info_lists[$val['uid']]['user_name'], $question_info, $topic_info);
			
			// 对于回复问题的
			if ($answer_infos[$val['associate_attached']] && (in_array($val['associate_action'], array(
				ACTION_LOG::ANSWER_QUESTION, 
				ACTION_LOG::ADD_AGREE
			))))
			{
				$question_info['answer_info'] = $answer_infos[$val['associate_attached']];
				
				if (! isset($user_info_lists[$question_info['answer_info']['uid']]))
				{
					$user_info_lists[$question_info['answer_info']['uid']] = $this->model('account')->get_user_info_by_uid($question_info['answer_info']['uid'], true);
				}
				
				$question_info['answer_info']['uid'] = $user_info_lists[$question_info['answer_info']['uid']]['uid'];
				$question_info['answer_info']['user_name'] = $user_info_lists[$question_info['answer_info']['uid']]['user_name'];
				$question_info['answer_info']['url_token'] = $user_info_lists[$question_info['answer_info']['uid']]['url_token'];
				$question_info['answer_info']['signature'] = $user_info_lists[$question_info['answer_info']['uid']]['signature'];
			}
			
			// 处理回复
			if ($question_info['answer_info']['answer_id'])
			{
				if ($question_info['answer_info']['anonymous'])
				{
					unset($action_list[$key]);
					
					continue;
				}
				
				$answer_all_ids[] = $question_info['answer_info']['answer_id'];
				
				if ($question_info['answer_info']['has_attach'])
				{
					$question_info['answer_info']['attachs'] = $answer_attachs[$question_info['answer_info']['answer_id']];
				}
			}
			
			foreach ($question_info as $qkey => $qval)
			{
				if ($qkey == 'add_time')
				{
					continue;
				}
				
				$action_list[$key][$qkey] = $qval;
			}
		
		}
		
		if ($answer_all_ids)
		{
			$answer_agree_users = $this->model('answer')->get_vote_user_by_answer_ids($answer_all_ids);
			$answer_vote_status = $this->model('answer')->get_answer_vote_status($answer_all_ids, $uid);
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
}