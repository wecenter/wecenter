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
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		return $rule_action;
	}

	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('发布'), '/publish/');
	}

	public function index_action()
	{
		if ($_GET['id'])
		{
			if (!$question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定问题不存在'));
			}

			if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_question'])
			{
				if ($question_info['published_uid'] != $this->user_id)
				{
					H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个问题'), '/question/' . $_GET['id']);
				}
			}

			TPL::assign('question_info', $question_info);
		}
		else if (!$this->user_info['permission']['publish_question'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布问题'));
		}
		else if ($this->is_post() AND $_POST['question_detail'])
		{
			TPL::assign('question_info', array(
				'question_content' => $_POST['question_content'],
				'question_detail' => $_POST['question_detail']
			));

			$question_info['category_id'] = $_POST['category_id'];
		}
		else
		{
			$draft_content = $this->model('draft')->get_data(1, 'question', $this->user_id);

			TPL::assign('question_info', array(
				'question_content' => $_POST['question_content'],
				'question_detail' => $draft_content['message']
			));
		}

		if ($this->user_info['integral'] < 0 AND get_setting('integral_system_enabled') == 'Y' AND !$_GET['id'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作'));
		}

		if (($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $question_info['published_uid'] == $this->user_id AND $_GET['id']) OR !$_GET['id'])
		{
			TPL::assign('attach_access_key', md5($this->user_id . time()));
		}

		if (!$question_info['category_id'] AND $_GET['category_id'])
		{
			$question_info['category_id'] = $_GET['category_id'];
		}

		if (get_setting('category_enable') == 'Y')
		{
			TPL::assign('question_category_list', $this->model('system')->build_category_html('question', 0, $question_info['category_id']));
		}

		if ($modify_reason = $this->model('question')->get_modify_reason())
		{
			TPL::assign('modify_reason', $modify_reason);
		}

		TPL::assign('human_valid', human_valid('question_valid_hour'));

		TPL::import_js('js/app/publish.js');

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		if (get_setting('upload_enable') == 'Y')
		{
			// fileupload
			TPL::import_js('js/fileupload.js');
		}

		TPL::assign('recent_topics', @unserialize($this->user_info['recent_topics']));

		TPL::output('publish/index');
	}

	public function article_action()
	{
		if ($_GET['id'])
		{
			if (!$article_info = $this->model('article')->get_article_info_by_id($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定文章不存在'));
			}

			if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_article'])
			{
				if ($article_info['uid'] != $this->user_id)
				{
					H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个文章'), '/article/' . $_GET['id']);
				}
			}

			TPL::assign('article_info', $article_info);
			TPL::assign('article_topics', $this->model('topic')->get_topics_by_item_id($article_info['id'], 'article'));
		}
		else if (!$this->user_info['permission']['publish_article'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布文章'));
		}
		else if ($this->is_post() AND $_POST['message'])
		{
			TPL::assign('article_info', array(
				'title' => $_POST['title'],
				'message' => $_POST['message']
			));
		}
		else
		{
			$draft_content = $this->model('draft')->get_data(1, 'article', $this->user_id);

			TPL::assign('article_info', array(
				'title' => $_POST['title'],
				'message' => $draft_content['message']
			));
		}

		if (($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $article_info['uid'] == $this->user_id AND $_GET['id']) OR !$_GET['id'])
		{
			TPL::assign('attach_access_key', md5($this->user_id . time()));
		}

		if (get_setting('category_enable') == 'Y')
		{
			TPL::assign('article_category_list', $this->model('system')->build_category_html('question', 0, $article_info['category_id']));
		}

		TPL::assign('human_valid', human_valid('question_valid_hour'));

		TPL::import_js('js/app/publish.js');

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		if (get_setting('upload_enable') == 'Y')
		{
			// fileupload
			TPL::import_js('js/fileupload.js');
		}

		TPL::output('publish/article');
	}

	public function wait_approval_action()
	{
		if ($_GET['question_id'])
		{
			if ($_GET['_is_mobile'])
			{
				$url = '/m/question/' . $_GET['question_id'];
			}
			else
			{
				$url = '/question/' . $_GET['question_id'];
			}
		}
		else
		{
			$url = '/';
		}

		H::redirect_msg(AWS_APP::lang()->_t('发布成功, 请等待管理员审核...'), $url);
	}
}