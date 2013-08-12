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

class core_mail
{
	private $transport;
	private $config;
	private $transport_error;
	
	private $sae_option;
	
	public function __construct()
	{
		$this->config = get_setting('mail_config');
		
		switch ($this->config['transport'])
		{
			case 'smtp':
				if (defined('IN_SAE'))
				{
					$this->sae_option['smtp_username'] = $this->config['username'];
					$this->sae_option['smtp_password'] = $this->config['password'];
					
					if ($this->config['port'])
					{
						$this->sae_option['smtp_port'] = $this->config['port'];
					}
					
					$this->sae_option['smtp_host'] = $this->config['server'];
					
					$this->transport = new SaeMail();
				}
				else
				{
					$auth = array(
						'auth' => 'login',
						'username' => $this->config['username'],
						'password' => $this->config['password']
					);
						
					if ($this->config['port'])
					{
						$auth['port'] = $this->config['port'];
					}
						
					if ($this->config['ssl'])
					{
						$auth['ssl'] = 'ssl';
					}
					
					try 
					{
						$this->transport = new Zend_Mail_Transport_Smtp($this->config['server'], $auth);
					}
					catch (Exception $e)
					{
						$this->transport_error = $e->getMessage();
					}
				}
			break;
			
			default:
			case 'sendmail':
				try 
				{
					$this->transport = new Zend_Mail_Transport_Sendmail(get_setting('from_email'));
				}
				catch (Exception $e)
				{
					$this->transport_error = $e->getMessage();
				}
			break;
		}
	}
	
	public function send($address, $title, $body, $from_name = '', $to_name = '')
	{
		if ($this->transport_error)
		{
			return $this->transport_error;
		}
		
		if (strtoupper($this->config['charset']) != 'UTF-8')
		{
			$from_name = convert_encoding($from_name, 'UTF-8', $this->config['charset']);
			$to_name = convert_encoding($to_name, 'UTF-8', $this->config['charset']);
			$title = convert_encoding($title, 'UTF-8', $this->config['charset']);
			$body = convert_encoding($body, 'UTF-8', $this->config['charset']);
		}
		
		if (defined('IN_SAE'))
		{
			$this->sae_option['from'] = get_setting('from_email');
			$this->sae_option['to'] = $address;
			$this->sae_option['subject'] = $title;
			$this->sae_option['content_type'] = 'HTML';
			$this->sae_option['content'] = $body;
			
			$this->transport->setOpt($this->sae_option);
			
			if (!$this->transport->send())
			{
				return $this->transport->errmsg();
			}
		}
		else
		{
			try
			{
				$zend_mail = new Zend_Mail($this->config['charset']);
				$zend_mail->setBodyHtml($body);
				$zend_mail->setFrom(get_setting('from_email'), $from_name);
				$zend_mail->addTo($address, $to_name);
				$zend_mail->setSubject($title);
				$zend_mail->send($this->transport);
			}
			catch (Exception $e)
			{
				return $e->getMessage();
			}
		}
	}
}