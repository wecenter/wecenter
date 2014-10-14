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

class posts_class extends AWS_MODEL
{
	public function set_posts_index($post_id, $post_type, $data = null)
	{
		if ($data)
		{
			$result = $data;
		}
		else
		{
			switch ($post_type)
			{
				case 'question':
					$result = $this->fetch_row('question', 'question_id = ' . intval($post_id));
				break;

				case 'article':
					$result = $this->fetch_row('article', 'id = ' . intval($post_id));
				break;
			}

			if (!$result)
			{
				return false;
			}
		}

		switch ($post_type)
		{
			case 'question':
				$data = array(
					'add_time' => $result['add_time'],
					'update_time' => $result['update_time'],
					'category_id' => $result['category_id'],
					'is_recommend' => $result['is_recommend'],
					'view_count' => $result['view_count'],
					'anonymous' => $result['anonymous'],
					'popular_value' => $result['popular_value'],
					'uid' => $result['published_uid'],
					'lock' => $result['lock'],
					'agree_count' => $result['agree_count'],
					'answer_count' => $result['answer_count']
				);
			break;

			case 'article':
				$data = array(
					'add_time' => $result['add_time'],
					'update_time' => $result['add_time'],
					'category_id' => $result['category_id'],
					'view_count' => $result['views'],
					'anonymous' => 0,
					'uid' => $result['uid'],
					'agree_count' => $result['votes'],
					'answer_count' => $result['comments'],
					'lock' => $result['lock'],
					'is_recommend' => $result['is_recommend'],
				);
			break;
		}

		if ($posts_index = $this->fetch_all('posts_index', "post_id = " . intval($post_id) . " AND post_type = '" . $this->quote($post_type) . "'"))
		{
			$post_index = end($posts_index);

			$this->update('posts_index', $data, 'id = ' . intval($post_index['id']));

			if (sizeof($posts_index) > 1)
			{
				$this->delete('posts_index', "post_id = " . intval($post_id) . " AND post_type = '" . $this->quote($post_type) . "' AND id != " . intval($post_index['id']));
			}
		}
		else
		{
			$data = array_merge($data, array(
				'post_id' => intval($post_id),
				'post_type' => $post_type
			));

			$this->remove_posts_index($post_id, $post_type);

			$this->insert('posts_index', $data);
		}
	}

	public function remove_posts_index($post_id, $post_type)
	{
		return $this->delete('posts_index', "post_id = " . intval($post_id) . " AND post_type = '" . $this->quote($post_type) . "'");
	}

	public function get_posts_list($post_type, $page = 1, $per_page = 10, $sort = null, $topic_ids = null, $category_id = null, $answer_count = null, $day = 30, $is_recommend = false)
	{
		$order_key = 'add_time DESC';

		switch ($sort)
		{
			case 'responsed':
				$answer_count = 1;

				break;

			case 'unresponsive':
				$answer_count = 0;

				break;

			case 'new' :
				$order_key = 'update_time DESC';

				break;
		}

		if (is_array($topic_ids))
		{
			foreach ($topic_ids AS $key => $val)
			{
				if (!$val)
				{
					unset($topic_ids[$key]);
				}
			}
		}

		if ($topic_ids)
		{
			$posts_index = $this->get_posts_list_by_topic_ids($post_type, $post_type, $topic_ids, $category_id, $answer_count, $order_key, $is_recommend, $page, $per_page);
		}
		else
		{
			$where = array();

			if (isset($answer_count))
			{
				$answer_count = intval($answer_count);

				if ($answer_count == 0)
				{
					$where[] = "answer_count = " . $answer_count;
				}
				else if ($answer_count > 0)
				{
					$where[] = "answer_count >= " . $answer_count;
				}
			}

			if ($is_recommend)
			{
				$where[] = 'is_recommend = 1';
			}

			if ($category_id)
			{
				$where[] = 'category_id IN(' . implode(',', $this->model('system')->get_category_with_child_ids('question', $category_id)) . ')';
			}

			if ($post_type)
			{
				$where[] = "post_type = '" . $this->quote($post_type) . "'";
			}

			$posts_index = $this->fetch_page('posts_index', implode(' AND ', $where), $order_key, $page, $per_page);

			$this->posts_list_total = $this->found_rows();
		}

		return $this->process_explore_list_data($posts_index);
	}

