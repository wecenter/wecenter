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

class search_fulltext_class extends AWS_MODEL
{
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
		
		switch ($table)
		{
			default:
				$order_key = 'agree_count DESC';
			break;
			
			case 'article':
				$order_key = 'votes DESC';
			break;
		}
		
		return "SELECT *, MATCH(" . $column . "_fulltext) AGAINST('" . $this->quote($this->encode_search_code($keyword)) . "' IN BOOLEAN MODE) AS score FROM " . $this->get_table($table) . " WHERE MATCH(" . $column . "_fulltext) AGAINST('" . $this->quote($this->encode_search_code($keyword)) . "' IN BOOLEAN MODE) " . $where . " ORDER BY score DESC, " . $order_key;
	}
	
	public function search_questions($q, $topic_ids = null, $limit = 20)
	{
		if ($topic_ids)
		{
			$topic_ids = explode(',', $topic_ids);
			
			array_walk_recursive($topic_ids, 'intval_string');
			
			$where = "question_id IN(SELECT item_id FROM " . $this->get_table('topic_relation') . " WHERE topic_id IN(" . implode(',', $topic_ids) . ") AND `type` = 'question')";
		}
		
		return $this->query_all($this->bulid_query('question', 'question_content', $q, $where), $limit);
	}
	
	public function search_articles($q, $topic_ids = null, $limit = 20)
	{
		if ($topic_ids)
		{
			$topic_ids = explode(',', $topic_ids);
			
			array_walk_recursive($topic_ids, 'intval_string');
			
			$where = "id IN(SELECT item_id FROM " . $this->get_table('topic_relation') . " WHERE topic_id IN(" . implode(',', $topic_ids) . ") AND `type` = 'article')";
		}
		
		return $this->query_all($this->bulid_query('article', 'title', $q, $where), $limit);
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
    			//$output .= '&#' . $code . ';'; 
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
		
		if (sizeof($keywords) > 10)
		{
			$keywords = array_slice($keywords, 0, 10);
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