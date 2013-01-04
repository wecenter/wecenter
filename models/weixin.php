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

if (! defined('IN_ANWSION'))
{
	die();
}

define('WEIXIN_TOKEN', 'weixin');

class weixin_class extends AWS_MODEL
{
	public function response_message()
	{
		$postStr = file_get_contents('php://input');
		
		//extract post data
		if (! empty($postStr))
		{
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$fromUsername = $postObj->FromUserName;
			$toUsername = $postObj->ToUserName;
			$keyword = trim($postObj->Content);
			$time = time();
			
			$textTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content><FuncFlag>0</FuncFlag></xml>";
			
			$msgType = 'text';
			
			if (! empty($keyword))
			{
				$contentStr = $this->model('system')->analysis_keyword($keyword);
			}
			else
			{
				$contentStr = '欢迎来到 ' . get_setting('site_name');
			}
			
			$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
			
			echo $resultStr;
			die;
		}
	}

	public function check_signature($signature, $timestamp, $nonce)
	{
		$tmpArr = array(
			WEIXIN_TOKEN, 
			$timestamp, 
			$nonce
		);
		
		sort($tmpArr);
		
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		
		if ($tmpStr == $signature)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}
