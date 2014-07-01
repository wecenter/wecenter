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
			if (!$this->count('favorite_tag', "answer_id = " . intval($answer_id) . " AND `title` = '" . $this->quote(htmlspecialchars(trim($tag))) . "' AND uid = " . intval($uid)))
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
}