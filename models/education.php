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

class education_class extends AWS_MODEL
{

	/**
	 * 添加学校信息
	 * 
	 * @param int $user_id 用户ID
	 * @param str $school_name_tmp 学校名称(仅显示用)
	 * @param int $years		年份
	 * @param str hostel	宿舍
	 * 
	 */
	
	function add_education_experience($uid, $school_name, $years, $departments = '')
	{
		$insert_arr['uid'] = intval($uid);
		$insert_arr['school_name'] = htmlspecialchars($school_name);
		$insert_arr['education_years'] = intval($years);
		$insert_arr['departments'] = htmlspecialchars($departments);
		$insert_arr['add_time'] = time();
		
		//插入获取用户ID
		return $this->insert('education_experience', $insert_arr);
	}

	/**
	  * 通过用户ID 获取 教育经历
	  * 
	  * @param $uid
	  * 
	  * @return array
	  */
	function get_education_experience_list($uid)
	{
		$uid = intval($uid);
		
		if (! $uid)
		{
			return false;
		}
		
		return $this->fetch_all('education_experience', 'uid = ' . intval($uid), 'education_years DESC');
	
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
		$uid = intval($uid);
		$education_id = intval($education_id);
		
		if (! $uid)
		{
			return false;
		}
		
		return $this->fetch_row('education_experience', "education_id = {$education_id} AND uid = {$uid}");
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
		$uid = intval($uid);
		$education_id = intval($education_id);
		
		if ((! $uid) || (! $education_id))
		{
			return false;
		}
		
		return $this->update('education_experience', $update_arr, "uid = {$uid} AND education_id = {$education_id}");
	
	}

	/**
	  * 删除学校经历
	  * 
	  * @param  $education_id
	  * @param  $uid
	  */
	function del_education_experience($education_id, $uid)
	{
		return $this->delete('education_experience', "uid = " . intval($uid) . " AND education_id = " . intval($education_id));
	}
}