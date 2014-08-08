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

class education_class extends AWS_MODEL
{
	public function add_education_experience($uid, $school_name, $years, $departments = '')
	{
		return $this->insert('education_experience', array(
			'uid' => intval($uid),
			'school_name' => htmlspecialchars($school_name),
			'education_years' => intval($years),
			'departments' => htmlspecialchars($departments),
			'add_time' => time()
		));
	}

	public function get_education_experience_list($uid)
	{
		if (! $uid)
		{
			return false;
		}

		return $this->fetch_all('education_experience', 'uid = ' . intval($uid), 'education_years DESC');

	}

	public function update_education_experience($update_data, $education_id, $uid)
	{
		if (! $uid OR ! $education_id)
		{
			return false;
		}

		return $this->update('education_experience', $update_data, 'uid = ' . intval($uid) . ' AND education_id = ' . intval($education_id));

	}

	public function del_education_experience($education_id, $uid)
	{
		return $this->delete('education_experience', 'uid = ' . intval($uid) . ' AND education_id = ' . intval($education_id));
	}
}