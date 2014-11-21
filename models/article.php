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

class article_class extends AWS_MODEL
{
	public function get_article_info_by_id($article_id)
	{
		if (!is_digits($article_id))
		{
			return false;
		}

		static $articles;

		if (!$articles[$article_id])
		{
			$articles[$article_id] = $this->fetch_row('article', 'id = ' . $article_id);
		}

		return $articles[$article_id];
	}

	public function get_article_info_by_ids($article_ids)
	{
		if (!is_array($article_ids) OR sizeof($article_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($article_ids, 'intval_string');

		if ($articles_list = $this->fetch_all('article', 'id IN(' . implode(',', $article_ids) . ')'))
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

			$comment['user_info'] = $comment_user_infos[$comment['uid']];
			$comment['at_user_info'] = $comment_user_infos[$comment['at_uid']];
		}

		return $comment;
	}

	public function get_comments_by_ids($comment_ids)
	{
		if (!is_array($comment_ids) OR !$comment_ids)
		{
			return false;
		}

		array_walk_recursive($comment_ids, 'intval_string');

		if ($comments = $this->fetch_all('article_comments', 'id IN (' . implode(',', $comment_ids) . ')'))
		{
			foreach ($comments AS $key => $val)
			{
				$article_comments[$val['id']] = $val;
			}
		}

		return $article_comments;
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

	public function remove_article($article_id)
	{
		if (!$article_info = $this->get_article_info_by_id($article_id))
		{
			return false;
		}

		$this->delete('article_comments', "article_id = " . intval($article_id)); // 删除关联的回复内容

		$this->delete('topic_relation', "`type` = 'article' AND item_id = " . intval($article_id));		// 删除话题关联

		ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action IN(' . ACTION_LOG::ADD_ARTICLE . ', ' . ACTION_LOG::ADD_AGREE_ARTICLE . ', ' . ACTION_LOG::ADD_COMMENT_ARTICLE . ') AND associate_id = ' . intval($article_id));	// 删除动作

		// 删除附件
		if ($attachs = $this->model('publish')->get_attach('article', $article_id))
		{
			foreach ($attachs as $key => $val)
			{
				$this->model('publish')->remove_attach($val['id'], $val['access_key']);
			}
		}

		$this->model('notify')->delete_notify('model_type = 8 AND source_id = ' . intval($article_id));	// 删除相关的通知

		$this->model('posts')->remove_posts_index($article_id, 'article');

		return $this->delete('article', 'id = ' . intval($article_id));
	}

	public function remove_comment($comment_id)
	{
		if ($comment_info = $this->get_comment_by_id($comment_id))
		{
			$this->delete('article_comments', 'id = ' . intval($comment_id));

			$this->update('article', array(
				'comments' => $this->count('article_comments', 'article_id = ' . $comment_info['article_id'])
			), 'id = ' . $comment_info['id']);

			return true;
		}
	}

	public function update_article($article_id, $title, $message, $topics, $category_id, $create_topic)
	{
		if (!$article_info = $this->model('article')->get_article_info_by_id($article_id))
		{
			return false;
		}

		$this->delete('topic_relation', 'item_id = ' . intval($article_id) . " AND `type` = 'article'");

		if (is_array($topics))
		{
			foreach ($topics as $key => $topic_title)
			{
				$topic_id = $this->model('topic')->save_topic($topic_title, $uid, $create_topic);

				$this->model('topic')->save_topic_relation($this->user_id, $topic_id, $article_id, 'article');
			}
		}

		$this->model('search_fulltext')->push_index('article', htmlspecialchars($title), $article_info['id']);

		$this->update('article', array(
			'title' => htmlspecialchars($title),
			'message' => htmlspecialchars($message),
			'category_id' => intval($category_id)
		), 'id = ' . intval($article_id));

		$this->model('posts')->set_posts_index($article_id, 'article');

		return true;
	}

	public function get_articles_list($category_id, $page, $per_page, $order_by, $day = null)
	{
		$where = array();

		if ($category_id)
		{
			$where[] = 'category_id = ' . intval($category_id);
		}

		if ($day)
		{
			$where[] = 'add_time > ' . (time() - $day * 24 * 60 * 60);
		}

		return $this->fetch_page('article', implode(' AND ', $where), $order_by, $page, $per_page);
	}

	public function get_articles_list_by_topic_ids($page, $per_page, $order_by, $topic_ids)
	{
		if (!$topic_ids)
		{
			return false;
		}

		if (!is_array($topic_ids))
		{
			$topic_ids = array(
				$topic_ids
			);
		}

		array_walk_recursive($topic_ids, 'intval_string');

		$result_cache_key = 'article_list_by_topic_ids_' . md5(implode('_', $topic_ids) . $order_by . $page . $per_page);

		$found_rows_cache_key = 'article_list_by_topic_ids_found_rows_' . md5(implode('_', $topic_ids) . $order_by . $page . $per_page);

		if (!$result = AWS_APP::cache()->get($result_cache_key) OR $found_rows = AWS_APP::cache()->get($found_rows_cache_key))
		{
			$topic_relation_where[] = '`topic_id` IN(' . implode(',', $topic_ids) . ')';
			$topic_relation_where[] = "`type` = 'article'";

			if ($topic_relation_query = $this->query_all("SELECT item_id FROM " . get_table('topic_relation') . " WHERE " . implode(' AND ', $topic_relation_where)))
			{
				foreach ($topic_relation_query AS $key => $val)
				{
					$article_ids[$val['item_id']] = $val['item_id'];
				}
			}

			if (!$article_ids)
			{
				return false;
			}

			$where[] = "id IN (" . implode(',', $article_ids) . ")";
		}


		if (!$result)
		{
			$result = $this->fetch_page('article', implode(' AND ', $where), $order_by, $page, $per_page);

			AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_high'));
		}


		if (!$found_rows)
		{
			$found_rows = $this->found_rows();

			AWS_APP::cache()->set($found_rows_cache_key, $found_rows, get_setting('cache_level_high'));
		}

		$this->article_list_total = $found_rows;

		return $result;
	}

	public function lock_article($article_id, $lock_status = true)
	{
		return $this->update('article', array(
			'lock' => intval($lock_status)
		), 'id = ' . intval($article_id));
	}

	public function article_vote($type, $item_id, $rating, $uid, $reputation_factor, $item_uid)
	{
		$this->delete('article_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . ' AND uid = ' . intval($uid));

		if ($rating)
		{
			if ($article_vote = $this->fetch_row('article_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . " AND rating = " . intval($rating) . ' AND uid = ' . intval($uid)))
			{
				$this->update('article_vote', array(
					'rating' => intval($rating),
					'time' => time(),
					'reputation_factor' => $reputation_factor
				), 'id = ' . intval($article_vote['id']));
			}
			else
			{
				$this->insert('article_vote', array(
					'type' => $type,
					'item_id' => intval($item_id),
					'rating' => intval($rating),
					'time' => time(),
					'uid' => intval($uid),
					'item_uid' => intval($item_uid),
					'reputation_factor' => $reputation_factor
				));
			}
		}

		switch ($type)
		{
			case 'article':
				$this->update('article', array(
					'votes' => $this->count('article_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . " AND rating = 1")
				), 'id = ' . intval($item_id));

				switch ($rating)
				{
					case 1:
						ACTION_LOG::save_action($uid, $item_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_AGREE_ARTICLE);
					break;

					case -1:
						ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ADD_AGREE_ARTICLE . ' AND uid = ' . intval($uid) . ' AND associate_id = ' . intval($item_id));
					break;
				}
			break;

			case 'comment':
				$this->update('article_comments', array(
					'votes' => $this->count('article_vote', "`type` = '" . $this->quote($type) . "' AND item_id = " . intval($item_id) . " AND rating = 1")
				), 'id = ' . intval($item_id));
			break;
		}

		$this->model('account')->sum_user_agree_count($item_uid);

		return true;
	}

	public function get_article_vote_by_id($type, $item_id, $rating = null, $uid = null)
	{
		if ($article_vote = $this->get_article_vote_by_ids($type, array(
			$item_id
		), $rating, $uid))
		{
			return end($article_vote[$item_id]);
		}
	}

	public function get_article_vote_by_ids($type, $item_ids, $rating = null, $uid = null)
	{
		if (!is_array($item_ids))
		{
			return false;
		}

		if (sizeof($item_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($item_ids, 'intval_string');

		$where[] = "`type` = '" . $this->quote($type) . "'";
		$where[] = 'item_id IN(' . implode(',', $item_ids) . ')';

		if ($rating)
		{
			$where[] = 'rating = ' . intval($rating);
		}

		if ($uid)
		{
			$where[] = 'uid = ' . intval($uid);
		}

		if ($article_votes = $this->fetch_all('article_vote', implode(' AND ', $where)))
		{
			foreach ($article_votes AS $key => $val)
			{
				$result[$val['item_id']][] = $val;
			}
		}

		return $result;
	}

	public function get_article_vote_users_by_id($type, $item_id, $rating = null, $limit = null)
	{
		$where[] = "`type` = '" . $this->quote($type) . "'";
		$where[] = 'item_id = ' . intval($item_id);

		if ($rating)
		{
			$where[] = 'rating = ' . intval($rating);
		}

		if ($article_votes = $this->fetch_all('article_vote', implode(' AND ', $where)))
		{
			foreach ($article_votes AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}

			return $this->model('account')->get_user_info_by_uids($uids);
		}
	}

	public function get_article_vote_users_by_ids($type, $item_ids, $rating = null, $limit = null)
	{
		if (! is_array($item_ids))
		{
			return false;
		}

		if (sizeof($item_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($item_ids, 'intval_string');

		$where[] = "`type` = '" . $this->quote($type) . "'";
		$where[] = 'item_id IN(' . implode(',', $item_ids) . ')';

		if ($rating)
		{
			$where[] = 'rating = ' . intval($rating);
		}

		if ($article_votes = $this->fetch_all('article_vote', implode(' AND ', $where)))
		{
			foreach ($article_votes AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}

			$users_info = $this->model('account')->get_user_info_by_uids($uids);

			foreach ($article_votes AS $key => $val)
			{
				$vote_users[$val['item_id']][$val['uid']] = $users_info[$val['uid']];
			}

			return $vote_users;
		}
	}

	public function update_views($article_id)
	{
		if (AWS_APP::cache()->get('update_views_article_' . md5(session_id()) . '_' . intval($article_id)))
		{
			return false;
		}

		AWS_APP::cache()->set('update_views_article_' . md5(session_id()) . '_' . intval($article_id), time(), 60);

		$this->shutdown_query("UPDATE " . $this->get_table('article') . " SET views = views + 1 WHERE id = " . intval($article_id));

		return true;
	}

	public function set_recommend($article_id)
	{
		$this->update('article', array(
			'is_recommend' => 1
		), 'id = ' . intval($article_id));

		$this->model('posts')->set_posts_index($article_id, 'article');
	}

	public function unset_recommend($article_id)
	{
		$this->update('article', array(
			'is_recommend' => 0
		), 'id = ' . intval($article_id));

		$this->model('posts')->set_posts_index($article_id, 'article');
	}
}