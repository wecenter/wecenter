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

class feature_class extends AWS_MODEL
{
	public function get_feature_list($where = null, $count = false, $order = null, $limit = null)
	{
		if ($count)
		{
			return $this->count('feature', $where);
		}
		else
		{
			if ($feature_list = $this->fetch_all('feature', $where, $order, $limit))
			{
				foreach($feature_list as $key => $val)
				{
					if (!$val['url_token'])
					{
						$feature_list[$key]['url_token'] = $val['id'];
					}
				}
				
				return $feature_list;
			}
			else
			{
				return array();
			}
		}
	}

	public function add_feature($title, $description = null)
	{
		return $this->insert('feature', array(
			'title' => $title, 
			'description' => $description
		));
	}

	public function update_feature($feature_id, $update_arr)
	{
		return $this->update('feature', $update_arr, 'id = ' . $feature_id);
	}
	
	public function get_feature_by_url_token($url_token)
	{
		return $this->fetch_row('feature', 'url_token = "' . $this->quote($url_token) . '"');
	}

	public function get_feature_by_title($title)
	{		
		if ($feature = $this->fetch_row('feature', 'title = "' . $this->quote($title) . '"'))
		{
			if (!$feature['url_token'])
			{
				$feature['url_token'] = $feature['id'];
			}
		}
		
		return $feature;
	}

	public function get_feature_by_id($feature_id)
	{
		if(!$feature_id)
		{
			return false;
		}
		
		$feature_ids = array();
		
		if (is_array($feature_id))
		{
			$feature_ids = $feature_id;
		}
		else
		{
			$feature_ids[] = intval($feature_id);
		}
		
		if ($rs = $this->fetch_all('feature', 'id IN (' . implode(',', $feature_ids) . ')'))
		{
			$data = array();
			
			foreach($rs as $key => $val)
			{
				if (!$val['url_token'])
				{
					$rs[$key]['url_token'] = $val['id'];
				}
				
				$data[$val['id']] = $rs[$key];
			}
		}
		
		if (is_array($feature_id))
		{
			return $data;
		}
		else
		{
			return $data[$feature_id];
		}
	}

	public function get_topics_by_feature_id($feature_id, $count = false, $detail = true)
	{
		if(!$rs = $this->query_all('SELECT * FROM ' . get_table('topic') . ' t LEFT JOIN ' . get_table('feature_topic') . ' ft ON t.topic_id = ft.topic_id WHERE ft.feature_id = ' . intval($feature_id) . ' ORDER BY t.discuss_count DESC', 10))
		{
			return false;
		}
		
		if ($count)
		{
			return count($rs);
		}
		
		if ($detail)
		{
			foreach ($rs as $key => $val)
			{
				if (!$val['url_token'])
				{
					$rs[$key]['url_token'] = urlencode($val['topic_title']);
				}
			}
			
			return $rs;
		}
		else
		{
			foreach ($rs as $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
			}
			
			return $topic_ids;
		}
	}

	public function add_topic($feature_id, $topic_id)
	{
		if (! $this->fetch_row('feature_topic', 'feature_id = ' . $feature_id . ' AND topic_id = ' . $topic_id))
		{
			return $this->insert('feature_topic', array(
				'feature_id' => $feature_id, 
				'topic_id' => $topic_id
			));
		}
		
		return false;
	}

	public function delete_topic($feature_id, $topic_id)
	{
		return $this->delete('feature_topic', 'feature_id = ' . intval($feature_id) . ' AND topic_id = ' . intval($topic_id));
	}

	public function delete_feature($feature_id)
	{
		$this->delete('feature_topic', 'feature_id = ' . intval($feature_id));
		
		return $this->delete('feature', 'id = ' . intval($feature_id));
	}

	public function update_topic_count($feature_id)
	{
		return $this->update_feature($feature_id, array(
			'topic_count' => intval($this->get_topics_by_feature_id($feature_id, true))
		));
	}

	public function get_best_question_list($feature_id, $page, $per_page)
	{
		$topic_ids = $this->get_topics_by_feature_id($feature_id, false, false);
		
		return $this->model('topic')->get_topic_action_list($topic_ids, $page, $per_page, TRUE);
	}
	
	function check_url_token($url_token, $feature_id)
	{
		return $this->count('feature', "url_token = '" . $this->quote($url_token) . "' AND id != " . intval($feature_id));
	}
}
