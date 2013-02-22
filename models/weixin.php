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
			
			if ($keyword != 'Hello2BizUser')
			{
				if ($search_result = $this->model('search')->search_questions($keyword, null, 6))
				{
					$contentStr = '为您找到下列相关问题:' . "\n";
					
					foreach ($search_result AS $key => $val)
					{
						$contentStr .= "\n" . '• <a href="' . get_js_url('/m/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a>' . "\n";
						
						if ($key == 0 AND $val['answer_count'] > 0)
						{
							$contentStr .= "--------------------\n";
							
							if ($val['best_answer'])
							{
								$answer_list = $this->model('answer')->get_answer_list_by_question_id($val['question_id'], 1, 'AND answer.answer_id = ' . (int)$val['best_answer']);
							}
							else
							{
								$answer_list = $this->model('answer')->get_answer_list_by_question_id($val['question_id'], 1, null, 'agree_count DESC');
							}
							
							$contentStr .= "最佳答案: \n\n" . cjk_substr($answer_list[0]['answer_content'], 0, 128, 'UTF-8', '...') . "\n";
							
							$contentStr .= "--------------------\n";
						}
					}
				}
				else
				{
					$contentStr = '没有找到相关问题';	
				}
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
		if (!get_setting('weixin_mp_token'))
		{
			return false;
		}
		
		$tmpArr = array(
			get_setting('weixin_mp_token'), 
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
