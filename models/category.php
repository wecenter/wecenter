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

class category_class extends AWS_MODEL
{
	public function update_category($category_id, $update_arr)
	{
		return $this->update('category', $update_arr, 'id = ' . intval($category_id));
	}
	
	public function add_category($type, $title, $parent_id)
	{
		$data = array(
			'type' => $type,
			'title' => $title,
			'parent_id' => intval($parent_id),
		);
		
		return $this->insert('category', $data);
	}

	public function delete_category($type, $category_id)
	{
		$childs = $this->model('system')->fetch_category_data($type, $category_id);
		
		if ($childs)
		{
			foreach($childs as $key => $val)
			{
				$this->delete_category($type, $val['id']);
			}
		}
		
		$this->delete('reputation_category', 'category_id = ' . intval($category_id));
		
		$this->delete('nav_menu', "type = 'category' AND type_id = " . intval($category_id));
		
		return $this->delete('category', 'id = ' . intval($category_id));
	}

	public function question_exists($category_id)
	{
		$question_count = $this->model('question')->count('question', 'category_id = ' . intval($category_id));
		
		return $question_count;
	}
	
	public function check_url_token($url_token, $category_id)
	{
		return $this->count('category', "url_token = '" . $this->quote($url_token) . "' AND id != " . intval($category_id));
	}
}
