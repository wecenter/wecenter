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

class notify extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function aliwap_action()
	{
		$result = $this->model('payment_aliwap')->verifyNotify();

		$verify_result = 'fail';

		if ($result)
		{
			$doc = new DOMDocument();

			switch (get_setting('alipay_sign_type')) {
				case 'MD5':
					$doc->loadXML($_POST['notify_data']);

					break;

				case '0001':
					$doc->loadXML($this->model('payment_aliwap')->decrypt($_POST['notify_data']));

					break;
			}

			if ($doc->getElementsByTagName("notify")->item(0)->nodeValue)
			{
				//商户订单号
				$out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue;
				//支付宝交易号
				$trade_no = $doc->getElementsByTagName( "trade_no" )->item(0)->nodeValue;
				//交易状态
				$trade_status = $doc->getElementsByTagName( "trade_status" )->item(0)->nodeValue;

				$order = $this->model('payment')->get_order($out_trade_no);

				if ($trade_status == 'TRADE_FINISHED' OR $trade_status == 'TRADE_SUCCESS')
				{
					if ($order['extra_param'])
					{
						$params = unserialize($order['extra_param']);
					}

					if (!$order['terrace_id'])
					{
						$this->model('payment')->set_order_terrace_id($trade_no, $order['order_id']);
						$this->model('payment')->set_payment_id('ALIPAY', $order['order_id']);

						if ($params['pay_to_project_order_id'])
						{
							if (!$this->model('payment')->pay_to_project_order_id($order['order_id'], $params['pay_to_project_order_id']))
							{
								//$result = 'fail';
							}
						}
					}

					$verify_result = 'success';
				}
			}
		}

		exit($verify_result);
	}

	public function alipay_action()
	{
		$result = $this->model('payment_alipay')->verifyNotify();

		$order = $this->model('payment')->get_order($_POST['out_trade_no']);

		if ($result AND $_POST['total_fee'] == $order['amount'])
		{
			if ($_POST['extra_common_param'])
			{
				$params = json_decode(base64_decode(urldecode($_POST['extra_common_param'])), TRUE);
			}
			else if ($order['extra_param'])
			{
				$params = unserialize($order['extra_param']);
			}

			if (!$order['terrace_id'])
			{
				$this->model('payment')->set_order_terrace_id($_POST['trade_no'], $order['order_id']);
				$this->model('payment')->set_payment_id('ALIPAY', $order['order_id']);

				if ($params['pay_to_project_order_id'])
				{
					if (!$this->model('payment')->pay_to_project_order_id($order['order_id'], $params['pay_to_project_order_id']))
					{
						//$result = 'fail';
					}
				}
			}

			$result = 'success';
		}
		else
		{
			$result = 'fail';
		}

		exit($result);
	}
}