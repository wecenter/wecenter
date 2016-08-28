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

class order extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();

		if (get_setting('project_enabled') != 'Y')
		{
			H::redirect_msg(AWS_APP::lang()->_t('活动系统未启用'), '/');
		}

		$this->crumb(AWS_APP::lang()->_t('活动'), '/project/');

		TPL::import_css('css/project.css');
	}

	public function add_action()
	{
		if (!$product_info = $this->model('project')->get_product_info_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定商品不存在'));
		}

		if (!$project_info = $this->model('project')->get_project_info_by_id($product_info['project_id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('项目不存在或已被删除'));
		}

		if ($project_info['approved'] != 1)
		{
			H::redirect_msg(AWS_APP::lang()->_t('当前项目未通过审核'));
		}

		if ($project_info['start_time'] > time())
		{
			H::redirect_msg(AWS_APP::lang()->_t('当前项目处于预热中,尚未开始'));
		}

		if ($project_info['end_time'] < time())
		{
			H::redirect_msg(AWS_APP::lang()->_t('当前项目已经结束'));
		}

		$this->crumb($project_info['title'], '/project/' . $project_info['id']);
		$this->crumb('支持项目', '/project/' . $project_info['id']);

		TPL::assign('product_info', $product_info);
		TPL::assign('project_info', $project_info);

		TPL::output('project/order/add');
	}

	public function init_payment_action()
	{
		if (!$order_info = $this->model('project')->get_project_order_info_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定商品不存在'));
		}

		if ($order_info['payment_time'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('该订单已支付'));
		}

		if (!$project_info = $this->model('project')->get_project_info_by_id($order_info['project_id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('项目不存在或已被删除'));
		}

		if ($project_info['approved'] != 1)
		{
			H::redirect_msg(AWS_APP::lang()->_t('当前项目未通过审核'));
		}

		if ($project_info['start_time'] > time())
		{
			H::redirect_msg(AWS_APP::lang()->_t('当前项目处于预热中,尚未开始'));
		}

		if ($project_info['end_time'] < time())
		{
			H::redirect_msg(AWS_APP::lang()->_t('当前项目已经结束'));
		}

		TPL::assign('order_info', $order_info);

		TPL::assign('params', base64_encode(json_encode(array(
			'pay_to_project_order_id' => $order_info['id']
		))));

		TPL::output('project/order/init_payment');
	}
}