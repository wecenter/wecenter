<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
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
		return $this->fetch_page('answer', 'add_time > ' . (time() - 86400 * intval(get_setting('reader_questions_last_days'))) . ' AND agree_count >= ' . intval(get_setting('reader_questions_agree_count')), 'add_time DESC', $page, $limit);
	}
}