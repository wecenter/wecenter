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

class statistic_class extends AWS_MODEL
{
	public function get_user_register_list_by_day($start_time = null, $end_time = null, $valid_email = false)
	{
		if (!$start_time)
		{
			$start_time = strtotime('Last week');
		}
		
		if (!$end_time)
		{
			$end_time = strtotime('Today');
		}		
		
		$data = array();
		
		if ($result = $this->query_all("SELECT COUNT(uid) AS num, FROM_UNIXTIME(reg_time, '%Y-%m-%d') AS reg_date FROM " . get_table('users') . " WHERE reg_time BETWEEN " . $start_time . " AND " . $end_time . " GROUP BY reg_date ASC"))
		{
			foreach ($result AS $key => $val)
			{
				$data[$val['reg_date']] = array(
					'date' => strtotime($val['reg_date']),
					'visits' => $val['num']
				);
			}
		}
		
		return $data;
	}
	
	public function get_user_register_list_by_month($start_time = null, $end_time = null, $valid_email = false)
	{
		if (!$start_time)
		{
			$start_time = strtotime('-12 months');
		}
		
		if (!$end_time)
		{
			$end_time = strtotime('Today');
		}		
		
		$data = array();
		
		if ($result = $this->query_all("SELECT COUNT(uid) AS num, FROM_UNIXTIME(reg_time, '%Y-%m') AS reg_month FROM " . get_table('users') . " WHERE reg_time BETWEEN " . $start_time . " AND " . $end_time . " GROUP BY reg_month ASC"))
		{
			foreach ($result AS $key => $val)
			{
				$data[] = array(
					'num' => $val['num'],
					'date' => $val['reg_month']
				);
			}
		}
		
		return $data;
	}
	
	public function get_new_question_by_day($start_time = null, $end_time = null)
	{		
		if (!$start_time)
		{
			$start_time = strtotime('Last week');
		}
		
		if (!$end_time)
		{
			$end_time = strtotime('Today');
		}		
		
		$data = array();
		
		if ($result = $this->query_all("SELECT COUNT(question_id) AS num, FROM_UNIXTIME(add_time, '%Y-%m-%d') AS add_date FROM " . get_table('question') . " WHERE add_time BETWEEN " . $start_time . " AND " . $end_time . " GROUP BY add_date ASC"))
		{
			foreach ($result AS $key => $val)
			{
				$data[$val['add_date']] = array(
					'date' => strtotime($val['add_date']),
					'visits' => $val['num']
				);
			}
		}
		
		return $data;
	}

	public function get_new_answer_by_day($start_time = null, $end_time = null)
	{		
		if (!$start_time)
		{
			$start_time = strtotime('Last week');
		}
		
		if (!$end_time)
		{
			$end_time = strtotime('Today');
		}		
		
		$data = array();
		
		if ($result = $this->query_all("SELECT COUNT(answer_id) AS num, FROM_UNIXTIME(add_time, '%Y-%m-%d') AS add_date FROM " . get_table('answer') . " WHERE add_time BETWEEN " . $start_time . " AND " . $end_time . " GROUP BY add_date ASC"))
		{
			foreach ($result AS $key => $val)
			{
				$data[$val['add_date']] = array(
					'date' => strtotime($val['add_date']),
					'visits' => $val['num']
				);
			}
		}
		
		return $data;
	}
	
	public function get_new_topic_by_day($start_time = null, $end_time = null)
	{		
		if (!$start_time)
		{
			$start_time = strtotime('Last week');
		}
		
		if (!$end_time)
		{
			$end_time = strtotime('Today');
		}		
		
		$data = array();
		
		if ($result = $this->query_all("SELECT COUNT(topic_id) AS num, FROM_UNIXTIME(add_time, '%Y-%m-%d') AS add_date FROM " . get_table('topic') . " WHERE add_time BETWEEN " . $start_time . " AND " . $end_time . " GROUP BY add_date ASC"))
		{
			foreach ($result AS $key => $val)
			{
				$data[$val['add_date']] = array(
					'date' => strtotime($val['add_date']),
					'visits' => $val['num']
				);
			}
		}
		
		return $data;
	}
}
