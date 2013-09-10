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

class work_class extends AWS_MODEL
{
	public function add_work_experience($uid, $start_year, $end_year, $company_name, $job_id)
	{
		$insert_data['uid'] = intval($uid);
		$insert_data['start_year'] = intval($start_year);
		$insert_data['end_year'] = intval($end_year);
		$insert_data['company_name'] = htmlspecialchars($company_name);
		$insert_data['job_id'] = intval($job_id);
		$insert_data['add_time'] = time();
		
		return $this->insert('work_experience', $insert_data);
	}
	
	public function get_jobs_list()
	{
		if ($jobs = $this->fetch_all('jobs', null, 'id ASC'))
		{
			foreach ($jobs as $key => $val)
			{
				$job_list[$val['id']] = $val['job_name'];
			}
		}
		
		return $job_list;
	}
	
	public function get_work_experience_list($uid)
	{
		return $this->fetch_all('work_experience', "uid = " . intval($uid), 'start_year DESC');
	}
	
	public function get_education_experience_row($education_id, $uid)
	{
		return $this->fetch_row('work_experience', 'education_id = ' . intval($education_id) . ' AND uid = ' . intval($uid));
	}
	
	public function update_education_experience($update_data, $education_id, $uid)
	{
		return $this->update('work_experience', $update_data, 'education_id = ' . intval($education_id) . ' AND uid = ' . intval($uid));
	}
	
	public function del_work_experience($work_id, $uid)
	{
		return $this->delete('work_experience', 'uid = ' . intval($uid) . ' AND work_id = ' . intval($work_id));
	}
	
	public function update_work_experience($update_data, $work_id, $uid)
	{
		if (! $uid OR ! $work_id)
		{
			return false;
		}
		
		return $this->update('work_experience', $update_data, 'uid = ' . intval($uid) . ' AND work_id = ' . intval($work_id));
	}

	public function remove_job($job_id)
	{
		return $this->delete('jobs', 'id = ' . intval($job_id));
	}

	public function add_job($job_name)
	{
		return $this->insert('jobs', array(
			'job_name' => htmlspecialchars($job_name)
		));
	}
	
	public function update_job($id, $data)
	{
		return $this->update('jobs', $data, 'id = ' . intval($id));
	}
}