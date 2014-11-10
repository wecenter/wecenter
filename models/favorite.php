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

class favorite_class extends AWS_MODEL
{
	public function add_favorite($item_id, $item_type, $uid)
	{
		if (!$item_id OR !$item_type)
		{
			return false;
		}

		if (!$this->fetch_one('favorite', 'id', "type = '" . $this->quote($item_type) . "' AND item_id = " . intval($item_id) . ' AND uid = ' . intval($uid)))
		{
			return $this->insert('favorite', array(
				'item_id' => intval($item_id),
				'type' => $item_type,
				'uid' => intval($uid),
				'time' => time()
			));
		}
	}

	public function update_favorite_tag($item_id, $item_type, $tags, $uid)
	{
		if (!$item_id OR !$tags OR !$item_type)
		{
			return false;
		}

		$tags = str_replace(array('，', ' ', '　'), ',', $tags);

		$tags = explode(',', rtrim($tags, ','));

		foreach ($tags AS $key => $tag)
		{
			$tag = trim($this->quote(htmlspecialchars($tag)));

			if (!$tag)
			{
				continue;
			}

			if (!$this->fetch_one('favorite_tag', 'id', "item_id = " . intval($item_id) . " AND `type` = '" . $this->quote($item_type) . "' AND `title` = '" . $tag . "' AND uid = " . intval($uid)))
			{
				$this->insert('favorite_tag', array(
					'item_id' => intval($item_id),
					'type' => $item_type,
					'uid' => intval($uid),
					'title' => trim(htmlspecialchars($tag))
				));
			}
		}

		return true;
	}

	public function remove_favorite_tag($item_id, $item_type, $tag, $uid)
	{
		if ($tag)
		{
			$where[] = "title = '" . $this->quote($tag) . "'";
		}

		if ($item_id)
		{
			$where[] = "item_id = " . intval($item_id);
		}

		$where[] = "`type` = '" . $this->quote($item_type) . "'";
		$where[] = 'uid = ' . intval($uid);

		return $this->delete('favorite_tag', implode(' AND ', $where));
	}

	public function remove_favorite_item($item_id, $item_type, $uid)
	{
		if (!$item_id OR !$item_type OR !$uid)
		{
			return false;
		}

		$this->delete('favorite', "item_id = " . intval($item_id) . " AND `type` = '" . $this->quote($item_type) . "' AND uid = " . intval($uid));
		$this->delete('favorite_tag', "item_id = " . intval($item_id) . " AND `type` = '" . $this->quote($item_type) . "' AND uid = " . intval($uid));
	}

	public function get_favorite_tags($uid, $limit = null)
	{
		return $this->query_all('SELECT DISTINCT title FROM ' . $this->get_table('favorite_tag') . ' WHERE uid = ' . intval($uid) . ' ORDER BY id DESC', $limit);
	}
	
	public function get_item_tags_by_item_id($item_id, $item_type)
	{
		if ($favorite_tags = $this->fetch_all('favorite_tag', 'item_id = ' . intval($item_id) . " AND `type` = '" . $this->quote($item_type) . "'"))
		{
			foreach ($favorite_tags AS $key => $val)
			{
				$item_tags[] = $val['title'];
			}
		}

		return $item_tags;
	}

	public function get_favorite_items_tags_by_item_id($uid, $item_ids)
	{
		if (!$item_ids)
		{
			return false;
		}

		array_walk_recursive($item_ids, 'intval_string');

		if ($favorite_tags = $this->fetch_all('favorite_tag', 'uid = ' . intval($uid) . ' AND item_id IN (' . implode(',', $item_ids) . ")"))
		{
			foreach ($favorite_tags AS $key => $val)
			{
				$items_tags[$val['item_id']][] = $val;
			}
		}

		return $items_tags;
	}

