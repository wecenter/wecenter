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
		$rule_action['rule_type'] = "white"; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

		if ($this->user_info['permission']['visit_feature'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'index'
			);
		}

		return $rule_action;
	}

	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('专题'), '/feature/');
	}

	public function index_action()
	{
		if (is_digits($_GET['id']))
		{
			if (! $feature_info = $this->model('feature')->get_feature_by_id($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('专题不存在'), '/');
			}
		}
		else if (! $feature_info = $this->model('feature')->get_feature_by_url_token($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('专题不存在'), '/');
		}

		if (!$feature_info['enabled'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('专题未启用'), '/');
		}

		if ($feature_info['url_token'] != $_GET['id'] AND !$_GET['sort_type'] AND !$_GET['is_recommend'])
		{
			HTTP::redirect('/feature/' . $feature_info['url_token']);
		}

		if (! $topic_list = $this->model('topic')->get_topics_by_ids($this->model('feature')->get_topics_by_feature_id($feature_info['id'])))
		{
			H::redirect_msg(AWS_APP::lang()->_t('专题下必须包含一个以上话题'), '/');
		}

		if ($feature_info['seo_title'])
		{
			TPL::assign('page_title', $feature_info['seo_title']);
		}
		else
		{
			$this->crumb($feature_info['title'], '/feature/' . $feature_info['url_token']);
		}

		TPL::assign('sidebar_hot_topics', $topic_list);

		TPL::assign('feature_info', $feature_info);

		TPL::import_js('js/app/feature.js');

		TPL::output('feature/detail');
	}
}