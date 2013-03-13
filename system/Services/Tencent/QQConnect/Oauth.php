<?php
/* PHP SDK
 * @version 2.0.0
 * @author connect@qq.com
 * @copyright © 2013, Tencent Corporation. All rights reserved.
 */
 
if (!defined('IN_ANWSION'))
{
	die;
}

class Services_Tencent_QQConnect_Oauth
{
	
	const VERSION = "2.0";
	const GET_AUTH_CODE_URL = "https://graph.qq.com/oauth2.0/authorize";
	const GET_ACCESS_TOKEN_URL = "https://graph.qq.com/oauth2.0/token";
	const GET_OPENID_URL = "https://graph.qq.com/oauth2.0/me";
	
	public $urlUtils;

	function __construct()
	{
		$this->urlUtils = load_class('Services_Tencent_QQConnect_URL');
	}

	public function qq_login($appid, $callback, $scope = '')
	{
		//-------生成唯一随机串防CSRF攻击
		$state = md5(uniqid(rand(), TRUE));
		
		//-------构造请求参数列表
		$keysArr = array(
			"response_type" => "code", 
			"client_id" => $appid, 
			"redirect_uri" => $callback, 
			"state" => $state, 
			"scope" => $scope
		);
		
		return $this->urlUtils->combineURL(self::GET_AUTH_CODE_URL, $keysArr);
	}

	public function qq_callback($appid, $callback, $appkey)
	{

        //-------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => $appid,
            "redirect_uri" => urlencode($callback),
            "client_secret" => $appkey,
            "code" => $_GET['code']
        )
		;
		
		//------构造请求access_token的url
		$token_url = $this->urlUtils->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
		$response = $this->urlUtils->get_contents($token_url);
		
		if (strpos($response, "callback") !== false)
		{
			
			$lpos = strpos($response, "(");
			$rpos = strrpos($response, ")");
			$response = substr($response, $lpos + 1, $rpos - $lpos - 1);
			$msg = json_decode($response);
			
			if (isset($msg->error))
			{
				die($msg->error . ': ' . $msg->error_description);
			}
		}
		
		$params = array();
		parse_str($response, $params);
		
		AWS_APP::session()->QQConnect['access_token'] = $params['access_token'];
		
		return $params['access_token'];
	
	}

	public function get_openid()
	{
		
		//-------请求参数列表
		$keysArr = array(
			"access_token" => AWS_APP::session()->QQConnect['access_token']
		);
		
		$graph_url = $this->urlUtils->combineURL(self::GET_OPENID_URL, $keysArr);
		$response = $this->urlUtils->get_contents($graph_url);
		
		//--------检测错误是否发生
		if (strpos($response, "callback") !== false)
		{
			
			$lpos = strpos($response, "(");
			$rpos = strrpos($response, ")");
			$response = substr($response, $lpos + 1, $rpos - $lpos - 1);
		}
		
		$user = json_decode($response);
		
		if (isset($user->error))
		{
			die($user->error . ': ' . $user->error_description);
		}
		
		return $user->openid;
	
	}
}
