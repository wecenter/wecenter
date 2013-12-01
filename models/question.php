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

class question_class extends AWS_MODEL
{
	var $questions_list_total = 0;
	var $search_questions_total = 0;
	
	public function get_questions_list($page = 1, $per_page = 10, $sort = null, $topic_id = null, $category_id = null, $answer_count = null, $day = 30, $is_recommend = false)
	{
		if (is_string($topic_id))
		{
			if (strstr($topic_id, ','))
			{
				$topic_id = explode(',', $topic_id);
			}
		}

		if ($sort == 'unresponsive')
		{
			$answer_count = 0;
		}
		
		switch ($sort)
		{
			default :
				$order_key = 'add_time DESC';
				break;
			
			case 'new' :
				$order_key = 'update_time DESC';
				break;
		}
		
		if ($topic_id)
		{
			$question_info_list = $this->get_question_list_by_topic_ids($topic_id, $category_id, $answer_count, $order_key, $is_recommend, $page, $per_page);
		}
		else
		{
			$where = array();
			
			if (isset($answer_count))
			{
				$where[] = 'answer_count = ' . intval($answer_count);
			}
			
			if ($is_recommend)
			{
				$where[] = 'is_recommend = 1';
			}
	
			if ($category_id)
			{
				$where[] = 'category_id IN(' . implode(',', $this->model('system')->get_category_with_child_ids('question', $category_id)) . ')';
			}
	
			$question_info_list = $this->fetch_page('question', implode(' AND ', $where), $order_key, $page, $per_page);
			
			$this->questions_list_total = $this->found_rows();
		}
		
		return $this->format_list_data($question_info_list);
	}
	
	public function format_list_data($question_info_list)
	{
		if (!$question_info_list)
		{
			return array();
		}
		
		$uids = array();
		
		foreach ($question_info_list as $key => $data)
		{
			$question_ids[] = $data['question_id'];
		}
		
		if ($last_answers = $this->model('answer')->get_last_answer_by_question_ids($question_ids))
		{
			foreach ($last_answers as $key => $val)
			{
				if (! in_array($val['uid'], $uids))
				{
					$uids[] = $val['uid'];
				}
			}
		}
		
		foreach ($question_info_list as $key => $question_info)
		{
			if (! in_array($question_info['published_uid'], $uids))
			{
				$uids[] = $question_info['published_uid'];
			}
			
			/*if ($question_info['has_attach'])
			{
				$has_attach_question_ids[] = $question_info['question_id'];
			}*/
		}
		
		$user_list = $this->model('account')->get_user_info_by_uids($uids, TRUE);
		
		$question_topics = $this->model('topic')->get_topics_by_item_ids($question_ids, 'question');
		//$question_attachs = $this->model('publish')->get_attachs('question', $has_attach_question_ids, 'min');
			
		foreach ($question_info_list AS $key => $question_info)
		{
			// 获取附件
			/*if ($question_info['has_attach'])
			{
				$question_info_list[$key]['attachs'] = $question_attachs[$question_info['question_id']];
			}*/
			
			if (get_setting('category_enable') == 'Y')
			{
				$question_info_list[$key]['category_info'] = $this->model('system')->get_category_info($question_info['category_id']);
			}
			
			$question_info_list[$key]['topics'] = $question_topics[$question_info['question_id']];
			
			$question_info_list[$key]['answer'] = array(
				'user_info' => $user_list[$last_answers[$question_info['question_id']]['uid']],
				'answer_content' => $last_answers[$question_info['question_id']]['answer_content'],
				'anonymous' => $last_answers[$question_info['question_id']]['anonymous']
			);
					
			$question_info_list[$key]['user_info'] = $user_list[$question_info['published_uid']];
		}
		
		return $question_info_list;
	}
	
	public function get_questions_list_total()
	{
		return $this->questions_list_total;
	}

	public function get_hot_question($category_id = 0, $topic_id = null, $day = 30, $page, $per_page)
	{
		if ($day)
		{
			$add_time = strtotime('-' . $day . 'day');
		}
		
		if (!$add_time)
		{
			$add_time = '0';
		}
		
		if ($category_id)
		{
			$question_all = $this->fetch_page('question', "add_time > " . $add_time . " AND focus_count > 0 AND agree_count > 0 AND answer_count > 0 AND category_id IN(" . implode(',', $this->model('system')->get_category_with_child_ids('question', $category_id)) . ')', 'popular_value DESC', $page, $per_page);
		
		}
		else if ($topic_id)
		{
			$topic_ids = array();
			
			if (is_array($topic_id))
			{
				$topic_ids = $topic_id;
			}
			else
			{
				$topic_ids[] = $topic_id;
			}
			
			if ($question_ids = $this->model('topic')->get_item_ids_by_topics_ids($topic_ids, 'question', 10))
			{				
				$question_all = $this->fetch_page('question', "add_time > " . $add_time . " AND question_id IN(" . implode(',', $question_ids) . ')', 'popular_value DESC', $page, $per_page);
			}
		}
		else
		{
			$question_all = $this->fetch_page('question', 'add_time > ' . $add_time, 'popular_value DESC', $page, $per_page);
		}
		
		$this->questions_list_total = $this->found_rows();
		
		return $this->format_list_data($question_all);
	}
	
	public function get_focus_uid_by_question_id($question_id)
	{
		return $this->query_all('SELECT uid FROM ' . $this->get_table('question_focus') . ' WHERE question_id = ' . intval($question_id));
	}
	
	public function get_answers_uid_by_question_id($question_id)
	{
		return $this->query_all('SELECT uid FROM ' . $this->get_table('answer') . ' WHERE question_id = ' . intval($question_id));
	}
	
	public function search_questions_list($page, $per_page, $keyword = null, $category_id = null, $start_date = null, $end_date = null, $answer_count_min = null, $answer_count_max = null, $user_name = null, $best_answer = false)
	{
		$where = array();
		
		if ($keyword)
		{
			$analysis_keyword = $this->model('system')->analysis_keyword($keyword);
				
			$where[] = "(MATCH(question_content_fulltext) AGAINST('" . $this->quote($this->model('search_fulltext')->encode_search_code($analysis_keyword)) . " " . implode(' ', $analysis_keyword) . "' IN BOOLEAN MODE))";
		}
		
		if ($category_id)
		{
			if ($category_ids = $this->model('system')->get_category_with_child_ids('question', $category_id))
			{
				$where[] = 'category_id IN (' . implode(',', $category_ids) . ')';
			}
			else
			{
				$where[] = 'category_id = ' . intval($category_id);
			}
		}
		
		if ($start_date)
		{
			$where[] = 'add_time >= ' . strtotime($start_date);
		}
		
		if ($end_date)
		{
			$where[] = 'add_time <= ' . strtotime('+1 day', strtotime($end_date));
		}
		
		if ($user_name)
		{
			$user_info = $this->model('account')->get_user_info_by_username($user_name);
			
			$where[] = 'published_uid = ' . intval($user_info['uid']);
		}
		
		if ($answer_count_min)
		{
			$where[] = 'answer_count >= ' . intval($answer_count_min);
		}
		
		if ($answer_count_max)
		{
			$where[] = 'answer_count <= ' . intval($answer_count_max);
		}
		
		if ($best_answer)
		{
			$where[] = 'best_answer > 0';
		}
		
		if ($question_info_list = $this->fetch_page('question', implode(' AND ', $where), 'question_id DESC', $page, $per_page))
		{
			$this->search_questions_total = $this->found_rows();
			
			return $question_info_list;
		}
	}
	
