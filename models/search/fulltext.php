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

class search_fulltext_class extends AWS_MODEL
{
	public $max_results = 1000;	// 搜索结果超过这个数值, 超过的部分将被抛弃

	public function get_search_hash($table, $column, $q, $where = null)
	{
		return md5($this->bulid_query($table, $column, $q, $where));
	}

	public function fetch_cache($search_hash)
	{
		if ($search_cache = $this->fetch_row('search_cache', "`hash` = '" . $this->quote($search_hash) . "'"))
		{
			return unserialize(gzuncompress(base64_decode($search_cache['data'])));
		}
	}

	public function save_cache($search_hash, $data)
	{
		if (!$data)
		{
			return false;
		}

		if ($this->fetch_cache($search_hash))
		{
			$this->remove_cache($search_hash);
		}

		return $this->insert('search_cache', array(
			'hash' => $search_hash,
			'data' => base64_encode(gzcompress(serialize($data))),
			'time' => time()
		));
	}

	public function remove_cache($search_hash)
	{
		return $this->delete('search_cache', "`hash` = '" . $this->quote($search_hash) . "'");
	}

	public function clean_cache()
	{
		return $this->delete('search_cache', 'time < ' . (time() - 900));
	}

	public function bulid_query($table, $column, $q, $where = null)
	{
		if (is_array($q))
		{
			$q = implode(' ', $q);
		}

		if ($analysis_keyword = $this->model('system')->analysis_keyword($q))
		{
			$keyword = implode(' ', $analysis_keyword);
		}
		else
		{
			$keyword = $q;
		}

		if ($where)
		{
			$where = ' AND (' . $where . ')';
		}

		return trim("SELECT *, MATCH(" . $column . "_fulltext) AGAINST('" . $this->quote($this->encode_search_code($keyword)) . "' IN BOOLEAN MODE) AS score FROM " . $this->get_table($table) . " WHERE MATCH(" . $column . "_fulltext) AGAINST('" . $this->quote($this->encode_search_code($keyword)) . "' IN BOOLEAN MODE) " . $where);
	}

	public function search_questions($q, $topic_ids = null, $page = 1, $limit = 20, $is_recommend = false)
	{
		if ($topic_ids)
		{
			$topic_ids = explode(',', $topic_ids);

			array_walk_recursive($topic_ids, 'intval_string');

			$where[] = '`question_id` IN (SELECT `item_id` FROM `' . $this->get_table('topic_relation') . '` WHERE `topic_id` IN(' . implode(',', $topic_ids) . ') AND `type` = "question")';
		}

		if ($is_recommend)
		{
			$where[] = '(`is_recommend` = "1" OR `chapter_id` IS NOT NULL)';
		}

		if ($where)
		{
			$where = implode(' AND ', $where);
		}

		$search_hash = $this->get_search_hash('question', 'question_content', $q, $where);

		if (!$result = $this->fetch_cache($search_hash))
		{
			if ($result = $this->query_all($this->bulid_query('question', 'question_content', $q, $where), $this->max_results))
			{
				$result = aasort($result, 'score', 'DESC');
			}
			else
			{
				return false;
			}

			$this->save_cache($search_hash, $result);
		}

		if (!$page)
		{
			$slice_offset = 0;
		}
		else
		{
			$slice_offset = (($page - 1) * $limit);
		}

		return array_slice($result, $slice_offset, $limit);
	}

	public function search_articles($q, $topic_ids = null, $page = 1, $limit = 20, $is_recommend = false)
	{
		if ($topic_ids)
		{
			$topic_ids = explode(',', $topic_ids);

			array_walk_recursive($topic_ids, 'intval_string');

			$where[] = '`id` IN (SELECT `item_id` FROM ' . $this->get_table('topic_relation') . ' WHERE topic_id IN(' . implode(',', $topic_ids) . ') AND `type` = "article")';
		}

		if ($is_recommend)
		{
			$where[] = '(`is_recommend` = "1" OR `chapter_id` IS NOT NULL)';
		}

		if ($where)
		{
			$where = implode(' AND ', $where);
		}

		$search_hash = $this->get_search_hash('article', 'title', $q, $where);

		if (!$result = $this->fetch_cache($search_hash))
		{
			if ($result = $this->query_all($this->bulid_query('article', 'title', $q, $where), $this->max_results))
			{
				$result = aasort($result, 'score', 'DESC');
			}
			else
			{
				return false;
			}

			$this->save_cache($search_hash, $result);
		}

		if (!$page)
		{
			$slice_offset = 0;
		}
		else
		{
			$slice_offset = (($page - 1) * $limit);
		}

		return array_slice($result, $slice_offset, $limit);
	}

	public function encode_search_code($string)
	{
		if (is_array($string))
		{
			$string = implode(' ', $string);
		}

		$string = convert_encoding($string, 'UTF-8', 'UTF-16');

		for ($i = 0; $i < strlen($string); $i++, $i++)
    	{
    		$code = ord($string{$i}) * 256 + ord($string{$i + 1});

    		if ($code == 32)
    		{
    			$output .= ' ';
    		}
    		else if ($code < 128)
    		{
    			$output .= chr($code);
    		}
    		else if ($code != 65279)
    		{
    			$output .= $code;
    		}
    	}

    	return htmlspecialchars($output);
	}

	public function push_index($type, $string, $item_id)
	{
		if (!$keywords = $this->model('system')->analysis_keyword($string))
		{
			return false;
		}

		$search_code = $this->encode_search_code($keywords);

		switch ($type)
		{
			case 'question':
				return $this->shutdown_update('question', array(
					'question_content_fulltext' => $search_code
				), 'question_id = ' . intval($item_id));
			break;

			case 'article':
				return $this->update('article', array(
					'title_fulltext' => $search_code
				), 'id = ' . intval($item_id));
			break;
		}
	}
}