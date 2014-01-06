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

class setting_class extends AWS_MODEL
{
	public function get_settings()
	{		
		if ($system_setting = $this->fetch_all('system_setting'))
		{
			foreach ($system_setting as $key => $val)
			{
				$settings[$val['varname']] = unserialize($val['value']);				
			}
		}
		
		return $settings;
	}

	public function get_ui_styles()
	{
		if ($handle = opendir(ROOT_PATH . 'views'))
		{
			while (false !== ($file = readdir($handle)))
			{
				if (substr($file, 0, 1) != '.' AND is_dir(ROOT_PATH . 'views/' . $file))
				{
					$dirs[] = $file;
				}
			}
			
			closedir($handle);
		}
		
		$ui_style = array();
		
		foreach ($dirs as $key => $val)
		{
			$ui_style[] = array(
				'id' => $val, 
				'title' => $val
			);
		}
		
		return $ui_style;
	}
}