	public function get_hot_posts($post_type, $category_id = 0, $topic_ids = null, $day = 30, $page = 1, $per_page = 10)
	{
		if ($day)
		{
			$add_time = strtotime('-' . $day . ' Day');
		}

		$where[] = 'add_time > ' . intval($add_time);

		if ($post_type)
		{
			$where[] = "post_type = '" . $this->quote($post_type) . "'";
		}

		if ($category_id)
		{
			$where[] = 'category_id IN(' . implode(',', $this->model('system')->get_category_with_child_ids('question', $category_id)) . ')';
		}

		if (is_array($topic_ids))
		{
			foreach ($topic_ids AS $key => $val)
			{
				if (!$val)
				{
					unset($topic_ids[$key]);
				}
			}
		}

		if ($topic_ids)
		{
			array_walk_recursive($topic_ids, 'intval_string');

			if (!$post_type)
			{
				if ($question_post_ids = $this->model('topic')->get_item_ids_by_topics_ids($topic_ids, 'question') OR $article_post_ids = $this->model('topic')->get_item_ids_by_topics_ids($topic_ids, 'article'))
				{
					if ($question_post_ids)
					{
						$topic_where[] = 'post_id IN(' . implode(',', $question_post_ids) . ") AND post_type = 'question'";
					}

					if ($article_post_ids)
					{
						$topic_where[] = 'post_id IN(' . implode(',', $article_post_ids) . ") AND post_type = 'article'";
					}

					if ($topic_where)
					{
						$where[] = '(' . implode(' OR ', $topic_where) . ')';
					}
				}
				else
				{
					return false;
				}
			}
			else if ($post_ids = $this->model('topic')->get_item_ids_by_topics_ids($topic_ids, $post_type))
			{
				$where[] = 'post_id IN(' . implode(',', $post_ids) . ") AND post_type = '" . $post_type . "'";
			}
			else
			{
				return false;
			}
		}

		$posts_index = $this->fetch_page('posts_index', implode(' AND ', $where), 'popular_value DESC', $page, $per_page);

		$this->posts_list_total = $this->found_rows();

		return $this->process_explore_list_data($posts_index);
	}

	public function get_posts_list_total()
	{
		return $this->posts_list_total;
	}

	public function process_explore_list_data($posts_index)
	{
		if (!$posts_index)
		{
			return false;
		}

		foreach ($posts_index as $key => $data)
		{
			switch ($data['post_type'])
			{
				case 'question':
					$question_ids[] = $data['post_id'];
				break;

				case 'article':
					$article_ids[] = $data['post_id'];
				break;
			}

			$data_list_uids[$data['uid']] = $data['uid'];
		}

		if ($question_ids)
		{
			if ($last_answers = $this->model('answer')->get_last_answer_by_question_ids($question_ids))
			{
				foreach ($last_answers as $key => $val)
				{
					$data_list_uids[$val['uid']] = $val['uid'];
				}
			}

			$topic_infos['question'] = $this->model('topic')->get_topics_by_item_ids($question_ids, 'question');

			$question_infos = $this->model('question')->get_question_info_by_ids($question_ids);
		}

		if ($article_ids)
		{
			$topic_infos['article'] = $this->model('topic')->get_topics_by_item_ids($article_ids, 'article');

			$article_infos = $this->model('article')->get_article_info_by_ids($article_ids);
		}

		$users_info = $this->model('account')->get_user_info_by_uids($data_list_uids);

		foreach ($posts_index as $key => $data)
		{
			switch ($data['post_type'])
			{
				case 'question':
					$explore_list_data[$key] = $question_infos[$data['post_id']];

					$explore_list_data[$key]['answer_info'] = $last_answers[$data['post_id']];

					if ($explore_list_data[$key]['answer_info'])
					{
						$explore_list_data[$key]['answer_info']['user_info'] = $users_info[$last_answers[$data['post_id']]['uid']];
					}

					break;

				case 'article':
					$explore_list_data[$key] = $article_infos[$data['post_id']];

					break;
			}

			$explore_list_data[$key]['post_type'] = $data['post_type'];


			if (get_setting('category_enable') == 'Y')
			{
				$explore_list_data[$key]['category_info'] = $this->model('system')->get_category_info($data['category_id']);
			}

			$explore_list_data[$key]['topics'] = $topic_infos[$data['post_type']][$data['post_id']];

			$explore_list_data[$key]['user_info'] = $users_info[$data['uid']];
		}

		return $explore_list_data;
	}

