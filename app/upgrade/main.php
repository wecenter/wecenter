<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
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
	var $versions = array();
	var $db_version = 0;
	var $db_engine = '';
	var $ignore_sql;
	
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
		
		if (is_dir(ROOT_PATH . 'plugins/aws_tinymce/'))
		{
			H::redirect_msg(AWS_APP::lang()->_t('1.1 版本已不支持 Tinymce 插件, 请删除 plugins/aws_tinymce/ 目录'));
		}
		
		if (!is_really_writable(AWS_PATH))
		{
			H::redirect_msg(AWS_APP::lang()->_t('目录 %s 无法写入, 请修改目录权限', AWS_PATH));
		}
		
		$this->model('upgrade')->db_clean();
		
		// 在此标记有 SQL 升级的版本号, 名称为上一个版本的 Build 编号
		$this->versions = array(
			/*20120608,
			20120615,
			20120622,
			20120629,
			20120706,
			20120713,
			20120719,
			20120720,*/
			
			20120727,
			20120803,
			20120810,
			20120817,
			20120824,
			20120831,
			20120921,
			20120928,
			20121012,
			20121019,
			20121026,
			20121102,
			20121109,
			20121123,
			20121228,
			20130111,
			20130118,
			20130125,
			20130201,
			20130301,
			20130308,
			20130315,
			20130322,
			20130329,
			20130412,
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
			20130906
		);
		
		if (!$this->db_version = get_setting('db_version', false))
		{
			$this->db_version = 20120608;
		}
		
		if ($this->db_version < 20120727)
		{
			H::redirect_msg(AWS_APP::lang()->_t('当前升级器只支持 1.0.2 以上版本升级, 你当前版本太低, 请下载 1.0.3 版进行升级'));
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
		else if (!in_array($this->db_version, $this->versions) AND $_GET['act'] != 'final')
		{
			H::redirect_msg(AWS_APP::lang()->_t('您的程序已经是最新版本'));
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
			
			if ($this->db_version <= $version AND file_exists($sql_file))
			{	
				$sql_query = file_get_contents($sql_file);
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
		
		H::redirect_msg(AWS_APP::lang()->_t('升级完成, 下面开始重建数据...'), '/upgrade/final/case-start');
	}
	
	public function final_action()
	{
		if (intval($_GET['page']) < 1)
		{
			$_GET['page'] = 1;
		}
		
		switch ($_GET['case'])
		{
			case 'start':				
				H::redirect_msg(AWS_APP::lang()->_t('正在进入重建数据阶段...'), '/upgrade/final/case-update_last_answer');
			break;
			
			// 0629
			/*case 'update_question_attach_statistics':
				if ($this->model('upgrade')->check_question_attach_statistics())
				{
					H::redirect_msg(AWS_APP::lang()->_t('问题附件统计重建完成, 开始重建回复附件统计...'), '/upgrade/final/case-update_answer_attach_statistics');
				}
				
				if ($this->model('upgrade')->update_question_attach_statistics($_GET['page'], 2500))
				{
					H::redirect_msg(AWS_APP::lang()->_t('正在重建问题附件统计') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/upgrade/final/case-update_question_attach_statistics__page-' . ($_GET['page'] + 1));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('问题附件统计重建完成, 开始重建附件统计...'), '/upgrade/final/case-update_answer_attach_statistics');
				}
			break;*/
			
			// 0803
			case 'update_last_answer':
				if ($this->model('upgrade')->check_last_answer())
				{
					H::redirect_msg(AWS_APP::lang()->_t('最后回复数据更新完成, 开始更新问题热门度...'), '/upgrade/final/case-update_popular_value');
				}
				
				if ($this->model('upgrade')->update_last_answer($_GET['page'], 2500))
				{
					H::redirect_msg(AWS_APP::lang()->_t('正在更新最后回复数据') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/upgrade/final/case-update_last_answer__page-' . ($_GET['page'] + 1));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('最后回复数据更新完成, 开始更新问题热门度...'), '/upgrade/final/case-update_popular_value');
				}
			break;
			
			// 0824
			case 'update_popular_value':
				if ($this->model('upgrade')->update_popular_value_answer($_GET['page'], 2000))
				{
					H::redirect_msg(AWS_APP::lang()->_t('正在更新问题热门度') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/upgrade/final/case-update_popular_value__page-' . ($_GET['page'] + 1));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('问题热门度更新完成, 开始重建附件统计...'), '/upgrade/final/case-update_answer_attach_statistics');
				}
			break;
			
			case 'update_answer_attach_statistics':
				if ($this->model('upgrade')->check_answer_attach_statistics())
				{
					H::redirect_msg(AWS_APP::lang()->_t('附件统计重建完成, 开始升级动作数据...'), '/upgrade/final/case-upgrade_user_action_history');
				}
				
				if ($this->model('upgrade')->update_answer_attach_statistics($_GET['page'], 2500))
				{
					H::redirect_msg(AWS_APP::lang()->_t('正在重建附件统计') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/upgrade/final/case-update_answer_attach_statistics__page-' . ($_GET['page'] + 1));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('附件统计重建完成, 开始升级动作数据...'), '/upgrade/final/case-upgrade_user_action_history');
				}
			break;
			
			//0201
			case 'upgrade_user_action_history':
				if (get_setting('user_action_history_fresh_upgrade') == 'Y')
				{
					H::redirect_msg(AWS_APP::lang()->_t('动作数据升级完成...'), '/upgrade/final/case-final');
				}
							
				if ($this->model('system')->update_associate_fresh_action($_GET['page'], 2000))
				{
					H::redirect_msg(AWS_APP::lang()->_t('正在升级动作数据') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/upgrade/final/case-upgrade_user_action_history__page-' . ($_GET['page'] + 1));
				}
				else
				{
					$this->model('setting')->set_vars(array(
						'user_action_history_fresh_upgrade' => 'Y'
					));
					
					H::redirect_msg(AWS_APP::lang()->_t('动作数据升级完成...'), '/upgrade/final/case-final');
				}
			break;
			
			case 'final':
				H::redirect_msg(AWS_APP::lang()->_t('升级完成, 您的程序已经是最新版本, 如遇搜索功能异常, 请进入后台更新搜索索引') . '<!-- Analytics --><img src="http://www.wecenter.com/analytics/?build=' . G_VERSION_BUILD . '&amp;site_name=' . urlencode(get_setting('site_name')) . '&amp;base_url=' . urlencode(get_setting('base_url')) . '&amp;php=' . PHP_VERSION . '" alt="" width="1" height="1" /><!-- / Analytics -->', '/');
			break;
		}
	}
}
