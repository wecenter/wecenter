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
	function get_settings()
	{		
		if ($system_setting = $this->fetch_all('system_setting'))
		{
			foreach ($system_setting as $key => $val)
			{
				$settings[$val['varname']] = unserialize($val['value']);				
			}
			
			return $settings;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 检查过滤系统识别的参数
	 * @param unknown_type $input
	 */
	function check_vars($input)
	{
		if (!is_array($input))
		{
			return false;
		}
		
		$r_vars = array();
		
		foreach ($input as $key => $val)
		{
			if (in_array($key, array_keys(AWS_APP::$settings)))
			{
				$r_vars[$key] = $val;
			}
		}
		
		return $r_vars;
	}

	/**
	 * 保存设置参数
	 * @param unknown_type $vars
	 */
	function set_vars($vars)
	{
		if (!is_array($vars))
		{
			return false;
		}
		
		foreach ($vars as $key => $val)
		{
			$this->update('system_setting', array(
				'value' => serialize($val)
			), "`varname` = '" . $this->quote($key) . "'");
		}
		
		return true;
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
