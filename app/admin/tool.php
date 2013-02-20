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

class tool extends AWS_CONTROLLER
{
	public function setup()
	{
		$this->model('admin_session')->init();
	}
	
	public function index_action()
	{
		$this->crumb(AWS_APP::lang()->_t('系统维护'), 'admin/tool/');
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 501));
		TPL::output('admin/tool');
	}
	
	public function init_action()
	{
		H::redirect_msg(AWS_APP::lang()->_t('正在准备...'), '?/admin/tool/' . $_POST['action'] . '/page-1__per_page-' . $_POST['per_page']);
	}
	
	public function cache_clean_action()
	{	
		AWS_APP::cache()->clean();
		
		H::redirect_msg(AWS_APP::lang()->_t('缓存清理完成'), '?/admin/tool/');
	}
	
	public function update_users_reputation_action()
	{
		if ($this->model('reputation')->calculate((($_GET['page'] * $_GET['per_page']) - $_GET['per_page']), $_GET['per_page']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('正在更新用户威望') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '?/admin/tool/update_users_reputation/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('用户威望更新完成'));
		}
	}
	
	public function bbcode_to_markdown_action()
	{
		switch ($_GET['type'])
		{
			default:
				if ($questions_list = $this->model('question')->fetch_page('question', null, 'question_id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($questions_list as $key => $val)
					{
						$this->model('question')->update_question_field($val['question_id'], array(
							'question_detail' => FORMAT::bbcode_2_markdown($val['question_detail'])
						));
					}
					
					H::redirect_msg(AWS_APP::lang()->_t('正在转换问题内容 BBCode') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '?/admin/tool/bbcode_to_markdown/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '?/admin/tool/bbcode_to_markdown/page-1__type-answer__per_page-' . $_GET['per_page']);
				}		
			break;
					
			case 'answer':
				if ($answer_list = $this->model('question')->fetch_page('answer', null, 'answer_id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($answer_list as $key => $val)
					{
						$this->model('answer')->update_answer_by_id($val['answer_id'], array(
							'answer_content' => FORMAT::bbcode_2_markdown($val['answer_content'])
						));
					}
					
					H::redirect_msg(AWS_APP::lang()->_t('正在转换回答内容 BBCode') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '?/admin/tool/bbcode_to_markdown/page-' . ($_GET['page'] + 1) . '__type-answer__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '?/admin/tool/bbcode_to_markdown/page-1__type-topic__per_page-' . $_GET['per_page']);
				}	
			break;
					
			case 'topic':
				if ($topic_list = $this->model('topic')->get_topic_list(null, calc_page_limit($_GET['page'], $_GET['per_page']), 'topic_id ASC'))
				{
					foreach ($topic_list as $key => $val)
					{
						$this->model('topic')->update('topic', array(
							'topic_description' => FORMAT::bbcode_2_markdown($val['topic_description'])
						), 'topic_id = ' . intval($val['topic_id']));
					}
					
					H::redirect_msg(AWS_APP::lang()->_t('正在转换话题内容 BBCode') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '?/admin/tool/bbcode_to_markdown/page-' . ($_GET['page'] + 1) . '__type-topic__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('BBCode 转换完成'));
				}		
			break;
		}
	}
	
	public function update_search_index_action()
	{
		if ($questions_list = $this->model('question')->fetch_page('question', null, 'question_id ASC', $_GET['page'], $_GET['per_page']))
		{
			foreach ($questions_list as $key => $val)
			{
				$this->model('search_index')->push_index('question', $val['question_content'], $val['question_id']);
			}
			
			H::redirect_msg(AWS_APP::lang()->_t('正在更新搜索索引') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '?/admin/tool/update_search_index/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('搜索索引更新完成'));
		}
	}

	public function update_fresh_actions_action()
	{
		if ($this->model('system')->update_associate_fresh_action($_GET['page'], $_GET['per_page']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('正在更新最新动态') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '?/admin/tool/update_fresh_actions/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('最新动态更新完成'));
		}
	}
}