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

class favorite_class extends AWS_MODEL
{
	public function add_favorite($answer_id, $uid)
	{
		if (!$answer_id)
		{
			return false;
		}
		
		if (!$this->count('favorite', 'answer_id = ' . intval($answer_id) . ' AND uid = ' . intval($uid)))
		{
			return $this->insert('favorite', array(
				'answer_id' => intval($answer_id),
				'uid' => intval($uid),
				'time' => time()
			));
		}
	}
	
	public function update_favorite_tag($answer_id, $tags, $uid)
	{
		if (!$answer_id OR !$tags)
		{
			return false;
		}
		
		$tags = str_replace(array('，', ' ', '　'), ',', $tags);
		
		$tags = explode(',', rtrim($tags, ','));
		
		foreach ($tags AS $key => $tag)
		{
			if (!$this->count('favorite_tag', 'answer_id = ' . intval($answer_id) . ' AND `title` = \'' . $this->quote(htmlspecialchars(trim($tag))) . '\' AND uid = ' . intval($uid)))
			{
				$this->insert('favorite_tag', array(
					'answer_id' => intval($answer_id),
					'uid' => intval($uid),
					'title' => htmlspecialchars(trim($tag))
				));
			}
		}
		
		return true;
	}
	
	public function remove_favorite_tag($answer_id, $tag, $uid)
	{
		if (!$answer_id OR !$tag)
		{
			return false;
		}
		
		return $this->delete('favorite_tag', 'title = \'' . $this->quote($tag) . '\' AND answer_id = ' . intval($answer_id) . ' AND uid = ' . intval($uid));
	}
	
	public function remove_favorite_item($answer_id, $uid)
	{
		if (!$answer_id OR !$uid)
		{
			return false;
		}
		
		$this->delete('favorite', 'answer_id = ' . intval($answer_id) . ' AND uid = ' . intval($uid));
		$this->delete('favorite_tag', 'answer_id = ' . intval($answer_id) . ' AND uid = ' . intval($uid));
	}
	
	public function get_favorite_tags($uid, $limit = null)
	{
		return $this->query_all("SELECT DISTINCT title FROM " . $this->get_table('favorite_tag') . " WHERE uid = " . intval($uid) . ' ORDER BY id DESC', $limit);
	}
	
	public function get_favorite_items_tags_by_answer_id($uid, $answer_ids)
	{
		if (sizeof($answer_ids) == 0 OR !is_array($answer_ids))
		{
			return false;
		}
		
		array_walk_recursive($answer_ids, 'intval_string');
		
		if ($favorite_tags = $this->fetch_all('favorite_tag', 'uid = ' . intval($uid) . ' AND answer_id IN (' . implode(',', $answer_ids) . ')'))
		{
			foreach ($favorite_tags AS $key => $val)
			{
				$items_tags[$val['answer_id']][] = $val;
			}
		}
		
		return $items_tags;
	}
	
	public function count_favorite_items($uid, $tag = null)
	{
		if ($tag)
		{
			$favorite_items = $this->query_all("SELECT DISTINCT answer_id FROM " . get_table('favorite_tag') . " WHERE uid = " . intval($uid) . " AND title = '" . $this->quote($tag) . "'");
			
			return sizeof($favorite_items);
		}
		else
		{
			return $this->count('favorite', 'uid = ' . intval($uid));
		}
	}
	
