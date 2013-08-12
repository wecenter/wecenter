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

class statistic_class extends AWS_MODEL
{	
	public function get_user_register_list_by_month($start_time = null, $end_time = null, $valid_email = false)
	{
		if (!$start_time)
		{
			$start_time = strtotime('-6 months');
		}
		
		if (!$end_time)
		{
			$end_time = strtotime('Today');
		}		
		
		$data = array();
		
		if ($result = $this->query_all("SELECT COUNT(uid) AS count, FROM_UNIXTIME(reg_time, '%y-%m') AS reg_month FROM " . get_table('users') . " WHERE reg_time BETWEEN " . $start_time . " AND " . $end_time . " GROUP BY reg_month ASC"))
		{
			foreach ($result AS $key => $val)
			{
				$data[] = array(
					'count' => $val['count'],
					'date' => $val['reg_month']
				);
			}
		}
		
		return $data;
	}
	
	public function get_new_question_by_month($start_time = null, $end_time = null)
	{		
		if (!$start_time)
		{
			$start_time = strtotime('-6 months');;
		}
		
		if (!$end_time)
		{
			$end_time = strtotime('Today');
		}		
		
		$data = array();
		
		if ($result = $this->query_all("SELECT COUNT(question_id) AS count, FROM_UNIXTIME(add_time, '%y-%m') AS add_date FROM " . get_table('question') . " WHERE add_time BETWEEN " . $start_time . " AND " . $end_time . " GROUP BY add_date ASC"))
		{
			foreach ($result AS $key => $val)
			{
				$data[] = array(
					'date' => $val['add_date'],
					'count' => $val['count']
				);
			}
		}
		
		return $data;
	}

	public function get_new_answer_by_month($start_time = null, $end_time = null)
	{		
		if (!$start_time)
		{
			$start_time = strtotime('-6 months');;
		}
		
		if (!$end_time)
		{
			$end_time = strtotime('Today');
		}		
		
		$data = array();
		
		if ($result = $this->query_all("SELECT COUNT(answer_id) AS count, FROM_UNIXTIME(add_time, '%y-%m') AS add_date FROM " . get_table('answer') . " WHERE add_time BETWEEN " . $start_time . " AND " . $end_time . " GROUP BY add_date ASC"))
		{
			foreach ($result AS $key => $val)
			{
				$data[] = array(
					'date' => $val['add_date'],
					'count' => $val['count']
				);
			}
		}
		
		return $data;
	}
	
	public function get_new_topic_by_month($start_time = null, $end_time = null)
	{		
		if (!$start_time)
		{
			$start_time = strtotime('-6 months');;
		}
		
		if (!$end_time)
		{
			$end_time = strtotime('Today');
		}		
		
		$data = array();
		
		if ($result = $this->query_all("SELECT COUNT(topic_id) AS count, FROM_UNIXTIME(add_time, '%y-%m') AS add_date FROM " . get_table('topic') . " WHERE add_time BETWEEN " . $start_time . " AND " . $end_time . " GROUP BY add_date ASC"))
		{
			foreach ($result AS $key => $val)
			{
				$data[] = array(
					'date' => $val['add_date'],
					'count' => $val['count']
				);
			}
		}
		
		return $data;
	}
}
