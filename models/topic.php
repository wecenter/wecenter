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

class topic_class extends AWS_MODEL
{
	public function get_topic_list($where = null, $order = 'topic_id DESC', $limit = 10, $page = null)
	{
		if ($topic_list = $this->fetch_page('topic', $where, $order, $page, $limit))
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

	public function get_focus_topic_list($uid, $limit = 20)
	{
		if (!$uid)
		{
			return false;
		}

		if (!$focus_topics = $this->fetch_all('topic_focus', 'uid = ' . intval($uid)))
		{
			return false;
		}

		foreach ($focus_topics AS $key => $val)
		{
			$topic_ids[] = $val['topic_id'];
		}

		if ($topic_list = $this->fetch_all('topic', 'topic_id IN(' . implode(',', $topic_ids) . ')', 'discuss_count DESC', $limit))
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

	public function get_focus_topic_ids_by_uid($uid)
	{
		if (!$uid)
		{
			return false;
		}

		if (!$topic_focus = $this->fetch_all('topic_focus', "uid = " . intval($uid)))
		{
			return false;
		}

		foreach ($topic_focus as $key => $val)
		{
			$topic_ids[$val['topic_id']] = $val['topic_id'];
		}

		return $topic_ids;
	}

	public function get_sized_file($size = null, $pic_file = null)
	{
		if (! $pic_file)
		{
			return false;
		}

		$original_file = str_replace('_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['h'] . '.', '.', $pic_file);

		if (! $size)
		{
			return $original_file;
		}

		// Fix date() bug
		if (!file_exists(get_setting('upload_dir') . '/topic/' . $original_file))
		{
			$dir_info = explode('/', $original_file);

			$dir_date = intval($dir_info[0]);

			$original_file = ($dir_date + 1) . '/' . basename($original_file);
		}

		return str_replace('.', '_' . AWS_APP::config()->get('image')->topic_thumbnail[$size]['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail[$size]['h'] . '.', $original_file);
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

	public function get_topics_by_ids($topic_ids)
	{
		if (!$topic_ids OR !is_array($topic_ids))
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

	public function get_topic_by_title($topic_title)
	{
		if ($topic_id = $this->fetch_one('topic', 'topic_id', "topic_title = '" . $this->quote(htmlspecialchars($topic_title)) . "'"))
		{
			return $this->get_topic_by_id($topic_id);
		}
	}

	public function get_topic_id_by_title($topic_title)
	{
		return $this->fetch_one('topic', 'topic_id', "topic_title = '" . $this->quote(htmlspecialchars($topic_title)) . "'");
	}

	public function save_topic($topic_title, $uid = null, $auto_create = true, $topic_description = null)
	{
		$topic_title = str_replace(array('-', '/'), '_', $topic_title);

		if (!$topic_id = $this->get_topic_id_by_title($topic_title) AND $auto_create)
		{
			$topic_id = $this->insert('topic', array(
				'topic_title' => htmlspecialchars($topic_title),
				'add_time' => time(),
				'topic_description' => htmlspecialchars($topic_description),
				'topic_lock' => 0
			));

			if ($uid)
			{
				ACTION_LOG::save_action($uid, $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::ADD_TOPIC, $topic_title);

				$this->add_focus_topic($uid, $topic_id);
			}
		}
		else
		{
			$this->update_discuss_count($topic_id);
		}

		return $topic_id;
	}

	public function remove_topic_relation($uid, $topic_id, $item_id, $type)
	{
		if (!$topic_info = $this->get_topic_by_id($topic_id))
		{
			return false;
		}

		switch ($type)
		{
			case 'question':
				ACTION_LOG::save_action($uid, $item_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::DELETE_TOPIC, $topic_info['topic_title'], $topic_id);
			break;
		}

		return $this->delete('topic_relation', 'topic_id = ' . intval($topic_id) . ' AND item_id = ' . intval($item_id) . " AND `type` = '" . $this->quote($type) . "'");
	}

	public function update_topic($uid, $topic_id, $topic_title = null, $topic_description = null, $topic_pic = null)
	{
		if (!$topic_info = $this->get_topic_by_id($topic_id))
		{
			return false;
		}

		if ($topic_title)
		{
			$data['topic_title'] = htmlspecialchars(trim($topic_title));
		}

		if ($topic_description)
		{
			$data['topic_description'] = htmlspecialchars($topic_description);
		}

		if ($topic_pic)
		{
			$data['topic_pic'] = htmlspecialchars($topic_pic);
		}

		if ($data)
		{
			$this->update('topic', $data, 'topic_id = ' . intval($topic_id));

			// 记录日志
			if ($topic_title AND $topic_title != $topic_info['topic_title'])
			{
				ACTION_LOG::save_action($uid, $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::MOD_TOPIC, $topic_title, $topic_info['topic_title']);
			}

			if ($topic_description AND $topic_description != $topic_info['topic_description'])
			{
				ACTION_LOG::save_action($uid, $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::MOD_TOPIC_DESCRI, $topic_description, $topic_info['topic_description']);
			}

			if ($topic_pic AND $topic_pic != $topic_info['topic_pic'])
			{
				ACTION_LOG::save_action($uid, $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::MOD_TOPIC_PIC, $topic_pic, $topic_info['topic_pic']);
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

		return $this->update('topic', array(
			'topic_lock' => $topic_lock
		), 'topic_id IN (' . implode(',', $topic_ids) . ')');

	}

	public function has_lock_topic($topic_id)
	{
		$topic_info = $this->get_topic_by_id($topic_id);

		return $topic_info['topic_lock'];
	}

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

			// 记录日志
			ACTION_LOG::save_action($uid, $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::ADD_TOPIC_FOCUS);
		}
		else
		{
			if ($this->delete_focus_topic($topic_id, $uid))
			{
				$this->query('UPDATE ' . $this->get_table('topic') . " SET focus_count = focus_count - 1 WHERE topic_id = " . intval($topic_id));
			}

			$result = 'remove';

			ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ADD_TOPIC_FOCUS . ' AND uid = ' . intval($uid) . ' AND associate_id = ' . intval($topic_id));
		}

		// 更新个人计数
		$focus_count = $this->count('topic_focus', 'uid = ' . intval($uid));

		$this->model('account')->update_users_fields(array(
			'topic_focus_count' => $focus_count
		), $uid);

		return $result;
	}

	public function delete_focus_topic($topic_id, $uid)
	{
		return $this->delete('topic_focus', 'uid = ' . intval($uid) . ' AND topic_id = ' . intval($topic_id));
	}

	public function has_focus_topic($uid, $topic_id)
	{
		return $this->fetch_one('topic_focus', 'focus_id', "uid = " . intval($uid) . " AND topic_id = " . intval($topic_id));
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

	public function update_discuss_count($topic_id)
	{
		if (! $topic_id)
		{
			return false;
		}

		$this->update('topic', array(
			'discuss_count' => $this->count('topic_relation', 'topic_id = ' . intval($topic_id)),
			'discuss_count_last_week' => $this->count('topic_relation', 'add_time > ' . (time() - 604800) . ' AND topic_id = ' . intval($topic_id)),
			'discuss_count_last_month' => $this->count('topic_relation', 'add_time > ' . (time() - 2592000) . ' AND topic_id = ' . intval($topic_id)),
			'discuss_count_update' => intval($this->fetch_one('topic_relation', 'add_time', 'topic_id = ' . intval($topic_id), 'add_time DESC'))
		), 'topic_id = ' . intval($topic_id));
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
			$this->delete('topic_relation', 'topic_id = ' . intval($topic_id));
			$this->delete('feature_topic', 'topic_id = ' . intval($topic_id));
			$this->delete('related_topic', 'topic_id = ' . intval($topic_id) . ' OR related_id = ' . intval($topic_id));
			$this->delete('reputation_topic', ' topic_id = ' . intval($topic_id));
			$this->delete('topic', 'topic_id = ' . intval($topic_id));

			$this->update('topic', array(
				'parent_id' => 0
			), 'parent_id = ' . intval($topic_id));
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
				$follow_uids[] = $val['uid'];
				$follow_users_array[$val['uid']] = $val;
			}
		}

		if (! $follow_uids)
		{
			return $this->get_topic_list("topic_id NOT IN(" . implode($topic_focus_ids, ',') . ")", 'topic_id DESC', $limit);
		}

		if ($topic_focus = $this->query_all("SELECT DISTINCT topic_id, uid FROM " . $this->get_table("topic_focus") . " WHERE uid IN(" . implode($follow_uids, ',') . ") AND topic_id NOT IN (" . implode($topic_focus_ids, ',') . ") ORDER BY focus_id DESC LIMIT " . $limit))
		{
			foreach ($topic_focus as $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
				$topic_id_focus_uid[$val['topic_id']] = $val['uid'];
			}
		}
		if (! $topic_ids)
		{
			if ($topic_focus_ids)
			{
				return $this->get_topic_list("topic_id NOT IN (" . implode($topic_focus_ids, ',') . ")", 'topic_id DESC', $limit);
			}
			else
			{
				return $this->get_topic_list(null, 'topic_id DESC', $limit);
			}
		}

		if ($topic_focus_ids)
		{
			$topics = $this->fetch_all('topic', 'topic_id IN(' . implode($topic_ids, ',') . ') AND topic_id NOT IN(' . implode($topic_focus_ids, ',') . ')', 'topic_id DESC', $limit);
		}
		else
		{
			$topics = $this->fetch_all('topic', 'topic_id IN(' . implode($topic_ids, ',') . ')', 'topic_id DESC', $limit);
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

	public function get_focus_users_by_topic($topic_id, $limit = 10)
	{
		$user_list = array();

		$uids = $this->query_all("SELECT DISTINCT uid FROM " . $this->get_table('topic_focus') . " WHERE topic_id = " . intval($topic_id), $limit);

		if ($uids)
		{
			$user_list_query = $this->model('account')->get_user_info_by_uids(fetch_array_value($uids, 'uid'));

			if ($user_list_query)
			{
				foreach ($user_list_query AS $user_info)
				{
					$user_list[$user_info['uid']]['uid'] = $user_info['uid'];

					$user_list[$user_info['uid']]['user_name'] = $user_info['user_name'];

					$user_list[$user_info['uid']]['avatar_file'] = get_avatar_url($user_info['uid'], 'mid');

					$user_list[$user_info['uid']]['url'] = get_js_url('/people/' . $user_info['url_token']);
				}
			}
		}

		return $user_list;
	}

	public function get_item_ids_by_topics_id($topic_id, $type = null, $limit = null)
	{
		return $this->get_item_ids_by_topics_ids(array(
			$topic_id
		), $limit);
	}

	public function get_item_ids_by_topics_ids($topic_ids, $type = null, $limit = null)
	{
		if (!is_array($topic_ids))
		{
			return false;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		$where[] = 'topic_id IN(' . implode(',', $topic_ids) . ')';

		if ($type)
		{
			$where[] = "`type` = '" . $this->quote($type) . "'";
		}

		if ($result = $this->query_all("SELECT item_id FROM " . $this->get_table('topic_relation') . " WHERE " . implode(' AND ', $where), $limit))
		{
			foreach ($result AS $key => $val)
			{
				$item_ids[] = $val['item_id'];
			}
		}

		return $item_ids;
	}

	public function get_best_answer_users_by_topic_id($topic_id, $limit)
	{
		if ($helpful_users = AWS_APP::cache()->get('helpful_users_' . md5($topic_id . '_' . $limit)))
		{
			return $helpful_users;
		}

		if ($reputation_list = $this->fetch_all('reputation_topic', 'reputation > 0 AND topic_id = ' . intval($topic_id), 'reputation DESC', $limit))
		{
			foreach ($reputation_list AS $key => $val)
			{
				$best_answer_uids[] = $val['uid'];

				$helpful_users[$val['uid']]['agree_count'] = $val['agree_count'];
				$helpful_users[$val['uid']]['thanks_count'] = $val['thanks_count'];
			}

			$users_info = $this->model('account')->get_user_info_by_uids($best_answer_uids, true);

			foreach ($users_info as $key => $val)
			{
				$helpful_users[$val['uid']]['user_info'] = $val;
			}
		}

		AWS_APP::cache()->set('helpful_users_' . md5($topic_id . '_' . $limit), $helpful_users, get_setting('cache_level_normal'));

		return $helpful_users;
	}

	public function get_helpful_users_by_topic_ids($topic_ids, $limit = 10, $experience_limit = 1)
	{
		if (!is_array($topic_ids))
		{
			return false;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		if ($helpful_users = AWS_APP::cache()->get('helpful_users_' . md5(implode('_', $topic_ids) . '_' . $limit . '_' . $experience_limit)))
		{
			return $helpful_users;
		}

		foreach ($topic_ids AS $topic_id)
		{
			if ($reputation_list = $this->fetch_all('reputation_topic', 'reputation > 0 AND topic_id = ' . intval($topic_id), 'reputation DESC', $limit))
			{
				foreach ($reputation_list AS $key => $val)
				{
					$best_answer_uids[$val['uid']] = $val['uid'];
				}
			}
		}

		if (!$best_answer_uids)
		{
			return false;
		}

		foreach ($best_answer_uids AS $best_answer_uid)
		{
			$rank_rate = $this->sum('reputation_topic', 'reputation', 'reputation > 0 AND topic_id IN(' . implode(',', $topic_ids) . ') AND uid = ' . intval($best_answer_uid));

			$best_answer_user_ranks[] = array(
				'rate' => $rank_rate,
				'uid' => $best_answer_uid
			);
		}

		$best_answer_user_ranks = aasort($best_answer_user_ranks, 'rate', 'DESC');

		if (sizeof($best_answer_user_ranks) > $limit)
		{
			$best_answer_user_ranks = array_slice($best_answer_user_ranks, 0, $limit);
		}

		unset($best_answer_uids);

		foreach ($best_answer_user_ranks AS $user_rank)
		{
			$best_answer_uids[$user_rank['uid']] = $user_rank['uid'];
		}

		$users_info = $this->model('account')->get_user_info_by_uids($best_answer_uids, true);

		foreach ($best_answer_user_ranks AS $user_rank)
		{
			$helpful_users[$user_rank['uid']]['user_info'] = $users_info[$user_rank['uid']];

			$experience = array();

			foreach ($topic_ids AS $topic_id)
			{
				$topic_agree_count = $this->model('reputation')->calculate_agree_count($user_rank['uid'], array($topic_id));

				$experience[] = array(
					'topic_id' => $topic_id,
					'agree_count' => $topic_agree_count
				);
			}

			$experience = aasort($experience, 'agree_count', 'DESC');

			if (sizeof($experience) > $experience_limit)
			{
				$experience = array_slice($experience, 0, $experience_limit);
			}

			foreach ($experience AS $key => $val)
			{
				$helpful_users[$user_rank['uid']]['experience'][] = array(
					'topic_id' => $val['topic_id'],
					'agree_count' => $val['agree_count']
				);

				$experience_topic_ids[$val['topic_id']] = $val['topic_id'];
			}
		}

		$experience_topics_info = $this->model('topic')->get_topics_by_ids($experience_topic_ids);

		if ($helpful_users)
		{
			foreach ($helpful_users AS $key => $val)
			{
				if (is_array($helpful_users[$key]['experience']))
				{
					foreach ($helpful_users[$key]['experience'] AS $exp_key => $exp_val)
					{
						$helpful_users[$key]['experience'][$exp_key]['topic_info'] = $experience_topics_info[$exp_val['topic_id']];
					}
				}
			}
		}

		AWS_APP::cache()->set('helpful_users_' . md5(implode('_', $topic_ids) . '_' . $limit . '_' . $experience_limit), $helpful_users, get_setting('cache_level_low'));

		return $helpful_users;
	}

	/**
	 * 获取热门话题
	 * @param  $category
	 * @param  $limit
	 */
	public function get_hot_topics($category_id = 0, $limit = 5, $section = null)
	{
		$where = array();

		if ($category_id)
		{
			if ($questions = $this->query_all("SELECT question_id FROM " . get_table('question') . " WHERE category_id IN(" . implode(',', $this->model('system')->get_category_with_child_ids('question', $category_id)) . ') ORDER BY add_time DESC LIMIT 200'))
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

			if (!$topic_relation = $this->fetch_all('topic_relation', 'item_id IN(' . implode(',', $question_ids) . ") AND `type` = 'question'"))
			{
				return false;
			}

			foreach ($topic_relation AS $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
			}

			$where[] = 'topic_id IN(' . implode(',', $topic_ids) . ')';
		}

		switch ($section)
		{
			default:
				return $this->fetch_all('topic', implode(' AND ', $where), 'discuss_count DESC', $limit);
			break;

			case 'week':
				$where[] = 'discuss_count_update > ' . (time() - 604801);

				return $this->fetch_all('topic', implode(' AND ', $where), 'discuss_count_last_week DESC', $limit);
			break;

			case 'month':
				$where[] = 'discuss_count_update > ' . (time() - 2592001);

				return $this->fetch_all('topic', implode(' AND ', $where), 'discuss_count_last_month DESC', $limit);
			break;
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
		$uid_list = array();
		$topic_list = array();

		if (!$log_list)
		{
			return false;
		}

		foreach ($log_list as $key => $log)
		{
			if (! in_array($log['uid'], $uid_list))
			{
				$uid_list[] = $log['uid'];
			}

			if ($log['associate_attached'] AND is_digits($log['associate_attached']) AND !in_array($log['associate_attached'], $topic_list))
			{
				$topic_list[] = $log['associate_attached'];
			}

			if ($log['associate_content'] AND is_digits($log['associate_content']) AND !in_array($log['associate_content'], $topic_list))
			{
				$topic_list[] = $log['associate_content'];
			}
		}

		/**
		 * 格式话简单数据类型
		 */
		if ($topics_array = $this->get_topics_by_ids($topic_list))
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
					$topic_info = $this->get_topic_by_id($log['associate_attached']);

					$title_list = '<a href="' . $user_url . '">' . $user_name . '</a> ' . AWS_APP::lang()->_t('添加了相关话题') . '<p><a href="topic/' . rawurlencode($topic_info['topic_title']) . '">' . $topic_info['topic_title'] . '</a></p>';
					break;

				case ACTION_LOG::DELETE_RELATED_TOPIC : //删除相关话题
					$topic_info = $this->get_topic_by_id($log['associate_attached']);

					$title_list = '<a href="' . $user_url . '">' . $user_name . '</a> ' . AWS_APP::lang()->_t('删除了相关话题') . '<p><a href="topic/' . rawurlencode($topic_info['topic_title']) . '">' . $topic_info['topic_title'] . '</a></p>';
					break;
			}

			$data_list[] = ($title_list) ? array(
				'user_name' => $user_name,
				'title' => $title_list,
				'add_time' => date('Y-m-d', $log['add_time']),
				'log_id' => sprintf('%06s', $log['history_id']),
				'user_url' => $user_url
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

			$this->shutdown_update('topic', array(
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
		if (! $question_ids = $this->get_item_ids_by_topics_id($topic_id, 'question', 10))
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

	public function get_topic_best_answer_action_list($topic_ids, $uid, $limit)
	{
		if (!is_digits($topic_ids))
		{
			return false;
		}

		$cache_key = 'topic_best_answer_action_list_' . md5($topic_ids . $limit);

		if (!$result = AWS_APP::cache()->get($cache_key))
		{
			if ($topic_relation = $this->query_all("SELECT item_id FROM " . $this->get_table('topic_relation') . " WHERE topic_id IN (" . implode(',', explode(',', $topic_ids)) . ") AND `type` = 'question'"))
			{
				foreach ($topic_relation AS $key => $val)
				{
					$question_ids[$val['item_id']] = $val['item_id'];
				}

				unset($topic_relation);
			}
			else
			{
				return false;
			}

			if ($best_answers = $this->query_all("SELECT question_id, best_answer FROM " . $this->get_table('question') . " WHERE best_answer > 0 AND question_id IN (" . implode(',', $question_ids) . ") ORDER BY update_time DESC LIMIT " . $limit))
			{
				unset($question_ids);

				foreach ($best_answers AS $key => $val)
				{
					$answer_ids[$val['best_answer']] = $val['best_answer'];
					$question_ids[$val['question_id']] = $val['question_id'];
				}
			}
			else
			{
				return false;
			}

			if ($questions_info = $this->model('question')->get_question_info_by_ids($question_ids))
			{
				foreach ($questions_info AS $key => $val)
				{
					$questions_info[$key]['associate_action'] = ACTION_LOG::ANSWER_QUESTION;

					$action_list_uids[$val['published_uid']] = $val['published_uid'];
				}
			}

			if ($answers_info = $this->model('answer')->get_answers_by_ids($answer_ids))
			{
				foreach ($answers_info AS $key => $val)
				{
					$action_list_uids[$val['uid']] = $val['uid'];
				}
			}

			if ($action_list_uids)
			{
				$action_list_users_info = $this->model('account')->get_user_info_by_uids($action_list_uids);
			}

			$answers_info_vote_user = $this->model('answer')->get_vote_user_by_answer_ids($answer_ids);

			$answer_attachs = $this->model('publish')->get_attachs('answer', $answer_ids, 'min');


			foreach ($questions_info AS $key => $val)
			{
				$result[$key]['question_info'] = $val;
				$result[$key]['user_info'] = $action_list_users_info[$answers_info[$val['best_answer']]['uid']];

				if ($val['has_attach'])
				{
					$result[$key]['question_info']['attachs'] = $question_attachs[$val['question_id']];
				}

				$result[$key]['answer_info'] = $answers_info[$val['best_answer']];

				if ($val['answer_info']['has_attach'])
				{
					$result[$key]['answer_info']['attachs'] = $answer_attachs[$val['best_answer']];
				}
			}

			AWS_APP::cache()->set($cache_key, $result, get_setting('cache_level_low'));
		}

		if ($uid)
		{
			foreach ($result AS $key => $val)
			{
				$question_ids[] = $val['question_info']['question_id'];

				if ($val['question_info']['best_answer'])
				{
					$answer_ids[] = $val['question_info']['best_answer'];
				}
			}

			$questions_focus = $this->model('question')->has_focus_questions($question_ids, $uid);
			$answers_info_vote_status = $this->model('answer')->get_answer_vote_status($answer_ids, $uid);
		}

		foreach ($result AS $key => $val)
		{
			$result[$key]['question_info']['has_focus'] = $questions_focus[$val['question_info']['question_id']];
			$result[$key]['answer_info']['agree_status'] = intval($answers_info_vote_status[$val['question_info']['best_answer']]);

			$result[$key]['title'] = $val['question_info']['question_content'];
			$result[$key]['link'] = get_js_url('/question/' . $val['question_info']['question_id']);

			$result[$key]['add_time'] = $result[$key]['answer_info']['add_time'];

			$result[$key]['last_action_str'] = ACTION_LOG::format_action_data(ACTION_LOG::ANSWER_QUESTION, $result[$key]['answer_info']['uid'], $result[$key]['user_info']['user_name'], $result[$key]['question_info']);
		}

		return $result;
	}

	public function check_url_token($url_token, $topic_id)
	{
		return $this->count('topic', "url_token = '" . $this->quote($url_token) . "' OR topic_title = '" . $this->quote($url_token) . "' AND topic_id != " . intval($topic_id));
	}

	public function update_url_token($url_token, $topic_id)
	{
		return $this->update('topic', array(
			'url_token' => htmlspecialchars($url_token)
		), 'topic_id = ' . intval($topic_id));
	}

	public function update_seo_title($seo_title, $topic_id)
	{
		return $this->update('topic', array(
			'seo_title' => htmlspecialchars($seo_title)
		), 'topic_id = ' . intval($topic_id));
	}

	public function save_topic_relation($uid, $topic_id, $item_id, $type)
	{
		if (!$topic_id OR !$item_id OR !$type)
		{
			return false;
		}

		if (!$topic_info = $this->get_topic_by_id($topic_id))
		{
			return false;
		}

		if ($flag = $this->check_topic_relation($topic_id, $item_id, $type))
		{
			return $flag;
		}

		switch ($type)
		{
			case 'question':
				ACTION_LOG::save_action($uid, $item_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_TOPIC, $topic_info['topic_title'], $topic_id);

				ACTION_LOG::save_action($uid, $topic_id, ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::ADD_TOPIC, $topic_info['topic_title'], $item_id);
			break;
		}

		$this->model('account')->save_recent_topics($uid, $topic_info['topic_title']);

		$insert_id = $this->insert('topic_relation', array(
			'topic_id' => intval($topic_id),
			'item_id' => intval($item_id),
			'add_time' => time(),
			'uid' => intval($uid),
			'type' => $type
		));

		$this->model('topic')->update_discuss_count($topic_id);

		return $insert_id;
	}

	public function check_topic_relation($topic_id, $item_id, $type)
	{
		$where[] = 'topic_id = ' . intval($topic_id);
		$where[] = "`type` = '" . $this->quote($type) . "'";

		if ($item_id)
		{
			$where[] = 'item_id = ' . intval($item_id);
		}

		return $this->fetch_one('topic_relation', 'id', implode(' AND ', $where));
	}

	public function get_topics_by_item_id($item_id, $type)
	{
		$result = $this->get_topics_by_item_ids(array(
			$item_id
		), $type);

		return $result[$item_id];
	}

	public function get_topics_by_item_ids($item_ids, $type)
	{
		if (!is_array($item_ids) OR sizeof($item_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($item_ids, 'intval_string');

		if (!$item_topics = $this->fetch_all('topic_relation', "item_id IN(" . implode(',', $item_ids) . ") AND `type` = '" . $this->quote($type) . "'"))
		{
			foreach ($item_ids AS $item_id)
			{
				if (!$result[$item_id])
				{
					$result[$item_id] = array();
				}
			}

			return $result;
		}

		foreach ($item_topics AS $key => $val)
		{
			$topic_ids[] = $val['topic_id'];
		}

		$topics_info = $this->model('topic')->get_topics_by_ids($topic_ids);

		foreach ($item_topics AS $key => $val)
		{
			$result[$val['item_id']][] = $topics_info[$val['topic_id']];
		}

		foreach ($item_ids AS $item_id)
		{
			if (!$result[$item_id])
			{
				$result[$item_id] = array();
			}
		}

		return $result;
	}

	public function set_is_parent($topic_id, $is_parent)
	{
		if (!$topic_id)
		{
			return false;
		}

		$to_update_topic['is_parent'] = intval($is_parent);

		if ($to_update_topic['is_parent'] != 0)
		{
			$to_update_topic['parent_id'] = 0;
		}

		if (is_array($topic_id))
		{
			array_walk_recursive($topic_id, 'intval_string');

			$where = 'topic_id IN (' . implode(',', $topic_id) . ')';
		}
		else
		{
			$where = 'topic_id = ' . intval($topic_id);
		}

		return $this->update('topic', $to_update_topic, $where);
	}

	public function set_parent_id($topic_id, $parent_id)
	{
		if (is_array($topic_id))
		{
			array_walk_recursive($topic_id, 'intval_string');

			$where = 'topic_id IN (' . implode(',', $topic_id) . ')';
		}
		else
		{
			$where = 'topic_id = ' . intval($topic_id);
		}

		return $this->update('topic', array('parent_id' => intval($parent_id)), $where);
	}

	public function get_parent_topics()
	{
		$parent_topic_list_query = $this->fetch_all('topic', 'is_parent = 1', 'topic_title ASC');

		if (!$parent_topic_list_query)
		{
			return false;
		}

		foreach ($parent_topic_list_query AS $parent_topic_info)
		{
			if (!$parent_topic_info['url_token'])
			{
				$parent_topic_info['url_token'] = urlencode($parent_topic_info['topic_title']);
			}

			$parent_topic_list[$parent_topic_info['topic_id']] = $parent_topic_info;
		}

		return $parent_topic_list;
	}

	public function get_child_topic_ids($topic_id)
	{
		if ($child_topics = $this->query_all("SELECT topic_id FROM " . get_table('topic') . " WHERE parent_id = " . intval($topic_id)))
		{
			foreach ($child_topics AS $key => $val)
			{
				$child_topic_ids[] = $val['topic_id'];
			}
		}

		return $child_topic_ids;
	}

	public function get_related_topic_ids_by_id($topic_id)
	{
		if (!$topic_info = $this->model('topic')->get_topic_by_id($topic_id))
		{
			return false;
		}

		if ($topic_info['merged_id'] AND $topic_info['merged_id'] != $topic_info['topic_id'])
		{
			$merged_topic_info = $this->model('topic')->get_topic_by_id($topic_info['merged_id']);

			if ($merged_topic_info)
			{
				$topic_info = $merged_topic_info;
			}
		}

		$related_topics_ids = array();

		$related_topics = $this->model('topic')->related_topics($topic_info['topic_id']);

		if ($related_topics)
		{
			foreach ($related_topics AS $related_topic)
			{
				$related_topics_ids[$related_topic['topic_id']] = $related_topic['topic_id'];
			}
		}

		$child_topic_ids = $this->model('topic')->get_child_topic_ids($topic_info['topic_id']);

		if ($child_topic_ids)
		{
			foreach ($child_topic_ids AS $topic_id)
			{
				$related_topics_ids[$topic_id] = $topic_id;
			}
		}

		$contents_topic_id = $topic_info['topic_id'];

		$merged_topics = $this->model('topic')->get_merged_topic_ids($topic_info['topic_id']);

		if ($merged_topics)
		{
			foreach ($merged_topics AS $merged_topic)
			{
				$merged_topic_ids[] = $merged_topic['source_id'];
			}

			$contents_topic_id .= ',' . implode(',', $merged_topic_ids);
		}

		return array_merge($related_topics_ids, explode(',', $contents_topic_id));
	}
}
