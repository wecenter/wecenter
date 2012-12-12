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

class menu_class extends AWS_MODEL
{	
	function add_nav_menu($title, $description, $type = 'custom', $type_id = 0, $link = null)
	{
		$data = array(
			'title' => $title, 
			'description' => $description, 
			'type' => $type, 
			'type_id' => $type_id, 
			'link' => $link, 
			'icon' => '', 
			'sort' => 99, 
		);
		
		return $this->insert('nav_menu', $data);
	}
	
	function get_nav_menu_list($where = null, $with_feature = false, $with_category = false)
	{
		if ($rs = $this->fetch_all('nav_menu', $where, 'sort ASC'))
		{
			$category_ids = array();
			
			foreach($rs as $key => $val)
			{
				if($val['type'] == 'category')
				{
					$category_ids[] = $val['type_id'];
				}
				else if($val['type'] == 'feature')
				{
					$feature_ids[] = $val['type_id'];
				}
			}
			
			$category_info = $this->model('category')->get_category_by_id($category_ids);
			
			$feature_info = $this->model('feature')->get_feature_by_id($feature_ids);
			
			foreach($rs as $key => $val)
			{
				switch($val['type'])
				{
					case 'category' :
						
						$rs[$key]['link'] = 'home/explore/category-' . $category_info[$val['type_id']]['url_token'];
						$rs[$key]['child'] = $this->model('system')->fetch_category('question', $val['type_id']);
						break;
					case 'feature' :
						
						$rs[$key]['link'] = 'feature/' . $feature_info[$val['type_id']]['url_token'];
						break;
				}
				
				if ($with_feature && ($val['type'] == 'feature'))
				{
					$rs['feature_ids'][] = $val['type_id'];
				}
				
				if ($with_category && ($val['type'] == 'category'))
				{
					$rs['category_ids'][] = $val['type_id'];
				}
			}
			
			return $rs;
		}
		else
		{
			return array();
		}
	}
	
	function update_nav_menu($nav_menu_id, $data)
	{
		return $this->update('nav_menu', $data, 'id = ' . intval($nav_menu_id));
	}
	
	function remove_nav_menu($nav_menu_id)
	{
		return $this->delete('nav_menu', 'id = ' . intval($nav_menu_id));
	}
	
}