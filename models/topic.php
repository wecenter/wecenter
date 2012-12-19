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

class topic_class extends AWS_MODEL
{

	/**
	 * 
	 * 根据指定条件获取话题数据
	 * @param string $where
	 * @param int    $limit
	 * 
	 * @return array
	 */
	public function get_topic_list($where = null, $limit = 10, $return_count = false, $order = 'topic_id DESC')
	{
		if ($return_count)
		{
			return $this->count('topic', $where);
		}
		else
		{
			if ($topic_list = $this->fetch_all('topic', $where, $order, $limit))
			{
				foreach ($topic_list AS $key => $val)
				{
					if (!$val['url_token'])
					{
						$topic_list[$key]['url_token'] = rawurlencode($val['topic_title']);
					}
				}
			}
			
			return $topic_list;
		}
	}
	
	public function get_topic_search_list($count = false, $query_data = null)
	{
		$sort_key = 'topic_id';
		$order = 'DESC';
		$where = array();
		$page = 0;
		$per_page = 15;

		if (is_array($query_data))
		{
			extract($query_data);
		}
		
		if ($keyword)
		{			
			$where[] = "topic_title LIKE '" . $this->quote($keyword) . "%'";
		}
		
		if ($question_count_min || $question_count_min == '0')
		{
			$where[] = 'discuss_count >= ' . intval($question_count_min);
		}
		
		if ($question_count_max || $question_count_max == '0')
		{
			$where[] = 'discuss_count <= ' . intval($question_count_max);
		}
		
		if ($topic_pic)
		{
			if ($topic_pic == 1)
			{
				$where[] = "topic_pic <> ''";
			}
			else if ($topic_pic == 2)
			{
				$where[] = "topic_pic = ''";
			}
		}
		
		if ($topic_description)
		{
			if ($topic_description == 1)
			{
				$where[] = "topic_description <> ''";
			}
			else if ($topic_description == 2)
			{
				$where[] = "topic_description = ''";
			}
		}
		
		if ($count)
		{
			return $this->count('topic', implode(' AND ', $where));
		}
		
		if ($topic_list = $this->fetch_page('topic', implode(' AND ', $where), $sort_key . ' ' . $order, $page, $per_page))
		{
			foreach($topic_list as $key => $val)
			{
				if (!$val['url_token'])
				{
					$topic_list[$key]['url_token'] = rawurlencode($val['topic_title']);
				}
				
				if ($detail)
				{
					$topic_list[$key]['related_topics'] = $this->related_topics($val['topic_id']);
				}
			}
			
			return $topic_list;
		}
		else
		{
			return array();
		}
	}
	

