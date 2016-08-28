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

class callback extends AWS_CONTROLLER
{
	public $callback_url = '/';
	
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
		$result = $this->model('payment_aliwap')->verifyReturn();	
		$order = $this->model('payment')->get_order($_GET['out_trade_no']);
		
		if ($result)
		{
			if ($_GET['extra_common_param'])
			{
				$params = json_decode(base64_decode(urldecode($_GET['extra_common_param'])), TRUE);
			}
			else if ($order['extra_param'])
			{
				$params = unserialize($order['extra_param']);
			}
			
			if (!$order['terrace_id'])
			{
				$this->model('payment')->set_order_terrace_id($_GET['trade_no'], $order['order_id']);
				$this->model('payment')->set_payment_id('ALIPAY', $order['order_id']);
				
				
				if ($params['pay_to_project_order_id'])
				{
					if (!$this->model('payment')->pay_to_project_order_id($order['order_id'], $params['pay_to_project_order_id']))
					{
						H::redirect_msg('订单处理失败，如有疑问请联系客服人员，网站订单编号：' . $params['pay_to_project_order_id']);
					}
				}
			}
			
			if ($params['pay_to_project_order_id'])
			{
				$this->callback_url = '/project/sponsored/';
			}
			
			H::redirect_msg('支付成功, 交易金额: ' . $order['amount'], $this->callback_url);
		}
		else
		{
			H::redirect_msg('交易失败，如有疑问请联系客服人员，支付宝订单编号：' . $_GET['out_trade_no']);
		}
	}
	
	public function alipay_action()
	{		
		$result = $this->model('payment_alipay')->verifyReturn();	
		$order = $this->model('payment')->get_order($_GET['out_trade_no']);
		
		if ($result AND $_GET['total_fee'] == $order['amount'])
		{
			if ($_GET['extra_common_param'])
			{
				$params = json_decode(base64_decode(urldecode($_GET['extra_common_param'])), TRUE);
			}
			else if ($order['extra_param'])
			{
				$params = unserialize($order['extra_param']);
			}
			
			if (!$order['terrace_id'])
			{
				$this->model('payment')->set_order_terrace_id($_GET['trade_no'], $order['order_id']);
				$this->model('payment')->set_payment_id('ALIPAY', $order['order_id']);
				
				
				if ($params['pay_to_project_order_id'])
				{
					if (!$this->model('payment')->pay_to_project_order_id($order['order_id'], $params['pay_to_project_order_id']))
					{
						H::redirect_msg('订单处理失败，如有疑问请联系客服人员，网站订单编号：' . $params['pay_to_project_order_id']);
					}
				}
			}
			
			if ($params['pay_to_project_order_id'])
			{
				$this->callback_url = '/project/sponsored/';
			}
			
			H::redirect_msg('支付成功, 交易金额: ' . $order['amount'], $this->callback_url);
		}
		else
		{
			H::redirect_msg('交易失败，如有疑问请联系客服人员，支付宝订单编号：' . $_GET['out_trade_no']);
		}
	}
}