	public function get_tag_action_list($tag, $uid, $limit)
	{
		if (!$uid)
		{
			return false;
		}
		
		if ($tag)
		{
			if (strstr($tag, ','))
			{
				$tag = explode(',', $tag);
				
				foreach ($tag AS $key => $val)
				{
					$tag[$key] = $this->quote($val);
				}
			}
			else
			{
				$tag = array(
					$this->quote($tag)
				);
			}
			
			$favorite_tags = $this->fetch_all('favorite_tag', "`title` IN ('" . implode("', '", $tag) . "') AND uid = " . intval($uid), 'answer_id DESC', $limit);
		}
		else
		{
			$favorite_tags = $this->fetch_all('favorite_tag', 'uid = ' . intval($uid), 'answer_id DESC', $limit);
		}
		
		if ($favorite_tags)
		{
			foreach ($favorite_tags AS $key => $val)
			{
				$answer_ids[] = $val['answer_id'];
			}
		}
		
		if (!$answer_ids)
		{
			return false;
		}
		
		if (!$action_list = ACTION_LOG::get_action_by_where("(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_attached IN (" . implode($answer_ids, ",") . ") AND associate_action = " . ACTION_LOG::ANSWER_QUESTION . ")", ''))
		{
			return false;
		}
		
		unset($answer_ids);
		
		foreach ($action_list as $key => $val)
		{
			switch ($val['associate_type'])
			{
				case ACTION_LOG::CATEGORY_QUESTION :
					$question_ids[] = $val['associate_id'];
					
					if (in_array($val['associate_action'], array(
						ACTION_LOG::ANSWER_QUESTION
					)))
					{
						$answer_ids[] = $val['associate_attached'];
					}
				break;
				
				case ACTION_LOG::CATEGORY_ANSWER :
					$question_ids[] = $val['associate_attached'];
					
					if (in_array($val['associate_action'], array(
						ACTION_LOG::ANSWER_QUESTION
					)))
					{
						$answer_ids[] = $val['associate_id'];
					}
				break;
			}
		}
		
		if ($question_ids)
		{
			$action_list_question_info = $this->model('question')->get_question_info_by_ids($question_ids);
			$action_list_question_focus = $this->model('question')->has_focus_question($question_ids, USER::get_client_uid());
			$action_list_answers = $this->model('answer')->get_answers_by_ids($answer_ids);
			$action_list_answers_vote_user = $this->model('answer')->get_vote_user_by_answer_ids($answer_ids);
			$action_list_answers_vote_status = $this->model('answer')->get_answer_vote_status($answer_ids, USER::get_client_uid());
		}
		
		foreach ($action_list as $key => $val)
		{
			switch ($val['associate_type'])
			{
				case ACTION_LOG::CATEGORY_QUESTION :
					$question_ids[] = $val['associate_id'];
					
					if (in_array($val['associate_action'], array(
						ACTION_LOG::ANSWER_QUESTION
					)))
					{
						$answer_ids[] = $val['associate_attached'];
					}
				break;
				
				case ACTION_LOG::CATEGORY_ANSWER :
					$question_ids[] = $val['associate_attached'];
					
					if (in_array($val['associate_action'], array(
						ACTION_LOG::ANSWER_QUESTION
					)))
					{
						$answer_ids[] = $val['associate_id'];
					}
				break;
			}
			
			if ($val['uid'])
			{
				$action_list_uids[] = $val['uid'];	
			}
		}
		
		if ($question_ids)
		{
			$action_list_question_info = $this->model('question')->get_question_info_by_ids($question_ids);
			$action_list_question_focus = $this->model('question')->has_focus_question($question_ids, USER::get_client_uid());
			$action_list_answers = $this->model('answer')->get_answers_by_ids($answer_ids);
			$action_list_answers_vote_user = $this->model('answer')->get_vote_user_by_answer_ids($answer_ids);
			$action_list_answers_vote_status = $this->model('answer')->get_answer_vote_status($answer_ids, USER::get_client_uid());
		}
		
		if ($action_list_uids)
		{
			$action_list_users_info = $this->model('account')->get_user_info_by_uids($action_list_uids, TRUE);
		}
		
		foreach ($action_list as $key => $val)
		{
			switch ($val['associate_type'])
			{
				case ACTION_LOG::CATEGORY_QUESTION :
					$question_id = $val['associate_id'];
					
					if (in_array($val['associate_action'], array(
						ACTION_LOG::ANSWER_QUESTION
					)))
					{
						$answer_id = $val['associate_attached'];
					}
				break;
				
				case ACTION_LOG::CATEGORY_ANSWER :
					$question_id = $val['associate_attached'];
					
					if (in_array($val['associate_action'], array(
						ACTION_LOG::ANSWER_QUESTION
					)))
					{
						$answer_id = $val['associate_id'];
					}
				break;
			}
			
			$action_list[$key]['user_info'] = $action_list_users_info[$val['uid']];
			
			$question_info = $action_list_question_info[$question_id];
					
			$question_info['has_focus'] = $action_list_question_focus[$question_info['question_id']];
					
			if (in_array($val['associate_action'], array(
				ACTION_LOG::ADD_QUESTION
			)) and $question_info['has_attach'])
			{
				$question_info['attachs'] = $this->model('publish')->get_attach('question', $question_info['question_id'], 'min'); // 获取附件
			}
										
			$question_info['last_action_str'] = ACTION_LOG::format_action_str($val['associate_action'], $val['uid'], $action_list_users_info[$val['uid']]['user_name'], $question_info, $topic_info);
					
			if (in_array($val['associate_action'], array(
				ACTION_LOG::ANSWER_QUESTION
			)))
			{
				$answer_info = $action_list_answers[$answer_id];
						
				if ($answer_info['has_attach'])
				{
					$answer_info['attachs'] = $this->model('publish')->get_attach('answer', $answer_id, 'min'); //获取附件
				}
						
				$answer_info['user_name'] = $action_list_users_info[$val['uid']]['user_name'];
				$answer_info['url_token'] = $action_list_users_info[$val['uid']]['url_token'];
				$answer_info['signature'] = $action_list_users_info[$val['uid']]['signature'];
				$answer_info['answer_content'] = strip_ubb($answer_info['answer_content']);
					
			}
			else
			{
				$answer_info = null;
			}
					
			if (! empty($answer_info))
			{
				$question_info['answer_info'] = $answer_info;
			}
					
			if ($question_info['answer_info']['agree_count'] > 0)
			{
				$question_info['answer_info']['agree_users'] = $action_list_answers_vote_user[$question_info['answer_info']['answer_id']];
			}
					
			$question_info['answer_info']['agree_status'] = $action_list_answers_vote_status[$question_info['answer_info']['answer_id']];
					
			foreach ($question_info as $qkey => $qval)
			{
				$action_list[$key][$qkey] = $qval;
			}
			
			$action_list[$key]['add_time'] = $val['add_time'];
		}
		
		return $action_list;
	}
}