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

if (! defined('IN_ANWSION'))
{
	die();
}

class weixin_class extends AWS_MODEL
{
	var $text_tpl = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content><FuncFlag>0</FuncFlag></xml>';
	
	var $image_tpl = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><ArticleCount>%s</ArticleCount><Articles>%s</Articles><FuncFlag>1</FuncFlag></xml>';
	
	var $image_article_tpl = '<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>';
	
	var $user_info;
	var $user_id;
	
	public function fetch_message()
	{
		$post_data = file_get_contents('php://input');
		
		// extract post data
		if (! empty($post_data))
		{
			$post_object = (array)simplexml_load_string($post_data, 'SimpleXMLElement', LIBXML_NOCDATA);
			
			$input_message = array(
				'fromUsername' => $post_object['FromUserName'],
				'toUsername' => $post_object['ToUserName'],
				'content' => trim($post_object['Content']),
				'time' => time(),
				'msgType' => $post_object['MsgType'],
				'event' => $post_object['Event'],
				'eventKey' => $post_object['EventKey']
			);
			
			if ($user_info = $this->model('account')->get_user_info_by_weixin_id($input_message['fromUsername']))
			{
				$this->user_info = $user_info;
				$this->user_id = $user_info['uid'];
				
				$user_group = $this->model('account')->get_user_group($user_info['group_id'], $user_info['reputation_group']);
				
				$this->user_info['permission'] = $user_group['permission'];
			}
			
			return $input_message;
		}
	}
	
	public function publish_approval_valid()
	{
		if ($this->user_info['permission']['publish_approval'] == 1)
		{
			if (!$this->user_info['permission']['publish_approval_time']['start'] AND !$this->user_info['permission']['publish_approval_time']['end'])
			{
				return true;
			}
			
			if ($this->user_info['permission']['publish_approval_time']['start'] < $this->user_info['permission']['publish_approval_time']['end'])
			{
				if (date('H') > $this->user_info['permission']['publish_approval_time']['start'] AND date('H') < $this->user_info['permission']['publish_approval_time']['end'])
				{
					return true;
				}
			}
			
			if ($this->user_info['permission']['publish_approval_time']['start'] > $this->user_info['permission']['publish_approval_time']['end'])
			{
				if (date('H') > $this->user_info['permission']['publish_approval_time']['start'] OR date('H') < $this->user_info['permission']['publish_approval_time']['end'])
				{
					return true;
				}
			}
			
			if ($this->user_info['permission']['publish_approval_time']['start'] == $this->user_info['permission']['publish_approval_time']['end'])
			{
				if (date('H') == $this->user_info['permission']['publish_approval_time']['start'])
				{
					return true;
				}
			}
		}
		
		return false;
	}
	
