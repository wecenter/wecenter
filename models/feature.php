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

class feature_class extends AWS_MODEL
{
	public function get_feature_list($order = 'title ASC', $page = null, $limit = null)
	{
		if ($feature_list = $this->fetch_page('feature', null, $order, $page, $limit))
		{
			foreach($feature_list as $key => $val)
			{
				if (!$val['url_token'])
				{
					$feature_list[$key]['url_token'] = $val['id'];
				}
			}
		}

		return $feature_list;
	}

	public function get_enabled_feature_list($order = 'title ASC', $page = null, $limit = null)
	{
		if ($feature_list = $this->fetch_page('feature', 'enabled = 1', $order, $page, $limit))
		{
			foreach($feature_list as $key => $val)
			{
				if (!$val['url_token'])
				{
					$feature_list[$key]['url_token'] = $val['id'];
				}
			}
		}

		return $feature_list;
	}

	public function add_feature($title)
	{
		AWS_APP::cache()->cleanGroup('feature_list');

		return $this->insert('feature', array(
			'title' => $title
		));
	}

	public function update_feature($feature_id, $update_data)
	{
		AWS_APP::cache()->cleanGroup('feature_list');

		return $this->update('feature', $update_data, 'id = ' . intval($feature_id));
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
		if (!$feature_id)
		{
			return false;
		}

		if (is_array($feature_id))
		{
			$feature_ids = $feature_id;

			if (sizeof($feature_ids) == 0)
			{
				return false;
			}
		}
		else
		{
			$feature_ids[] = $feature_id;
		}

		array_walk_recursive($feature_ids, 'intval_string');

		if ($features = $this->fetch_all('feature', 'id IN (' . implode(',', $feature_ids) . ')'))
		{
			foreach($features as $key => $val)
			{
				if (!$val['url_token'])
				{
					$features[$key]['url_token'] = $val['id'];
				}

				$data[$val['id']] = $features[$key];
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

	public function get_topics_by_feature_id($feature_id)
	{
		if (!$topics = $this->query_all('SELECT topic_id FROM ' . get_table('feature_topic') . ' WHERE feature_id = ' . intval($feature_id)))
		{
			return false;
		}

		foreach ($topics as $key => $val)
		{
			$topic_ids[] = $val['topic_id'];
		}

		return $topic_ids;
	}

	public function add_topic($feature_id, $topic_id)
	{
		if (! $this->fetch_row('feature_topic', 'feature_id = ' . intval($feature_id) . ' AND topic_id = ' . intval($topic_id)))
		{
			$this->insert('feature_topic', array(
				'feature_id' => $feature_id,
				'topic_id' => $topic_id
			));

			$this->update_feature($feature_id, array(
				'topic_count' => $this->count('feature_topic', 'feature_id = ' . intval($feature_id))
			));
		}

		return true;
	}

	public function delete_topic($feature_id, $topic_id)
	{
		$this->delete('feature_topic', 'feature_id = ' . intval($feature_id) . ' AND topic_id = ' . intval($topic_id));

		$this->update_feature($feature_id, array(
			'topic_count' => $this->count('feature_topic', 'feature_id = ' . intval($feature_id))
		));

		return true;
	}

	public function empty_topics($feature_id)
	{
		$this->delete('feature_topic', 'feature_id = ' . intval($feature_id));

		$this->update_feature($feature_id, array(
			'topic_count' => 0
		));

		return true;
	}

	public function delete_feature($feature_id)
	{
		$this->delete('feature_topic', 'feature_id = ' . intval($feature_id));
		$this->delete('feature', 'id = ' . intval($feature_id));

		AWS_APP::cache()->cleanGroup('feature_list');

		return true;
	}

	public function check_url_token($url_token, $feature_id)
	{
		return $this->count('feature', "url_token = '" . $this->quote($url_token) . "' AND id != " . intval($feature_id));
	}

	public function get_topic_in_feature_ids($topic_id)
	{
		$feature_ids = array();

		if ($features = $this->fetch_all('feature_topic', 'topic_id = ' . intval($topic_id)))
		{
			foreach ($features AS $key => $val)
			{
				$feature_ids[] = $val['feature_id'];
			}
		}

		return $feature_ids;
	}

	public function update_feature_enabled($id, $status)
	{
		return $this->update('feature', array(
			'enabled' => intval($status)
		), 'id = ' . intval($id));
	}
}
