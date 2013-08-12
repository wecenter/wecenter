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

	/**
	 * 添加工作经历
	 */
	function add_work_experience($uid, $start_year, $end_year, $company_name, $job_id)
	{
		$insert_arr['uid'] = intval($uid);
		$insert_arr['start_year'] = intval($start_year);
		$insert_arr['end_year'] = intval($end_year);
		$insert_arr['company_name'] = htmlspecialchars($company_name);
		$insert_arr['job_id'] = intval($job_id);
		$insert_arr['add_time'] = time();
		
		//插入获取用户ID
		return $this->insert('work_experience', $insert_arr);
	}

	/**
	  * 获取职位信息
	  */
	function get_jobs_list()
	{
		if ($rs = $this->fetch_all('jobs', null, 'id ASC'))
		{
			$job_list = array();
			
			foreach ($rs as $key => $val)
			{
				$job_list[$val['id']] = $val['job_name'];
			}
			
			return $job_list;
		}
		else
		{
			return false;
		}
	}

	/**
	  * 根据职位ID获取职位信息
	  */
	function get_jobs_by_id($id)
	{
		return $this->fetch_row('jobs', 'id = ' . intval($id));
	}

	/**
	  * 通过用户ID 获取 教育经历
	  * 
	  * @param $uid
	  * 
	  * @return array
	  */
	function get_work_experience_list($uid)
	{
		return $this->fetch_all('work_experience', "uid = " . intval($uid), ' start_year DESC');
	}

	/**
	  * 通过用户ID 获取 教育经历
	  * 
	  * @param $uid
	  * 
	  * @return array
	  */
	function get_education_experience_row($education_id, $uid)
	{
		return $this->fetch_row('work_experience', 'education_id = ' . intval($education_id) . ' AND uid = ' . intval($uid));
	}

	/**
	 * 更新学校经历
	 * 
	 * @param  $update_arr
	 * @param  $education_id
	 * @param  $uid
	 */
	function update_education_experience($update_arr, $education_id, $uid)
	{
		return $this->update('work_experience', $update_arr, 'education_id = ' . intval($education_id) . ' AND uid = ' . intval($uid));
	}

	/**
	  * 删除学校经历
	  * 
	  * @param  $education_id
	  * @param  $uid
	  */
	function del_work_experience($work_id, $uid)
	{
		return $this->delete('work_experience', 'uid = ' . intval($uid) . ' AND work_id = ' . intval($work_id));
	}

	/**
	  * 通过用户ID 获取 工作经历
	  * 
	  * @param $uid
	  * 
	  * @return array
	  */
	function get_work_experience_row($work_id, $uid)
	{
		return $this->fetch_row('work_experience', 'work_id = ' . intval($work_id) . ' AND uid =' . intval($uid));
	}

	/**
	 * 更新学校经历
	 * 
	 * @param  $update_arr
	 * @param  $education_id
	 * @param  $uid
	 */
	function update_work_experience($update_arr, $work_id, $uid)
	{
		$uid = intval($uid);
		$work_id = intval($work_id);
		
		if ((! $uid) || (! $work_id))
		{
			return false;
		}
		
		return $this->update('work_experience', $update_arr, "uid = {$uid} AND work_id = {$work_id}");
	}

	function remove_job($job_id)
	{
		return $this->delete('jobs', 'id = ' . intval($job_id));
	}

	function add_job($job_name)
	{
		return $this->insert('jobs', array(
			'job_name' => htmlspecialchars($job_name)
		));
	}
	
	function update_job($id, $data)
	{
		return $this->update('jobs', $data, 'id = ' . intval($id));
	}
}