	/**
	 * 根据用户 ID, 得到用户关注话题列表
	 */
	public function get_focus_topic_list($uid = null, $limit = 20)
	{		
		if (!$uid)
		{
			return false;
		}
		
		if ($topic_list = $this->query_all("SELECT SQL_CALC_FOUND_ROWS topic.* FROM " . $this->get_table('topic') . " AS topic LEFT JOIN " . $this->get_table("topic_focus") . " AS focus ON focus.topic_id = topic.topic_id WHERE focus.uid = " . intval($uid) . " ORDER BY focus.focus_id DESC", $limit))
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
	
	public function get_sized_file($size = null, $pic_file = null)
	{
		if (! $pic_file)
		{
			return false;
		}
		else
		{
			if (!$size)
			{
				return str_replace('_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['h'], '', $pic_file);
			}
			
			return str_replace(AWS_APP::config()->get('image')->topic_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['h'], AWS_APP::config()->get('image')->topic_thumbnail[$size]['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail[$size]['h'], $pic_file);
		}
	}
	
	/**
	 * 
	 * 得到单条话题内容
	 * @param int $topic_id 话题ID
	 * 
	 * @return array
	 */
	public function get_topic_by_id($topic_id)
	{
		static $topics;
		
		if (! $topic_id)
		{
			return false;
		}
		
		if (! $topics[$topic_id])
		{
			$topics[$topic_id] = $this->fetch_row('topic', 'topic_id = ' . intval($topic_id));
			
			if ($topics[$topic_id] AND !$topics[$topic_id]['url_token'])
			{
				$topics[$topic_id]['url_token'] = urlencode($topics[$topic_id]['topic_title']);
			}
		}
		
		return $topics[$topic_id];
	}
	
	public function get_topic_by_url_token($url_token)
	{
		if ($topic_id = $this->fetch_one('topic', 'topic_id', "url_token = '" . $this->quote($url_token) . "'"))
		{
			return $this->get_topic_by_id($topic_id);
		}
	}
	
	public function get_merged_topic_ids($topic_id)
	{
		return $this->fetch_all('topic_merge', 'target_id = ' . intval($topic_id));
	}
	
	public function merge_topic($source_id, $target_id, $uid)
	{
		if ($this->count('topic', 'topic_id = ' . intval($source_id) . ' AND merged_id = 0'))
		{
			$this->update('topic', array(
				'merged_id' => intval($target_id)
			), 'topic_id = ' . intval($source_id));
			
			return $this->insert('topic_merge', array(
				'source_id' => intval($source_id),
				'target_id' => intval($target_id),
				'uid' => intval($uid),
				'time' => time()
			));
		}
		
		return false;
	}
	
	public function remove_merge_topic($source_id, $target_id)
	{
		$this->update('topic', array(
			'merged_id' => 0
		), 'topic_id = ' . intval($source_id));
		
		return $this->delete('topic_merge', 'source_id = ' . intval($source_id) . ' AND target_id = ' . intval($target_id));
	}

	/**
	 * 批量获取话题
	 * @param  $topic_array
	 */
	public function get_topics_by_ids($topic_ids)
	{
		if (! is_array($topic_ids))
		{
			return false;
		}
		
		array_walk_recursive($topic_ids, 'intval_string');
		
		$topics = $this->fetch_all('topic', 'topic_id IN(' . implode(',', $topic_ids) . ')');
		
		foreach ($topics AS $key => $val)
		{
			if (!$val['url_token'])
			{
				$val['url_token'] = urlencode($val['topic_title']);
			}
			
			$result[$val['topic_id']] = $val;
		}
		
		return $result;
	}
	
	/**
	 * 获取单条话题-通过话题名称
	 * @param  $topic_title 
	 */
	public function get_topic_by_title($topic_title)
	{
		if ($topic_id = $this->fetch_one('topic', 'topic_id', "topic_title = '" . $this->quote($topic_title) . "'"))
		{
			return $this->get_topic_by_id($topic_id);
		}
	}

	/**
	 * 获取单条话题id-通过话题名称
	 * @param  $topic_title
	 */
	public function get_topic_id_by_title($topic_title)
	{		
		return $this->fetch_one('topic', 'topic_id', "topic_title = '" . $this->quote($topic_title) . "'");
	}
	
	public function save_topic($question_id, $topic_title, $uid = null, $topic_lock = 0, $topic_type = null, $auto_create = true)
	{		
		if (!$topic_id = $this->get_topic_id_by_title($topic_title) AND $auto_create)
		{
			$topic_id = $this->insert('topic', array(
				'topic_title' => htmlspecialchars($topic_title), 
				'add_time' => time(), 
				'topic_description' => htmlspecialchars($topic_description), 
				'topic_lock' => intval($topic_lock)
			));
		
			//$this->model('search_index')->push_index('topic', $topic_title, $topic_id);	
		}
		
		if (!$topic_id)
		{
			return false;
		}
		
		if ($question_id AND $uid)
		{
			ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_TOPIC, $topic_title, $topic_id);
			
			return $topic_id;
		}
		
		// 判断话题是否锁定
		if ($this->has_lock_topic($topic_id))
		{
			return false;
		}
		
		if (!$uid)
		{
			return $topic_id;
		}
		
		switch ($topic_type)
		{
			case 1:
				// 添加问题添加到话题的动作
				ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_TOPIC, $topic_title, $topic_id);
					
				ACTION_LOG::save_action($uid, $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::ADD_TOPIC, $topic_title, $question_id);
			break;
				
			case 3:
				ACTION_LOG::save_action($uid, $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::ADD_TOPIC, $topic_title, '');
			break;
		}

		return $topic_id;
	}

	/**
	 * 
	 * 删除话题内容
	 * @param int $topic_id 话题ID
	 * @param int $question_id 话题关联类型ID
	 * 
	 * @return boolean true|false
	 */
	public function delete_question_topic($topic_id, $question_id, $action_log = true, $delete_record = false)
	{
		if (intval($topic_id) == 0)
		{
			return false;
		}
		
		$topic_info = $this->get_topic_by_id($topic_id);
		
		if ($question_id)
		{
			$this->delete('topic_question', ' topic_id = ' . intval($topic_id) . ' AND question_id = ' . intval($question_id));
			
			if ($action_log)
			{
				ACTION_LOG::save_action(USER::get_client_uid(), $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::DELETE_TOPIC, $topic_info['topic_title'], $topic_id);
				//ACTION_LOG::save_action(USER::get_client_uid(), $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::DELETE_TOPIC, $topic_info['topic_title'], $question_id);
			}
		}
		
		if (!$question_id)
		{
			$this->delete("topic_question", 'topic_id = ' . intval($topic_id));
			
			if ($action_log)
			{
				ACTION_LOG::save_action(USER::get_client_uid(), $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::DELETE_TOPIC, $topic_info['topic_title']);
			}
		}
		
		if ($delete_record)
		{
			$this->delete('topic', 'topic_id = ' . intval($topic_id));
		}
		
		return true;
	}
	
	public function update_topic($topic_id, $topic_title = '', $topic_description = '', $topic_pic = '', $topic_lock = 0)
	{
		$topic_title = htmlspecialchars(trim($topic_title));
		$topic_description = htmlspecialchars($topic_description);
		$topic_pic = htmlspecialchars($topic_pic);
		
		$topic_info = $this->get_topic_by_id($topic_id); //得到话题信息
		
		if (! empty($topic_title))
		{
			$data['topic_title'] = $topic_title;
		}
		
		if (! empty($topic_description))
		{
			$data['topic_description'] = $topic_description;
		}
		
		if (! empty($topic_pic))
		{
			$data['topic_pic'] = $topic_pic;
		}
		
		if (! empty($topic_lock))
		{
			$data['topic_lock'] = $topic_lock;
		}
		
		if ($data)
		{
			$this->update('topic', $data, 'topic_id = ' . intval($topic_id));
			
			//记录日志
			if ($topic_title && $topic_title != $topic_info['topic_title'])
			{
				ACTION_LOG::save_action(USER::get_client_uid(), $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::MOD_TOPIC, $topic_title, $topic_info['topic_title']);
				
				//$this->model('search_index')->push_index('topic', $topic_title, $topic_id);
			}
			
			if ($topic_description && $topic_description != $topic_info['topic_description'])
			{
				ACTION_LOG::save_action(USER::get_client_uid(), $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::MOD_TOPIC_DESCRI, $topic_description, $topic_info['topic_description']);
			}
			
			if ($topic_pic && $topic_pic != $topic_info['topic_pic'])
			{
				ACTION_LOG::save_action(USER::get_client_uid(), $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::MOD_TOPIC_PIC, $topic_pic, $topic_info['topic_pic']);
			}
		}
		
		return TRUE;
	}

	/**
	 * 
	 * 锁定/解锁话题
	 * @param int $topic_id
	 * @param int $topic_lock
	 * 
	 * @return boolean true|false
	 */
	public function lock_topic_by_ids($topic_ids, $topic_lock = 0)
	{
		if (!$topic_ids)
		{
			return false;
		}
		
		if (!is_array($topic_ids))
		{
			$topic_ids = array(
				$topic_ids,
			);
		}
		
		array_walk_recursive($topic_ids, 'intval_string');
		
		return $this->UPDATE('topic', array(
			'topic_lock' => $topic_lock
		), 'topic_id IN (' . implode(',', $topic_ids) . ')');
	
	}

	/**
	 * 
	 * 判断话题是否锁定
	 * @param int $topic_id
	 * 
	 * @return boolean true|false
	 */
	public function has_lock_topic($topic_id)
	{
		$topic = $this->get_topic_by_id($topic_id);
		
		return $topic['topic_lock'];
	}

	/**
	 * 
	 * 增加话题关注
	 * 
	 * @return boolean true|false
	 */
	public function add_focus_topic($uid, $topic_id)
	{
		if (! $this->has_focus_topic($uid, $topic_id))
		{
			if ($this->insert('topic_focus', array(
				"topic_id" => intval($topic_id), 
				"uid" => intval($uid), 
				"add_time" => time()
			)))
			{
				$this->query('UPDATE ' . $this->get_table('topic') . " SET focus_count = focus_count + 1 WHERE topic_id = " . intval($topic_id));
			}
			
			$result = 'add';
			
			//记录日志
			ACTION_LOG::save_action($uid, $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::ADD_TOPIC_FOCUS);
		}
		else
		{
			if ($this->delete_focus_topic($topic_id, $uid))
			{
				$this->query('UPDATE ' . $this->get_table('topic') . " SET focus_count = focus_count - 1 WHERE topic_id = " . intval($topic_id));
			}
			
			$result = 'remove';
			
			//记录日志
			ACTION_LOG::save_action($uid, $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::DELETE_TOPIC_FOCUS);
		}
		
		//更新个人计数
		

		$focus_count = $this->count('topic_focus', 'uid = ' . intval($uid));
		
		$this->model('account')->update_users_fields(array(
			'topic_focus_count' => $focus_count
		), $uid);
		
		return $result;
	}

	/**
	 * 
	 * 删除话题关注
	 * @param int/string $topic_id [1, 12]
	 * @param int $uid
	 * 
	 * @return boolean true|false
	 */
	public function delete_focus_topic($topic_id, $uid)
	{
		if (intval($topic_id) == 0)
		{
			return false;
		}
		
		return $this->delete('topic_focus', 'uid = ' . intval($uid) . ' AND topic_id = ' . intval($topic_id));
	
	}

	/**
	 * 
	 * 判断是否已经关注该话题
	 * @param int $uid
	 * @param int $topic_id
	 * 
	 * @return boolean true|false
	 */
	public function has_focus_topic($uid, $topic_id)
	{
		return $this->count('topic_focus', "uid = " . intval($uid) . " AND topic_id = " . intval($topic_id));
	}

	public function has_focus_topics($uid, $topic_ids)
	{
		if (!is_array($topic_ids))
		{
			return false;
		}
		
		array_walk_recursive($topic_ids, 'intval_string');
		
		if ($focus = $this->query_all('SELECT focus_id, topic_id FROM ' . $this->get_table('topic_focus') . " WHERE uid = " . intval($uid) . " AND topic_id IN(" . implode(',', $topic_ids) . ")"))
		{
			foreach ($focus as $key => $val)
			{
				$result[$val['topic_id']] = $val['focus_id'];
			}
		}
		
		return $result;
	}

	/**
	 * 更新问题统计
	 * @param  $topic_id
	 */
	function update_discuss_count($topic_id)
	{
		if (! $topic_id)
		{
			return false;
		}
		
		return $this->query("UPDATE " . $this->get_table('topic') . " SET discuss_count = (SELECT COUNT(*) FROM " . $this->get_table('topic_question') . " WHERE topic_id = " . intval($topic_id) . ") WHERE topic_id = " . intval($topic_id));
	}

	/**
	 * 物理删除话题及其关联的图片等
	 * 
	 * @param  $topic_id
	 */
	public function remove_topic_by_ids($topic_id)
	{
		if (!$topic_id)
		{
			return false;
		}
		
		if (is_array($topic_id))
		{
			$topic_ids = $topic_id;
		}
		else
		{
			$topic_ids[] = $topic_id;
		}
		
		array_walk_recursive($topic_ids, 'intval_string');
		
		foreach($topic_ids as $topic_id)
		{
			if (!$topic_info = $this->get_topic_by_id($topic_id))
			{
				continue;
			}
			
			if ($topic_info['topic_pic'])
			{
				foreach (AWS_APP::config()->get('image')->topic_thumbnail as $size)
				{
					@unlink(get_setting('upload_dir') . '/topic/' . str_replace(AWS_APP::config()->get('image')->topic_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['h'], $size['w'] . '_' . $size['h'], $topic_info['topic_pic']));
						
				}
					
				@unlink(get_setting('upload_dir') . '/topic/' . str_replace('_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['h'], '', $topic_info['topic_pic']));
			}
			
			// 删除动作
			ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_TOPIC . ' AND associate_id = ' . intval($topic_id));
			ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ADD_TOPIC . ' AND associate_attached = ' . intval($topic_id));
			
			$this->delete('topic_focus', 'topic_id = ' . intval($topic_id));
			$this->delete('topic_question', 'topic_id = ' . intval($topic_id));
			$this->delete('feature_topic', 'topic_id = ' . intval($topic_id));
			$this->delete('related_topic', 'topic_id = ' . intval($topic_id) . ' OR related_id = ' . intval($topic_id));
			$this->delete('reputation_topic', ' topic_id = ' . intval($topic_id));
			$this->delete('topic', 'topic_id = ' . intval($topic_id));
		}
	
		return true;
	}
	
	// 我关注的人关注的话题
	public function get_user_recommend_v2($uid, $limit = 10)
	{
		$topic_focus_ids = array(0);
		
		$follow_uids = array(0);
		
		if ($topic_focus = $this->query_all("SELECT topic_id FROM " . $this->get_table("topic_focus") . " WHERE uid = " . (int)$uid))
		{
			foreach ($topic_focus as $key => $val)
			{
				$topic_focus_ids[] = $val['topic_id'];
			}
		}
		
		if ($friends = $this->model('follow')->get_user_friends($uid, false))
		{
			foreach ($friends as $key => $val)
			{
				$follow_uids[] = $val['friend_uid'];
				$follow_users_array[$val['friend_uid']] = $val;
			}
		}
		
		if (! $follow_uids)
		{
			return $this->get_topic_list("topic_id NOT IN(" . implode($topic_focus_ids, ',') . ")", $limit, false, 'topic_id DESC');
		}
		
		if ($topic_focus = $this->query_all("SELECT DISTINCT topic_id, uid FROM " . $this->get_table("topic_focus") . " WHERE uid IN(" . implode($follow_uids, ',') . ") AND topic_id NOT IN (" . implode($topic_focus_ids, ',') . ") ORDER BY focus_id DESC LIMIT " . $limit))
		{
			foreach ($topic_focus as $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
				$topic_id_focus_uid[$val['topic_id']] = $val[uid];
			}
		}
		if (! $topic_ids)
		{
			if ($topic_focus_ids)
			{
				return $this->get_topic_list("topic_id NOT IN (" . implode($topic_focus_ids, ',') . ")", $limit, false, 'topic_id DESC');
			}
			else
			{
				return $this->get_topic_list(null, $limit, false, 'topic_id DESC');
			}
		}
		
		if ($topic_focus_ids)
		{
			$topics = $this->query_all("SELECT * FROM " . $this->get_table('topic') . " WHERE topic_id IN(" . implode($topic_ids, ',') . ") AND topic_id NOT IN (" . implode($topic_focus_ids, ',') . ") ORDER BY topic_id DESC LIMIT " . $limit);
		}
		else
		{
			$topics = $this->query_all("SELECT * FROM " . $this->get_table('topic') . " WHERE topic_id IN(" . implode($topic_ids, ',') . ") ORDER BY topic_id DESC LIMIT " . $limit);
		}
		
		foreach ($topics as $key => $val)
		{
			$topics[$key]['focus_users'] = $follow_users_array[$topic_id_focus_uid[$val['topic_id']]];
			
			if (!$val['url_token'])
			{
				$topics[$key]['url_token'] = urlencode($val['topic_title']);
			}
		}
		
		return $topics;
	}

	function get_focus_users_by_topic($topic_id, $limit)
	{
		$users_list = array();
		
		if ($uids = $this->query_all("SELECT DISTINCT uid FROM " . $this->get_table('topic_focus') . " WHERE topic_id = " . intval($topic_id), $limit))
		{
			$users_list = $this->model('account')->get_user_info_by_uids(fetch_array_value($uids, 'uid'));
		}
		
		return $users_list;
	}

	function get_question_ids_by_topics_ids($topic_ids, $limit, $where = null, $order = 'update_time DESC')
	{
		if (!is_array($topic_ids))
		{
			$topic_ids = array(
				$topic_ids
			);
		}
		
		array_walk_recursive($topic_ids, 'intval_string');
		
		$topic_id_in = implode(',', $topic_ids);
		
		if ($where)
		{
			$where = ' AND ' . $where;
		}
		
		$_order = explode(' ', $order);
		
		if (!$where AND $_order[0] == 'question_id')
		{
			$result = $this->query_all("SELECT question_id FROM " . $this->get_table('topic_question') . " WHERE topic_id IN (" . $topic_id_in . ") ORDER BY " . $order, $limit);
		}
		else
		{
			if (!$result = $this->model('cache')->load('question_ids_by_topics_ids_' . md5($topic_id_in . $where . $limit . $order)))
			{
				$result = $this->query_all("SELECT question_id FROM " . get_table('question') . " WHERE EXISTS (SELECT question_id FROM " . $this->get_table('topic_question') . " WHERE " . get_table('question') . ".question_id = " . $this->get_table('topic_question') . ".question_id AND topic_id IN (" . $topic_id_in . ")) " . $where . " ORDER BY " . $order, $limit);
				
				$this->model('cache')->save('question_ids_by_topics_ids_' . md5($topic_id_in . $where . $limit . $order), $result, 3600);
			}
		}
		
		if ($result)
		{
			foreach ($result AS $key => $val)
			{
				$question_ids[] = $val['question_id'];	
			}
		}
		
		return $question_ids;
	}

	/**
	 * 获取最佳回复者,对于本话题
	 * @param  $topic_id
	 */
	public function get_best_answer_users($topic_id, $uid, $limit)
	{
		if (!$uid_list = $this->fetch_all('reputation_topic', 'reputation > 0 AND topic_id = ' . intval($topic_id), 'reputation DESC', $limit))
		{
			return false;
		}
		
		foreach ($uid_list AS $key => $val)
		{
			$user_ids[] = $val['uid'];
			$best_users[$val['uid']]['agree_count'] = $val['agree_count'];
			$best_users[$val['uid']]['thanks_count'] = $val['thanks_count'];
		}
				
		$users_list = $this->model('account')->get_user_info_by_uids($user_ids, true);
		$users_follow_check = $this->model('follow')->users_follow_check($uid, $user_ids);
		
		foreach ($users_list as $key => $val)
		{
			$best_users[$val['uid']]['user_info'] = $val;
			$best_users[$val['uid']]['user_info']['focus'] = $users_follow_check[$val['uid']];
		}
		
		return $best_users;
	}

	/**
	 * 获取 分类 的热门话题
	 * @param  $category
	 */
	public function get_hot_topic($category_id = 0, $limit = 5)
	{	
		if ($category_id)
		{
			$questions = $this->query_all("SELECT question_id FROM " . get_table('question') . " WHERE answer_count > 0 AND category_id IN(" . implode(',', $this->model('system')->get_category_with_child_ids('question', $category_id)) . ') ORDER BY add_time DESC LIMIT 200');
		}
		else
		{
			$questions = $this->query_all("SELECT question_id FROM " . get_table('question') . " WHERE  answer_count > 0 ORDER BY add_time DESC LIMIT 200");
		}
		
		if ($questions)
		{
			foreach ($questions AS $key => $val)
			{
				$question_ids[] = $val['question_id'];
			}
		}
		
		if (!$question_ids)
		{
			return false;
		}
		
		if ($result = $this->query_all('SELECT topic_question.topic_id, topic_question.question_id, topic.topic_description, topic.focus_count, topic.topic_title, topic.topic_pic, topic.url_token, topic.discuss_count, topic.focus_count FROM ' . $this->get_table('topic_question') . ' AS topic_question LEFT JOIN ' . $this->get_table('topic') . ' AS topic ON topic_question.topic_id = topic.topic_id WHERE topic.discuss_count > 1 AND topic_question.question_id IN (' . implode(',', $question_ids) . ')'))
		{
			foreach ($result as $key => $val)
			{
				$topics[$val['topic_id']]['topic_id'] = $val['topic_id'];
				$topics[$val['topic_id']]['focus_count'] = $val['focus_count'];
				$topics[$val['topic_id']]['discuss_count'] = $val['discuss_count'];
				$topics[$val['topic_id']]['topic_title'] = $val['topic_title'];
				$topics[$val['topic_id']]['topic_description'] = $val['topic_description'];
				$topics[$val['topic_id']]['topic_pic'] = $val['topic_pic'];
				$topics[$val['topic_id']]['question_count'] = intval($topics[$val['topic_id']]['question_count']) + 1;
				$topics[$val['topic_id']]['scores'] = $topics[$val['topic_id']]['focus_count'] + $topics[$val['topic_id']]['question_count'];
				
				if (!$val['url_token'])
				{
					$topics[$val['topic_id']]['url_token'] = urlencode($val['topic_title']);
				}
				else
				{
					$topics[$val['topic_id']]['url_token'] = $val['url_token'];
				}
			}
		}
		
		if (is_array($topics))
		{
			$topics = aasort($topics, 'scores', 'DESC');
			
			$topics_count = count($topics);
			
			if ($topics)
			{
				if (strstr($limit, ','))
				{
					$limit = explode(',', $limit, 2);
					
					$topics = array_slice($topics, $limit[0], $limit[1]);
				}
				else
				{
					$topics = array_slice($topics, 0, $limit);
				}
				
				return array(
					'topics' => $topics, 
					'topics_count' => $topics_count
				);
			}
			else
			{
				return array(
					'topics' => array(), 
					'topics_count' => 0
				);
			}
		}
	}

	/**
	 * 处理话题日志
	 * @param array $log_list
	 * 
	 * @return array
	 */
	public function analysis_log($log_list)
	{
		$data_list = array();
		
		$uid_list = array(
			0
		);
		
		$topic_list = array(
			0
		);
		
		if (empty($log_list))
		{
			return;
		}
		/**
		 * 找到唯一数据值
		 */
		foreach ($log_list as $key => $log)
		{
			
			if (! in_array($log['uid'], $uid_list))
			{
				$uid_list[] = $log['uid'];
			}
			if (! empty($log['associate_attached']) && is_numeric($log['associate_attached']) && ! in_array($log['associate_attached'], $topic_list))
			{
				$topic_list[] = $log['associate_attached'];
			}
			if (! empty($log['associate_content']) && is_numeric($log['associate_content']) && ! in_array($log['associate_content'], $topic_list))
			{
				$topic_list[] = $log['associate_content'];
			}
		}
		
		/**
		 * 格式话简单数据类型
		 */
		
		if ($topics_array = $this->model('topic')->get_topics_by_ids($topic_list))
		{
			foreach ($topics_array as $key => $val)
			{
				$topic_title_list[$val['topic_id']] = $val['topic_title'];
			}
		}
		
		if ($user_name_array = $this->model('account')->get_user_info_by_uids($uid_list))
		{
			foreach ($user_name_array as $user_info)
			{
				$user_info_list[$user_info['uid']] = $user_info;
			}
		}
		
		/**
		 * 格式话数组
		 */
		foreach ($log_list as $key => $log)
		{
			$title_list = "";
			$user_name = $user_info_list[$log['uid']]['user_name'];
			
			$user_url = get_js_url('people/' . $user_info_list[$log['uid']]['url_token']);
			
			switch ($log['associate_action'])
			{
				case ACTION_LOG::ADD_TOPIC : //增加话题
					$title_list = '<a href="' . $user_url . '">' . $user_name . '</a> ' . AWS_APP::lang()->_t('创建了该话题'). '</p>';
					break;
				
				case ACTION_LOG::ADD_TOPIC_FOCUS : //关注话题
					

					break;
				
				case ACTION_LOG::DELETE_TOPIC : //删除话题
					

					break;
				
				case ACTION_LOG::DELETE_TOPIC_FOCUS : //删除话题关注
					

					break;
				
				case ACTION_LOG::MOD_TOPIC : //修改话题标题
					$Services_Diff = new Services_Diff($log['associate_attached'], $log['associate_content']);
					
					$title_list = '<a href="' . $user_url . '">' . $user_name . '</a> ' . AWS_APP::lang()->_t('修改了话题标题') . ' <p>' . $Services_Diff->get_Text_Diff_Renderer_inline() . "</p>";
					break;
				
				case ACTION_LOG::MOD_TOPIC_DESCRI : //修改话题描述
					$log['associate_attached'] = trim($log['associate_attached']);
					$log['associate_content'] = trim($log['associate_content']);
					
					$Services_Diff = new Services_Diff($log['associate_attached'], $log['associate_content']);
					$title_list = '<a href="' . $user_url . '">' . $user_name . '</a> ' . AWS_APP::lang()->_t('修改了话题描述') . ' <p>' . $Services_Diff->get_Text_Diff_Renderer_inline() . '</p>';
					
					break;
				
				case ACTION_LOG::MOD_TOPIC_PIC : //修改话题图片
					$title_list = '<a href="' . $user_url . '">' . $user_name . '</a> ' . AWS_APP::lang()->_t('修改了话题图片');
					break;
				
				case ACTION_LOG::ADD_RELATED_TOPIC : //添加相关话题
					$topic_info = $this->model('topic')->get_topic_by_id($log['associate_attached']);
					
					$title_list = '<a href="' . $user_url . '">' . $user_name . '</a> ' . AWS_APP::lang()->_t('添加了相关话题') . '<p><a href="topic/' . rawurlencode($topic_info['topic_title']) . '">' . $topic_info['topic_title'] . '</a></p>';
					break;
				
				case ACTION_LOG::DELETE_RELATED_TOPIC : //删除相关话题
					$topic_info = $this->model('topic')->get_topic_by_id($log['associate_attached']);
					
					$title_list = '<a href="' . $user_url . '">' . $user_name . '</a> ' . AWS_APP::lang()->_t('删除了相关话题') . '<p><a href="topic/' . rawurlencode($topic_info['topic_title']) . '">' . $topic_info['topic_title'] . '</a></p>';
					break;
			}
			
			(! empty($title_list)) ? $data_list[] = array(
				'user_name' => $user_name, 
				'title' => $title_list, 
				'add_time' => date('Y-m-d', $log['add_time']), 
				'log_id' => sprintf('%06s', $log['history_id'])
			) : '';
		}
		
		return $data_list;
	}

	public function save_related_topic($topic_id, $related_id)
	{
		$this->pre_save_auto_related_topics($topic_id);
		
		if (! $related_topic = $this->fetch_row('related_topic', 'topic_id = ' . intval($topic_id) . ' AND related_id = ' . intval($related_id)))
		{
			return $this->insert('related_topic', array(
				'topic_id' => intval($topic_id), 
				'related_id' => intval($related_id)
			));
		}
		
		return false;
	}

	public function remove_related_topic($topic_id, $related_id)
	{
		$this->pre_save_auto_related_topics($topic_id);
		
		return $this->delete('related_topic', 'topic_id = ' . intval($topic_id) . ' AND related_id = ' . intval($related_id));
	}

	public function pre_save_auto_related_topics($topic_id)
	{
		if (! $this->is_user_related($topic_id))
		{
			if ($auto_related_topics = $this->get_auto_related_topics($topic_id))
			{
				foreach ($auto_related_topics as $key => $val)
				{
					$this->insert('related_topic', array(
						'topic_id' => intval($topic_id), 
						'related_id' => $val['topic_id']
					));
				}
			}
			
			$this->update('topic', array(
				'user_related' => 1
			), 'topic_id = ' . intval($topic_id));
		}
	}

	public function get_related_topics($topic_id)
	{
		if ($related_topic = $this->fetch_all('related_topic', 'topic_id = ' . intval($topic_id)))
		{
			foreach ($related_topic as $key => $val)
			{
				$topic_ids[] = $val['related_id'];
			}
		}
		
		if ($topic_ids)
		{
			return $this->get_topics_by_ids($topic_ids);
		}
	}

	public function get_auto_related_topics($topic_id)
	{
		if (! $question_ids = $this->get_question_ids_by_topics_ids($topic_id, 10, null, 'question_id DESC'))
		{
			return false;
		}
		
		if ($question_ids)
		{
			if ($topics = $this->model('question')->get_question_topic_by_questions($question_ids, 10))
			{
				foreach ($topics as $key => $val)
				{
					if ($val['topic_id'] == $topic_id)
					{
						unset($topics[$key]);
					}
				}
				
				return $topics;
			}
		}
	}

	public function related_topics($topic_id)
	{
		if ($this->is_user_related($topic_id))
		{
			$related_topic = $this->get_related_topics($topic_id);
		}
		else
		{
			$related_topic = $this->get_auto_related_topics($topic_id);
		}
		
		return $related_topic;
	}

	public function is_user_related($topic_id)
	{
		$topic = $this->get_topic_by_id($topic_id);
		
		return $topic['user_related'];
	}

	public function get_topic_action_list($topic_ids, $limit, $best_answer = false)
	{
		if (is_string($topic_ids))
		{
			if (strstr($topic_ids, ','))
			{
				$topic_ids = explode(',', $topic_ids);
			}
		}
		
		if ($best_answer)
		{	
			if (!$question_ids = $this->model('topic')->get_question_ids_by_topics_ids($topic_ids, $limit, 'best_answer > 0'))
			{
				return false;
			}
			
			if (!$best_answers = $this->query_all("SELECT best_answer FROM " . $this->get_table('question') . " WHERE best_answer > 0 AND question_id IN (" . implode(',', $question_ids) . ")"))
			{
				return false;
			}
			
			foreach ($best_answers AS $key => $val)
			{
				$answer_ids[] = $val['best_answer'];
			}
		}
		else
		{
			if (!$question_ids = $this->model('topic')->get_question_ids_by_topics_ids($topic_ids, $limit))
			{
				return false;
			}
		}
		
		if ($best_answer)
		{
			$associate_action = array(
				ACTION_LOG::ANSWER_QUESTION
			);
			
			$associate_type = ACTION_LOG::CATEGORY_ANSWER;
			
			$associate_id = $answer_ids;
		}
		else
		{
			$associate_action = array(
				ACTION_LOG::ADD_QUESTION,
				ACTION_LOG::ANSWER_QUESTION
			);
			
			$associate_type = ACTION_LOG::CATEGORY_QUESTION;
			
			$associate_id = $question_ids;
		}
		
		if (!$action_list = ACTION_LOG::get_actions_distint_by_where("(associate_type = " . $associate_type . " AND associate_id IN (" . implode($associate_id, ",") . ") AND associate_action IN(" . implode(',', $associate_action) . "))", null))
		{
			return false;
		}
		
		unset($question_ids);
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
	
	function check_url_token($url_token, $topic_id)
	{
		return $this->count('topic', "url_token = '" . $this->quote($url_token) . "' OR topic_title = '" . $this->quote($url_token) . "' AND topic_id != " . intval($topic_id));
	}
	
	function update_url_token($url_token, $topic_id)
	{
		return $this->update('topic', array(
			'url_token' => $url_token
		), 'topic_id = ' . intval($topic_id));
	}
}