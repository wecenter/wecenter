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

class main extends AWS_CONTROLLER
{
	public function download_action()
	{
		$url = @base64_decode($_GET['url']);
		
		if (! $url)
		{
			H::redirect_msg(AWS_APP::lang()->_t('文件未找到'));
		}
						
		$path = get_setting('upload_dir') . '/' . str_replace(get_setting('upload_url'), '', $url);
		
		if (strstr($path, '..') OR !file_exists($path))
		{
			H::redirect_msg(AWS_APP::lang()->_t('文件未找到'));
		}
		
		HTTP::force_download_header(base64_decode($_GET['file_name']), filesize($path));
		
		readfile($path);
	}
}