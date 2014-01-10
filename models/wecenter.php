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

class wecenter_class extends AWS_MODEL
{
	public function mp_server_query($node, $post_data = null)
	{
		if ($post_data)
		{
			foreach ($post_data AS $key => $val)
			{
				$_post_data[] = $key . '=' . rawurlencode($val);
			}
		}
		
		if (get_setting('wecenter_access_token'))
		{
			$_post_data[] = 'wecenter_access_token=' . get_setting('wecenter_access_token');
		}
		
		$_post_data[] = 'version=1';
		
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, 'http://mp.wecenter.com/?/services/' . $node . '/');
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $_post_data));
		
		$content = trim(curl_exec($curl));
		
		curl_close($curl);
		
		return json_decode($content, true);
	}
}
