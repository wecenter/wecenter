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
	var $post_hash_cache_key;
	var $post_hash_lib = array();

	public function __construct()
	{
		$this->post_hash_cache_key = 'post_hash_' . md5(session_id());

		if ($post_hash_lib = AWS_APP::cache()->get($this->post_hash_cache_key))
		{
			$this->post_hash_lib = $post_hash_lib;
		}
	}

	public function save_post_hash_lib()
	{
		AWS_APP::cache()->set($this->post_hash_cache_key, $this->post_hash_lib, 3600);
	}

	public function new_post_hash()
	{
		// 超过 50 个的时候开始清理
		if (sizeof($this->post_hash_lib) >= 50)
		{
			$this->post_hash_lib = array_values($this->post_hash_lib);

			unset($this->post_hash_lib[0]);
		}

		$post_hash = substr(md5(rand(1, 88888888) . microtime()), 8, 16);

		if (rand(0, 1) == 1)
		{
			$post_hash = strtoupper($post_hash);
		}

		$this->post_hash_lib[] = $post_hash;

		return $post_hash;
	}

	public function remove_post_hash($hash)
	{
		foreach ($this->post_hash_lib AS $key => $val)
		{
			if ($val == $hash)
			{
				unset($this->post_hash_lib[$key]);

				break;
			}
		}
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

	public function __destruct()
	{
		$this->save_post_hash_lib();
	}
}