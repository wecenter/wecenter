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
			'ip' => $_SERVER['REMOTE_ADDR']
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
