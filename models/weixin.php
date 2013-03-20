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
	var $text_tpl = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content><FuncFlag>0</FuncFlag></xml>';
	
	public function fetch_message()
	{
		$postStr = file_get_contents('php://input');
		
		//extract post data
		if (! empty($postStr))
		{
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			
			return array(
				'fromUsername' => $postObj->FromUserName,
				'toUsername' => $postObj->ToUserName,
				'content' => trim($postObj->Content),
				'time' => time(),
				'msgType' => $postObj->MsgType,
				'eventKey' => $postObj->EventKey
			);
		}
	}
	
	public function response_message($input_message = array())
	{
		if ($input_message['msgType'] == 'subscribe')
		{
			$response_message = '您已经成功关注 ' . get_setting('site_name') . ',您可以随意输入您想问的问题,会有意想不到的结果等着您! 如果需要其他使用帮助,请输入 FN99';
		}
		else
		{
			if ($search_result = $this->model('search')->search_questions($input_message['content'], null, 6))
			{
				$response_message = '为您找到下列相关问题:' . "\n";
					
				foreach ($search_result AS $key => $val)
				{
					$response_message .= "\n" . '• <a href="' . get_js_url('/m/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a>' . "\n";
						
					if ($key == 0 AND $val['answer_count'] > 0)
					{
						$response_message .= "--------------------\n";
							
						if ($val['best_answer'])
						{
							$answer_list = $this->model('answer')->get_answer_list_by_question_id($val['question_id'], 1, 'answer.answer_id = ' . (int)$val['best_answer']);
						}
						else
						{
							$answer_list = $this->model('answer')->get_answer_list_by_question_id($val['question_id'], 1, null, 'agree_count DESC');
						}
							
						$response_message .= "最佳答案: \n\n" . cjk_substr($answer_list[0]['answer_content'], 0, 128, 'UTF-8', '...') . "\n";
							
						$response_message .= "--------------------\n";
					}
				}
			}
			else
			{
				$response_message = "抱歉, 没有找到相关问题, 请您替换关键词重新检索\n如果您要继续提问，可以<a href='" . get_js_url('/m/add_question/question_content-' . urlencode($input_message['content'])) . "'>点击这里提问</a>. 您也可以通过输入: FNNF 查看最新通知";
			}
		}
			
		echo sprintf($this->text_tpl, $input_message['fromUsername'], $input_message['toUsername'], $input_message['time'], 'text', $response_message);
		die;
	}
	
	public function func_parser($input_message = array())
	{
		$func_code = strtoupper(substr($input_message['content'], 2, 2));
		$func_param_original = trim(substr($input_message['content'], 4));
		$func_param = strtoupper($func_param_original);
		
		switch ($func_code)
		{
			default:
				$response_message = "代码无效, 支持的代码: \nFN00 - 查询微信绑定状态\nFN02 - 解除微信绑定\nFN10 - 我的提问\nFN11 - 最新通知\nFN20用户名 - 查询用户动态";
			break;
			
			case '99':
				$response_message = "代码无效, 支持的代码: \nFN00 - 查询微信绑定状态\nFN02 - 解除微信绑定\nFN10 - 我的提问\nFN11 - 最新通知\nFN20用户名 - 查询用户动态";
			break;
			
			case '11':
				if ($user_info = $this->model('account')->get_user_info_by_weixin_id($input_message['fromUsername']))
				{
					if ($notifications = $this->model('notify')->list_notification($user_info['uid'], 0, 5))
					{
						TPL::assign('list', $notifications);
					
						$response_message = "最新通知:" . str_replace(array("\r", "\n", '	', '•'), array('', '', '', "\n\n•"),  TPL::output('weixin/notifications', false));
					}
					else
					{
						$response_message = '暂时没有新通知';
					}
				}
				else
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请登录网站绑定';
				}
			break;
			
			case '10':
				if ($user_info = $this->model('account')->get_user_info_by_weixin_id($input_message['fromUsername']))
				{
					if ($user_actions = $this->model('account')->get_user_actions($user_info['uid'], 5, 101))
					{
						$response_message = "我的提问: \n";
						
						foreach ($user_actions AS $key => $val)
						{
							$response_message .= "\n" . '• <a href="' . get_js_url('/m/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a> (' . $val['answer_count'] . ' 个回答)' . "\n";
							
							if ($val['answer_count'] > 0)
							{
								$response_message .= "--------------------\n";
									
								if ($val['best_answer'])
								{
									$answer_list = $this->model('answer')->get_answer_list_by_question_id($val['question_id'], 1, 'answer.answer_id = ' . (int)$val['best_answer']);
								}
								else
								{
									$answer_list = $this->model('answer')->get_answer_list_by_question_id($val['question_id'], 1, 'answer.uninterested_count < ' . get_setting('uninterested_fold') . ' AND answer.force_fold = 0', 'add_time DESC');
								}
									
								$response_message .= "最新答案: \n\n" . cjk_substr($answer_list[0]['answer_content'], 0, 128, 'UTF-8', '...') . "\n";
									
								$response_message .= "--------------------\n";
							}
						}
					}
					else
					{
						$response_message = '你还没有进行提问';
					}
				}
				else
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请登录网站绑定';
				}
			break;
			
			case '20':
				if ($user_info = $this->model('account')->get_user_info_by_username($func_param_original))
				{
					if ($user_actions = $this->model('account')->get_user_actions($user_info['uid'], 5, 101))
					{
						$response_message = $user_info['user_name'] . "的动态: \n";
						
						foreach ($user_actions AS $key => $val)
						{
							$response_message .= "\n" . '• ' . $val['last_action_str'] . ', <a href="' . get_js_url('/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a> (' . date_friendly($val['add_time']) . ')' . "\n";
						}
					}
					else
					{
						$response_message = '该用户目前没有动态';
					}
				}
				else
				{
					$response_message = '没有找到相关用户';
				}
			break;
			
			// 绑定认证
			case '00':
				if ($user_info = $this->model('account')->get_user_info_by_weixin_id($input_message['fromUsername']))
				{
					$response_message = '你的微信帐号绑定社区帐号: ' . $user_info['user_name'];
				}
				else
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请登录网站绑定';
				}
			break;
			
			// 绑定认证
			case '01':
				$response_message = $this->weixin_valid($func_param, $input_message['fromUsername']);
			break;
			
			// 解除绑定
			case '02':
				$response_message = $this->weixin_unbind($input_message['fromUsername']);
			break;
		}
		
		echo sprintf($this->text_tpl, $input_message['fromUsername'], $input_message['toUsername'], $input_message['time'], 'text', $response_message);
		die;
	}
	
	public function create_weixin_valid($uid)
	{
		if ($weixin_valid = $this->fetch_row('weixin_valid', "uid = " . intval($uid)))
		{
			return $weixin_valid['code'];
		}
		else
		{
			$valid_code = strtoupper(fetch_salt(6));
			
			while($this->fetch_row('weixin_valid', "`code` = '" . $this->quote($valid_code) . "'"))
			{
				$valid_code = strtoupper(fetch_salt(6));
			}
			
			$this->insert('weixin_valid', array(
				'uid' => intval($uid),
				'code' => $valid_code
			));
			
			return $valid_code;
		}
	}
	
	public function weixin_valid($param, $weixin_id)
	{
		if ($this->model('account')->get_user_info_by_weixin_id($weixin_id))
		{
			return '微信帐号已经与一个账户绑定, 解绑请回复 FN02';
		}
		else if ($weixin_valid = $this->fetch_row('weixin_valid', "`code` = '" . $this->quote($param) . "'"))
		{
			$this->update('users', array(
				'weixin_id' => $weixin_id
			), 'uid = ' . intval($weixin_valid['uid']));
			
			$this->delete('weixin_valid', 'id = ' . intval($weixin_valid['id']));
			
			return '微信帐号绑定成功';
		}
		
		return '微信绑定代码无效';
	}
	
	public function weixin_unbind($weixin_id)
	{
		$this->update('users', array('weixin_id' => ''), "`weixin_id` = '" . $this->quote($weixin_id) . "'");
		
		return '微信绑定解除成功';
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
