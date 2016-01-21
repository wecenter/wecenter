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

class core_form
{
	private $csrf_key = '';

	public function __construct()
	{
		$this->csrf_key = md5(G_COOKIE_HASH_KEY . $_SERVER['HTTP_USER_AGENT'] . AWS_APP::user()->get_info('uid') . session_id());
	}

	public function new_post_hash()
	{
		return $this->csrf_key;
	}
	
	public function valid_post_hash($post_hash)
	{
		if ($post_hash == $this->csrf_key)
		{
			return TRUE;
		}

		return FALSE;
	}
}
