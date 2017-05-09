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

class main extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();

		$this->crumb('Loading...', '/payment/');
	}

	public function alipay_action()
	{
		if (!$_POST['amount'])
		{
			H::redirect_msg('业务参数错误, 操作已中止');
		}

		if ($_POST['params'])
		{
			$extra_param = $_POST['params'];
		}
		else
		{
			H::redirect_msg('业务参数错误, 操作已中止');
		}

		$amount = str_replace(',', '', number_format($_POST['amount'], 2));

		if (!$_POST['order_id'])
		{
			if (is_mobile())
			{
				$source = 'mobile';
			}

			$order_id = $this->model('payment')->create($this->user_id, $amount, '支付宝付款', $source);

			$order_info = $this->model('payment')->get_order($order_id);
		}
		else
		{
			if ($order_info = $this->model('payment')->get_order($_POST['order_id']))
			{
				$order_id = $order_info['order_id'];

				if (str_replace(',', '', number_format($order_info['amount'], 2)) != $amount)
				{
					H::redirect_msg('订单金额参数错误, 操作已中止');
				}
			}
			else
			{
				H::redirect_msg('支付订单不存在, 操作已中止');
			}
		}

		if ($order_info['source'] == 'mobile' AND !is_mobile())
		{
			H::redirect_msg('这是手机版产生的订单只能在手机端支付');
		}

		if ($order_info['source'] != 'mobile' AND is_mobile())
		{
			H::redirect_msg('这是 PC 版产生的订单只能在 PC 端支付');
		}

		$order_name = get_setting('site_name') . ' 支付 ' . $order_id;

		$payment_params = json_decode(base64_decode($extra_param), true);

		// 自定义支付逻辑
		if ($payment_params['pay_to_project_order_id'])
		{
			$this->model('project')->set_project_payment_order_id($_POST['project_order_id'], $order_id);
		}

		$this->model('payment')->set_extra_param($order_id, $payment_params);

		TPL::assign('notify_url', get_js_url('/payment/notify/alipay/'));
		TPL::assign('partner', get_setting('alipay_partner'));
		TPL::assign('seller_email', get_setting('alipay_seller_email'));
		TPL::assign('order_id', $order_id);
		TPL::assign('amount', $amount);
		TPL::assign('return_url', get_js_url('/payment/callback/alipay/'));
		TPL::assign('order_name', $order_name);

		if (is_mobile())
		{
			$notify_url = get_js_url('/payment/notify/aliwap/');
			$call_back_url = get_js_url('/payment/callback/aliwap/');

			$req_id = $order_id;

			$html_text = $this->model('payment_aliwap')->buildRequestHttp(array(
					"service" => "alipay.wap.trade.create.direct",
					"partner" => get_setting('alipay_partner'),
					"sec_id" => get_setting('alipay_sign_type'),
					"format"    => 'xml',
					"v" => '2.0',
					"req_id"    => $req_id,
					"req_data"  => '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' . get_setting('alipay_seller_email') . '</seller_account_name><out_trade_no>' . $order_id . '</out_trade_no><subject>' . $order_name . '</subject><total_fee>' . $amount . '</total_fee><merchant_url>' . get_setting('base_url') . '</merchant_url></direct_trade_create_req>',
					"_input_charset"    => get_setting('alipay_input_charset')
			));

			// 解析远程模拟提交后返回的信息
			$para_html_text = $this->model('payment_aliwap')->parseResponse(urldecode($html_text));

			// 获取 request_token
			$request_token = $para_html_text['request_token'];

			echo $this->model('payment_aliwap')->buildRequestForm(array(
					"service" => "alipay.wap.auth.authAndExecute",
					"partner" => get_setting('alipay_partner'),
					"sec_id" => get_setting('alipay_sign_type'),
					"format"    => 'xml',
					"v" => '2.0',
					"req_id"    => $req_id,
					"req_data"  => '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>',
					"_input_charset"    => get_setting('alipay_input_charset')
			), 'get', '确认');
		}
		else
		{
			TPL::assign('show_url', get_setting('base_url'));
			TPL::assign('sign', $this->model('payment_alipay')->createSign($order_id, $order_name, $amount));

			TPL::output('payment/alipay');
		}
	}
}