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
		
		$result_cache_key = 'reader_list_by_topic_ids_' . implode('_', $topic_ids) . '_' . md5($page . $limit);
		
		if (!$result = AWS_APP::cache()->get($result_cache_key))
		{
			$result = $this->query_all('SELECT * FROM ' . $this->get_table('answer') . ' WHERE question_id IN(SELECT question.question_id FROM ' . $this->get_table('question') . ' AS question LEFT JOIN ' . $this->get_table('topic_question') . ' AS topic_question ON question.question_id = topic_question.question_id WHERE topic_question.topic_id IN(' . implode(',', $topic_ids) . ')) AND add_time > ' . (time() - 86400 * intval(get_setting('reader_questions_last_days'))) . ' AND agree_count >= ' . intval(get_setting('reader_questions_agree_count')) . ' ORDER BY add_time DESC', calc_page_limit($page, $limit));
			
			AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_low'));
		}
		
		return $result;
	}
}