	public function response_message($input_message)
	{
		switch ($input_message['msgType'])
		{
			case 'event':
				if (substr($input_message['eventKey'], 0, 8) == 'COMMAND_')
				{
					if (strstr($input_message['eventKey'], '__'))
					{
						$event_key = explode('__', substr($input_message['eventKey'], 8));
						
						$content = $event_key[0];
						$param = $event_key[1];
					}
					else
					{
						$content = substr($input_message['eventKey'], 8);
					}
					
					if ($response = $this->message_parser(array(
						'content' => $content,
						'fromUsername' => $input_message['fromUsername'],
						'param' => $param
					)))
					{
						$response_message = $response['message'];
						$action = $response['action'];
					}
				}
				else if (substr($input_message['eventKey'], 0, 5) == 'RULE_')
				{
					if ($reply_rule = $this->get_reply_rule_by_event_key(substr($input_message['eventKey'], 5)))
					{
						$response_message = $this->create_response_by_reply_rule_keyword($reply_rule['keyword']);
					}
					else
					{
						$response_message = '菜单指令错误';
					}
				}
				else
				{
					switch ($input_message['event'])
					{
						case 'subscribe':
							//$response_message = get_setting('weixin_subscribe_message');
							
							if (get_setting('weixin_subscribe_message_key'))
							{
								$response_message = $this->create_response_by_reply_rule_keyword(get_setting('weixin_subscribe_message_key'));
							}
						break;
					}
				}
			break;
			
			default:
				if ($response_message = $this->create_response_by_register_keyword($input_message))
				{
					// resiter user
				}
				else if ($response_message = $this->create_response_by_reply_rule_keyword($input_message['content']))
				{
					// response by reply rule keyword...
				}
				else if ($response_message = $this->create_response_by_publish_rule_keyword($input_message['content'], $input_message))
				{
					// response by publish rule keyword...
				}
				else if ($response = $this->message_parser($input_message))
				{
					// Success...
					$response_message = $response['message'];
					$action = $response['action'];
				}
				else if ($this->is_language($input_message['content'], 'ok'))
				{
					$response = $this->process_last_action($input_message['fromUsername']);
					
					$response_message = $response['message'];
					$action = $response['action'];
				}
				else if ($this->is_language($input_message['content'], 'cancel'))
				{
					$this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");
					
					$response_message = '好的, 还有什么可以帮您的吗?';
				}
				else if ($this->is_language($input_message['content'], 'bad'))
				{
					$response_message = AWS_APP::config()->get('weixin')->bad_language_message;
				}
				else if ($search_result = $this->model('search')->search_questions($input_message['content'], null, 6))
				{
					$response_message = '下列内容可以帮到您么:' . "\n";
					
					foreach ($search_result AS $key => $val)
					{
						$response_message .= "\n" . '• <a href="' . get_js_url('/m/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a>' . "\n";
					}
					
					if (cjk_strlen($input_message['content']) > 5)
					{
						$response_message .= "\n\n" . AWS_APP::config()->get('weixin')->publish_message;
						
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
						if (!$response_message = $this->create_response_by_reply_rule_keyword(get_setting('weixin_no_result_message_key')))
						{
							$response_message = AWS_APP::config()->get('weixin')->publish_message;
						}
					
						$action = 'publish';
					}
				}
			break;
		}
		
		if (is_array($response_message))
		{
			echo $this->create_image_response($input_message, $response_message);
		}
		else
		{
			echo $this->create_response($input_message, $response_message, $action);
		}
		
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
	
	public function create_image_response($input_message, $image_data = array())
	{
		foreach ($image_data AS $key => $val)
		{
			if ($article_tpl)
			{
				$image_size = 'square';
			}
			else
			{
				unset($image_size);
			}
			
			if (substr($val['image_file'], 0, 4) == 'http')
			{
				$image_file = $val['image_file'];
			}
			else
			{
				$image_file = $this->get_weixin_rule_image($val['image_file'], $image_size);
			}
			
			$article_tpl .= sprintf($this->image_article_tpl, $val['title'], $val['description'], $image_file, $val['link']);
		}
		
		if (!$article_tpl)
		{
			return false;
		}
		
		return sprintf($this->image_tpl, $input_message['fromUsername'], $input_message['toUsername'], $input_message['time'], 'news', sizeof($image_data), $article_tpl);
	}
	
	public function message_parser($input_message, $param = null)
	{
		$message_code = strtoupper(trim($input_message['content']));
		
		if (cjk_strlen($message_code) < 2)
		{
			return false;
		}
		
		switch ($message_code)
		{
			default:
				if (cjk_strlen($input_message['content']) > 1)
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
						
						if ($user_actions = $this->model('account')->get_user_actions($user_info['uid'], 5, 101))
						{
							$response_message .= "\n\n" . $user_info['user_name'] . " 的动态: \n";
							
							foreach ($user_actions AS $key => $val)
							{
								$response_message .= "\n" . '• ' . strip_tags($val['last_action_str']) . ', <a href="' . get_js_url('/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a> (' . date_friendly($val['add_time']) . ')' . "\n";
							}
						}
					}
					
					if ($topic_info = $this->model('topic')->get_topic_by_title($input_message['content']))
					{
						if ($response_message)
						{
							$response_message .= "\n\n============\n\n关于 " . $input_message['content'] . " 的话题:\n\n";
						}
						
						$response_message .= strip_tags($topic_info['topic_description']);
						
						if ($topic_questions = $this->model('question')->get_questions_list(1, 5, 'new', $topic_info['topic_id']))
						{
							$response_message .= $topic_info['topic_title'] . " 话题下的问题: \n";
							
							foreach ($topic_questions AS $key => $val)
							{
								$response_message .= "\n" . '• <a href="' . get_js_url('/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a> (' . date_friendly($val['add_time']) . ')' . "\n";
							}
						}
					}
				}
			break;
			
			case '帮助':
			case 'HELP':
				$response_message = AWS_APP::config()->get('weixin')->help_message;
			break;
			
			case AWS_APP::config()->get('weixin')->command_hot:
			case 'HOT_QUESTION':
				if ($input_message['param'])
				{
					switch (AWS_APP::config()->get('weixin')->key_param_type)
					{
						case 'CATEGORY':
							$category_id = intval($input_message['param']);
						break;
						
						case 'FEATURE':
							$topics_id = $this->model('feature')->get_topics_by_feature_id($input_message['param']);
						break;
					}
				}
				
				if ($question_list = $this->model('question')->get_hot_question($category_id, $topics_id, 7, 1, 10))
				{
					/*response_message .= "热门问题: \n";
							
					foreach ($question_list AS $key => $val)
					{
						$response_message .= "\n" . '• <a href="' . get_js_url('/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a> (' . date_friendly($val['add_time']) . ')' . "\n";
					}*/
					
					foreach ($question_list AS $key => $val)
					{
						if (!$response_message)
						{
							$image_file = AWS_APP::config()->get('weixin')->default_list_image_hot;
						}
						else
						{
							$image_file = get_avatar_url($val['published_uid'], 'max');
						}
						
						$response_message[] = array(
							'title' => $val['question_content'],
							'link' => get_js_url('/question/' . $val['question_id']),
							'image_file' => $image_file
						);
					}
				}
				else
				{
					$response_message = '暂无问题';
				}
			break;
			
			case AWS_APP::config()->get('weixin')->command_new:
			case 'NEW_QUESTION':
				if ($input_message['param'])
				{
					switch (AWS_APP::config()->get('weixin')->key_param_type)
					{
						case 'CATEGORY':
							$category_id = intval($input_message['param']);
						break;
						
						case 'FEATURE':
							$topics_id = $this->model('feature')->get_topics_by_feature_id($input_message['param']);
						break;
					}
				}
				
				if ($question_list = $this->model('question')->get_questions_list(1, 10, 'new', $topics_id, $category_id))
				{
					/*$response_message .= "最新问题: \n";
							
					foreach ($question_list AS $key => $val)
					{
						$response_message .= "\n" . '• <a href="' . get_js_url('/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a> (' . date_friendly($val['add_time']) . ')' . "\n";
					}*/
					
					foreach ($question_list AS $key => $val)
					{
						if (!$response_message)
						{
							$image_file = AWS_APP::config()->get('weixin')->default_list_image_new;
						}
						else
						{
							$image_file = get_avatar_url($val['published_uid'], 'max');
						}
						
						$response_message[] = array(
							'title' => $val['question_content'],
							'link' => get_js_url('/question/' . $val['question_id']),
							'image_file' => $image_file
						);
					}
				}
				else
				{
					$response_message = '暂无问题';
				}
			break;
			
			case AWS_APP::config()->get('weixin')->command_recommend:
			case 'RECOMMEND_QUESTION':
				if ($question_list = $this->model('question')->get_questions_list(1, 10, null, null, null, null, null, true))
				{
					/*$response_message .= "推荐问题: \n";
							
					foreach ($question_list AS $key => $val)
					{
						$response_message .= "\n" . '• <a href="' . get_js_url('/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a> (' . date_friendly($val['add_time']) . ')' . "\n";
					}*/
					
					foreach ($question_list AS $key => $val)
					{
						if (!$response_message)
						{
							$image_file = AWS_APP::config()->get('weixin')->default_list_image_recommend;
						}
						else
						{
							$image_file = get_avatar_url($val['published_uid'], 'max');
						}
						
						$response_message[] = array(
							'title' => $val['question_content'],
							'link' => get_js_url('/question/' . $val['question_id']),
							'image_file' => $image_file
						);
					}
				}
				else
				{
					$response_message = '暂无问题';
				}
			break;
			
			case '最新动态':
			case 'HOME_ACTIONS':
				if ($this->user_id)
				{
					if ($index_actions = $this->model('index')->get_index_focus($this->user_id, 10))
					{
						$response_message = '最新动态:';
						
						foreach ($index_actions AS $key => $val)
						{
							if ($val['associate_action'] == ACTION_LOG::ANSWER_QUESTION OR $val['associate_action'] == ACTION_LOG::ADD_AGREE)
							{
								$response_message .= "\n\n• " . '<a href="' . get_js_url('/m/answer/' . $val['answer_info']['answer_id']) . '">';
							}
							else
							{
								$response_message .= "\n\n• " . '<a href="' . get_js_url('/m/question/' . $val['answer_info']['answer_id']) . '">';
							}
							
							$response_message .= $val['question_content'] . '</a>';
							$response_message .= "\n" . strip_tags($val['last_action_str']);
						}
					}
					else
					{
						$response_message = '暂时没有最新动态';
					}
				}
				else
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请<a href="' . get_js_url('/m/login/?weixin_id=' . base64_encode($input_message['fromUsername'])) . '">点此绑定</a>或<a href="' . get_js_url('/m/register/?weixin_id=' . base64_encode($input_message['fromUsername'])) . '">注册新账户</a>';
				}
			break;
			
			case AWS_APP::config()->get('weixin')->command_notifications:
			case 'NOTIFICATIONS':
				if ($this->user_id)
				{
					if ($notifications = $this->model('notify')->list_notification($this->user_id, 0, calc_page_limit($param, 5)))
					{
						$response_message = '最新通知:';
						
						foreach($notifications AS $key => $val)
						{
							$response_message .= "\n\n• " . $val['message'];
						}
						
						$response_message .= "\n\n请输入 '更多' 显示其他相关内容";
						
						if (!$param)
						{
							$param = 1;
						}
						
						$action = 'notification-' . ($param + 1);
					}
					else
					{
						$this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");
						
						if ($param > 1)
						{
							$response_message = '没有更多新通知了';
						}
						else
						{
							$response_message = '暂时没有新通知';
						}
					}
				}
				else
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请<a href="' . get_js_url('/m/login/?weixin_id=' . base64_encode($input_message['fromUsername'])) . '">点此绑定</a>或<a href="' . get_js_url('/m/register/?weixin_id=' . base64_encode($input_message['fromUsername'])) . '">注册新账户</a>';
				}
			break;
			
			case AWS_APP::config()->get('weixin')->command_my:
			case 'MY_QUESTION':
				if ($this->user_id)
				{
					if ($user_actions = $this->model('account')->get_user_actions($this->user_id, calc_page_limit($param, 5), 101))
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
									if ($answer_list = $this->model('answer')->get_answer_by_id($val['best_answer']))
									{
										$response_message .= "最新答案: \n\n" . cjk_substr($answer_list['answer_content'], 0, 128, 'UTF-8', '...') . "\n";
									}	
								}
								else
								{
									if ($answer_list = $this->model('answer')->get_answer_list_by_question_id($val['question_id'], 1, 'uninterested_count < ' . get_setting('uninterested_fold') . ' AND force_fold = 0', 'add_time DESC'))
									{
										$response_message .= "最新答案: \n\n" . cjk_substr($answer_list[0]['answer_content'], 0, 128, 'UTF-8', '...') . "\n";
									}
								}
								
								$response_message .= "--------------------\n";
							}
						}
						
