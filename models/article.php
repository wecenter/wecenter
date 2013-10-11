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

class article_class extends AWS_MODEL
{
	public function get_article_info_by_id($article_id)
	{
		return $this->fetch_row('article', 'id = ' . intval($article_id));
	}
	
	public function get_article_info_by_ids($article_ids)
	{
		if (!is_array($article_ids) OR sizeof($article_ids) == 0)
		{
			return false;
		}
		
		array_walk_recursive($article_ids, 'intval_string');
		
	    if ($articles_list = $this->fetch_all('article', "id IN(" . implode(',', $article_ids) . ")"))
	    {
		    foreach ($articles_list AS $key => $val)
		    {
		    	$result[$val['id']] = $val;
		    }
	    }
	    
	    return $result;
	}
}