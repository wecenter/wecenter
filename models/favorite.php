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
		if ($tag)
		{
			$where[] = "title = '" . $this->quote($tag) . "'";
		}
		
		if ($answer_id)
		{
			$where[] = "answer_id = " . intval($answer_id);
		}
		
		$where[] = 'uid = ' . intval($uid);
		
		return $this->delete('favorite_tag', implode(' AND ', $where));
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
		return $this->query_all('SELECT DISTINCT title FROM ' . $this->get_table('favorite_tag') . ' WHERE uid = ' . intval($uid) . ' ORDER BY id DESC', $limit);
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
			$favorite_items = $this->query_all('SELECT DISTINCT answer_id FROM ' . get_table('favorite_tag') . ' WHERE uid = ' . intval($uid) . " AND title = '" . $this->quote($tag) . "'");
			
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
			
			$favorite_items = $this->fetch_all('favorite_tag', "`title` IN ('" . implode("', '", $tag) . "') AND uid = " . intval($uid), 'answer_id DESC', $limit);
		}
		else
		{
			$favorite_items = $this->fetch_all('favorite', "uid = " . intval($uid), 'answer_id DESC', $limit);
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
			$answer_ids[] = $data['answer_id'];
			
			$favorite_uids[$data['uid']] = $data['uid'];
		}
		
		if ($answer_ids)
		{
			if ($answer_infos = $this->model('answer')->get_answers_by_ids($answer_ids))
			{
				foreach ($answer_infos AS $key => $val)
				{
					$question_ids[$val['question_id']] = $val['question_id'];
				}
				
				$answer_attachs = $this->model('publish')->get_attachs('answer', $answer_ids, 'min');
				
				$question_infos = $this->model('question')->get_question_info_by_ids($question_ids);
			}
		}
		
		$users_info = $this->model('account')->get_user_info_by_uids($favorite_uids);

		foreach ($favorite_items as $key => $data)
		{
			$favorite_list_data[$key]['title'] = $question_infos[$answer_infos[$data['answer_id']]['question_id']]['question_content'];
			$favorite_list_data[$key]['link'] = get_js_url('/question/' . $question_infos[$answer_infos[$data['answer_id']]['question_id']] . '?rf=false&item_id=' . $data['answer_id'] . '#!answer_' . $data['answer_id']);
			$favorite_list_data[$key]['add_time'] = $question_infos[$answer_infos[$data['answer_id']]['question_id']]['add_time'];
			
			$favorite_list_data[$key]['answer_info'] = $answer_infos[$data['answer_id']];
			
			if ($favorite_list_data[$key]['answer_info']['has_attach'])
			{
				$favorite_list_data[$key]['answer_info']['attachs'] = $answer_attachs[$data['answer_id']];
			}
			
			$favorite_list_data[$key]['question_info'] = $question_infos[$answer_infos[$data['answer_id']]['question_id']];
			
			$favorite_list_data[$key]['user_info'] = $users_info[$data['uid']];
		}
		
		return $favorite_list_data;
	}
}