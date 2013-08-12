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

class admin_class
{
	public function fetch_menu_list($select_id)
	{
		$admin_menu = (array)AWS_APP::config()->get('admin_menu');
		
		if (empty($admin_menu))
		{
			return false;
		}
		
		foreach($admin_menu as $m_id => $menu)
		{
			if ($menu['children'])
			{
				foreach($menu['children'] as $c_id => $c_menu)
				{
					if ($select_id == $c_menu['id'])
					{
						$admin_menu[$m_id]['children'][$c_id]['select'] = true;
						$admin_menu[$m_id]['select'] = true;
					}
				}
			}
		}
		
		return $admin_menu;
	}
	
	public function set_admin_login($uid)
	{
		AWS_APP::session()->admin_login = H::encode_hash(array(
			'uid' => $uid,
			'UA' => $_SERVER['HTTP_USER_AGENT'],
			'ip' => fetch_ip()
		));
	}
	
	public function admin_logout()
	{
		if (isset(AWS_APP::session()->admin_login))
		{
			unset(AWS_APP::session()->admin_login);
		}
	}
}
