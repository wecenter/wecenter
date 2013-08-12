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

class upgrade_class extends AWS_MODEL
{
	var $db_engine = '';
	
	public function setup()
	{
		$this->db_engine = get_setting('db_engine');
		
		if (!$this->db_engine)
		{
			$this->db_engine = 'MyISAM';
		}
	}
	
	public function db_clean()
	{
		$users_columns = $this->query_all("SHOW COLUMNS FROM `" . get_table('users') . "`");
		
		foreach ($users_columns AS $key => $val)
		{
			if (in_array($val['Field'], array(
				'avatar_type',
				'url'
			)))
			{
				$this->query("ALTER TABLE `" . get_table('users') . "` DROP `" . $val['Field'] . "`;");
			}
		}
	}
	
	public function run_query($sql_query)
	{
		$sql_query = str_replace("\n", "\r", $sql_query);
		
		if ($db_table_querys = explode(";\r", str_replace(array('[#DB_PREFIX#]', '[#DB_ENGINE#]'), array(AWS_APP::config()->get('database')->prefix, $this->db_engine), $sql_query)))
		{
			foreach ($db_table_querys as $_sql)
			{
				if ($query_string = trim(str_replace(array(
					"\r", 
					"\n", 
					"\t"
				), '', $_sql)))
				{
					try {
						$this->db()->query($query_string);
					} catch (Exception $e) {
						return "<b>SQL:</b> <i>{$query_string}</i><br /><b>错误描述:</b> " . $e->getMessage();
					}
				}
			}
		}
	}
		
	public function check_last_answer()
	{
		return $this->fetch_row('question', 'last_answer > 0');
	}
	
	public function update_last_answer($page, $limit = 100)
	{
		if (!$all_questions = $this->query_all("SELECT question_id FROM " . get_table('question') . ' LIMIT ' . calc_page_limit($page, $limit)))
		{
			return false;
		}
		
		foreach ($all_questions AS $key => $val)
		{
			if ($last_answer = $this->fetch_row('answer', "question_id = " . $val['question_id'], 'add_time DESC'))
			{
				$this->update('question', array(
					'last_answer' => $last_answer['answer_id']
				), 'question_id = ' . $val['question_id']);
			}
		}
		
		return true;
	}
	
	public function update_popular_value_answer($page, $limit = 100)
	{
		if (!$all_questions = $this->query_all("SELECT question_id FROM " . get_table('question') . ' LIMIT ' . calc_page_limit($page, $limit)))
		{
			return false;
		}
		
		foreach ($all_questions AS $key => $val)
		{
			$this->model('question')->calc_popular_value($val['question_id']);
		}
		
		return true;
	}
	
	public function check_answer_attach_statistics()
	{
		return $this->fetch_row('answer', 'has_attach = 1');
	}
	
	public function update_answer_attach_statistics($page, $limit = 100)
	{
		if (!$answers = $this->query_all("SELECT answer_id FROM " . $this->get_table('answer'). ' LIMIT ' . calc_page_limit($page, $limit)))
		{
			return false;
		}
		
		foreach ($answers AS $key => $val)
		{
			if ($this->count('attach', "item_type = 'answer' AND item_id = " . $val['answer_id']))
			{
				$this->update('answer', array('has_attach' => 1), 'answer_id = ' . intval($val['answer_id']));
			}
			else
			{
				$this->update('answer', array('has_attach' => 0), 'answer_id = ' . intval($val['answer_id']));
			}
		}
		
		return true;
	}
}