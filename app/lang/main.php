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
	public function setup()
	{
		set_time_limit(0);
		
		HTTP::no_cache_header();
	}
	
	public function dump_action()
	{
		$lang = $this->model('system')->fetch_all('lang', null);
		
		$file_content = "<?php \r\n\r\n";
		
		foreach ($lang AS $key => $data)
		{
			$file_content .= '$language[\'' . addcslashes($data['string'], "'") . '\'] = \'' . addcslashes($data['string'], "'") . "';\r\n";
		}
		
		echo $file_content;
	}
	
	public function models_action()
	{
		$dir_handle = opendir(ROOT_PATH . 'models/openid/');
	    
	    while (($file = readdir($dir_handle)) !== false)
	    {
	    	if ($file != '.' AND $file != '..' AND !is_dir(ROOT_PATH . 'models/openid/' . $file))
	    	{
		    	if (strstr($file, '.php'))
		    	{
			    	$files_list[] = ROOT_PATH . 'models/openid/' . $file;
		    	}
	    	}
	    }
	    
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
				
				if (!$this->model('system')->fetch_row('lang', "string = '" . $this->model('system')->quote($string) . "'"))
				{
					$this->model('system')->insert('lang', array(
						'string' => $string
					));
				}
			}   
	    }
	}
	
	public function app_action()
	{
		$dir_handle = opendir(ROOT_PATH . 'app/');
	    
	    while (($file = readdir($dir_handle)) !== false)
	    {
	    	if ($file != '.' AND $file != '..' AND is_dir(ROOT_PATH . 'app/' . $file))
	    	{
		    	$app_dir_handle = opendir(ROOT_PATH . 'app/' . $file . '/');
		    	
		    	while (($_file = readdir($app_dir_handle)) !== false)
		    	{
		    		if ($_file != '.' AND $_file != '..' AND !is_dir(ROOT_PATH . 'app/' . $file . '/' . $_file))
		    		{
		    			if (strstr($_file, '.php'))
		    			{
			    			$files_list[] = ROOT_PATH . 'app/' . $file . '/' . $_file;
		    			}
		    		}
		    	}
	    	}
	    }
	    
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
				
				if (!$this->model('system')->fetch_row('lang', "string = '" . $this->model('system')->quote($string) . "'"))
				{
					$this->model('system')->insert('lang', array(
						'string' => $string
					));
				}
			}   
	    }
	}
	
	public function views_action()
	{
		$dir_handle = opendir(ROOT_PATH . 'views/default/');
	    
	    while (($file = readdir($dir_handle)) !== false)
	    {
	    	if ($file != '.' AND $file != '..' AND is_dir(ROOT_PATH . 'views/default/' . $file))
	    	{
		    	$app_dir_handle = opendir(ROOT_PATH . 'views/default/' . $file . '/');
		    	
		    	while (($_file = readdir($app_dir_handle)) !== false)
		    	{
		    		if ($_file != '.' AND $_file != '..' AND !is_dir(ROOT_PATH . 'views/default/' . $file . '/' . $_file))
		    		{
		    			if (strstr($_file, '.htm'))
		    			{
			    			$files_list[] = ROOT_PATH . 'views/default/' . $file . '/' . $_file;
		    			}
		    		}
		    		else if ($_file != '.' AND $_file != '..' AND is_dir(ROOT_PATH . 'views/default/' . $file . '/' . $_file))
		    		{
			    		$sub_dir_handle = opendir(ROOT_PATH . 'views/default/' . $file . '/' . $_file);
			    		
			    		while (($__file = readdir($sub_dir_handle)) !== false)
			    		{
				    		if ($__file != '.' AND $__file != '..' AND !is_dir(ROOT_PATH . 'views/default/' . $file . '/' . $_file . '/' . $__file))
				    		{
					    		if (strstr($__file, '.htm'))
					    		{
						    		$files_list[] = ROOT_PATH . 'views/default/' . $file . '/' . $_file . '/' . $__file;
						    	}	
				    		}
				    	}
		    		}
		    	}
	    	}
	    }
	    
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
				
				if (!$this->model('system')->fetch_row('lang', "string = '" . $this->model('system')->quote($string) . "'"))
				{
					$this->model('system')->insert('lang', array(
						'string' => $string
					));
				}
			}   
	    }
	}
}