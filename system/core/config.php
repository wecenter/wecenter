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

class core_config
{
	private $config = array();

	function get($config_id)
	{
		if (defined('IN_SAE'))
		{
			switch ($config_id)
			{
				case 'database':
					return (object)array(
						'charset' => 'utf8',
						'prefix' => 'aws_',
						'driver' => 'PDO_MYSQL',
						'master' => array(
							'host' => SAE_MYSQL_HOST_M . ':' . SAE_MYSQL_PORT,
							'username' =>  SAE_MYSQL_USER,
							'password' => SAE_MYSQL_PASS,
							'dbname' => SAE_MYSQL_DB
						),
						'slave' => array(
							'host' => SAE_MYSQL_HOST_S . ':' . SAE_MYSQL_PORT,
							'username' =>  SAE_MYSQL_USER,
							'password' => SAE_MYSQL_PASS,
							'dbname' => SAE_MYSQL_DB
						)
					);
				break;
			}
		}

		if (isset($this->config[$config_id]))
		{
			return $this->config[$config_id];
		}
		else
		{
			return $this->load_config($config_id);
		}
	}

	function load_config($config_id)
	{
		if (! file_exists(AWS_PATH . 'config/' . $config_id . '.php'))
		{
			throw new Zend_Exception('The configuration file config/' . $config_id . '.php does not exist.');
		}
		else
		{
			include (AWS_PATH . 'config/' . $config_id . '.php');

			if (! is_array($config))
			{
				throw new Zend_Exception('Your config/' . $config_id . '.php file does not appear to contain a valid configuration array.');
			}

			$this->config[$config_id] = (object)$config;

			return $this->config[$config_id];
		}
	}

	public function set($config_id, $data)
	{
		if (!$data || ! is_array($data))
		{
			throw new Zend_Exception('config data type error');
		}

		$content = "<?php\n\n";

		foreach($data as $key => $val)
		{
			if (is_array($val))
			{
				$content .= "\$config['{$key}'] = " . var_export($val, true) . ";";;
			}
			else if (is_bool($val))
			{
				$content .= "\$config['{$key}'] = " . ($val ? 'true' : 'false') . ";";
			}
			else
			{
				$content .= "\$config['{$key}'] = '" . addcslashes($val, "'") . "';";
			}

			$content .= "\r\n";
		}

		$config_path = AWS_PATH . 'config/' . $config_id . '.php';

		$fp = @fopen($config_path, "w");

		@chmod($config_path, 0777);

		$fwlen = @fwrite($fp, $content);

		@fclose($fp);

		return $fwlen;
	}
}