	public function count_favorite_items($uid, $tag = null)
	{
		if ($tag)
		{
			$favorite_items = $this->query_all('SELECT DISTINCT item_id FROM ' . get_table('favorite_tag') . ' WHERE uid = ' . intval($uid) . " AND title = '" . $this->quote($tag) . "'");

			return sizeof($favorite_items);
		}
		else
		{
			return $this->count('favorite', 'uid = ' . intval($uid));
		}
	}

	public function get_item_list($tag, $uid, $limit)
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

			$favorite_items = $this->fetch_all('favorite_tag', "`title` IN ('" . implode("', '", $tag) . "') AND uid = " . intval($uid), 'item_id DESC', $limit);
		}
		else
		{
			$favorite_items = $this->fetch_all('favorite', "uid = " . intval($uid), 'item_id DESC', $limit);
		}

		return $this->process_list_data($favorite_items);
	}

	public function process_list_data($favorite_items)
	{
		if (!$favorite_items)
		{
			return false;
		}

		foreach ($favorite_items as $key => $data)
		{
			switch ($data['type'])
			{
				case 'answer':
					$answer_ids[] = $data['item_id'];
				break;

				case 'article':
					$article_ids[] = $data['item_id'];
				break;
			}
		}

		if ($answer_ids)
		{
			if ($answer_infos = $this->model('answer')->get_answers_by_ids($answer_ids))
			{
				foreach ($answer_infos AS $key => $data)
				{
					$question_ids[$val['question_id']] = $data['question_id'];
					
					$favorite_uids[$data['uid']] = $data['uid'];
				}

				$answer_attachs = $this->model('publish')->get_attachs('answer', $answer_ids, 'min');

				$question_infos = $this->model('question')->get_question_info_by_ids($question_ids);
			}
		}

		if ($article_ids)
		{
			if ($article_infos = $this->model('article')->get_article_info_by_ids($article_ids))
			{
				foreach ($article_infos AS $key => $data)
				{
					$favorite_uids[$data['uid']] = $data['uid'];
				}
			}
		}

		$users_info = $this->model('account')->get_user_info_by_uids($favorite_uids);

		foreach ($favorite_items as $key => $data)
		{
			switch ($data['type'])
			{
				case 'answer':
					$favorite_list_data[$key]['title'] = $question_infos[$answer_infos[$data['item_id']]['question_id']]['question_content'];
					$favorite_list_data[$key]['link'] = get_js_url('/question/' . $answer_infos[$data['item_id']]['question_id'] . '?rf=false&item_id=' . $data['item_id'] . '#!answer_' . $data['item_id']);
					$favorite_list_data[$key]['add_time'] = $question_infos[$answer_infos[$data['item_id']]['question_id']]['add_time'];

					$favorite_list_data[$key]['answer_info'] = $answer_infos[$data['item_id']];

					if ($favorite_list_data[$key]['answer_info']['has_attach'])
					{
						$favorite_list_data[$key]['answer_info']['attachs'] = $answer_attachs[$data['item_id']];
					}

					$favorite_list_data[$key]['question_info'] = $question_infos[$answer_infos[$data['item_id']]['question_id']];
					$favorite_list_data[$key]['user_info'] = $users_info[$answer_infos[$data['item_id']]['uid']];
				break;

				case 'article':
					$favorite_list_data[$key]['title'] = $article_infos[$data['item_id']]['title'];
					$favorite_list_data[$key]['link'] = get_js_url('/article/' . $data['item_id']);
					$favorite_list_data[$key]['add_time'] = $article_infos[$data['item_id']]['add_time'];

					$favorite_list_data[$key]['article_info'] = $article_infos[$data['item_id']];

					$favorite_list_data[$key]['last_action_str'] = ACTION_LOG::format_action_data(ACTION_LOG::ADD_ARTICLE, $data['uid'], $users_info[$data['uid']]['user_name']);
					
					$favorite_list_data[$key]['user_info'] = $users_info[$article_infos[$data['item_id']]['uid']];
				break;
			}

			$favorite_list_data[$key]['item_id'] = $data['item_id'];
			$favorite_list_data[$key]['item_type'] = $data['type'];
		}

		return $favorite_list_data;
	}
}