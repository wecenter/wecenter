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
	
	var $language_characteristic = array(
		'ok' => array(
			'好', '是', '恩', '可', '行', '中', '要', '哦', '嗯', '确认', '确定'
		),
		
		'cancel' => array(
			'不', '别', '算了', '取消'
		),
		
		'bad' => array(
			'fuck', 'shit', '狗屎', '婊子', '贱', '你妈', '你娘', '你祖宗', '滚'
		),
	);
	
	public function fetch_message()
	{
		$post_data = file_get_contents('php://input');
		
		// extract post data
		if (! empty($post_data))
		{
			$post_object = (array)simplexml_load_string($post_data, 'SimpleXMLElement', LIBXML_NOCDATA);
			
			return array(
				'fromUsername' => $post_object['FromUserName'],
				'toUsername' => $post_object['ToUserName'],
				'content' => trim($post_object['Content']),
				'time' => time(),
				'msgType' => $post_object['MsgType'],
				'event' => $post_object['Event'],
				'eventKey' => $post_object['EventKey']
			);
		}
	}
	
	public function response_message($input_message)
	{
		switch ($input_message['msgType'])
		{
			case 'event':
				switch ($input_message['event'])
				{
					case 'subscribe':
						$response_message = '您已经成功关注 ' . get_setting('site_name') . ', 您可以随意输入您想问的问题, 会有意想不到的结果等着您! 是否需要指令帮助?';
						$action = 'help';
					break;
				}
			break;
			
			default:
				if ($this->is_language($input_message['content'], 'ok'))
				{
					$response_message = $this->process_last_action($input_message['fromUsername']);
				}
				else if ($this->is_language($input_message['content'], 'cancel'))
				{
					$this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");
					
					$response_message = '好的, 还有什么可以帮您的吗?';
				}
				else if ($response_message = $this->message_parser($input_message))
				{
					// Success...
				}
				else if ($this->is_language($input_message['content'], 'bad'))
				{
					$response_message = '说脏话都不是好孩子!';
				}
				else if ($search_result = $this->model('search')->search_questions($input_message['content'], null, 6))
				{
					$response_message = '下列内容可以帮到您么:' . "\n";
						
					foreach ($search_result AS $key => $val)
					{
						if ($this->model('search')->is_hight_similar($input_message['content'], $val['question_content']))
						{
							if ($val['best_answer'])
							{
								$answer_list = $this->model('answer')->get_answer_list_by_question_id($val['question_id'], 1, 'answer.answer_id = ' . (int)$val['best_answer']);
							}
							else
							{
								$answer_list = $this->model('answer')->get_answer_list_by_question_id($val['question_id'], 1, null, 'agree_count DESC');
							}
								
							$response_message = $answer_list[0]['answer_content'];
												
							break;
						}
						else
						{
							$response_message .= "\n" . '• <a href="' . get_js_url('/m/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a>' . "\n";
						}
					}
					
					if (!$answer_list)
					{
						$response_message .= "\n\n问题没有人提到过? 需要帮忙么?";
						
						$action = 'publish';
					}
				}
				else
				{
					if ($response = $this->func_parser($input_message['fromUsername'], $input_message['content']))
					{
						$response_message = $response['message'];
						
						$action = $response['action'];
					}
					else
					{
						$response_message = '您的问题没有人提到过, 需要帮忙么?';
						
						$action = 'publish';
					}
				}
			break;
		}
			
		echo $this->create_response($input_message, $response_message, $action);
		die;
	}
	
	public function create_response($input_message, $response_message, $action = null)
	{
		if ($action)
		{
			$this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");
		
			$this->insert('weixin_message', array(
				'weixin_id' => $input_message['fromUsername'],
				'content' => $input_message['content'],
				'action' => $action,
				'time' => time()
			));
		}
		
		return sprintf($this->text_tpl, $input_message['fromUsername'], $input_message['toUsername'], $input_message['time'], 'text', $response_message);
	}
	
	public function message_parser($input_message)
	{
		$message_code = strtoupper(trim($input_message['content']));
		
		switch ($message_code)
		{
			default:
				if (cjk_strlen($input_message['content']) > 2)
				{
					if ($user_info = $this->model('account')->get_user_info_by_username($input_message['content']))
					{
						$response_message = '用户 ' . $input_message['content'] . ' 的资料:';
						
						if ($user_info['signature'])
						{
							$response_message .= "\n\n介绍: " . $user_info['signature'];
						}
						
						if ($user_info['province'])
						{
							$response_message .= "\n\n现居: " . $user_info['province'] . ', ' . $user_info['city'];
						}
						
						if ($job_info = $this->model('account')->get_jobs_by_id($user_info['job_id']))
						{
							$response_message .= "\n\n职位: " . $job_info['job_name'];
						}
						
						$response_message .= "\n\n威望: " . $user_info['reputation'] . "\n\n赞同: " . $user_info['agree_count'] . "\n\n感谢: " . $user_info['thanks_count'] . "\n\n最后活跃: " . date_friendly($user_info['last_active']);
					}
					
					if ($topic_info = $this->model('topic')->get_topic_by_title($input_message['content']))
					{
						if ($response_message)
						{
							$response_message .= "\n\n============\n\n关于 " . $input_message['content'] . " 的话题:\n\n";
						}
						
						$response_message .= strip_tags($topic_info['topic_description']);
					}
				}
			break;
			
			case 'H':
			case '?':
			case 'HELP':
				$response_message = "支持的指令: \n\n绑定状态 - 查询微信绑定状态\n解除绑定 - 解除微信绑定\n我的问题 - 显示我的提问\n最新通知 - 显示最新通知";
			break;
			
			case '通知':
			case '最新通知':
				if ($user_info = $this->model('account')->get_user_info_by_weixin_id($input_message['fromUsername']))
				{
					if ($notifications = $this->model('notify')->list_notification($user_info['uid'], 0, 5))
					{
						$response_message = '最新通知:';
						
						foreach($notifications AS $key => $val)
						{
							$response_message .= "\n\n• " . $val['message'];
						}	
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
			
			case '问题':
			case '我的问题':
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
			
			case '绑定':
			case '绑定状态':
				if ($user_info = $this->model('account')->get_user_info_by_weixin_id($input_message['fromUsername']))
				{
					$response_message = '你的微信帐号绑定社区帐号: ' . $user_info['user_name'];
				}
				else
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请登录网站绑定';
				}
			break;
			
			case '解除绑定':
				$response_message = $this->weixin_unbind($input_message['fromUsername']);
			break;
		}
		
		return $response_message;
	}
	
	public function func_parser($weixin_id, $message_content)
	{
		$func_code = strtoupper(substr($message_content, 0, 4));
		$func_param_original = trim(substr($message_content, 4));
		$func_param = strtoupper($func_param_original);
		
		switch ($func_code)
		{			
			// 查询用户动态
			case 'INFO':
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
					$response_message = '目前没有找到相关用户';
				}
			break;
			
			// 绑定认证
			case 'BIND':
				if ($this->model('account')->get_user_info_by_weixin_id($weixin_id))
				{
					$response_message = '微信帐号已经与一个账户绑定, 是否解除绑定?';
					
					$action = 'unbind';
				}
				else if ($weixin_valid = $this->fetch_row('weixin_valid', "`code` = '" . $this->quote($func_param) . "'"))
				{
					$this->update('users', array(
						'weixin_id' => $weixin_id
					), 'uid = ' . intval($weixin_valid['uid']));
					
					$this->delete('weixin_valid', 'id = ' . intval($weixin_valid['id']));
					
					$response_message = '微信帐号绑定成功';
				}
				else
				{
					$response_message = '微信绑定代码无效';
				}
			break;
		}
		
		return array(
			'message' => $response_message,
			'action' => $action
		);
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
	
	public function is_language($string, $type)
	{
		if (!$characteristic = $this->language_characteristic[$type])
		{
			return false;
		}
		
		$string = strtolower($string);
		
		foreach ($characteristic AS $key => $text)
		{
			if (strstr($string, $text))
			{
				return true;
			}
		}
	}
	
	public function process_last_action($weixin_id)
	{
		if (!$last_action = $this->get_last_message($weixin_id))
		{
			return '这是地球语言么?';
		}
		
		$this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");
		
		switch ($last_action['action'])
		{
			case 'help':
				$response_message = "支持的指令: \n\n绑定状态 - 查询微信绑定状态\n解除绑定 - 解除微信绑定\n我的问题 - 显示我的提问\n最新通知 - 显示最新通知";
			break;
			
			case 'publish':
				if (!$user_info = $this->model('account')->get_user_info_by_weixin_id($weixin_id))
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请登录网站绑定';
				}
				else
				{
					if ($this->user_info['integral'] < 0 AND get_setting('integral_system_enabled') == 'Y')
					{
						$response_message = AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作');
					}
					else
					{
						$this->model('publish')->publish_question($last_action['content'], '', 1, $user_info['uid']);
						
						$response_message = '您的问题已提交，晚点您可以输入 "我的问题" 或点击菜单我的问题查看';
					}
				}
			break;
			
			case 'unbind':
				$response_message = $this->message_parser(array(
					'content' => '解除绑定',
					'fromUsername' => $weixin_id
				));
			break;
			
			default:
				$response_message = '您好, 请问需要什么帮助?';
			break;
		}
		
		return $response_message;
	}
	
	public function get_last_message($weixin_id)
	{
		return $this->fetch_row('weixin_message', "weixin_id = '" . $this->quote($weixin_id) . "'");
	}
}
