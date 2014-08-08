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

class core_plugins
{
	public $plugins = array();
	public $plugins_path;

	private $plugins_table = array();
	private $plugins_model = array();

	public function __construct()
	{
		$this->plugins_path = ROOT_PATH . 'plugins/';

		$this->load_plugins();
	}

	public function plugins_list()
	{
		$plugins_list = array();

		foreach ($this->plugins AS $key => $data)
		{
			$plugins_list[$key] = $data['title'] . ' - 版本: ' . $data['version'];
		}

		return $plugins_list;
	}

	public function installed($plugin_id)
	{
		foreach ($this->plugins AS $key => $data)
		{
			if ($key == $plugin_id)
			{
				return true;
			}
		}
	}

	public function load_plugins()
	{
		$plugins_cache = TEMP_PATH . 'plugins.php';
		$plugins_table_cache = TEMP_PATH . 'plugins_table.php';
		$plugins_model_cache = TEMP_PATH . 'plugins_model.php';

		if (file_exists($plugins_cache) AND file_exists($plugins_table_cache) AND file_exists($plugins_model_cache))
		{
			$this->plugins = unserialize(file_get_contents($plugins_cache));
			$this->plugins_table = unserialize(file_get_contents($plugins_table_cache));
			$this->plugins_model = unserialize(file_get_contents($plugins_model_cache));

			return false;
		}

		$dir_handle = opendir($this->plugins_path);

	    while (($file = readdir($dir_handle)) !== false)
	    {
	       	if ($file != '.' AND $file != '..' AND is_dir($this->plugins_path . $file))
	       	{
	       		$config_file = $this->plugins_path . $file . '/config.php';

	            if (file_exists($config_file))
	            {
	            	$aws_plugin = false;

		            require_once($config_file);

		            if (is_array($aws_plugin) AND G_VERSION_BUILD >= $aws_plugin['requirements'])
		            {
		            	if ($aws_plugin['contents']['model'])
		            	{
			            	$this->plugins_model[$aws_plugin['contents']['model']['class_name']] = $this->plugins_path . $file . '/' . $aws_plugin['contents']['model']['include'];
		            	}

		            	if ($aws_plugin['contents']['setups'])
		            	{
			            	foreach ($aws_plugin['contents']['setups'] AS $key => $data)
			            	{
			            		if ($data['app'] AND $data['controller'] AND $data['include'])
			            		{
				            		$this->plugins_table[$data['app']][$data['controller']]['setup'][] = array(
				            			'file' => $this->plugins_path . $file . '/' . $data['include'],
				            		);
			            		}
			            	}
		            	}

		            	if ($aws_plugin['contents']['actions'])
		            	{
			            	foreach ($aws_plugin['contents']['actions'] AS $key => $data)
			            	{
			            		if ($data['app'] AND $data['controller'] AND $data['include'])
			            		{
				            		$this->plugins_table[$data['app']][$data['controller']][$data['action']][] = array(
				            			'file' => $this->plugins_path . $file . '/' . $data['include'],
				            			'template' => $data['template']
				            		);
			            		}
			            	}
		            	}

			            $this->plugins[$file] = $aws_plugin;
		            }
	            }
	        }
	    }

	   	closedir($dir_handle);

	   	@file_put_contents($plugins_cache, serialize($this->plugins));
	   	@file_put_contents($plugins_table_cache, serialize($this->plugins_table));
	   	@file_put_contents($plugins_model_cache, serialize($this->plugins_model));

	   	return true;
	}

	public function model()
	{
		return $this->plugins_model;
	}

	public function parse($app, $controller, $action, $template = NULL)
	{
		if (!$controller)
		{
			$controller = 'main';
		}

		if (!$action)
		{
			$controller = 'index';
		}

		if ($this->plugins_table[$app][$controller][$action])
		{
			foreach ($this->plugins_table[$app][$controller][$action] AS $key => $plugins_files)
			{
				if ($plugins_files['template'] AND $template)
				{
					if ($template == $plugins_files['template'])
					{
						$files_list[] = $plugins_files['file'];
					}
				}
				else
				{
					$files_list[] = $plugins_files['file'];
				}
			}

			return $files_list;
		}
	}
}