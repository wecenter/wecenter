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

class reader_class extends AWS_MODEL
{
	public function fetch_answers_list($page, $limit)
	{
		return $this->fetch_all('answer', 'add_time > ' . (time() - 86400 * intval(get_setting('reader_questions_last_days'))) . ' AND agree_count >= ' . intval(get_setting('reader_questions_agree_count')), 'add_time DESC', calc_page_limit($page, $limit));
	}

	public function fetch_answers_list_by_topic_ids($topic_ids, $page, $limit)
	{
		if (!is_array($topic_ids))
		{
			return false;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		$result_cache_key = 'reader_list_by_topic_ids_' . md5(implode('_', $topic_ids) . $page . $limit);

		if (!$result = AWS_APP::cache()->get($result_cache_key))
		{
			$topic_relation_where[] = '`topic_id` IN(' . implode(',', $topic_ids) . ')';
			$topic_relation_where[] = "`type` = 'question'";

			if ($topic_relation_query = $this->query_all("SELECT item_id FROM " . get_table('topic_relation') . " WHERE " . implode(' AND ', $topic_relation_where)))
			{
				foreach ($topic_relation_query AS $key => $val)
				{
					$question_ids[$val['item_id']] = $val['item_id'];
				}
			}

			if (!$question_ids)
			{
				return false;
			}

			$result = $this->fetch_all('answer', 'question_id IN (' . implode(',', $question_ids) . ') AND add_time > ' . (time() - 86400 * intval(get_setting('reader_questions_last_days'))) . ' AND agree_count >= ' . intval(get_setting('reader_questions_agree_count')), 'add_time DESC', calc_page_limit($page, $limit));

			AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_low'));
		}

		return $result;
	}
}