	public function get_posts_list_by_topic_ids($post_type, $topic_type, $topic_ids, $category_id = null, $answer_count = null, $order_by = 'post_id DESC', $is_recommend = false, $page = 1, $per_page = 10)
	{
		if (!is_array($topic_ids))
		{
			return false;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		$result_cache_key = 'posts_list_by_topic_ids_' .  md5(implode(',', $topic_ids) . $answer_count . $category_id . $order_by . $is_recommend . $page . $per_page . $post_type . $topic_type);

		$found_rows_cache_key = 'posts_list_by_topic_ids_found_rows_' . md5(implode(',', $topic_ids) . $answer_count . $category_id . $is_recommend . $per_page . $post_type . $topic_type);

		$topic_relation_where[] = '`topic_id` IN(' . implode(',', $topic_ids) . ')';

		if ($topic_type)
		{
			$topic_relation_where[] = "`type` = '" . $this->quote($topic_type) . "'";
		}

		if ($topic_relation_query = $this->query_all("SELECT `item_id`, `type` FROM " . get_table('topic_relation') . " WHERE " . implode(' AND ', $topic_relation_where)))
		{
			foreach ($topic_relation_query AS $key => $val)
			{
				$post_ids[$val['type']][$val['item_id']] = $val['item_id'];
			}
		}

		if (!$post_ids)
		{
			return false;
		}

		foreach ($post_ids AS $key => $val)
		{
			$post_id_where[] = "(post_id IN (" . implode(',', $val) . ") AND post_type = '" . $this->quote($key) . "')";
		}

		if ($post_id_where)
		{
			$where[] = '(' . implode(' OR ', $post_id_where) . ')';
		}

		if (is_digits($answer_count))
		{
			if ($answer_count == 0)
			{
				$where[] = "answer_count = " . $answer_count;
			}
			else if ($answer_count > 0)
			{
				$where[] = "answer_count >= " . $answer_count;
			}
		}

		if ($is_recommend)
		{
			$where[] = 'is_recommend = 1';
		}

		if ($post_type)
		{
			$where[] = "post_type = '" . $this->quote($post_type) . "'";
		}

		if ($category_id)
		{
			$where[] = 'category_id IN(' . implode(',', $this->model('system')->get_category_with_child_ids('question', $category_id)) . ')';
		}

		if (!$result = AWS_APP::cache()->get($result_cache_key))
		{
			$result = $this->fetch_page('posts_index', implode(' AND ', $where), $order_by, $page, $per_page);

			AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_high'));
		}

		if (!$found_rows = AWS_APP::cache()->get($found_rows_cache_key))
		{
			$found_rows = $this->found_rows();

			AWS_APP::cache()->set($found_rows_cache_key, $found_rows, get_setting('cache_level_high'));
		}

		$this->posts_list_total = $found_rows;

		return $result;
	}

	public function get_recommend_posts_by_topic_ids($topic_ids)
	{
		if (!$topic_ids OR !is_array($topic_ids))
		{
			return false;
		}

		$related_topic_ids = array();

		foreach ($topic_ids AS $topic_id)
		{
			$related_topic_ids = array_merge($related_topic_ids, $this->model('topic')->get_related_topic_ids_by_id($topic_id));
		}

		if ($related_topic_ids)
		{
			$recommend_posts = $this->model('posts')->get_posts_list(null, 1, 10, null, $related_topic_ids, null, null, 30, true);
		}

		return $recommend_posts;
	}
}