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


if (!defined('IN_ANWSION'))
{
	die;
}

class main extends AWS_CONTROLLER
{
	public function setup()
	{
		@set_time_limit(0);

		HTTP::no_cache_header();
	}

	public function dump_action()
	{
		$lang = $this->model('system')->fetch_all('lang', "type = '" . $this->model('system')->quote($_GET['type']) . "'");

		if ($_GET['type'] == 'js')
		{
			$file_content = "var aws_lang = new Array();\r\n\r\n";

			foreach ($lang AS $key => $data)
			{
				$file_content .= 'aws_lang[\'' . addcslashes($data['string'], "'") . '\'] = \'' . addcslashes($data['string'], "'") . "';\r\n";
			}
		}
		else
		{
			require_once(ROOT_PATH . 'language/en_US.php');

			$file_content = "<?php\r\n\r\n";

			foreach ($lang AS $key => $data)
			{
				if ($this->model('system')->count('lang', "type = '" . $this->model('system')->quote($_GET['type']) . "' AND `string` = '" . $this->model('system')->quote($data['string']) . "'"))
				{
					if (!$language[$data['string']])
					{
						$file_content .= '$language[\'' . addcslashes($data['string'], "'") . '\'] = \'' . addcslashes($data['string'], "'") . "';\r\n";
					}
					else
					{
						$file_content .= '$language[\'' . addcslashes($data['string'], "'") . '\'] = \'' . addcslashes($language[$data['string']], "'") . "';\r\n";
					}
				}
			}
		}

		echo $file_content;
	}

	public function models_action()
	{
		$files_list = fetch_file_lists(ROOT_PATH . 'models/', 'php');

	    foreach ($files_list AS $search_file)
	    {
		 	$data = file_get_contents($search_file);

			preg_match_all("#" . preg_quote('AWS_APP::lang()->_t(\'') . "(.*)" . preg_quote('\')') . "#isU", $data, $matchs);

			foreach ($matchs[1] AS $key => $val)
			{
				$string = $val;

				if (strstr($string, "', "))
				{
					$string = explode("', ", $string);
					$string = $string[0];
				}

				if (strstr($string, "'), "))
				{
					$string = explode("'), ", $string);
					$string = $string[0];
				}

				if (strstr($string, "') ."))
				{
					$string = explode("') .", $string);
					$string = $string[0];
				}

				if (strstr($string, "') "))
				{
					$string = explode("') ", $string);
					$string = $string[0];
				}

				if (!$this->model('system')->fetch_row('lang', "string = '" . $this->model('system')->quote($string) . "' AND type = 'app'"))
				{
					$this->model('system')->insert('lang', array(
						'string' => $string,
						'type' => 'app'
					));
				}
			}
	    }
	}

	public function app_action()
	{
		$files_list = fetch_file_lists(ROOT_PATH . 'app/', 'php');

		$files_list[] = AWS_PATH . 'class/cls_action_log_class.inc.php';
		$files_list[] = AWS_PATH . 'functions.inc.php';

	    foreach ($files_list AS $search_file)
	    {
		 	$data = file_get_contents($search_file);

			preg_match_all("#" . preg_quote('AWS_APP::lang()->_t(\'') . "(.*)" . preg_quote('\')') . "#isU", $data, $matchs);

			foreach ($matchs[1] AS $key => $val)
			{
				$string = $val;

				if (strstr($string, "', "))
				{
					$string = explode("', ", $string);
					$string = $string[0];
				}

				if (strstr($string, "'), "))
				{
					$string = explode("'), ", $string);
					$string = $string[0];
				}

				if (strstr($string, "') ."))
				{
					$string = explode("') .", $string);
					$string = $string[0];
				}

				if (strstr($string, "') "))
				{
					$string = explode("') ", $string);
					$string = $string[0];
				}

				if (!$this->model('system')->fetch_row('lang', "string = '" . $this->model('system')->quote($string) . "' AND type = 'app'"))
				{
					$this->model('system')->insert('lang', array(
						'string' => $string,
						'type' => 'app'
					));
				}
			}
	    }
	}

	public function views_action()
	{
		$files_list = fetch_file_lists(ROOT_PATH . 'views/default/', 'htm');

	    foreach ($files_list AS $search_file)
	    {
		 	$data = file_get_contents($search_file);

			preg_match_all("#" . preg_quote('_e(\'') . "(.*)" . preg_quote('\')') . "#isU", $data, $matchs);

			foreach ($matchs[1] AS $key => $val)
			{
				$string = $val;

				if (strstr($string, "', "))
				{
					$string = explode("', ", $string);
					$string = $string[0];
				}

				if (strstr($string, "'), "))
				{
					$string = explode("'), ", $string);
					$string = $string[0];
				}

				if (strstr($string, "') ."))
				{
					$string = explode("') .", $string);
					$string = $string[0];
				}

				if (strstr($string, "') "))
				{
					$string = explode("') ", $string);
					$string = $string[0];
				}

				if (!$this->model('system')->fetch_row('lang', "string = '" . $this->model('system')->quote($string) . "' AND type = 'app'"))
				{
					$this->model('system')->insert('lang', array(
						'string' => $string,
						'type' => 'app'
					));
				}
			}
	    }
	}

	public function js_views_action()
	{
		$files_list = fetch_file_lists(ROOT_PATH . 'views/default/', 'htm');

	    foreach ($files_list AS $search_file)
	    {
		 	$data = file_get_contents($search_file);

			preg_match_all("#" . preg_quote('_t(\'') . "(.*)" . preg_quote('\')') . "#isU", $data, $matchs);

			foreach ($matchs[1] AS $key => $val)
			{
				$string = $val;

				if (strstr($string, "', "))
				{
					$string = explode("', ", $string);
					$string = $string[0];
				}

				if (strstr($string, "'), "))
				{
					$string = explode("'), ", $string);
					$string = $string[0];
				}

				if (strstr($string, "') ."))
				{
					$string = explode("') .", $string);
					$string = $string[0];
				}

				if (strstr($string, "') "))
				{
					$string = explode("') ", $string);
					$string = $string[0];
				}

				if (!$this->model('system')->fetch_row('lang', "string = '" . $this->model('system')->quote($string) . "' AND `type` = 'js'"))
				{
					$this->model('system')->insert('lang', array(
						'string' => $string,
						'type' => 'js'
					));
				}
			}
	    }
	}

	public function js_action()
	{
		$files_list = fetch_file_lists(ROOT_PATH . 'static/js/', 'js');

	    foreach ($files_list AS $search_file)
	    {
		 	$data = file_get_contents($search_file);

			preg_match_all("#" . preg_quote('_t(\'') . "(.*)" . preg_quote('\')') . "#isU", $data, $matchs);

			foreach ($matchs[1] AS $key => $val)
			{
				$string = $val;

				if (strstr($string, "', "))
				{
					$string = explode("', ", $string);
					$string = $string[0];
				}

				if (strstr($string, "'), "))
				{
					$string = explode("'), ", $string);
					$string = $string[0];
				}

				if (strstr($string, "') ."))
				{
					$string = explode("') .", $string);
					$string = $string[0];
				}

				if (strstr($string, "') "))
				{
					$string = explode("') ", $string);
					$string = $string[0];
				}

				if (!$this->model('system')->fetch_row('lang', "string = '" . $this->model('system')->quote($string) . "' AND type = 'js'"))
				{
					$this->model('system')->insert('lang', array(
						'string' => $string,
						'type' => 'js'
					));
				}
			}
	    }
	}
}