<?php

class payment_alipay_class
{
	public function createSign($out_trade_no, $subject, $total_fee, $extra_common_param = null)
	{
		$parameter = array(
			"service" => "create_direct_pay_by_user",
			"payment_type" => "1",

			"partner" => trim(get_setting('alipay_partner')),
			"_input_charset" => 'utf-8',
			"seller_email" => trim(get_setting('alipay_seller_email')),
			"return_url" => get_js_url('/payment/callback/alipay/'),
			'notify_url' => get_js_url('/payment/notify/alipay/'),

			"out_trade_no" => $out_trade_no,
			"subject" => $subject,
			"body" => '',
			"total_fee" => $total_fee,

			"paymethod" => '',
			"defaultbank" => '',

			"anti_phishing_key" => '',
			"exter_invoke_ip" => '',

			//"it_b_pay" => '30m',

			"show_url" => get_setting('base_url'),
			"extra_common_param" => $extra_common_param,

			"royalty_type" => '',
			"royalty_parameters" => ''
		);

		//除去待签名参数数组中的空值和签名参数
		$parameter = $this->paraFilter($parameter);

		//对待签名参数数组排序
		$para_sort = $this->argSort($parameter);

		return $this->buildMysign($para_sort, get_setting('alipay_key'));
	}

	public function createLoginSign($return_url)
	{
		$parameter = array(
			"service" => "alipay.auth.authorize",
			"target_service" => "user.auth.quick.login",
			"partner" => trim(get_setting('alipay_partner')),
			"_input_charset" => 'utf-8',
			"return_url" => $return_url
		);

		//除去待签名参数数组中的空值和签名参数
		$parameter = $this->paraFilter($parameter);

		//对待签名参数数组排序
		$para_sort = $this->argSort($parameter);

		return $this->buildMysign($para_sort, get_setting('alipay_key'));
	}