	public function get_question_info_by_id($question_id, $cache = true)
	{
		if (! $question_id)
		{
			return false;
		}
		
		if (!$cache)
		{
			$questions[$question_id] = $this->fetch_row('question', 'question_id = ' . intval($question_id));
		}
		else
		{
			static $questions;
		
			if ($questions[$question_id])
			{
				return $questions[$question_id];
			}
			
			$questions[$question_id] = $this->fetch_row('question', 'question_id = ' . intval($question_id));
		}
		
		if ($questions[$question_id])
		{

			$questions[$question_id]['unverified_modify'] = @unserialize($questions[$question_id]['unverified_modify']);
			
			if (is_array($questions[$question_id]['unverified_modify']))
			{
				$counter = 0;
		
				foreach ($questions[$question_id]['unverified_modify'] AS $key => $val)
				{
					$counter = $counter + sizeof($val);
				}
					
				$questions[$question_id]['unverified_modify_count'] = $counter;
			}
		}
		
		return $questions[$question_id];
	}
	
	public function get_question_info_by_ids($question_ids)
	{
		if (!is_array($question_ids) OR sizeof($question_ids) == 0)
		{
			return false;
		}
		
		array_walk_recursive($question_ids, 'intval_string');
		
	    if ($questions_list = $this->fetch_all('question', "question_id IN(" . implode(',', $question_ids) . ")"))
	    {
		    foreach ($questions_list AS $key => $val)
		    {
		    	$result[$val['question_id']] = $val;
		    }
	    }
	    
	    return $result;
	}

	/**
	 * 增加问题浏览次数记录
	 * @param int $question_id
	 */
	public function update_views($question_id)
	{
		if (AWS_APP::cache()->get('update_views_question_' . md5(session_id()) . '_' . intval($question_id)))
		{
			return false;
		}
		
		AWS_APP::cache()->set('update_views_question_' . md5(session_id()) . '_' . intval($question_id), time(), 60);
		
		$this->shutdown_query("UPDATE " . $this->get_table('question') . " SET view_count = view_count + 1 WHERE question_id = " . intval($question_id));
		
		return true;
	}

	/**
	 * 
	 * 增加问题内容
	 * @param string $question_content //问题内容
	 * @param string $question_detail  //问题说明
	 * 
	 * @return boolean true|false
	 */
	public function save_question($question_content, $question_detail, $published_uid, $anonymous = 0, $ip_address = null)
	{
		if (!$ip_address)
		{
			$ip_address = fetch_ip();
		}
		
		if ($question_id = $this->insert('question', array(
			'question_content' => htmlspecialchars($question_content), 
			'question_detail' => htmlspecialchars($question_detail), 
			'add_time' => time(), 
			'update_time' => time(), 
			'published_uid' => intval($published_uid), 
			'anonymous' => intval($anonymous),
			'ip' => ip2long($ip_address)
		)))
		{
			$this->shutdown_update('users', array(
				'question_count' => $this->count('question', 'published_uid = ' . intval($published_uid))
			), 'uid = ' . intval($published_uid));
			
			$this->model('search_fulltext')->push_index('question', $question_content, $question_id);
		}
		
		return $question_id;
	}

	function update_question_category($question_id, $category_id)
	{
		if (! $question_id or ! $category_id)
		{
			return false;
		}
		
		return $this->update('question', array(
			'category_id' => $category_id
		), 'question_id = ' . intval($question_id));
	}

	/**
	 * 修改问题内容
	 */
	public function update_question($question_id, $question_content, $question_detail, $uid, $verified = true, $modify_reason = null, $anonymous = null)
	{		
		if (!$quesion_info = $this->get_question_info_by_id($question_id) OR !$uid)
		{
			return false;
		}
		
		$question_content = htmlspecialchars($question_content);
		$question_detail = htmlspecialchars($question_detail);
		
		if ($verified)
		{
			//$data['update_time'] = time();
			
			$data['question_detail'] = $question_detail;
		
			if ($question_content)
			{
				$data['question_content'] = $question_content;
			}
			
			$this->model('search_fulltext')->push_index('question', $question_content, $question_id);
		
			$this->update('question', $data, 'question_id = ' . $question_id);
			
			if (defined('G_LUCENE_SUPPORT') AND G_LUCENE_SUPPORT)
			{
				$this->model('search_lucene')->push_index('question', $question_content, $question_info['question_id'], array(
					'best_answer' => $question_info['best_answer'],
					'answer_count' => $question_info['answer_count'],
					'comment_count' => $question_info['comment_count'],
					'focus_count' => $question_info['focus_count'],
					'agree_count' => $question_info['agree_count']
				));
			}
		}
		
		$addon_data = array(
			'modify_reason' => $modify_reason,
		);
		
		if ($quesion_info['question_detail'] != $question_detail)
		{
			$log_id = ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::MOD_QUESTION_DESCRI, htmlspecialchars($question_detail), $quesion_info['question_detail'], null, $anonymous, $addon_data);
			
			if (!$verified)
			{
				$this->track_unverified_modify($question_id, $log_id, 'detail');
			}
			
		}
		
		//记录日志
		if ($quesion_info['question_content'] != $question_content)
		{
			$log_id = ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::MOD_QUESTON_TITLE, htmlspecialchars($question_content), $quesion_info['question_content'], 0, 0, $addon_data);
			
