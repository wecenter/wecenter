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
	
	public function get_comments($article_id, $page, $per_page)
	{
		return $this->fetch_page('article_comments', 'article_id = ' . intval($article_id), 'add_time ASC', $page, $per_page);
	}
	
	public function save_comment($article_id, $message, $uid, $at_uid = null)
	{
		if (!$article_info = $this->get_article_info_by_id($article_id))
		{
			return false;
		}
		
		$comment_id = $this->insert('article_comments', array(
			'uid' => intval($uid),
			'article_id' => intval($article_id),
			'message' => htmlspecialchars($message),
			'add_time' => time(),
			'at_uid' => intval($at_uid)
		));
		
		if ($at_uid AND $at_uid != $uid)
		{
			$this->model('notify')->send($uid, $at_uid, notify_class::TYPE_ARTICLE_COMMENT_AT_ME, notify_class::CATEGORY_QUESTION, $article_info['article_id'], array(
				'from_uid' => $uid, 
				'article_id' => $article_info['article_id'], 
				'item_id' => $comment_id
			));
		}
		
		set_human_valid('answer_valid_hour');
		
		// 记录日志
		ACTION_LOG::save_action($uid, $comment_id, ACTION_LOG::CATEGORY_ANSWER, ACTION_LOG::COMMENT_ARTICLE, htmlspecialchars($message), $article_info['article_id']);
			
		ACTION_LOG::save_action($uid, $article_info['article_id'], ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::COMMENT_ARTICLE, htmlspecialchars($message), $comment_id, 0);
				
		return $comment_id;
	}
}