	/**
	 * 针对notify_url验证消息是否是支付宝发出的合法消息
	 * @return 验证结果
	 */
	public function verifyNotify()
	{
		if (empty($_POST))
		{ //判断POST来的数组是否为空
			return false;
		}
		else
		{
			//生成签名结果
			$mysign = $this->getReturnSign($_POST);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';

			if (! empty($_GET["notify_id"]))
			{
				//$responseTxt = $this->getResponse($_GET["notify_id"]);
			}

			//判断veryfy_result是否为ture，生成的签名结果my sign与获得的签名结果sign是否一致
			//$veryfy_result的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//mysign与sign不等，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if ($responseTxt == 'true' and $mysign == $_POST["sign"])
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	public function verifyReturn()
	{
		if (empty($_GET))
		{ //判断POST来的数组是否为空
			return false;
		}
		else
		{
			// 删除 Anwsion 系统参数
			unset($_GET['c'], $_GET['app'], $_GET['act']);

			//生成签名结果
			$mysign = $this->getReturnSign($_GET);

			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';

			if (! empty($_GET["notify_id"]))
			{
				//$responseTxt = $this->getResponse($_GET["notify_id"]);
			}

			//判断veryfy_result是否为ture，生成的签名结果my sign与获得的签名结果sign是否一致
			//$veryfy_result的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//mysign与sign不等，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if ($responseTxt == 'true' and $mysign == $_GET["sign"])
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * 根据反馈回来的信息，生成签名结果
	 * @param $para_temp 通知返回来的参数数组
	 * @return 生成的签名结果
	 */
	public function getReturnSign($para_temp)
	{
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//生成签名结果
		$mysign = $this->buildMysign($para_sort, get_setting('alipay_key'));

		return $mysign;
	}

	/**
	 * 获取远程服务器ATN结果,验证返回URL
	 * @param $notify_id 通知校验ID
	 * @return 服务器ATN结果
	 * 验证结果集：
	 * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
	 * true 返回正确信息
	 * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
	 */
	public function getResponse($notify_id)
	{
		$transport = strtolower(trim(get_setting('alipay_transport')));
		$partner = trim(get_setting('alipay_partner'));
		$veryfy_url = '';
		if ($transport == 'https')
		{
			$veryfy_url = 'https://www.alipay.com/cooperate/gateway.do?service=notify_verify&';
		}
		else
		{
			$veryfy_url = 'http://notify.alipay.com/trade/notify_query.do?';
		}

		$veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" . $notify_id;

		return $this->getHttpResponse($veryfy_url);
		//return file_get_contents($veryfy_url);
	}

	public function getHttpResponse($url)
	{
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$responseText = curl_exec($curl);

		curl_close($curl);

		$responseText = trim(trim($responseText, "\r\n\r\n"), "\r\n");

		return $responseText;
	}

	public function createCloseTradeSign($out_order_no, $trade_no = '')
	{
		$parameter = array(
			'service' => 'close_trade',
			'partner' => trim(get_setting('alipay_partner')),
			'trade_no' => $trade_no,
			'out_order_no' => $out_order_no,
			'_input_charset' => 'utf-8'
		);

		//除去待签名参数数组中的空值和签名参数
		$parameter = $this->paraFilter($parameter);

		//对待签名参数数组排序
		$para_sort = $this->argSort($parameter);

		return $this->buildMysign($para_sort, get_setting('alipay_key'));
	}

	/**
	 * 关闭交易 ($out_order_no 网站订单号, $trade_no 支付宝订单号, 二选一)
	 * @return 验证结果
	 */
	public function closeTrade($out_order_no, $trade_no = '')
	{
		if ($alipay_callback = curl_get_contents('https://mapi.alipay.com/gateway.do?service=close_trade&sign=' . $this->createCloseTradeSign($out_order_no, $trade_no) . '&order_no=' . $trade_no . '&out_order_no=' . $out_order_no . '&partner=' . get_setting('alipay_partner') . '&_input_charset=' . get_setting('alipay_input_charset') . '&sign_type=MD5'))
		{
			$alipay_callback_result = (array)simplexml_load_string($alipay_callback, 'SimpleXMLElement', LIBXML_NOCDATA);

			if ($alipay_callback_result['is_success'] == 'T')
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * 生成签名结果
	 * @param $sort_para 要签名的数组
	 * @param $key 支付宝交易安全校验码
	 * @param $sign_type 签名类型 默认值：MD5
	 * return 签名结果字符串
	 */
	public function buildMysign($sort_para, $key, $sign_type = "MD5")
	{
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($sort_para);
		//把拼接后的字符串再与安全校验码直接连接起来
		$prestr = $prestr . $key;
		//把最终的字符串签名，获得签名结果
		$mysgin = $this->sign($prestr, $sign_type);

		return $mysgin;
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	public function createLinkstring($para)
	{
		foreach ($para as $key => $val)
		{
			$arg .= $key . "=" . $val . "&";
		}
		//去掉最后一个&字符
		$arg = substr($arg, 0, count($arg) - 2);

		//如果存在转义字符，那么去掉转义
		if (get_magic_quotes_gpc())
		{
			$arg = stripslashes($arg);
		}

		return $arg;
	}

	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	public function paraFilter($para)
	{
		$para_filter = array();

		while (list($key, $val) = each($para))
		{
			if ($key == "sign" || $key == "sign_type" || $val == "")
				continue;
			else
				$para_filter[$key] = $para[$key];
		}
		return $para_filter;
	}

	/**
	 * 对数组排序
	 * @param $para 排序前的数组
	 * return 排序后的数组
	 */
	public function argSort($para)
	{
		ksort($para);
		reset($para);
		return $para;
	}

	/**
	 * 签名字符串
	 * @param $prestr 需要签名的字符串
	 * @param $sign_type 签名类型 默认值：MD5
	 * return 签名结果
	 */
	public function sign($prestr, $sign_type = 'MD5')
	{
		if ($sign_type == 'MD5')
		{
			$sign = md5($prestr);
		}
		elseif ($sign_type == 'DSA')
		{
			//DSA 签名方法待后续开发
			die("DSA 签名方法待后续开发，请先使用MD5签名方式");
		}
		else
		{
			die("支付宝暂不支持" . $sign_type . "类型的签名方式");
		}

		return $sign;
	}
}