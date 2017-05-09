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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array(
			'list',
			'random_projects'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();

		if (get_setting('project_enabled') != 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('活动系统未启用')));
		}
	}

	public function attach_edit_list_action()
	{
		if (! $project_info = $this->model('project')->get_project_info_by_id($_POST['project_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('无法获取附件列表')));
		}

		if ($project_info['uid'] != $this->user_id AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个附件列表')));
		}

		if ($project_attach = $this->model('publish')->get_attach('project', $_POST['project_id']))
		{
			foreach ($project_attach as $attach_id => $val)
			{
				$project_attach[$attach_id]['class_name'] = $this->model('publish')->get_file_class($val['file_name']);

				$project_attach[$attach_id]['delete_link'] = get_js_url('/publish/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
					'attach_id' => $attach_id,
					'access_key' => $val['access_key']
				))));

				$project_attach[$attach_id]['attach_id'] = $attach_id;
				$project_attach[$attach_id]['attach_tag'] = 'attach';
			}
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'attachs' => $project_attach
		), 1, null));
	}

	public function publish_project_action()
	{
		if (!$this->user_info['permission']['publish_project'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布活动'));
		}

		if (!$_POST['title'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入项目名称')));
		}

		if (get_setting('category_enable') != 'Y')
		{
			$_POST['category_id'] = 1;
		}

		if (!$_POST['category_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择项目分类')));
		}

		if (!is_digits($_POST['start_time']) OR !is_digits($_POST['end_time']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('活动时间错误')));
		}

		if (date('Ymd', $_POST['start_time']) < date('Ymd', time()))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('活动开始时间不能小于当前日期')));
		}

		if ($_POST['end_time'] <= $_POST['start_time'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('活动结束时间必须大于开始时间')));
		}

		if (!preg_match('/^\d+(\.\d{1,2})?$/', $_POST['amount']) OR intval($_POST['amount']) <= 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('活动金额错误')));
		}

		if (!is_array($_POST['project_product']) AND $_POST['project_type'] == 'DEFAULT')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请设置回报')));
		}

		if ($_POST['video_link'])
		{
			if (!load_class('Services_VideoUrlParser')->parse($_POST['video_link']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('视频链接无效,如果没有视频请留空')));
			}
		}

		if ($_FILES['cover']['name'])
		{
			AWS_APP::upload()->initialize(array(
				'allowed_types' => 'jpg,jpeg,png',
				'upload_path' => get_setting('upload_dir') . '/project',
				'is_image' => TRUE
			))->do_upload('cover');


			if (AWS_APP::upload()->get_error())
			{
				switch (AWS_APP::upload()->get_error())
				{
					default:
						H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
					break;

					case 'upload_invalid_filetype':
						H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
					break;
				}
			}

			if (! $upload_data = AWS_APP::upload()->data())
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
			}
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请上传封面图片')));
		}

		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		$project_id = $this->model('project')->publish_project($this->user_id, $_POST['project_type'], $_POST['category_id'], $_POST['title'], $_POST['country'], $_POST['province'], $_POST['city'], $_POST['summary'], $_POST['description'], $_POST['amount'], $_POST['start_time'], $_POST['end_time'], $_POST['contact'], $_POST['topics'], $_POST['video_link']);

		if ($_POST['attach_access_key'])
		{
			$this->model('publish')->update_attach('project', $project_id, $_POST['attach_access_key']);
		}

		foreach ($_POST['project_product'] AS $key => $val)
		{
			if (!$val['stock'])
			{
				$val['stock'] = -99;
			}

			$this->model('project')->add_product($project_id, $val['title'], $val['amount'], $val['stock'], $val['description']);
		}

		AWS_APP::image()->initialize(array(
			'quality' => 90,
			'source_image' => $upload_data['full_path'],
			'new_image' => $upload_data['file_path'] . $project_id . '_thumb.jpg',
			'width' => 223,
			'height' => 165
		))->resize();

		AWS_APP::image()->initialize(array(
			'quality' => 90,
			'source_image' => $upload_data['full_path'],
			'new_image' => $upload_data['file_path'] . $project_id . '_main.jpg',
			'width' => 600,
			'height' => 450
			//'scale' => IMAGE_CORE_SC_BEST_RESIZE_WIDTH
		))->resize();

		unlink($upload_data['full_path']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/project/' . $project_id)
		), 1, null));
	}

	public function update_project_action()
	{
		if (!$project_info = $this->model('project')->get_project_info_by_id($_POST['project_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('项目不存在')));
		}

		if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个项目')));
		}

		if (get_setting('category_enable') == 'N')
		{
			$_POST['category_id'] = 1;
		}

		if (!$_POST['category_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择项目分类')));
		}

		if (!is_digits($_POST['start_time']) OR !is_digits($_POST['end_time']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('活动时间错误')));
		}

		if (date('Ymd', $_POST['start_time']) < date('Ymd', time()))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('活动开始时间不能小于当前日期')));
		}

		if ($_POST['end_time'] <= $_POST['start_time'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('活动结束时间必须大于开始时间')));
		}

		if ($_POST['video_link'])
		{
			if (!load_class('Services_VideoUrlParser')->parse($_POST['video_link']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('视频链接无效,如果没有视频请留空')));
			}
		}

		if ($_FILES['cover']['name'])
		{
			AWS_APP::upload()->initialize(array(
				'allowed_types' => 'jpg,jpeg,png',
				'upload_path' => get_setting('upload_dir') . '/project',
				'is_image' => TRUE
			))->do_upload('cover');


			if (AWS_APP::upload()->get_error())
			{
				switch (AWS_APP::upload()->get_error())
				{
					default:
						H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
					break;

					case 'upload_invalid_filetype':
						H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
					break;
				}
			}

			if (! $upload_data = AWS_APP::upload()->data())
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
			}
		}

		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		$this->model('project')->update_project($_POST['project_id'], $_POST['title'], $_POST['category_id'],  $_POST['country'], $_POST['province'], $_POST['city'], $_POST['summary'], $_POST['description'], $_POST['amount'], $_POST['attach_access_key'], $_POST['topics'], $_POST['video_link'], $_POST['start_time'], $_POST['end_time']);

		if ($_POST['project_product'])
		{
			foreach ($_POST['project_product'] AS $key => $val)
			{
				if (!$val['stock'])
				{
					$val['stock'] = -99;
				}

				$this->model('project')->add_product($_POST['project_id'], $val['title'], $val['amount'], $val['stock'], $val['description']);
			}
		}

		if ($upload_data)
		{
			AWS_APP::image()->initialize(array(
				'quality' => 90,
				'source_image' => $upload_data['full_path'],
				'new_image' => $upload_data['file_path'] . intval($_POST['project_id']) . '_thumb.jpg',
				'width' => 223,
				'height' => 165
			))->resize();

			AWS_APP::image()->initialize(array(
				'quality' => 90,
				'source_image' => $upload_data['full_path'],
				'new_image' => $upload_data['file_path'] . intval($_POST['project_id']) . '_main.jpg',
				'width' => 600,
				'height' => 450
				//'scale' => IMAGE_CORE_SC_BEST_RESIZE_WIDTH
			))->resize();

			unlink($upload_data['full_path']);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/project/' . intval($_POST['project_id']))
		), 1, null));
	}

	public function add_product_order_action()
	{
		if (!$project_info = $this->model('project')->get_project_info_by_id($_POST['project_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('项目不存在')));
		}

		if ($project_info['approved'] != 1)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('当前项目未通过审核')));
		}

		if ($project_info['start_time'] > time())
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('当前项目处于预热中,尚未开始')));
		}

		if ($project_info['end_time'] < time())
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('当前项目已经结束')));
		}

		switch ($project_info['project_type'])
		{
			case 'DEFAULT':
				if (!$product_info = $this->model('project')->get_product_info_by_id($_POST['product_id']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定商品不存在')));
				}

				if ($_POST['is_donate'] != 1 AND (!$_POST['shipping_name'] OR !$_POST['shipping_address'] OR !$_POST['shipping_province'] OR !$_POST['shipping_city'] OR !$_POST['shipping_mobile'] OR !$_POST['shipping_zipcode']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请完善收货信息')));
				}

				if (intval($product_info['amount']) == 0 AND isset($_POST['amount']) AND (!preg_match('/^\d+(\.\d{1,2})?$/', $_POST['amount']) OR intval($_POST['amount']) <= 0))
				{
					H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入正确的金额')));
				}

				if ($order_id = $this->model('project')->add_project_order($this->user_id, $product_info['id'], $_POST['shipping_name'], $_POST['shipping_province'], $_POST['shipping_city'], $_POST['shipping_address'], $_POST['shipping_zipcode'], $_POST['shipping_mobile'], $_POST['is_donate'], $_POST['note'], $_POST['amount']))
				{
					// Modify by wecenter
					ACTION_LOG::save_action($this->user_id, $product_info['project_id'], ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_SUPPORT_PROJECT, '', $product_info['id']);

					if ($this->model('project')->get_like_status_by_uid($product_info['project_id'], $this->user_id))
					{
						$this->model('project')->unset_project_like($product_info['project_id'], $this->user_id);
					}

					if (intval($product_info['amount']) == 0 AND intval($_POST['amount']) == 0)
					{
						$this->model('payment')->set_order_payment_time($order_id);

						H::ajax_json_output(AWS_APP::RSM(array(
							'url' => get_js_url('/project/sponsored/')
						), 1, null));
					}
					else
					{
						/*if (is_mobile())
						{
							$url = get_js_url('/m/add_project_order/' . $order_id);
						}
						else
						{*/
							$url = get_js_url('/project/order/init_payment/' . $order_id);
						//}

						H::ajax_json_output(AWS_APP::RSM(array(
							'url' => $url
						), 1, null));
					}
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('订单生产失败,库存不足')));
				}
			break;

			case 'EVENT':
				if (!$_POST['name'] OR !$_POST['mobile'] OR !$_POST['email'] OR !$_POST['address'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请完善报名信息')));
				}

				if ($this->model('project')->get_single_project_order_by_uid($this->user_id, $project_info['id']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('一个活动只允许报名一次, 你已经报名')));
				}

				$this->model('project')->add_project_event($project_info['id'], $this->user_id, 0, $_POST['name'], $_POST['mobile'], $_POST['email'], $_POST['address']);

				H::ajax_json_output(AWS_APP::RSM(null, 1, null));
			break;

			case 'STOCK':
				if (!$_POST['amount'] OR !$_POST['name'] OR !$_POST['mobile'] OR !$_POST['email'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请完善报名信息')));
				}

				if ($this->model('project')->get_single_project_order_by_uid($this->user_id, $project_info['id']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('一个活动只允许报名一次, 你已经报名')));
				}

				$this->model('project')->add_project_event($project_info['id'], $this->user_id, $_POST['amount'], $_POST['name'], $_POST['mobile'], $_POST['email']);

				H::ajax_json_output(AWS_APP::RSM(null, 1, null));
			break;
		}

		H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('项目类型错误')));
	}

	public function cancel_project_order_action()
	{
		if (!$order_info = $this->model('project')->get_project_order_info_by_id($_POST['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定订单不存在')));
		}

		if ($order_info['uid'] != $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定订单不存在')));
		}

		if (!$this->model('project')->cancel_project_order_by_id($_POST['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('订单取消失败,请联系客服')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function add_order_shipping_action()
	{
		if (!$order_info = $this->model('project')->get_project_order_info_by_id($_POST['order_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定订单不存在')));
		}

		if (!$project_info = $this->model('project')->get_project_info_by_id($order_info['project_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('项目不存在或已被删除')));
		}

		if ($project_info['uid'] != $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限对这个订单进行发货操作')));
		}

		if (!$order_info['payment_time'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('订单尚未支付')));
		}

		if ($order_info['is_donate'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('此订单无需回报')));
		}

		if ($order_info['track_branch'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('此订单已发货')));
		}

		if (!$_POST['track_branch'] OR !$_POST['track_no'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请完善发货信息')));
		}

		if ($this->model('project')->add_order_shipping($order_info['id'], $_POST['track_branch'], $_POST['track_no']))
		{
			$this->model('notify')->send(0, $order_info['uid'], notify_class::TYPE_CONTEXT, notify_class::CATEGORY_CONTEXT, 'PROJS_' . $order_info['id'], array(
				'content' => '<a href="project/' . $project_info['id'] . '">你参加的活动 ' . $project_info['title'] . ' 已发货</a>'
			));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remove_project_product_action()
	{
		if ($this->model('project')->get_product_orders_by_product_id($_POST['product_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('此回报已经产生订单,不可删除')));
		}

		$this->model('project')->remove_project_product_by_id($_POST['product_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function list_action()
	{
		TPL::assign('project_list', $this->model('project')->get_project_list_by_topic_ids(explode(',', $_GET['topic_id']), 'add_time DESC', $_GET['page'], get_setting('contents_per_page')));

		TPL::output('project/ajax/list');
	}

	public function set_like_action()
	{
		if ($this->model('project')->get_like_status_by_uid($_POST['project_id'], $this->user_id))
		{
			$this->model('project')->unset_project_like($_POST['project_id'], $this->user_id);
		}
		else
		{
			$this->model('project')->set_project_like($_POST['project_id'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function random_projects_action()
	{
		$random_projects_query = $this->model('project')->get_random_project(null, 2);

		$random_projects = array();

		if ($random_projects_query)
		{
			foreach ($random_projects_query AS $project_info)
			{
				$random_projects[] = array(
					'id' => $project_info['id'],
					'pic_url' => get_setting('upload_url') . '/project/' . $project_info['id']. '_thumb.jpg',
					'url' => get_js_url('/project/' . $project_info['id']),
					'title' => $project_info['title']
				);
			}
		}

		exit(json_encode($random_projects));
	}
}
