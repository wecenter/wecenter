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
		
		AWS_APP::cache()->cleanGroup('nav_menu');
		
		return $this->insert('nav_menu', $data);
	}
	
	function get_nav_menu_list($where = null, $with_feature = false, $with_category = false)
	{
		if (!$nav_menu = AWS_APP::cache()->get('nav_menu_list_' . md5($where . $with_feature . $with_category)))
		{
			$nav_menu = $this->fetch_all('nav_menu', $where, 'sort ASC');
			
			AWS_APP::cache()->set('nav_menu_list_' . md5($where . $with_feature . $with_category), $nav_menu, get_setting('cache_level_low'), 'nav_menu');
		}
		
		if ($nav_menu)
		{
			foreach($nav_menu as $key => $val)
			{
				if ($val['type'] == 'feature')
				{
					$feature_ids[] = $val['type_id'];
				}
			}
			
			$category_info = $this->model('system')->get_category_list('question');
			
			$feature_info = $this->model('feature')->get_feature_by_id($feature_ids);
			
			foreach($nav_menu as $key => $val)
			{
				switch($val['type'])
				{
					case 'category':
						if (defined('IN_MOBILE'))
						{
							$nav_menu[$key]['link'] = 'm/explore/category-' . $category_info[$val['type_id']]['id'];
						}
						else
						{
							$nav_menu[$key]['link'] = 'home/explore/category-' . $category_info[$val['type_id']]['url_token'];
							$nav_menu[$key]['child'] = $this->model('system')->fetch_category('question', $val['type_id']);
						}
					break;
					
					case 'feature':
						if (defined('IN_MOBILE'))
						{
							$nav_menu[$key]['link'] = 'm/explore/feature_id-' . $feature_info[$val['type_id']]['id'];
						}
						else
						{
							$nav_menu[$key]['link'] = 'feature/' . $feature_info[$val['type_id']]['url_token'];
						}
					break;
				}
				
				if ($with_feature AND ($val['type'] == 'feature'))
				{
					$nav_menu['feature_ids'][] = $val['type_id'];
				}
				
				if ($with_category AND ($val['type'] == 'category'))
				{
					$nav_menu['category_ids'][] = $val['type_id'];
				}
			}
		}
			
		return $nav_menu;
	}
	
	function update_nav_menu($nav_menu_id, $data)
	{
		$this->update('nav_menu', $data, 'id = ' . intval($nav_menu_id));
		
		AWS_APP::cache()->cleanGroup('nav_menu');
		
		return true;
	}
	
	function remove_nav_menu($nav_menu_id)
	{
		$this->delete('nav_menu', 'id = ' . intval($nav_menu_id));
		
		AWS_APP::cache()->cleanGroup('nav_menu');
		
		return true;
	}
}