						$response_message .= "\n\n请输入 '更多' 显示其他相关内容";
						
						if (!$param)
						{
							$param = 1;
						}
						
						$action = 'my_questions-' . ($param + 1);
					}
					else
					{
						$this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");	
						
						if ($param > 1)
						{
							$response_message = '没有更多提问了';
						}
						else
						{
							$response_message = '你还没有进行提问';
						}
					}
				}
				else
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请<a href="' . get_js_url('/m/login/?weixin_id=' . base64_encode($input_message['fromUsername'])) . '">点此绑定</a>或<a href="' . get_js_url('/m/register/?weixin_id=' . base64_encode($input_message['fromUsername'])) . '">注册新账户</a>';
				}
			break;
			
			case AWS_APP::config()->get('weixin')->command_bind_info:
			case 'BIND_INDO':
				if ($this->user_id)
				{
					$response_message = '你的微信帐号绑定社区帐号: ' . $this->user_info['user_name'];
				}
				else
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请<a href="' . get_js_url('/m/login/?weixin_id=' . base64_encode($input_message['fromUsername'])) . '">点此绑定</a>或<a href="' . get_js_url('/m/register/?weixin_id=' . ($input_message['fromUsername'])) . '">注册新账户</a>';
				}
			break;
			
			case AWS_APP::config()->get('weixin')->command_unbind:
				$response_message = $this->weixin_unbind($input_message['fromUsername']);
			break;
		}
		
		if (!$response_message)
		{
			return false;
		}
		
		return array(
			'message' => $response_message,
			'action' => $action
		);
	}
	
	public function func_parser($weixin_id, $message_content)
	{
		$func_code = strtoupper(substr($message_content, 0, 4));
		$func_param_original = trim(substr($message_content, 4));
		$func_param = strtoupper($func_param_original);
		
		switch ($func_code)
		{			
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
		
		if (!$response_message)
		{
			return false;
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
		if (!$characteristic = AWS_APP::config()->get('weixin')->language_characteristic[$type])
		{
			return false;
		}
		
		$string = trim(strtolower($string));
		
		foreach ($characteristic AS $key => $text)
		{
			if ($type == 'bad')
			{
				if (strstr($string, $text))
				{
					return true;
				}
			}
			else
			{
				if ($string == $text)
				{
					return true;
				}
			}
		}
	}
	
	public function process_last_action($weixin_id)
	{
		if (!$last_action = $this->get_last_message($weixin_id))
		{
			return '您好, 请问需要什么帮助?';
		}
		
		$this->delete('weixin_message', "weixin_id = '" . $this->quote($weixin_id) . "'");
		
		if (strstr($last_action['action'], '-'))
		{
			$last_actions = explode('-', $last_action['action']);
			
			$last_action['action'] = $last_actions[0];
			$last_action_param = $last_actions[1];
		}
		
		switch ($last_action['action'])
		{			
			case 'publish':
				if (!$this->user_id)
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请<a href="' . get_js_url('/m/login/?weixin_id=' . base64_encode($weixin_id)) . '">点此绑定</a>或<a href="' . get_js_url('/m/register/?weixin_id=' . base64_encode($weixin_id)) . '">注册新账户</a>';
				}
				else
				{
					if (!$this->user_info['permission']['publish_question'])
					{
						$response_message = AWS_APP::lang()->_t('你没有权限发布问题');
					}
					else if ($this->user_info['integral'] < 0 AND get_setting('integral_system_enabled') == 'Y')
					{
						$response_message = AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作');
					}
					else
					{
						if (trim($last_action['content'] != ''))
						{
							if ($this->publish_approval_valid())
							{			
								$this->model('publish')->publish_approval('question', array(
									'question_content' => $last_action['content']
								), $this->user_id);
								
								$response_message = AWS_APP::lang()->_t('发布成功, 请等待管理员审核...');
							}
							else
							{
								if ($question_id = $this->model('publish')->publish_question($last_action['content'], '', 1, $this->user_id))
								{
									$this->model('wecenter')->set_wechat_fake_id_by_question($last_action['content'], $question_id);
								}
								
								$response_message = AWS_APP::config()->get('weixin')->publish_success_message;
							}
						}
						else
						{
							$response_message = AWS_APP::lang()->_t('请输入问题标题');
						}
					}
				}
			break;
			
			case 'unbind':
				return $this->message_parser(array(
					'content' => AWS_APP::config()->get('weixin')->command_unbind,
					'fromUsername' => $weixin_id
				));
			break;
			
			case 'my_questions':
				return $this->message_parser(array(
					'content' => AWS_APP::config()->get('weixin')->command_my,
					'fromUsername' => $weixin_id
				), $last_action_param);
			break;
			
			case 'notification':
				return $this->message_parser(array(
					'content' => AWS_APP::config()->get('weixin')->command_notifications,
					'fromUsername' => $weixin_id
				), $last_action_param);
			break;
			
			default:
				$response_message = '您好, 请问需要什么帮助?';
			break;
		}
		
		return array(
			'message' => $response_message,
			'action' => $action
		);
	}
	
	public function get_last_message($weixin_id)
	{
		return $this->fetch_row('weixin_message', "weixin_id = '" . $this->quote($weixin_id) . "' AND `time` > " . (time() - 3600));
	}
	
	public function fetch_reply_rule_list($where = null)
	{
		return $this->fetch_all('weixin_reply_rule', $where, 'keyword ASC');
	}
	
	public function add_reply_rule($keyword, $event_key, $title, $description = '', $link = '', $image_file = '')
	{
		if ($event_key)
		{
			$this->update('weixin_reply_rule', array(
				'event_key' => trim($event_key)
			), "keyword = '" . $this->quote($keyword) . "'");
		}
		
		return $this->insert('weixin_reply_rule', array(
			'keyword' => trim($keyword),
			'event_key' => trim($event_key),
			'title' => $title,
			'description' => $description,
			'image_file' => $image_file,
			'link' => $link,
			'enabled' => 1
		));
	}
	
	public function update_reply_rule_enabled($id, $status)
	{
		return $this->update('weixin_reply_rule', array(
			'enabled' => intval($status)
		), 'id = ' . $id);
	}
	
	public function update_reply_rule_sort($id, $status)
	{
		return $this->update('weixin_reply_rule', array(
			'sort_status' => intval($status)
		), 'id = ' . $id);
	}
	
	public function update_reply_rule($id, $event_key, $title, $description = '', $link = '', $image_file = '')
	{
		if ($event_key)
		{
			if ($reply_rule = $this->get_reply_rule_by_id($id))
			{
				$this->update('weixin_reply_rule', array(
					'event_key' => trim($event_key)
				), "keyword = '" . $this->quote($reply_rule['keyword']) . "'");
			}
		}
		
		return $this->update('weixin_reply_rule', array(
			'event_key' => trim($event_key),
			'title' => $title,
			'description' => $description,
			'image_file' => $image_file,
			'link' => $link
		), 'id = ' . $id);
	}
	
	public function get_reply_rule_by_id($id)
	{
		return $this->fetch_row('weixin_reply_rule', 'id = ' . intval($id));
	}
	
	public function get_reply_rule_by_keyword($keyword)
	{
		return $this->fetch_row('weixin_reply_rule', "`keyword` = '" . trim($this->quote($keyword)) . "'");
	}
	
	public function get_reply_rule_by_event_key($event_key)
	{
		return $this->fetch_row('weixin_reply_rule', "`event_key` = '" . trim($this->quote($event_key)) . "'");
	}
	
	public function create_response_by_reply_rule_keyword($keyword)
	{
		if (!$keyword)
		{
			return false;
		}
		
		// is text message
		if ($reply_rule = $this->fetch_row('weixin_reply_rule', "`keyword` = '" . trim($this->quote($keyword)) . "' AND (`image_file` = '' OR `image_file` IS NULL) AND `enabled` = 1"))
		{
			return $reply_rule['title'];
		}
		
		if ($reply_rule = $this->fetch_all('weixin_reply_rule', "`keyword` = '" . trim($this->quote($keyword)) . "' AND `image_file` <> '' AND `enabled` = 1", 'sort_status ASC', 10))
		{
			return $reply_rule;
		}
	}
	
	public function remove_reply_rule($id)
	{
		if ($reply_rule = $this->get_reply_rule_by_id($id))
		{
			unlink(get_setting('upload_dir') . '/weixin/' . $reply_rule['image_file']);
			unlink(get_setting('upload_dir') . '/weixin/square_' . $reply_rule['image_file']);
			
			return $this->delete('weixin_reply_rule', 'id = ' . intval($id));
		}
	}
	
	public function fetch_publish_rule_list()
	{
		return $this->fetch_all('weixin_publish_rule', null, 'id DESC');
	}
	
	public function add_publish_rule($keyword, $title, $description = '', $link = '', $publish_type = '', $item_id = '', $topics = '', $image_file = '')
	{		
		return $this->insert('weixin_publish_rule', array(
			'keyword' => trim($keyword),
			'title' => $title,
			'description' => $description,
			'image_file' => $image_file,
			'link' => $link,
			'publish_type' => $publish_type,
			'item_id' => intval($item_id),
			'topics' => $topics,
			'enabled' => 1
		));
	}
	
	public function update_publish_rule_enabled($id, $status)
	{
		return $this->update('weixin_publish_rule', array(
			'enabled' => intval($status)
		), 'id = ' . $id);
	}
	
	public function update_publish_rule($id, $title, $description = '', $link = '', $publish_type = '', $item_id = '', $topics = '', $image_file = '')
	{
		return $this->update('weixin_publish_rule', array(
			'title' => $title,
			'description' => $description,
			'image_file' => $image_file,
			'link' => $link,
			'publish_type' => $publish_type,
			'item_id' => intval($item_id),
			'topics' => $topics
		), 'id = ' . intval($id));
	}
	
	public function get_publish_rule_by_id($id)
	{
		return $this->fetch_row('weixin_publish_rule', 'id = ' . intval($id));
	}
	
	public function get_publish_rule_by_keyword($keyword)
	{
		return $this->fetch_row('weixin_publish_rule', "`keyword` = '" . trim($this->quote($keyword)) . "'");
	}
	
	public function create_response_by_register_keyword($input_message)
	{
		$command_register_length = strlen(AWS_APP::config()->get('weixin')->command_register);
		
		if (strtolower(substr($input_message['content'], 0, $command_register_length)) == strtolower(AWS_APP::config()->get('weixin')->command_register))
		{
			if ($this->user_id)
			{
				return '你的微信帐号已绑定社区帐号: ' . $this->user_info['user_name'];
			}
			
			if (get_setting('invite_reg_only') == 'Y')
			{
				return AWS_APP::lang()->_t('本站只能通过邀请注册');
			}
			
			$register_email = trim(substr($input_message['content'], $command_register_length));
			
			if ($this->model('account')->check_email($register_email))
			{
				return AWS_APP::lang()->_t('E-Mail 已经被使用, 或格式不正确');
			}
			
			$register_password = rand(111111111, 999999999);
			
			if (get_setting('ucenter_enabled') == 'Y')
			{
				$result = $this->model('ucenter')->register($register_email, $register_password, $register_email, false);
				
				if (is_array($result))
				{				
					$uid = $result['user_info']['uid'];
				}
				else
				{
					return $result;
				}
			}
			else
			{
				$uid = $this->model('account')->user_register($register_email, $register_password, $register_email, false);
			}
			
			$this->update('users', array(
				'weixin_id' => $input_message['fromUsername']
			), 'uid = ' . intval($uid));
			
			return '注册成功, 请妥善保存登录密码: ' . $register_password;
		}
	}
	
	public function create_response_by_publish_rule_keyword($keyword, $input_message)
	{
		if (!$publish_rule = $this->fetch_all('weixin_publish_rule', "`enabled` = 1"))
		{
			return false;
		}
		
		$keyword = trim($keyword);
		
		foreach ($publish_rule AS $key => $val)
		{
			if (substr($keyword, 0, strlen($val['keyword'])) == $val['keyword'])
			{
				if (!$this->user_id)
				{
					 return '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请<a href="' . get_js_url('/m/login/?weixin_id=' . base64_encode($weixin_id)) . '">点此绑定</a>或<a href="' . get_js_url('/m/register/?weixin_id=' . base64_encode($weixin_id)) . '">注册新账户</a>';
				}
				
				switch ($val['publish_type'])
				{
					case 'question':
						if (! $this->user_info['permission']['publish_question'])
						{
							return AWS_APP::lang()->_t('你没有权限发布问题');
						}
						
						if (get_setting('question_title_limit') > 0 && cjk_strlen(substr($keyword, strlen($val['keyword']))) > get_setting('question_title_limit'))
						{
							return AWS_APP::lang()->_t('问题标题字数不得大于') . ' ' . get_setting('question_title_limit') . ' ' . AWS_APP::lang()->_t('字节');
						}
						
						if (! $this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($keyword))
						{
							return AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接');
						}
						
						if ($this->publish_approval_valid())
						{
							$this->model('publish')->publish_approval('question', array(
								'question_content' => substr($keyword, strlen($val['keyword'])),
								'topics' => explode(',', $val['topics']),
							), $this->user_id);
						}
						else
						{
							if ($question_id = $this->model('publish')->publish_question(substr($keyword, strlen($val['keyword'])), '', 1, $this->user_id, explode(',', $val['topics']), null, null, null, true))
							{
								$this->model('wecenter')->set_wechat_fake_id_by_question($keyword, $question_id);
							}
						}
					break;
					
					case 'answer':						
						if (!$question_info = $this->model('question')->get_question_info_by_id($val['item_id']))
						{
							return AWS_APP::lang()->_t('问题不存在');
						}
						
						if ($question_info['lock'] && ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
						{
							return AWS_APP::lang()->_t('已经锁定的问题不能回复');
						}
						
						// 判断是否是问题发起者
						if (get_setting('answer_self_question') == 'N' and $question_info['published_uid'] == $this->user_id)
						{
							return AWS_APP::lang()->_t('不能回复自己发布的问题，你可以修改问题内容');
						}
						
						// 判断是否已回复过问题
						if ((get_setting('answer_unique') == 'Y') && $this->model('answer')->has_answer_by_uid($_POST['question_id'], $this->user_id))
						{
							return AWS_APP::lang()->_t('一个问题只能回复一次，你可以编辑回复过的回复');
						}
						
						if (! $this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($keyword))
						{
							return AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接');
						}
						
						if ($answer_id = $this->model('publish')->publish_answer($val['item_id'], substr($keyword, strlen($val['keyword'])), $this->user_id))	
						{
							$this->model('answer')->set_answer_publish_source($answer_id, 'weixin');
						}
					break;
				}
				
				if ($val['image_file'])
				{
					return array(
						0 => $val
					);
				}
				
				return $val['title'];
			}
		}
	}
	
	public function remove_publish_rule($id)
	{
		if ($publish_rule = $this->get_publish_rule_by_id($id))
		{
			unlink(get_setting('upload_dir') . '/weixin/' . $reply_rule['image_file']);
			unlink(get_setting('upload_dir') . '/weixin/square_' . $reply_rule['image_file']);
			
			return $this->delete('weixin_publish_rule', 'id = ' . intval($id));
		}
	}
	
	public function get_weixin_rule_image($image_file, $size = '')
	{
		if ($size)
		{
			$size .= '_';
		}
		
		return get_setting('upload_url') . '/weixin/' . $size . $image_file;
	}
	
	public function get_access_token()
	{
		$token_cache_key = 'weixin_access_token_' . md5(AWS_APP::config()->get('weixin')->app_id . AWS_APP::config()->get('weixin')->app_secret);
		
		if ($access_token = AWS_APP::cache()->get($token_cache_key))
		{
			return $access_token;
		}
		
		if ($result = curl_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . AWS_APP::config()->get('weixin')->app_id . '&secret=' . AWS_APP::config()->get('weixin')->app_secret))
		{
			$result = json_decode($result, true);
			
			if ($result['access_token'])
			{
				AWS_APP::cache()->set($token_cache_key, $result['access_token'], $result['expires_in']);
				
				return $result['access_token'];
			}
		}
	}
	
	public function update_menu()
	{
		$mp_menu = get_setting('weixin_mp_menu');
		
		foreach ($mp_menu AS $key => $val)
		{
			if ($val['sub_button'])
			{
				foreach ($val['sub_button'] AS $sub_key => $sub_val)
				{
					unset($sub_val['sort']);
					
					if ($sub_val['type'] == 'view')
					{
						unset($sub_val['key']);
					}
					
					$val['sub_button_no_key'][] = $sub_val;
				}
				
				$val['sub_button'] = $val['sub_button_no_key'];
				
				unset($val['sub_button_no_key']);
			}
			
			unset($val['sort']);
			
			if ($val['type'] == 'view')
			{
				unset($val['key']);
			}
			
			$mp_menu_no_key[] = $val;
		}
		
		if ($result = HTTP::request('https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->get_access_token(), 'POST', preg_replace("#\\\u([0-9a-f]+)#ie", "convert_encoding(pack('H4', '\\1'), 'UCS-2', 'UTF-8')", json_encode(array('button' => $mp_menu_no_key)))))
		{
			$result = json_decode($result, true);
			
			if ($result['errcode'])
			{
				return $result['errmsg'];
			}
		}
		else
		{
			return '由于网络问题, 菜单更新失败';
		}
	}
}
