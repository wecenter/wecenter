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
			if (!$this->fetch_one('favorite_tag', 'id', "item_id = " . intval($item_id) . " AND `type` = '" . $this->quote($item_type) . "' AND `title` = '" . trim($this->quote(htmlspecialchars($tag))) . "' AND uid = " . intval($uid)))
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
		
		$where[] = "item_type = '" . $this->quote($item_type) . "'";
		$where[] = 'uid = ' . intval($uid);
		
		return $this->delete('favorite_tag', implode(' AND ', $where));
	}
	
	public function remove_favorite_item($item_id, $item_type, $uid)
	{
		if (!$item_id OR !$item_type OR !$uid)
		{
			return false;
		}
		
		$this->delete('favorite', "item_id = " . intval($item_id) . " AND item_type = '" . $this->quote($item_type) . "' AND uid = " . intval($uid));
		$this->delete('favorite_tag', "item_id = " . intval($item_id) . " AND item_type = '" . $this->quote($item_type) . "' AND uid = " . intval($uid));
	}
	
	public function get_favorite_tags($uid, $limit = null)
	{
		return $this->query_all('SELECT DISTINCT title FROM ' . $this->get_table('favorite_tag') . ' WHERE uid = ' . intval($uid) . ' ORDER BY id DESC', $limit);
	}
	
	public function get_favorite_items_tags_by_item_id($uid, $item_ids, $item_type)
	{
		if (!$item_ids)
		{
			return false;
		}
		
		array_walk_recursive($item_ids, 'intval_string');
		
		if ($favorite_tags = $this->fetch_all('favorite_tag', 'uid = ' . intval($uid) . ' AND item_id IN (' . implode(',', $item_ids) . ") AND `type` = '" . $this->quote($item_type) . "'"))
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
}