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
	public $api_version = '1.1';

	public function mp_server_query($node, $post_data = null, $account_id = 0)
	{
		if ($post_data)
		{
			foreach ($post_data AS $key => $val)
			{
				if ($val)
				{
					$_post_data[] = $key . '=' . rawurlencode($val);
				}
			}
		}

		if ($account_info = $this->model('weixin')->get_account_info_by_id($account_id))
		{
			if ($account_info['wecenter_access_token'])
			{
				$_post_data[] = 'wecenter_access_token=' . $account_info['wecenter_access_token'];
				$_post_data[] = 'wecenter_access_secret=' . $account_info['wecenter_access_secret'];
			}

			$_post_data[] = 'wecenter_account_role=' . $account_info['weixin_account_role'];
		}

		$_post_data[] = 'version=' . $this->api_version;

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, 'http://mp.wecenter.com/?/services/' . $node . '/');
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($curl, CURLOPT_TIMEOUT, 10);

		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $_post_data));

		$content = trim(curl_exec($curl));

		curl_close($curl);

		return json_decode($content, true);
	}
}
