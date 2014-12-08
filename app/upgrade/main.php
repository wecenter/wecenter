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
	public $versions = array();
	public $db_version = 0;
	public $db_engine = '';
	public $ignore_sql;

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		@set_time_limit(0);

		// 升级程序禁止任何输入
		unset($_POST);

		if (!is_really_writable(AWS_PATH) AND !defined('IN_SAE'))
		{
			H::redirect_msg(AWS_APP::lang()->_t('目录 %s 无法写入, 请修改目录权限', AWS_PATH));
		}

		$this->model('upgrade')->db_clean();

		// 在此标记有 SQL 升级的版本号, 名称为上一个版本的 Build 编号
		$this->versions = array(
			20130419,
			20130426,
			20130607,
			20130614,
			20130628,
			20130704,
			20130719,
			20130725,
			20130802,
			20130830,
			20130906,
			20130918,
			20131018,
			20131025,
			20131101,
			20131108,
			20131122,
			20131206,
			20131213,
			20140117,
			20140124,
			20140214,
			20140221,
			20140228,
			20140307,
			20140314,
			20140331,
			20140415,
			20140521,
			20140526,
			20140530,
			20140702,
			20140707,
			20140728,
			20140811,
			20140814,
			20140830,
			20140912,
			20140922,
			20140930,
			20141014,
			20141103,
			20141107,
			20141110,
			20141121
		);

		$this->db_version = get_setting('db_version', false);

		if ($this->db_version < 20130419)
		{
			H::redirect_msg(AWS_APP::lang()->_t('当前升级器只支持 2.0 与以上版本升级, 你当前版本太低, 请下载 2.0 - 2.5.1 版先进行升级'));
		}

		if (!in_array($this->db_version, $this->versions))
		{
			// is upgrade error version
			$this->db_version = $this->db_version - 1;
			$this->ignore_sql = true;
		}

		if (in_array($this->db_version, $this->versions) AND $this->ignore_sql)
		{
			// ignore
			$this->db_version = $this->db_version + 1;
		}
		else if (!in_array($this->db_version, $this->versions) AND $_GET['act'] != 'final' AND $_GET['act'] != 'script')
		{
			if ($this->db_version > end($this->versions))
			{
				H::redirect_msg(AWS_APP::lang()->_t('您的程序已经是最新版本'));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('无法定位您的程序版本, 请手动执行升级, Build: %s', $this->db_version));
			}
		}

		TPL::assign('static_url', G_STATIC_URL);

		if (!$this->db_engine = get_setting('db_engine'))
		{
			$this->db_engine = 'MyISAM';
		}
	}

	public function index_action()
	{
		if ($this->user_id)
		{
			$this->model('account')->setcookie_logout();
			$this->model('account')->setsession_logout();

			HTTP::redirect('/upgrade/');
		}

		TPL::assign('db_version', $this->db_version);
		TPL::output('install/upgrade');
	}

	public function sql_action()
	{
		$sql_file = ROOT_PATH . 'app/upgrade/db/' . str_replace('.', '', $_GET['id']) . '.sql';

		if (file_exists($sql_file))
		{
			$sql_query = file_get_contents($sql_file);
		}

		if (trim($sql_query))
		{
			$sql_query .= "\n\nUPDATE `[#DB_PREFIX#]system_setting` SET `value` = 's:8:\"" . ($_GET['id'] + 1) . "\";' WHERE `varname` = 'db_version';";

			header('Content-type: text/plain; charset=UTF-8');

			echo str_replace(array('[#DB_PREFIX#]', '[#DB_ENGINE#]'), array(AWS_APP::config()->get('database')->prefix, $this->db_engine), $sql_query);
			die;
		}
	}

	public function run_action()
	{
		foreach ($this->versions AS $version)
		{
			$sql_query = null;

			$sql_file = ROOT_PATH . 'app/upgrade/db/' . $version . '.sql';
			$upgrade_script = ROOT_PATH . 'app/upgrade/script/' . $version . '.php';

			if ($this->db_version <= $version AND file_exists($sql_file))
			{
				$sql_query = file_get_contents($sql_file);
			}

			if ($this->db_version <= $version AND file_exists($upgrade_script))
			{
				$this->model('upgrade')->setup_upgrade_script($version);
			}

			if ($this->db_version == $version AND $this->ignore_sql)
			{
				unset($sql_query);
			}

			if (trim($sql_query))
			{
				if ($sql_error = $this->model('upgrade')->run_query($sql_query))
				{
					TPL::assign('sql_error', $sql_error);
					TPL::assign('version', $version);
					TPL::output('install/upgrade_fail');
					die;
				}
			}

			$this->model('setting')->set_vars(array(
				'db_version' => $version
			));
		}

		$this->model('setting')->set_vars(array(
			'db_version' => G_VERSION_BUILD
		));

		AWS_APP::cache()->clean();

		$upgrade_script = $this->model('upgrade')->get_upgrade_script();

		if (sizeof($upgrade_script) > 0)
		{
			HTTP::redirect('/upgrade/script/');
		}

		HTTP::redirect('/upgrade/final/');
	}

	public function script_action()
	{
		$upgrade_script = $this->model('upgrade')->get_upgrade_script();

		if (sizeof($upgrade_script) == 0 OR !$upgrade_script)
		{
			HTTP::redirect('/upgrade/final/');
		}

		krsort($upgrade_script);

		$script_version = end($upgrade_script);

		include(ROOT_PATH . 'app/upgrade/script/' . $script_version . '.php');

		$this->model('upgrade')->remove_upgrade_script($script_version);

		H::redirect_msg(AWS_APP::lang()->_t('正在执行升级脚本 %s, 请耐心等待...', $script_version), '/upgrade/script/' . rand(100000, 666666));
	}

	public function final_action()
	{
		H::redirect_msg(AWS_APP::lang()->_t('升级完成, 您的程序已经是最新版本, 如遇搜索功能异常, 请进入后台更新搜索索引') . '<!-- Analytics --><img src="http://www.wecenter.com/analytics/?build=' . G_VERSION_BUILD . '&amp;site_name=' . urlencode(get_setting('site_name')) . '&amp;base_url=' . urlencode(base_url()) . '&amp;php=' . PHP_VERSION . '" alt="" width="1" height="1" /><!-- / Analytics -->', '/');
	}
}
