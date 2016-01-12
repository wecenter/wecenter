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
	public function new_post_hash()
	{
		// 超过 100 个的时候开始清理
		if (sizeof(AWS_APP::session()->post_hash) >= 100)
		{
			AWS_APP::session()->post_hash = array_values(AWS_APP::session()->post_hash);

			unset(AWS_APP::session()->post_hash[0]);
		}

		$post_hash = substr(md5(rand(1, 88888888) . microtime()), 8, 16);

		if (rand(0, 1) == 1)
		{
			$post_hash = strtoupper($post_hash);
		}

		AWS_APP::session()->post_hash[] = $post_hash;

		return $post_hash;
	}

	public function remove_post_hash($hash)
	{
		foreach (AWS_APP::session()->post_hash AS $key => $val)
		{
			if ($val == $hash)
			{
				unset(AWS_APP::session()->post_hash[$key]);

				break;
			}
		}
	}

	public function valid_post_hash($hash)
	{
		if (in_array($hash, AWS_APP::session()->post_hash))
		{
			$this->remove_post_hash($hash);

			return TRUE;
		}

		return FALSE;
	}
}