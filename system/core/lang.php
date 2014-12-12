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
		$search = '%s';

		if (is_array($replace))
		{
			$search = array();

			for ($i=0; $i<count($replace); $i++)
			{
				$search[] = '%s' . $i;
			};
		}

		if ($translate = $this->lang[trim($string)])
		{
			if (isset($replace))
			{
				$translate = str_replace($search, $replace, $translate);
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
				$string = str_replace($search, $replace, $string);
			}

			return $string;
		}
	}

	public function _t($string, $replace = null, $display = false)
	{
		return $this->translate($string, $replace, $display);
	}
}