			if (!$verified)
			{
				$this->track_unverified_modify($question_id, $log_id, 'content');
			}
		}
		
		return true;
	}
	
	public function verify_modify($question_id, $log_id)
	{
		if (!$unverified_modify = $this->get_unverified_modify($question_id))
		{
			return false;
		}
		
		if (!$action_log = ACTION_LOG::get_action_by_history_id($log_id))
		{
			return false;
		}
		
		if (@in_array($log_id, $unverified_modify['content']))
		{
			$this->update('question', array(
				'question_content' => $action_log['associate_content'],
				'update_time' => time()
			), 'question_id = ' . intval($question_id));
			
			$this->model('search_fulltext')->push_index('question', $action_log['associate_content'], $question_id);
			
			$this->clean_unverified_modify($question_id, 'content');
			
			ACTION_LOG::update_action_time_by_history_id($log_id);
		}
		else if (@in_array($log_id, $unverified_modify['detail']))
		{
			$this->update('question', array(
				'question_detail' => $action_log['associate_content'],
				'update_time' => time()
			), 'question_id = ' . intval($question_id));
			
			$this->clean_unverified_modify($question_id, 'detail');
			
			ACTION_LOG::update_action_time_by_history_id($log_id);
		}
		
		return false;
	}
	
	public function unverify_modify($question_id, $log_id)
	{
		if (!$unverified_modify = $this->get_unverified_modify($question_id))
		{
			return false;
		}
		
		if (!$log_id)
		{
			return false;
		}
		
		if (@in_array($log_id, $unverified_modify['content']))
		{
			unset($unverified_modify['content'][$log_id]);
			
			ACTION_LOG::delete_action_history('history_id = ' . intval($log_id));
		}
		else if (@in_array($log_id, $unverified_modify['detail']))
		{
			unset($unverified_modify['detail'][$log_id]);
			
			ACTION_LOG::delete_action_history('history_id = ' . intval($log_id));
		}
		
		$this->save_unverified_modify($question_id, $unverified_modify);
		
		return false;
	}
	
	public function get_unverified_modify($question_id)
	{
		if (!$quesion_info = $this->get_question_info_by_id($question_id, false)) 
		{
			return false;
		}
		
		if (is_array($quesion_info['unverified_modify']))
		{
			return $quesion_info['unverified_modify'];
		}
		
		if ($quesion_info['unverified_modify'] = @unserialize($quesion_info['unverified_modify']))
		{
			return $quesion_info['unverified_modify'];
		}
		
		return array();
	}
	
	public function save_unverified_modify($question_id, $unverified_modify = array())
	{
		return $this->update('question', array(
			'unverified_modify' => serialize($unverified_modify)
		), 'question_id = ' . $question_id);
	}
	
	public function track_unverified_modify($question_id, $log_id, $type)
	{
		$unverified_modify = $this->get_unverified_modify($question_id);
		
		$unverified_modify[$type][$log_id] = $log_id;
		
		return $this->save_unverified_modify($question_id, $unverified_modify);
	}
	
	public function clean_unverified_modify($question_id, $type)
	{
		$unverified_modify = $this->get_unverified_modify($question_id);
		
		unset($unverified_modify[$type]);
		
		return $this->save_unverified_modify($question_id, $unverified_modify);
	}
	
	public function get_unverified_modify_count($question_id)
	{
		$counter = 0;
		
		if ($unverified_modify = $this->get_unverified_modify($question_id))
		{
			foreach ($unverified_modify AS $key => $val)
			{
				$counter = $counter + sizeof($val);
			}
		}
				
		return $counter;
	}
	
	public function remove_question($question_id)
	{		
		if (!$question_info = $this->get_question_info_by_id($question_id))
		{
			return false;
		}
		
		$this->model('answer')->remove_answers_by_question_id($question_id); // 删除关联的回复内容
		
		// 删除评论
		$this->delete('question_comments', 'question_id = ' . intval($question_id));
		
		$this->delete('question_focus', 'question_id = ' . intval($question_id));
		
		$this->delete('question_thanks', 'question_id = ' . intval($question_id));
		
		$this->delete('topic_relation', "`type` = 'question' AND item_id = " . intval($question_id));		// 删除话题关联
		
		$this->delete('question_invite', 'question_id = ' . intval($question_id));	// 删除邀请记录
		
		$this->delete('question_uninterested', 'question_id = ' . intval($question_id));	// 删除不感兴趣的
		
		ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION .  ' AND associate_id = ' . intval($question_id));	// 删除动作
		
		ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION .  ' AND associate_action = ' . ACTION_LOG::ANSWER_QUESTION . ' AND associate_attached = ' . intval($question_id));	// 删除动作
		
		ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_TOPIC . ' AND associate_action = ' . ACTION_LOG::ADD_TOPIC . ' AND associate_attached = ' . intval($question_id));	// 删除动作
		
		// 删除附件
		if ($attachs = $this->model('publish')->get_attach('question', $question_id))
		{
			foreach ($attachs as $key => $val)
			{
				$this->model('publish')->remove_attach($val['id'], $val['access_key']);
			}
		}
		
		$this->model('notify')->delete_notify('model_type = 1 AND source_id = ' . intval($question_id));	// 删除相关的通知
		
		$this->shutdown_update('users', array(
			'question_count' => $this->count('question', 'published_uid = ' . intval($question_info['published_uid']))
		), 'uid = ' . intval($question_info['published_uid']));
		
		$this->delete('redirect', "item_id = " . intval($question_id) . " OR target_id = " . intval($question_id));
		
		return $this->delete('question', 'question_id = ' . intval($question_id));	// 删除问题
	}
	
	public function add_focus_question($question_id, $uid, $anonymous = 0, $save_action = true)
	{
		if (!$question_id OR !$uid)
		{
			return false;
		}
		
		if (! $this->has_focus_question($question_id, $uid))
		{
			if ($this->insert('question_focus', array(
				'question_id' => intval($question_id), 
				'uid' => intval($uid), 
				'add_time' => time()
			)))
			{
				$this->update_focus_count($question_id);
			}
			
			// 记录日志
			if ($save_action)
			{
				ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_REQUESTION_FOCUS, '', '', 0, intval($anonymous));
			}
		
			return 'add';
		}
		else
		{
			// 减少问题关注数量
			if ($this->delete_focus_question($question_id, $uid))
			{
				$this->update_focus_count($question_id);
			}
			
			return 'remove';
		}
	}

	/**
	 * 
	 * 取消问题关注
	 * @param int $question_id
	 * 
	 * @return boolean true|false
	 */
	public function delete_focus_question($question_id, $uid)
	{
		if (!$question_id || !$uid)
		{
			return false;
		}
		
		// 记录日志
		ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::DELETE_REQUESTION_FOCUS);
		
		return $this->delete('question_focus', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}
	
	public function get_focus_question_ids_by_uid($uid)
	{
		if (!$uid)
		{
			return false;
		}
		
		if (!$question_focus = $this->fetch_all('question_focus', "uid = " . intval($uid)))
		{
			return false;
		}
		
		foreach ($question_focus as $key => $val)
		{
			$question_ids[$val['question_id']] = $val['question_id'];
		}
		
		return $question_ids;
	}
	
	/**
	 * 
	 * 判断是否已经关注问题
	 * @param int $question_id
	 * @param int $uid
	 * 
	 * @return boolean true|false
	 */
	public function has_focus_question($question_id, $uid)
	{
		if (!$uid OR !$question_id)
		{
			return false;
		}
		
		return $this->fetch_one('question_focus', 'focus_id', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}
	
	public function has_focus_questions($question_ids, $uid)
	{
		if (!$uid OR !is_array($question_ids) OR sizeof($question_ids) < 1)
		{
			return array();
		}
		
		$question_focus = $this->fetch_all('question_focus', "question_id IN(" . implode(',', $question_ids) . ") AND uid = " . intval($uid));
		
		if ($question_focus)
		{
			foreach ($question_focus AS $key => $val)
			{
				$result[$val['question_id']] = $val['focus_id'];
			}
			
			return $result;
		}
		else
		{
			return array();
		}
	}

	function get_focus_users_by_question($question_id, $limit = 10)
	{	
		if ($uids = $this->query_all('SELECT DISTINCT uid FROM ' . $this->get_table('question_focus') . ' WHERE question_id = ' . intval($question_id) . ' ORDER BY focus_id DESC', intval($limit)))
		{
			$users_list = $this->model('account')->get_user_info_by_uids(fetch_array_value($uids, 'uid'));
		}
		
		return $users_list;
	}

	public function get_user_focus($uid, $limit = 10)
	{
		if ($question_focus = $this->fetch_all('question_focus', "uid = " . intval($uid), 'question_id DESC', $limit))
		{
			foreach ($question_focus as $key => $val)
			{
				$question_ids[] = $val['question_id'];
			}
		}
		
		if ($question_ids)
		{
			return $this->fetch_all('question', "question_id IN(" . implode(',', $question_ids) . ")", 'add_time DESC');
		}
	}
	
	public function update_answer_count($question_id)
	{
		if (!$question_id)
		{
			return false;
		}
		
		return $this->update('question', array(
			'answer_count' => $this->count('answer', 'question_id = ' . intval($question_id))
		), 'question_id = ' . intval($question_id));
	}
	
	public function update_answer_users_count($question_id)
	{
		if (!$question_id)
		{
			return false;
		}
		
		return $this->update('question', array(
			'answer_users' => $this->count('answer', 'question_id = ' . intval($question_id))
		), 'question_id = ' . intval($question_id));
	}
	
	public function update_focus_count($question_id)
	{
		if (!$question_id)
		{
			return false;
		}
		
		return $this->update('question', array(
			'focus_count' => $this->count('question_focus', 'question_id = ' . intval($question_id))
		), 'question_id = ' . intval($question_id));
	}
	
	function get_related_question_list($question_id, $question_content, $limit = 10)
	{
		if ($question_id)
		{
			$question_related_list = AWS_APP::cache()->get('question_related_list_' . $question_id . '_' . $limit);
		
			if (is_array($question_related_list))
			{
				return $question_related_list;
			}
		}
		
		if ($question_keywords = $this->model('system')->analysis_keyword($question_content))
		{
			if (sizeof($question_keywords) <= 1)
			{
				return false;
			}
			
			if ($question_list = $this->model('search')->search_questions($question_keywords, null, ($limit + 1)))
			{
				foreach ($question_list as $key => $val)
				{
					if ($val['question_id'] == $question_id)
					{
						unset($question_list[$key]);
					}
					else
					{
						if (! isset($question_lnk[$val['question_id']]))
						{
							$question_lnk[$val['question_id']] = $val['question_content'];
							
							$question_info[$val['question_id']] = $val;
						}
					}
				}
			}
		}
		
		if ($question_lnk)
		{
			foreach ($question_lnk as $key => $question_content)
			{
				$question_lnk_list[] = array(
					'question_id' => $key, 
					'question_content' => $question_content, 
					'answer_count' => $question_info[$key]['answer_count']
				);
			}
		}
		
		if (sizeof($question_lnk) > $limit)
		{
			array_pop($question_lnk);
		}
		
		if ($question_id)
		{
			AWS_APP::cache()->set('question_related_list_' . $question_id . '_' . $limit, $question_related_list, get_setting('cache_level_low'));
		}
		
		return $question_lnk_list;
	}

	/**
	 *
	 * 得到用户感兴趣问题列表
	 * @param int $uid
	 * @return array
	 */
	public function get_question_uninterested($uid)
	{
		if ($questions = $this->fetch_all('question_uninterested', 'uid = ' . intval($uid)))
		{
			foreach ($questions as $key => $val)
			{
				$data[] = $val['question_id'];
			}
		}
		
		return $data;
	}

	/**
	 *
	 * 保存用户不感兴趣问题列表
	 * @param int $uid
	 * @param int $question_id
	 *
	 * @return boolean true|false
	 */
	public function add_question_uninterested($uid, $question_id)
	{
		if (!$uid || !$question_id)
		{
			return false;
		}
		
		if (! $this->has_question_uninterested($uid, $question_id))
		{
			return $this->insert('question_uninterested', array(
				"question_id" => $question_id, 
				"uid" => $uid, 
				"add_time" => time()
			));
		}
		else
		{
			return false;
		}
	
	}

	/**
	 *
	 * 删除用户不感兴趣问题列表
	 * @param int $uid
	 * @param int $question_id
	 *
	 * @return boolean true|false
	 */
	public function delete_question_uninterested($uid, $question_id)
	{
		return $this->delete('question_uninterested', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}

	public function has_question_uninterested($uid, $question_id)
	{
		return $this->fetch_row('question_uninterested', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}

	public function add_invite($question_id, $sender_uid, $recipients_uid = 0, $email = null)
	{		
		if (!$question_id || !$sender_uid)
		{
			return false;
		}
		
		if (!$recipients_uid && !$email)
		{
			return false;
		}
		
		$data = array(
			'question_id' => intval($question_id), 
			'sender_uid' => intval($sender_uid), 
			'add_time' => time(),
		);
		
		if ($recipients_uid)
		{
			$data['recipients_uid'] = intval($recipients_uid);
		}
		else if ($email)
		{
			$data['email'] = $email;
		}
		
		return $this->insert('question_invite', $data);
	}

	/**
	 * 发起者取消邀请
	 * @param unknown_type $question_id
	 * @param unknown_type $sender_uid
	 * @param unknown_type $recipients_uid
	 */
	public function cancel_question_invite($question_id, $sender_uid, $recipients_uid)
	{
		return $this->delete('question_invite', 'question_id = ' . intval($question_id) . ' AND sender_uid = ' . intval($sender_uid) . ' AND recipients_uid = ' . intval($recipients_uid));
	}

	/**
	 * 接收者删除邀请
	 * @param unknown_type $question_invite_id
	 * @param unknown_type $recipients_uid
	 */
	public function delete_question_invite($question_invite_id, $recipients_uid)
	{
		return $this->delete('question_invite', 'question_invite_id = ' . intval($question_invite_id) . ' AND recipients_uid = ' . intval($recipients_uid));
	}

	/**
	 * 接收者回复邀请的问题
	 * @param unknown_type $question_invite_id
	 * @param unknown_type $recipients_uid
	 */
	public function answer_question_invite($question_id, $recipients_uid)
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($question_id))
		{
			return false;
		}
		
		if ($question_invites = $this->fetch_row('question_invite', 'question_id = ' . intval($question_id) . ' AND sender_uid = ' . $question_info['published_uid'] . ' AND recipients_uid = ' . intval($recipients_uid)))
		{
			$this->model('integral')->process($question_info['published_uid'], 'INVITE_ANSWER', get_setting('integral_system_config_invite_answer'), '邀请回答成功 #' . $question_id, $question_id);
			
			$this->model('integral')->process($recipients_uid, 'ANSWER_INVITE', -get_setting('integral_system_config_invite_answer'), '回复邀请回答 #' . $question_id, $question_id);
		}
		
		$this->delete('question_invite', 'question_id = ' . intval($question_id) . ' AND recipients_uid = ' . intval($recipients_uid));
		
		$this->model('account')->update_question_invite_count($recipients_uid);
	}

	public function check_question_invite($question_id, $sender_uid, $recipients_uid)
	{
		if (!$sender_uid)
		{
			return $this->fetch_row('question_invite', 'question_id = ' . intval($question_id) . ' AND recipients_uid = ' . intval($recipients_uid));
		}
		else
		{
			return $this->fetch_row('question_invite', 'question_id = ' . intval($question_id) . ' AND sender_uid = ' . intval($sender_uid) . ' AND recipients_uid = ' . intval($recipients_uid));
		}
	}

	public function check_email_invite($question_id, $sender_uid, $email)
	{
		return $this->fetch_row('question_invite', 'question_id = ' . intval($question_id) . ' AND email = \'' . $email . '\'');
	}

	public function get_invite_users($question_id, $exclude_uid = null)
	{
		$data = array();
		
		$where = array(
			'question_id = ' . intval($question_id),
			'recipients_uid > 0'
		);
		
		if ($exclude_uid)
		{
			$where[] = 'recipients_uid NOT IN(' . implode(',', $exclude_uid) . ')';
		}
		
		if ($invites = $this->fetch_all('question_invite', implode(' AND ', $where), 'question_invite_id DESC', 10))
		{
			$invite_users = array();
			
			foreach ($invites as $key => $val)
			{
				$invite_users[] = $val['recipients_uid'];
			}
			
			$user_info = $this->model('account')->get_user_info_by_uids($invite_users, true);
			
			foreach ($invites as $key => $val)
			{
				$user = $user_info[$val['recipients_uid']];
				
				$data[] = array(
					'uid' => $user['uid'], 
					'user_name' => $user['user_name'], 
					'signature' => $user['signature'], 
					'sender_uid' => $val['sender_uid'], 
				);
			}
		}
		
		return $data;
	}

	public function get_invite_question_list($uid, $limit = 10)
	{
		if ($list = $this->fetch_all('question_invite', 'recipients_uid = ' . intval($uid), 'question_invite_id DESC', $limit))
		{
			foreach ($list as $key => $val)
			{
				$question_ids[] = $val['question_id'];
			}
			
			$question_infos = $this->get_question_info_by_ids($question_ids);
			
			foreach ($list as $key => $val)
			{
				$list[$key]['question_content'] = $question_infos[$val['question_id']]['question_content'];
			}
			
			return $list;
		}
		else
		{
			return false;
		}
	}

	public function parse_at_user($content, $popup = false, $with_user = false, $to_uid = false)
	{
		preg_match_all('/@([^@,:\s,]+)/i', strip_tags($content), $matchs);
		
		if (is_array($matchs[1]))
		{
			$match_name = array();
			
			foreach ($matchs[1] as $key => $user_name)
			{
				if (in_array($user_name, $match_name))
				{
					continue;
				}
				
				$match_name[] = $user_name;
			}
			
			$match_name = array_unique($match_name);
			
			arsort($match_name);
			
			$all_users = array();
			
			$content_uid = $content;
			
			foreach ($match_name as $key => $user_name)
			{				
				if (preg_match('/^[0-9]+$/', $user_name))
				{
					$user_info = $this->model('account')->get_user_info_by_uid($user_name);
				}
				else
				{
					$user_info = $this->model('account')->get_user_info_by_username($user_name);
				}
				
				if ($user_info)
				{	
					$content = str_replace('@' . $user_name, '<a href="people/' . $user_info['url_token'] . '"' . (($popup) ? ' target="_blank"' : '') . ' class="aw-user-name" data-id="' . $user_info['uid'] . '">@' . $user_info['user_name'] . '</a>', $content);

					if ($to_uid)
					{
						$content_uid = str_replace('@' . $user_name, '@' . $user_info['uid'], $content_uid);
					}
					
					if ($with_user)
					{
						$all_users[] = $user_info['uid'];
					}
				}
			}
		}
		
		if ($with_user)
		{
			return $all_users;
		}
		
		if ($to_uid)
		{
			return $content_uid;
		}
		
		return $content;
	}

	public function update_question_comments_count($question_id)
	{
		$count = $this->count('question_comments', 'question_id = ' . intval($question_id));
		
		$this->shutdown_update('question', array(
			'comment_count' => $count
		), 'question_id = ' . intval($question_id));
	}
	
	public function set_question_recommend($question_id)
	{
		$this->update('question', array(
			'is_recommend' => 1
		), 'question_id = ' . intval($question_id));
	}
	
	public function unset_question_recommend($question_id)
	{
		$this->update('question', array(
			'is_recommend' => 0
		), 'question_id = ' . intval($question_id));
	}

	public function insert_question_comment($question_id, $uid, $message)
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($question_id))
		{
			return false;
		}
		
		$message = $this->model('question')->parse_at_user($_POST['message'], false, false, true);
		
		$comment_id = $this->insert('question_comments', array(
			'uid' => intval($uid), 
			'question_id' => intval($question_id), 
			'message' => htmlspecialchars($message), 
			'time' => time()
		));
			
		if ($question_info['published_uid'] != $uid)
		{
			$this->model('notify')->send($uid, $question_info['published_uid'], notify_class::TYPE_QUESTION_COMMENT, notify_class::CATEGORY_QUESTION, $question_info['question_id'], array(
				'from_uid' => $uid, 
				'question_id' => $question_info['question_id'], 
				'comment_id' => $comment_id
			));
		}
			
		if ($at_users = $this->model('question')->parse_at_user($message, false, true))
		{
			foreach ($at_users as $user_id)
			{
				if ($user_id == $question_info['published_uid'])
				{
					continue;
				}
					
				$this->model('notify')->send($uid, $user_id, notify_class::TYPE_COMMENT_AT_ME, notify_class::CATEGORY_QUESTION, $question_info['question_id'], array(
					'from_uid' => $uid, 
					'question_id' => $question_info['question_id'], 
					'comment_id' => $comment_id
				));
			}
		}
		
		$this->update_question_comments_count($question_id);
		
		return $comment_id;
	}

	public function get_question_comments($question_id)
	{
		return $this->fetch_all('question_comments', 'question_id = ' . intval($question_id), "time ASC");
	}

	public function get_comment_by_id($comment_id)
	{
		return $this->fetch_row('question_comments', "id = " . intval($comment_id));
	}
	
	public function remove_comment($comment_id)
	{
		return $this->delete('question_comments', "id = " . intval($comment_id));
	}

	/**
	 * 处理话题日志
	 * @param array $log_list
	 *
	 * @return array
	 */
	public function analysis_log($log_list, $published_uid = 0, $anonymous = 0)
	{
		if (empty($log_list))
		{
			return $log_list;
		}
		
		foreach ($log_list as $key => $log)
		{
			$log_user_ids[] = $log['uid'];
		}
		
		if ($log_user_ids)
		{
			$log_users_info = $this->model('account')->get_user_info_by_uids($log_user_ids);
		}
		
		foreach ($log_list as $key => $log)
		{
			$title_list = null;
			$user_info = $log_users_info[$log['uid']];
			$user_name = $user_info['user_name'];
			$user_url = 'people/' . $user_info['url_token'];
			
			if ($published_uid == $log['uid'] AND $anonymous)
			{
				$user_name_string = '匿名用户';	
			}
			else
			{
				$user_name_string = '<a href="' . $user_url . '">' . $user_name . '</a>';
			}
			
			switch ($log['associate_action'])
			{
				case ACTION_LOG::ADD_QUESTION :
					$title_list = $user_name_string . ' 添加了该问题</p><p>' . $log['associate_content'] . '</p><p>' . $log['associate_attached'] . '';
					break;
				
				case ACTION_LOG::MOD_QUESTON_TITLE : //修改问题标题
						
					$title_list = $user_name_string . ' 修改了问题标题';
					
					if ($log['addon_data']['modify_reason'])
					{
						$title_list .= '[' . $log['addon_data']['modify_reason'] . ']';
					}
					
					$Services_Diff = new Services_Diff($log['associate_attached'], $log['associate_content']);
					
					$title_list .= '<p>' . $Services_Diff->get_Text_Diff_Renderer_inline() . '</p>';
					
					break;
				
				case ACTION_LOG::MOD_QUESTION_DESCRI : //修改问题
					
					$title_list = $user_name_string . ' 修改了问题内容';
			
					if ($log['addon_data']['modify_reason'])
					{
						$title_list .= '[' . $log['addon_data']['modify_reason'] . ']';
					}
					
					$Services_Diff = new Services_Diff($log['associate_attached'], $log['associate_content']);
					 
					$title_list .= '<p>' .$Services_Diff->get_Text_Diff_Renderer_inline() . '</p>';
					
					break;
				
				case ACTION_LOG::ADD_TOPIC : //添加话题
					
					$topic_info = $this->model('topic')->get_topic_by_id($log['associate_attached']);
					$title_list = $user_name_string . ' 给该问题添加了一个话题 <p><a href="topic/' . $topic_info['url_token'] . '">' . $log['associate_content'] . '</a>';
					
					break;
				
				case ACTION_LOG::DELETE_TOPIC : //移除话题
					
					$topic_info = $this->model('topic')->get_topic_by_id($log['associate_attached']);
					$title_list = $user_name_string . ' 移除了该问题的一个话题 <p><a href="topic/' . $topic_info['url_token'] . '">' . $log['associate_content'] . '</a>';
					
					break;
				
				case ACTION_LOG::MOD_QUESTION_CATEGORY : //修改分类
					

					$title_list = $user_name_string . ' 修改了该问题的分类 <p><a href="home/explore/category-' . $log['associate_attached'] . '">' . $log['associate_content'] . '</a>';
					
					break;
				
				case ACTION_LOG::MOD_QUESTION_ATTACH : //修改附件
					

					$title_list = $user_name_string . ' 修改了该问题的附件 ';
					
					break;
				
				case ACTION_LOG::REDIRECT_QUESTION : //问题重定向
					
					$question_info = $this->get_question_info_by_id($log['associate_attached']);
					
					if ($question_info)
					{
						$title_list = $user_name_string . ' 将问题重定向至：<a href="question/' . $log['associate_attached'] . '">' . $question_info['question_content'] . '</a>';
					}
					
					break;
				
				case ACTION_LOG::DEL_REDIRECT_QUESTION : //取消问题重定向
					

					$title_list = $user_name_string . ' 取消了问题重定向 ';
					
					break;
			}
			
			(! empty($title_list)) ? $data_list[] = array(
				'title' => $title_list, 
				'add_time' => date('Y-m-d H:i:s', $log['add_time']), 
				'log_id' => sprintf('%06s', $log['history_id'])
			) : '';
		}
		
		return $data_list;
	}

	public function redirect($uid, $item_id, $target_id = NULL)
	{
		if ($item_id == $target_id)
		{
			return false;
		}
		
		if (! $target_id)
		{
			if ($this->delete('redirect', 'item_id = ' . intval($item_id)))
			{
				return ACTION_LOG::save_action($uid, $item_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::DEL_REDIRECT_QUESTION);
			}
		}
		else if ($question = $this->get_question_info_by_id($item_id))
		{
			if (! $this->fetch_row('redirect', 'item_id = ' . intval($item_id) . ' AND target_id = ' . intval($target_id)))
			{
				$redirect_id = $this->insert('redirect', array(
					'item_id' => intval($item_id), 
					'target_id' => intval($target_id), 
					'time' => time(), 
					'uid' => intval($uid)
				));
				
				ACTION_LOG::save_action($uid, $item_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::REDIRECT_QUESTION, $question['question_content'], $target_id);
				
				return $redirect_id;
			}
		}
	}

	public function get_redirect($item_id)
	{
		return $this->fetch_row('redirect', 'item_id = ' . intval($item_id));
	}
	
	public function question_move_category($from_ids = array(), $target_id)
	{
		if (!is_array($from_ids) OR !$target_id)
		{
			return false;
		}
		
		array_walk_recursive($from_ids, 'intval_string');
		
		return $this->update('question', array(
			'category_id' => intval($target_id)
		), 'category_id IN (' . implode(',', $from_ids) .')');
	}
	
	public function save_last_answer($question_id, $answer_id = null)
	{
		if (!$answer_id)
		{
			if ($last_answer = $this->fetch_row('answer', 'question_id = ' . intval($question_id), 'add_time DESC'))
			{
				$answer_id = $last_answer['answer_id'];
			}
		}
		
		return $this->shutdown_update('question', array('last_answer' => intval($answer_id)), 'question_id = ' . intval($question_id));
	}
	
	public function get_helpful_users($related_question_ids, $limit, $exclude_uids = null)
	{
		if (!is_array($related_question_ids) OR sizeof($related_question_ids) == 0)
		{
			return false;
		}
		
		if (!$exclude_uids)
		{
			$exclude_uids = array(
				0
			);
		}
		
		array_walk_recursive($exclude_uids, 'intval_string');
		
		if ($related_answers = $this->fetch_all('answer', "question_id IN(" . implode(',', $related_question_ids) . ") AND agree_count > 0 AND uid NOT IN (" . implode(',', $exclude_uids) . ")", 'agree_count DESC', 3))
		{
			foreach ($related_answers AS $key => $val)
			{
				$helpful_users[] = $val['uid'];
			}
		}
		
		if ($helpful_users)
		{
			return $this->model('account')->get_user_info_by_uids($helpful_users, true);
		}
	}
	
	public function calc_popular_value($question_id)
	{
		if (!$question_info = $this->fetch_row('question', 'question_id = ' . intval($question_id)))
		{
			return false;
		}
		
		if ($question_info['popular_value_update'] > time() - 300)
		{
			return false;
		}
		
		//$popular_value = (log($question_info['view_count'], 10) * 4 + $question_info['focus_count'] + ($question_info['agree_count'] * $question_info['agree_count'] / ($question_info['agree_count'] + $question_info['against_count'] + 1))) / (round(((time() - $question_info['add_time']) / 3600), 1) / 2 + round(((time() - $question_info['update_time']) / 3600), 1) / 2 + 1);
		$popular_value = log($question_info['view_count'], 10) + $question_info['focus_count'] + ($question_info['agree_count'] * $question_info['agree_count'] / ($question_info['agree_count'] + $question_info['against_count'] + 1));
		
		return $this->shutdown_update('question', array(
			'popular_value' => $popular_value,
			'popular_value_update' => time()
		), 'question_id = ' . intval($question_id));
	}
	
	public function get_modify_reason()
	{
		if ($modify_reasons = explode("\n", get_setting('question_modify_reason')))
		{
			$modify_reason = array();
			
			foreach($modify_reasons as $key => $val)
			{
				$val = trim($val);
				
				if ($val)
				{
					$modify_reason[] = $val;
				}
			}
			
			return $modify_reason;
		}
		else
		{
			return false;
		}
	}
	
	public function save_report($uid, $type, $target_id, $reason, $url)
	{
		return $this->insert('report', array(
			'uid' => $uid,
			'type' => htmlspecialchars($type),
			'target_id' => $target_id,
			'reason' => htmlspecialchars($reason),
			'url' => htmlspecialchars($url),
			'add_time' => time(),
			'status' => 0,
		));
	}
	
	public function get_report_list($where, $page, $pre_page, $order = 'id DESC')
	{
		return $this->fetch_page('report', $where, $order, $page, $pre_page);
	}
	
	public function update_report($report_id, $data)
	{
		return $this->update('report', $data, 'id = ' . intval($report_id));
	}
	
	public function delete_report($report_id)
	{
		return $this->delete('report', 'id = ' . intval($report_id));
	}
	
	public function get_question_list_by_topic_ids($topic_ids, $category_id = null, $answer_count = null, $order_by = 'question_id DESC', $is_recommend = false, $page, $per_page)
	{
		if (!$topic_ids)
		{
			return false;
		}
		
		if (!is_array($topic_ids))
		{
			$topic_ids = array(
				$topic_ids
			);
		}

		array_walk_recursive($topic_ids, 'intval_string');
		
		$result_cache_key = 'question_list_by_topic_ids_' . implode('_', $topic_ids) . '_' . md5($answer_count . $category_id . $order_by . $is_recommend . $page . $per_page);
		
		$found_rows_cache_key = 'question_list_by_topic_ids_found_rows_' . implode('_', $topic_ids) . '_' . md5($answer_count . $category_id . $is_recommend . $per_page);
			
		$where[] = 'topic_relation.topic_id IN(' . implode(',', $topic_ids) . ')';
			
		if ($answer_count !== null)
		{
			$where[] = "question.answer_count = " . intval($answer_count);
		}
		
		if ($is_recommend)
		{
			$where[] = 'question.is_recommend = 1';
		}
				
		if ($category_id)
		{
			$where[] = 'question.category_id IN(' . implode(',', $this->model('system')->get_category_with_child_ids('question', $category_id)) . ')';
		}
		
		if (!$found_rows = AWS_APP::cache()->get($found_rows_cache_key))
		{
			$_found_rows = $this->query_row('SELECT COUNT(DISTINCT question.question_id) AS count FROM ' . $this->get_table('question') . ' AS question LEFT JOIN ' . $this->get_table('topic_relation') . " AS topic_relation ON question.question_id = topic_relation.item_id AND topic_relation.type = 'question' WHERE " . implode(' AND ', $where));
			
			$found_rows = $_found_rows['count'];
			
			AWS_APP::cache()->set($found_rows_cache_key, $found_rows, get_setting('cache_level_high'));
		}
		
		$this->questions_list_total = $found_rows;
		
		if (!$result = AWS_APP::cache()->get($result_cache_key))
		{
			$result = $this->query_all('SELECT question.* FROM ' . $this->get_table('question') . ' AS question LEFT JOIN ' . $this->get_table('topic_relation') . " AS topic_relation ON question.question_id = topic_relation.item_id AND topic_relation.type = 'question' WHERE " . implode(' AND ', $where) . ' GROUP BY question.question_id ORDER BY question.' . $order_by, calc_page_limit($page, $per_page));
			
			AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_high'));
		}
		
		return $result;
	}
	
	/**
	 *
	 * 根据问题ID,得到相关联的话题标题信息
	 * @param int $question_id
	 * @param string $limit
	 *
	 * @return array
	 */
	public function get_question_topic_by_questions($question_ids, $limit = null)
	{
		if (!is_array($question_ids) OR sizeof($question_ids) == 0)
		{
			return false;
		}
		
		if (!$topic_ids_query = $this->query_all("SELECT DISTINCT topic_id FROM " . $this->get_table('topic_relation') . " WHERE item_id IN(" . implode(',', $question_ids) . ") AND `type` = 'question'"))
		{
			return false;	
		}
		
		foreach ($topic_ids_query AS $key => $val)
		{
			$topic_ids[] = $val['topic_id'];	
		}
	
		if ($topic_list = $this->query_all("SELECT * FROM " . $this->get_table('topic') . " WHERE topic_id IN(" . implode(',', $topic_ids) . ") ORDER BY discuss_count DESC", $limit))
		{
			foreach ($topic_list AS $key => $val)
			{
				if (!$val['url_token'])
				{
					$topic_list[$key]['url_token'] = urlencode($val['topic_title']);
				}
			}
		}
		
		return $topic_list;
	}
	
	public function get_focus_questions_topic($question_ids, $topic_ids)
	{
		if (!is_array($question_ids) OR !is_array($topic_ids))
		{
			return false;
		}
		
		$topics = $this->model('topic')->get_topics_by_ids($topic_ids);
		
		if ($topic_question = $this->fetch_all('topic_relation', 'item_id IN(' . implode(',', $question_ids) . ') AND topic_id IN(' . implode(',', $topic_ids) . ") AND `type` = 'question'"))
		{
			foreach ($topic_question AS $key => $val)
			{			
				$topics_by_questions_ids[$val['question_id']] = array(
					'topic_id' => $val['topic_id'], 
					'topic_title' => $topics[$val['topic_id']]['topic_title'],
					'url_token' => $topics[$val['topic_id']]['url_token'], 
				);
			}
		}
				
		return $topics_by_questions_ids;
	}
	
	public function lock_question($question_id, $lock_status = true)
	{
		return $this->update('question', array(
			'lock' => intval($lock_status)
		), 'question_id = ' . intval($question_id));
	}
	
	public function auto_lock_question()
	{
		if (!get_setting('auto_question_lock_day'))
		{
			return false;
		}
		
		return $this->shutdown_update('question', array(
			'lock' => 1
		), '`lock` = 0 AND `update_time` < ' . (time() - 3600 * 24 * get_setting('auto_question_lock_day')));
	}
	
	public function get_question_thanks($question_id, $uid)
	{
		if (!$question_id OR !$uid)
		{
			return false;
		}
		
		return $this->fetch_row('question_thanks', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}
	
	public function question_thanks($question_id, $uid, $user_name)
	{
		if (!$question_id OR !$uid)
		{
			return false;
		}
		
		if (!$question_info = $this->get_question_info_by_id($question_id))
		{
			return false;
		}
		
		if ($question_thanks = $this->get_question_thanks($question_id, $uid))
		{
			//$this->delete('question_thanks', "id = " . $question_thanks['id']);
			
			return false;
		}
		else
		{
			$this->insert('question_thanks', array(
				'question_id' => $question_id,
				'uid' => $uid,
				'user_name' => $user_name
			));
			
			$this->shutdown_update('question', array(
				'thanks_count' => $this->count('question_thanks', 'question_id = ' . intval($question_id)),
			), 'question_id = ' . intval($question_id));
			
			$this->model('integral')->process($uid, 'QUESTION_THANKS', get_setting('integral_system_config_thanks'), '感谢问题 #' . $question_id, $question_id);
			
			$this->model('integral')->process($question_info['published_uid'], 'THANKS_QUESTION', -get_setting('integral_system_config_thanks'), '问题被感谢 #' . $question_id, $question_id);
			
			$this->model('account')->update_thanks_count($question_info['published_uid']);
			
			return true;
		}
	}
	
	public function get_related_topics($question_content)
	{
		$question_keywords = $this->model('system')->analysis_keyword($question_content);
			
		if ($question_related_list = $this->get_related_question_list(null, $question_content, 3))
		{
			foreach ($question_related_list AS $key => $val)
			{
				$question_related_ids[$val['question_id']] = $val['question_id'];
			}
				
			$questions_info = $this->model('question')->get_question_info_by_ids($question_related_ids);
				
			foreach ($questions_info AS $key => $val)
			{
				$keywords = $this->model('system')->analysis_keyword($val['question_content']);
					
				$keyword_hits = 0;
					
				foreach ($keywords AS $keyword)
				{
					if (in_array($keyword, $question_keywords))
					{
						$keyword_hits++;
					}
				}
					
				if ($keyword_hits < 3)
				{
					unset($question_related_ids[$val['question_id']]);
				}
			}
				
			if ($related_topics = $this->model('topic')->get_topics_by_item_ids($question_related_ids, 'question'))
			{
				foreach ($related_topics AS $related_topic)
				{
					foreach ($related_topic AS $key => $val)
					{
						$topics[$val['topic_title']] = $val['topic_title'];
					}
				}
			}
		}
		
		return $topics;
	}
	
	public function get_answer_users_by_question_id($question_id, $limit = 5, $published_uid = null)
	{
		if ($result = AWS_APP::cache()->get('answer_users_by_question_id_' . md5($question_id . $limit . $published_uid)))
		{
			return $result;
		}
		
		if (!$published_uid)
		{
			if (!$question_info = $this->get_question_info_by_id($question_id))
			{
				return false;
			}
			
			$published_uid = $question_info['published_uid'];
		}
		
		if ($answer_users = $this->query_all("SELECT DISTINCT uid FROM " . get_table('answer') . " WHERE question_id = " . intval($question_id) . " AND uid <> " . intval($published_uid) . " AND anonymous = 0 ORDER BY agree_count DESC LIMIT " . intval($limit)))
		{
			foreach ($answer_users AS $key => $val)
			{
				$answer_uids[] = $val['uid'];
			}
			
			$result = $this->model('account')->get_user_info_by_uids($answer_uids);
			
			AWS_APP::cache()->set('answer_users_by_question_id_' . md5($question_id . $limit . $published_uid), $result, get_setting('cache_level_normal'));
		}
		
		return $result;
	}
}
