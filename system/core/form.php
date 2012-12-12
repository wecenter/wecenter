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

class core_form
{
	var $post_hash_session_name = 'post_hash';
	var $post_hash_lib = array();
	
	public function __construct()
	{
		$this->reload_post_hash_lib();
	}
	
	public function reload_post_hash_lib()
	{
		if ($_SESSION[$this->post_hash_session_name])
		{
			$this->post_hash_lib = $_SESSION[$this->post_hash_session_name];
		}
	}
	
	public function new_post_hash()
	{
		// 超过 50 个的时候开始清理
		if (sizeof($_SESSION[$this->post_hash_session_name]) >= 50)
		{
			$_SESSION[$this->post_hash_session_name] = array_values($_SESSION[$this->post_hash_session_name]);

			unset($_SESSION[$this->post_hash_session_name][0]);
		}
		
		$post_hash = substr(md5(rand(1, 88888888) . microtime()), 8, 16);
		
		if (rand(0, 1) == 1)
		{
			$post_hash = strtoupper($post_hash);
		}
		
		$_SESSION[$this->post_hash_session_name][] = $post_hash;
		
		$this->reload_post_hash_lib();
		
		return $post_hash;
	}
	
	public function remove_post_hash($hash)
	{
		foreach ($_SESSION[$this->post_hash_session_name] AS $key => $val)
		{
			if ($val == $hash)
			{
				unset($_SESSION[$this->post_hash_session_name][$key]);
				
				break;
			}
		}
		
		$this->reload_post_hash_lib();
	}
	
	public function valid_post_hash($hash)
	{
		if (in_array($hash, $this->post_hash_lib))
		{
			$this->remove_post_hash($hash);
				
			return TRUE;
		}
			
		return FALSE;
	}
}