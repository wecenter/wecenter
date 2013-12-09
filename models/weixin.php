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
	die;
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
				'eventKey' => $post_object['EventKey'],
				'mediaID' => $post_object['MediaID'],
				'format' => $post_object['Format'],
				'recognition' => $post_object['Recognition'],
				'msgID' => $post_object['MsgID'],
				'latitude' => $post_object['Latitude'],
				'longitude' => $post_object['Longitude'],
				'precision' => $post_object['Precision'],
				'location_X' => $post_object['Location_X'],
				'location_Y' => $post_object['Location_y'],
				'label' => $post_object['Label'],
			);
			
			if ($weixin_info = $this->model('openid_weixin')->get_user_info_by_openid($input_message['fromUsername']))
			{
				$this->user_info = $this->model('account')->get_user_info_by_uid($uid, true);
				$this->user_id = $weixin_info['uid'];
			}
			
			return $input_message;
		}
	}
	
	public function response_message($input_message)
	{
		switch ($input_message['msgType'])
		{
			case 'event':
				if ($input_message['event'] == 'LOCATION')
				{
					if ($this->user_id)
					{
						$this->update('users_weixin', array(
							'latitude' => $input_message['latitude'],
							'longitude' => $input_message['longitude'],
							'location_update' => time()
						), 'uid = ' . $this->user_id);
					}
				}
				else if (substr($input_message['eventKey'], 0, 8) == 'COMMAND_')
				{
					if ($input_message['eventKey'] == 'COMMAND_MORE')
					{
						$input_message['content'] = 'yes';
						$input_message['msgType'] = 'text';
					
						$response = $this->response_message($input_message);
					}
					else
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
						
						$response = $this->message_parser(array(
							'content' => $content,
							'fromUsername' => $input_message['fromUsername'],
							'param' => $param
						));
					}
					
					$response_message = $response['message'];
					$action = $response['action'];
				}
				else if (substr($input_message['eventKey'], 0, 11) == 'REPLY_RULE_')
				{
					if ($reply_rule = $this->get_reply_rule_by_id(substr($input_message['eventKey'], 11)))
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
							if (get_setting('weixin_subscribe_message_key'))
							{
								$response_message = $this->create_response_by_reply_rule_keyword(get_setting('weixin_subscribe_message_key'));
							}
						break;
					}
				}
			break;
			
			case 'location':
				if (!$near_by_questions = $this->model('people')->get_near_by_users($input_message['location_X'], $input_message['location_y'], $this->user_id, 10))
				{
					$response_message = '你的附近暂时没有问题';
				}
				else
				{
					foreach ($near_by_questions AS $key => $val)
					{
						if (!$response_message)
						{
							$image_file = AWS_APP::config()->get('weixin')->default_list_image;
						}
						else
						{
							$image_file = get_avatar_url($val['published_uid'], 'max');
						}
						
						$response_message[] = array(
							'title' => $val['question_content'],
							'link' => $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_id']),
							'image_file' => $image_file
						);
					}
				}
			break;
						
			case 'voice':
				$input_message['content'] = $input_message['recognition'];
				$input_message['msgType'] = 'text';
				
				$response_message = $this->response_message($input_message);
			break;
			
			default:
				if ($response_message = $this->create_response_by_reply_rule_keyword($input_message['content']))
				{
					// response by reply rule keyword...
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
					$response_message = '说脏话都不是好孩子!';
				}
				else if ($search_result = $this->model('search')->search_questions($input_message['content'], null, 10))
				{
					foreach ($search_result AS $key => $val)
					{
						if (!$response_message)
						{
							$image_file = AWS_APP::config()->get('weixin')->default_list_image;
						}
						else
						{
							$image_file = get_avatar_url($val['published_uid'], 'max');
							
						}
						
						$response_message[] = array(
							'title' => $val['question_content'],
							'link' => $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_id']),
							'image_file' => $image_file
						);
					}
				}
				else
				{
					if (!$response_message = $this->create_response_by_reply_rule_keyword(get_setting('weixin_no_result_message_key')))
					{
						$response_message = '您的问题: ' . $input_message['content'] . ', 目前没有人提到过';
					}
				}
			break;
		}
		
		if (is_array($response_message))
		{
			echo $this->create_image_response($input_message, $response_message, $action);
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
	
	public function create_image_response($input_message, $image_data = array(), $action = null)
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
		
		if (!$param)
		{
			$param = 1;
		}
		
		switch ($message_code)
		{
			default:
				if (cjk_strlen($input_message['content']) > 1 AND substr($input_message['content'], 0, 1) == '@')
				{
					if ($user_info = $this->model('account')->get_user_info_by_username(substr($input_message['content'], 1), true))
					{
						$response_message[] = array(
							'title' => $user_info['signature'],
							'link' => $this->model('openid_weixin')->redirect_url('/m/people/' . $user_info['url_token']),
							'image_file' => get_avatar_url($user_info['uid'], '')
						);
						
						if ($user_actions = $this->model('account')->get_user_actions($user_info['uid'], calc_page_limit($param, 9), 101))
						{							
							foreach ($user_actions AS $key => $val)
							{								
								$response_message[] = array(
									'title' => $val['question_info']['question_content'],
									'link' => $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_info']['question_id']),
									'image_file' => get_avatar_url($val['question_info']['published_uid'], 'max')
								);
							}
						}
					}
				}
				else
				{
					if ($topic_info = $this->model('topic')->get_topic_by_title($input_message['content']))
					{						
						$response_message[] = array(
							'title' => $topic_info['topic_title'],
							'link' => $this->model('openid_weixin')->redirect_url('/m/topic/' . $topic_info['url_token']),
							'image_file' => get_topic_pic_url('', $topic_info['topic_pic'])
						);
						
						if ($topic_questions = $this->model('question')->get_questions_list($param, 9, 'new', $topic_info['topic_id']))
						{
							foreach ($topic_questions AS $key => $val)
							{
								$response_message[] = array(
									'title' => $val['question_content'],
									'link' => $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_id']),
									'image_file' => get_avatar_url($val['published_uid'], 'max')
								);
							}
						}
					}
				}
			break;
			
			case 'NEW_ARTICLE':
				if ($input_message['param'])
				{
					$child_param = explode('_', $input_message['param']);
					
					switch ($child_param[0])
					{
						case 'FEATURE':
							$topics_id = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
						break;
					}
				}
				
				if ($topics_id)
				{
					$article_list = $this->model('article')->get_articles_list_by_topic_ids($param, 10, 'add_time DESC', $topics_id);
				}
				else
				{
					$article_list = $this->model('article')->get_articles_list($param, 10, 'add_time DESC');
				}
				
				foreach ($article_list AS $key => $val)
				{
					if (!$response_message)
					{
						if (!$image_file = $this->get_list_image_by_command('COMMAND_' . $message_code))
						{
							$image_file = AWS_APP::config()->get('weixin')->default_list_image;
						}
					}
					else
					{
						$image_file = get_avatar_url($val['uid'], 'max');
					}
					
					$response_message[] = array(
						'title' => $val['title'],
						'link' => $this->model('openid_weixin')->redirect_url('/m/article/' . $val['id']),
						'image_file' => $image_file
					);
				}
			break;
			
			case 'HOT_QUESTION':
				if ($input_message['param'])
				{
					$child_param = explode('_', $input_message['param']);
					
					switch ($child_param[0])
					{
						case 'CATEGORY':
							$category_id = intval($child_param[1]);
						break;
						
						case 'FEATURE':
							$topics_id = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
						break;
					}
				}
				
				if ($question_list = $this->model('question')->get_hot_question($category_id, $topics_id, 7, $param, 10))
				{
					foreach ($question_list AS $key => $val)
					{
						if (!$response_message)
						{
							if (!$image_file = $this->get_list_image_by_command('COMMAND_' . $message_code))
							{
								$image_file = AWS_APP::config()->get('weixin')->default_list_image;
							}
						}
						else
						{
							$image_file = get_avatar_url($val['published_uid'], 'max');
						}
						
						$response_message[] = array(
							'title' => $val['question_content'],
							'link' => $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_id']),
							'image_file' => $image_file
						);
					}
				}
				else
				{
					$response_message = '暂无问题';
				}
			break;
			
			case 'NEW_QUESTION':
				if ($input_message['param'])
				{
					$child_param = explode('_', $input_message['param']);
					
					switch ($child_param[0])
					{
						case 'CATEGORY':
							$category_id = intval($child_param[1]);
						break;
						
						case 'FEATURE':
							$topics_id = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
						break;
					}
				}
				
				if ($question_list = $this->model('question')->get_questions_list($param, 10, 'new', $topics_id, $category_id))
				{					
					foreach ($question_list AS $key => $val)
					{
						if (!$response_message)
						{
							if (!$image_file = $this->get_list_image_by_command('COMMAND_' . $message_code))
							{
								$image_file = AWS_APP::config()->get('weixin')->default_list_image;
							}
						}
						else
						{
							$image_file = get_avatar_url($val['published_uid'], 'max');
						}
						
						$response_message[] = array(
							'title' => $val['question_content'],
							'link' => $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_id']),
							'image_file' => $image_file
						);
					}
				}
				else
				{
					$response_message = '暂无问题';
				}
			break;
			
			case 'NO_ANSWER_QUESTION':
				if ($input_message['param'])
				{
					$child_param = explode('_', $input_message['param']);
					
					switch ($child_param[0])
					{
						case 'CATEGORY':
							$category_id = intval($child_param[1]);
						break;
						
						case 'FEATURE':
							$topics_id = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
						break;
					}
				}
				
				if ($question_list = $this->model('question')->get_questions_list($param, 10, 'unresponsive', $topics_id, $category_id))
				{					
					foreach ($question_list AS $key => $val)
					{
						if (!$response_message)
						{
							if (!$image_file = $this->get_list_image_by_command('COMMAND_' . $message_code))
							{
								$image_file = AWS_APP::config()->get('weixin')->default_list_image;
							}
						}
						else
						{
							$image_file = get_avatar_url($val['published_uid'], 'max');
						}
						
						$response_message[] = array(
							'title' => $val['question_content'],
							'link' => $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_id']),
							'image_file' => $image_file
						);
					}
				}
				else
				{
					$response_message = '暂无问题';
				}
			break;
			
			case 'RECOMMEND_QUESTION':
				if ($input_message['param'])
				{
					$child_param = explode('_', $input_message['param']);
					
					switch ($child_param[0])
					{
						case 'CATEGORY':
							$category_id = intval($child_param[1]);
						break;
						
						case 'FEATURE':
							$topics_id = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
						break;
					}
				}
				
				if ($question_list = $this->model('question')->get_questions_list($param, 10, null, $topics_id, $category_id, null, null, true))
				{
					foreach ($question_list AS $key => $val)
					{
						if (!$response_message)
						{
							if (!$image_file = $this->get_list_image_by_command('COMMAND_' . $message_code))
							{
								$image_file = AWS_APP::config()->get('weixin')->default_list_image;
							}
						}
						else
						{
							$image_file = get_avatar_url($val['published_uid'], 'max');
						}
						
						$response_message[] = array(
							'title' => $val['question_content'],
							'link' => $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_id']),
							'image_file' => $image_file
						);
					}
				}
				else
				{
					$response_message = '暂无问题';
				}
			break;
			
			case 'HOME_ACTIONS':
				if ($this->user_id)
				{
					if ($index_actions = $this->model('index')->get_index_focus($this->user_id, calc_page_limit($param, 10)))
					{
						foreach ($index_actions AS $key => $val)
						{
							if (!$response_message)
							{
								if (!$image_file = $this->get_list_image_by_command('COMMAND_' . $message_code))
								{
									$image_file = AWS_APP::config()->get('weixin')->default_list_image;
								}
							}
							else
							{
								$image_file = get_avatar_url($val['question_info']['published_uid'], 'max');
							}
							
							if ($val['associate_action'] == ACTION_LOG::ANSWER_QUESTION OR $val['associate_action'] == ACTION_LOG::ADD_AGREE)
							{
								$link = $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_info']['question_id'] . '?answer_id=' . $val['answer_info']['answer_id'] . '&single=TRUE');
							}
							else
							{
								$link = $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_info']['question_id']);
							}
							
							$response_message[] = array(
								'title' => $val['question_info']['question_content'],
								'link' => $link,
								'image_file' => $image_file
							);
						}
					}
					else
					{
						$response_message = '暂时没有最新动态';
					}
				}
				else
				{
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请<a href="' . $this->model('openid_weixin')->get_oauth_url(get_js_url('/m/weixin/authorization/'), 'snsapi_userinfo') . '">点此绑定</a>或<a href="' . get_js_url('/m/register/') . '">注册新账户</a>';
				}
			break;
			
			case 'NOTIFICATIONS':
				if ($this->user_id)
				{
					if ($notifications = $this->model('notify')->list_notification($this->user_id, 0, calc_page_limit($param, 10)))
					{
						$response_message = '最新通知:';
						
						foreach($notifications AS $key => $val)
						{
							$response_message .= "\n\n• " . $val['message'];
						}
						
						$response_message .= "\n\n请输入 '更多' 显示其他相关内容";
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
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请<a href="' . $this->model('openid_weixin')->get_oauth_url(get_js_url('/m/weixin/authorization/'), 'snsapi_userinfo') . '">点此绑定</a>或<a href="' . get_js_url('/m/register/') . '">注册新账户</a>';
				}
			break;
			
			case 'MY_QUESTION':
				if ($this->user_id)
				{
					if ($user_actions = $this->model('account')->get_user_actions($this->user_id, calc_page_limit($param, 10), 101))
					{						
						foreach ($user_actions AS $key => $val)
						{
							if (!$response_message)
							{
								if (!$image_file = $this->get_list_image_by_command('COMMAND_' . $message_code))
								{
									$image_file = AWS_APP::config()->get('weixin')->default_list_image;
								}
							}
							else
							{
								$image_file = get_avatar_url($val['question_info']['published_uid'], 'max');
							}
						
							$response_message[] = array(
								'title' => $val['question_info']['question_content'],
								'link' => $this->model('openid_weixin')->redirect_url('/m/question/' . $val['question_id']),
								'image_file' => $image_file
							);
						}
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
					$response_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请<a href="' . $this->model('openid_weixin')->get_oauth_url(get_js_url('/m/weixin/authorization/'), 'snsapi_userinfo') . '">点此绑定</a>或<a href="' . get_js_url('/m/register/') . '">注册新账户</a>';
				}
			break;
		}
		
		if (!$response_message)
		{
			return false;
		}
		
		return array(
			'message' => $response_message,
			'action' => $message_code . '-' . ($param + 1)
		);
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
		
		if ($response = $this->message_parser(array(
			'content' => $last_action['action'],
			'fromUsername' => $weixin_id
		), $last_action_param))
		{
			$response_message = $response['message'];
			$action = $response['action'];
		}
		else
		{
			$response_message = '您好, 请问需要什么帮助?';
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
	
	public function fetch_unique_reply_rule_list($where = null)
	{
		return $this->query_all("SELECT * FROM `" . get_table('weixin_reply_rule') . "`", null, null, $where, 'keyword');
	}
	
	public function add_reply_rule($keyword, $title, $description = '', $link = '', $image_file = '')
	{		
		return $this->insert('weixin_reply_rule', array(
			'keyword' => trim($keyword),
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
	
	public function update_reply_rule($id, $title, $description = '', $link = '', $image_file = '')
	{		
		return $this->update('weixin_reply_rule', array(
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
			if ($reply_rule['image_file'])
			{
				unlink(get_setting('upload_dir') . '/weixin/' . $reply_rule['image_file']);
				unlink(get_setting('upload_dir') . '/weixin/square_' . $reply_rule['image_file']);
			}
			
			return $this->delete('weixin_reply_rule', 'id = ' . intval($id));
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
	
	public function send_text_message($openid, $message)
	{
		HTTP::request('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $this->get_access_token(), 'POST', preg_replace("#\\\u([0-9a-f]+)#ie", "convert_encoding(pack('H4', '\\1'), 'UCS-2', 'UTF-8')", json_encode(array(
			'touser' => $openid,
			'msgtype' => 'text',
			'text' => array(
				'content' => $message
			)
		))));
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
					unset($sub_val['command_type']);
					unset($sub_val['attach_key']);
					
					if ($sub_val['type'] == 'view')
					{
						unset($sub_val['key']);
						
						if (strstr($sub_val['url'], get_setting('base_url')))
						{
							$sub_val['url'] = $this->model('openid_weixin')->redirect_url($sub_val['url']);
						}
					}
					
					$val['sub_button_no_key'][] = $sub_val;
				}
				
				$val['sub_button'] = $val['sub_button_no_key'];
				
				unset($val['sub_button_no_key']);
			}
			
			unset($val['sort']);
			unset($val['command_type']);
			unset($val['attach_key']);
			
			if ($val['type'] == 'view')
			{
				unset($val['key']);
				
				if (strstr($val['url'], get_setting('base_url')))
				{
					$val['url'] = $this->model('openid_weixin')->redirect_url($val['url']);
				}
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
	
	public function list_image_clean()
	{
		if (!is_dir(ROOT_PATH . 'weixin/list_image/'))
		{
			return false;
		}
		
		$mp_menu = get_setting('weixin_mp_menu');
		
		foreach ($mp_menu AS $key => $val)
		{
			if ($val['sub_button'])
			{
				foreach ($val['sub_button'] AS $sub_key => $sub_val)
				{
					$attach_list[] = $sub_val['attch_key'] . '.jpg';
				}
			}
			
			$attach_list[] = $val['attch_key'] . '.jpg';
		}
		
		$files_list = fetch_file_lists(ROOT_PATH . 'weixin/list_image/', 'jpg');
			    
	    foreach ($files_list AS $search_file)
	    {
	    	if (!in_array(str_replace('square_', '', base_name($search_file))))
	    	{
		    	unlink($search_file);
	    	}
		}
	}
	
	public function get_list_image_by_command($command)
	{
		$mp_menu = get_setting('weixin_mp_menu');
		
		foreach ($mp_menu AS $key => $val)
		{
			if ($val['sub_button'])
			{
				foreach ($val['sub_button'] AS $sub_key => $sub_val)
				{
					if ($sub_key == $command)
					{
						return $sub_val['attch_key'];
					}
				}
			}
			
			if ($key == $command)
			{
				return $val['attch_key'];
			}
		}
	}	
}
