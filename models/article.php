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
	
	public function get_comment_by_id($comment_id)
	{
		if ($comment = $this->fetch_row('article_comments', 'id = ' . intval($comment_id)))
		{
			$comment_user_infos = $this->model('account')->get_user_info_by_uids(array(
				$comment['uid'],
				$comment['at_uid']
			));
			
			$comment['user_info'] = $this->model('account')->get_user_info_by_uid($comment_user_infos[$comment['uid']]);
			
			$comment['at_user_info'] = $this->model('account')->get_user_info_by_uid($comment_user_infos[$comment['at_uid']]);
		}
		
		return $comment;
	}
	
	public function get_comments($article_id, $page, $per_page)
	{
		if ($comments = $this->fetch_page('article_comments', 'article_id = ' . intval($article_id), 'add_time ASC', $page, $per_page))
		{
			foreach ($comments AS $key => $val)
			{
				$comment_uids[$val['uid']] = $val['uid'];
				
				if ($val['at_uid'])
				{
					$comment_uids[$val['at_uid']] = $val['at_uid'];
				}
			}
			
			if ($comment_uids)
			{
				$comment_user_infos = $this->model('account')->get_user_info_by_uids($comment_uids);
			}
			
			foreach ($comments AS $key => $val)
			{
				$comments[$key]['user_info'] = $comment_user_infos[$val['uid']];
				$comments[$key]['at_user_info'] = $comment_user_infos[$val['at_uid']];
			}
		}
		
		return $comments;
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
		
		$this->update('article', array(
			'comments' => $this->count('article_comments', 'article_id = ' . intval($article_id))
		), 'id = ' . intval($article_id));
		
		if ($at_uid AND $at_uid != $uid)
		{
			$this->model('notify')->send($uid, $at_uid, notify_class::TYPE_ARTICLE_COMMENT_AT_ME, notify_class::CATEGORY_ARTICLE, $article_info['id'], array(
				'from_uid' => $uid, 
				'article_id' => $article_info['id'], 
				'item_id' => $comment_id
			));
		}
		
		set_human_valid('answer_valid_hour');
				
		$this->model('notify')->send($uid, $article_info['uid'], notify_class::TYPE_ARTICLE_NEW_COMMENT, notify_class::CATEGORY_ARTICLE, $article_info['id'], array(
			'from_uid' => $uid, 
			'article_id' => $article_info['id'], 
			'item_id' => $comment_id
		));
				
		return $comment_id;
	}
}