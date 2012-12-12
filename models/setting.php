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

class setting_class extends AWS_MODEL
{
	function get_setting($where = null)
	{
		if ($system_setting = $this->fetch_all('system_setting', $where))
		{
			foreach ($system_setting as $key => $val)
			{
				$setting[$val['varname']] = unserialize($val['value']);				
			}
			
			return $setting;
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
		if (empty($input))
		{
			return false;
		}
		
		$r_vars = array();
		
		foreach ($input as $key => $val)
		{
			if (in_array($key, array_keys(AWS_APP::setting())))
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
		if (empty($vars))
		{
			return false;
		}
		
		foreach ($vars as $key => $val)
		{
			$this->update('system_setting', array(
				'value' => serialize($val)
			), "varname = '" . $this->quote($key) . "'");
		}
		
		return true;
	}

	public function get_ui_styles()
	{
		$exclude_dir = array(
			'.', 
			'..', 
			'.svn'
		);
		
		$dirs = array();
		
		if ($handle = opendir(ROOT_PATH . 'views'))
		{
			while (false !== ($file = readdir($handle)))
			{
				if (! in_array($file, $exclude_dir) && is_dir('./views/' . $file))
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
