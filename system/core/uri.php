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

class core_uri
{
	var $params = array(
		'sep_value' => '-',	// 赋值分隔符
		'sep_var' => '__',	// 变量分割符
		'sep_act' => '/'	// 动作分割符
	);

	// 默认控制器
	var $default_vars = array(
		'app_dir' => 'explore',
		'controller' => 'main',
		'action' => 'index'
	);

	var $app_dir = '';
	var $controller = '';
	var $action = '';

	var $request_main = '';
	var $index_script = '';

	public function __construct()
	{
		if (!defined('G_INDEX_SCRIPT'))
		{
			return false;
		}

		if (G_INDEX_SCRIPT == '')
		{
			$this->index_script = '?/';
		}
		else
		{
			$this->index_script = G_INDEX_SCRIPT;
		}

		if ($_SERVER['REQUEST_URI'])
		{
			if (isset($_SERVER['HTTP_X_REWRITE_URL']))
			{
				$request_main = $_SERVER['HTTP_X_REWRITE_URL'];
			}
			else
			{
				$request_main = $_SERVER['REQUEST_URI'];
			}

			$requests = explode($this->index_script, $request_main);

			if (count($requests) == 1 AND dirname($_SERVER['SCRIPT_NAME']) != '/')
			{
				$request_main = preg_replace('/^' . preg_quote(dirname($_SERVER['SCRIPT_NAME']), '/') . '/i', '', $request_main);
			}
			else if (count($requests) == 2)
			{
				if ($requests[0] != '/')
				{
					$request_main = str_replace($requests[0], '', $request_main);
				}

				$request_main = str_replace($this->index_script, '', $request_main);
			}
		}
		else if ($_SERVER['QUERY_STRING'])
		{
			$request_main = $_SERVER['QUERY_STRING'];
		}

		$request_main = ltrim($request_main, "/\\");

		$base_script = basename($_SERVER['SCRIPT_NAME']);

		if (!strstr($request_main, '=') AND !strstr($request_main, '/') AND !strstr($request_main, '-') AND !strstr($request_main, '.'))
		{
			$request_main .= '/';
		}

		if (strstr($base_script, '.php'))
		{
			$request_main = str_replace($base_script . '/', '', $request_main);
		}

		if (count($requests) == 1)
		{
			$request_main = $this->parse_uri($request_main);
		}

		$this->request_main = $request_main;
	}

	public function parse_uri($request_main)
	{
		if (get_setting('url_rewrite_enable') == 'Y' AND $request_routes = get_request_route(false))
		{
			if (!$request_main)
			{
				$request_main = '/';
			}

			foreach($request_routes as $key => $val)
			{
				if (preg_match('/^' . $val[0] . '/', $request_main))
				{
					$request_main = preg_replace('/^' . $val[0] . '/', $val[1], $request_main);

					return $request_main;
				}
			}
		}

		return $request_main;
	}

	public function set_rewrite()
	{
		if (!defined('G_INDEX_SCRIPT'))
		{
			return false;
		}

		$request_main = $this->request_main;

		if (!$request_main OR $this->index_script == $request_main)
		{
			$this->controller = 'main';
			$this->action = 'index';

			return $this;
		}

  		$request = explode('?', $request_main, 2);

  		if (count($request) == 1)
  		{
  			$request = explode('&', $request_main, 2);
  		}

		$uri = array(
			'first' => array_shift($request),
			'last' => ltrim(implode($request), '?')
		);

		if ($uri['last'])
		{
			parse_str($uri['last'], $query_string);

			foreach ($query_string AS $key => $val)
			{
				if (!$_GET[$key])
				{
					if (! strstr($val, '%'))
					{
						$_GET[$key] = $val;
					}
					else
					{
						$_GET[$key] = urldecode($val);
					}
				}
			}
		}

		$request = explode($this->params['sep_act'], $uri['first']);

		$uri['first'] = array(
			'pattern' => '',
			'args' => $request
		);

		$__app_dir = $this->default_vars['app_dir'];	// 应用目录
		$this->controller = $this->default_vars['controller'];	// 控制器
		$this->action = $this->default_vars['action'];	// 动作

		$args_var_str = '';

		// 删除空值
		foreach ($uri['first']['args'] AS $key => $val)
		{
			if (strstr($val, $this->params['sep_value']) AND !$start_key)
			{
				$start_key = $key;
			}
			else if ($start_key)
			{
				$uri['first']['args'][$start_key] .= $this->params['sep_act'] . $val;

				unset($uri['first']['args'][$key]);
			}
		}

		$args_count = count($uri['first']['args']);
		
		switch ($args_count)
		{
			default:
				return $this;
			break;

			case 1:
				$args_var_str = $uri['first']['args'][0];
			break;

			case 2:
				$__app_dir = $uri['first']['args'][0] ? $uri['first']['args'][0] : $this->default_vars['app_dir'];	// 应用目录
				$args_var_str = $uri['first']['args'][1];
			break;

			case 3:
				$args_var_str = $uri['first']['args'][2];
				
				$__app_dir = $uri['first']['args'][0] ? $uri['first']['args'][0] : $this->default_vars['app_dir'];	// 应用目录

				if (file_exists(ROOT_PATH . 'app/' . $__app_dir . '/' . $uri['first']['args'][1] . '.php'))
				{
					$this->controller = $uri['first']['args'][1];	// 控制器
				}
				else
				{
					$this->controller = $this->default_vars['controller'];	// 控制器
					$this->action = $uri['first']['args'][1];	// 动作
				}
			break;

			case 4:
				$args_var_str = $uri['first']['args'][3];
				
				$__app_dir = $uri['first']['args'][0] ? $uri['first']['args'][0] : $this->default_vars['app_dir'];	// 应用目录
				$this->controller = $uri['first']['args'][1] ? $uri['first']['args'][1] : $this->default_vars['controller'];	// 控制器
				$this->action = $uri['first']['args'][2] ? $uri['first']['args'][2] : $this->default_vars['action'];	// 动作
			break;
			
			case 5:
				$args_var_str = $uri['first']['args'][4];
						
				$__app_dir = $uri['first']['args'][0] ? $uri['first']['args'][0] : $this->default_vars['app_dir'];	// 应用目录
				$this->controller = $uri['first']['args'][2] ? $uri['first']['args'][1] . '/' . $uri['first']['args'][2] : $this->default_vars['controller'];	// 控制器
				$this->action = $uri['first']['args'][3] ? $uri['first']['args'][3] : $this->default_vars['action'];	// 动作
			break;
		}
		
		$this->app_dir = ROOT_PATH . 'app/' . $__app_dir . '/';

		$_GET['c'] = $this->controller;
		$_GET['act'] = $this->action;
		$_GET['app'] = $__app_dir;

		if ($args_var_str)
		{
			if (substr($args_var_str, 0, strlen($this->params['sep_var'])) == $this->params['sep_var'])
			{
				$args_var_str = substr($args_var_str, strlen($this->params['sep_var']));
			}

			if (!strstr($args_var_str,'-'))
			{
				$_GET['id'] = urldecode($args_var_str);
			}

			$uri['last'] = explode($this->params['sep_var'], $args_var_str);

			foreach ($uri['last'] as $val)
			{
				@list($k, $v) = explode($this->params['sep_value'], $val, 2);

				if ($k)
				{
					if (! strstr($v, '%'))
					{
						$_GET[$k] = $v;
					}
					else
					{
						$_GET[$k] = urldecode($v);
					}
				}
			}
		}

		foreach ($_GET AS $key => $val)
		{
			if (strstr($key, '/'))
			{
				unset($_GET[$key]);
			}
		}

		return $this;
	}
}
