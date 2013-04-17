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

class core_mail
{
	private $transport;
	private $config;
	private $transport_error;
	
	public function __construct()
	{
		$this->config = get_setting('mail_config');
		
		switch ($this->config['transport'])
		{
			case 'smtp':
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