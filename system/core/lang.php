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

class core_lang
{
	private $lang = array();
	
	public function __construct()
	{
		if (!defined('SYSTEM_LANG'))
		{
			return false;
		}
		
		if (SYSTEM_LANG == '')
		{
			return false;
		}
		
		$language_file = ROOT_PATH . 'language/' . SYSTEM_LANG . '.php';
		
		if (file_exists($language_file))
		{
			require $language_file;
		}
		
		if (is_array($language))
		{
			$this->lang = $language;
		}
	}
	
	public function translate($string, $replace = null, $display = false)
	{
		if ($translate = $this->lang[trim($string)])
		{
			if (isset($replace))
			{
				$translate = str_replace('%s', $replace, $translate);
			}
			
			if (!$display)
			{
				return $translate;
			}
			
			echo $translate;
		}
		else
		{
			if (isset($replace))
			{
				$string = str_replace('%s', $replace, $string);
			}
			
			return $string;
		}
	}
	
	public function _t($string, $replace = null, $display = false)
	{
		return $this->translate($string, $replace, $display);
	}
}