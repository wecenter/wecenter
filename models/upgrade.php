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

class upgrade_class extends AWS_MODEL
{
	public $db_engine = '';

	public $upgrade_script_log_file = '';
	public $upgrade_script_log = '';

	public function setup()
	{
		if (!$this->db_engine = get_setting('db_engine'))
		{
			$this->db_engine = 'MyISAM';
		}

		$this->upgrade_script_log_file = TEMP_PATH . 'upgrade_script.log';

		if (!file_exists($this->upgrade_script_log_file))
		{
			file_put_contents($this->upgrade_script_log_file, 'a:0:{}');
		}

		$this->upgrade_script_log = unserialize(file_get_contents($this->upgrade_script_log_file));
	}

	public function db_clean()
	{
		$users_columns = $this->query_all("SHOW COLUMNS FROM `" . get_table('users') . "`");

		foreach ($users_columns AS $key => $val)
		{
			if (in_array($val['Field'], array(
				'avatar_type',
				'url'
			)))
			{
				$this->query("ALTER TABLE `" . get_table('users') . "` DROP `" . $val['Field'] . "`;");
			}
		}
	}

	public function run_query($sql_query)
	{
		$sql_query = str_replace("\n", "\r", $sql_query);

		if ($db_table_querys = explode(";\r", str_replace(array('[#DB_PREFIX#]', '[#DB_ENGINE#]'), array(AWS_APP::config()->get('database')->prefix, $this->db_engine), $sql_query)))
		{
			foreach ($db_table_querys as $_sql)
			{
				if ($query_string = trim(str_replace(array(
					"\r",
					"\n",
					"\t"
				), '', $_sql)))
				{
					try {
						$this->db()->query($query_string);
					} catch (Exception $e) {
						return "<b>SQL:</b> <i>{$query_string}</i><br /><b>错误描述:</b> " . $e->getMessage();
					}
				}
			}
		}
	}

	public function get_upgrade_script()
	{
		return $this->upgrade_script_log;
	}

	public function setup_upgrade_script($date)
	{
		$this->upgrade_script_log[$date] = $date;

		$this->save_upgrade_script_log();
	}

	public function remove_upgrade_script($date)
	{
		unset($this->upgrade_script_log[$date]);

		$this->save_upgrade_script_log();
	}

	public function save_upgrade_script_log()
	{
		file_put_contents($this->upgrade_script_log_file, serialize($this->upgrade_script_log));
	}
}