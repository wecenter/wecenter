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

class admin_group_class extends AWS_MODEL
{
    
	/**
	 * 获得组用户权限
	 * @param unknown_type $group_id
	 */
	function get_permission_by_group_id($group_id)
	{		
		if (!$group = $this->fetch_row('admin_group', 'group_id = ' . intval($group_id)))
		{
			return false;
		}
		
		$data = array();
		
		foreach(explode(",", $group['permission']) as $key => $val)
		{
			$pri_arr = explode("->", $val);

			$data[$pri_arr[0]][] = $pri_arr[1];
		}
		
		return $data;
	}
	
	
	/**
	 * 获得组可见栏目
	 * @param unknown_type $group_id
	 */
	function get_menu_list($group_id, $select_id = 0)
	{
		if (!$group = $this->fetch_row('admin_group', 'group_id = ' . intval($group_id)))
		{
			return false;
		}
		
		return $this->get_menu_by_ids($group['menu'], $group['no_menu'], $select_id);
	}
	
	/**
	 * 根据栏目id集获得栏目列表
	 * @param unknown_type $ids
	 */
	public function get_menu_by_ids($in_ids, $noin_ids = null, $select_id = 0)
	{
		$menu_array = (array)AWS_APP::config()->get('admin_menu');
		
		if (empty($menu_array))
		{
			return false;
		}
		
		if ($in_ids != 'all')
		{
			$in_ids_arr = explode(',', $in_ids);
		}
		
		if ($noin_ids)
		{
			$noin_ids_arr = explode(',', $noin_ids);
		}
		
		foreach($menu_array as $m_id => $menu)
		{
			if ($menu['status'] == '-1')
			{
				unset($menu_array[$m_id]);
				continue;
			}
		
			if ((is_array($in_ids) && (!in_array($menu['id'], $in_ids))) || (is_array($noin_ids) && (in_array($menu['id'], $noin_ids))))
			{
				unset($menu_array[$m_id]);
				continue;
			}
				
			if ($menu['children'])
			{
				foreach($menu['children'] as $c_id => $c_menu)
				{
					if ($c_menu['status'] == '-1')
					{
						unset($menu_array[$m_id]['children'][$c_id]);
						continue;
					}
					
					if ((is_array($in_ids) && (!in_array($c_menu['id'], $in_ids))) || (is_array($noin_ids) && (in_array($c_menu['id'], $noin_ids))))
					{
						unset($menu_array[$m_id]['children'][$c_id]);
						continue;
					}
					
					if ($select_id == $c_menu['id'])
					{
						$menu_array[$m_id]['children'][$c_id]['select'] = true;
						$menu_array[$m_id]['select'] = true;
					}
				}
			}
		}
		
		return $menu_array;
	}
}
