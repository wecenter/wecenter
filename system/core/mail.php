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
	private $mail;
	private $mail_transport;
	private $charset = 'utf-8';
		
	public function connect($email_type = null, $smtp_config = null)
	{
		if (!$email_type)
		{
			$email_type = get_setting('email_type');
		}
		
		switch ($email_type)
		{
			case 1:	// SMTP
				if (!$smtp_config)
				{
					$auth = array(
						'auth' => 'login',
						'username' => get_setting('smtp_username'),
						'password' => get_setting('smtp_password')
					);
					
					if (get_setting('smtp_port') > 0)
					{
						$auth['port'] = get_setting('smtp_port');
					}
					
					if (get_setting('smtp_ssl') == 'Y')
					{
						$auth['ssl'] = 'ssl';
					}
					
					$smtp_server = get_setting('smtp_server');
				}
				else
				{
					$auth = array(
						'auth' => 'login',
						'username' => $smtp_config['smtp_username'],
						'password' => $smtp_config['smtp_password'],
					);
					
					if ($smtp_config['smtp_port'] > 0)
					{
						$auth['port'] = $smtp_config['smtp_port'];
					}
					
					if ($smtp_config['smtp_ssl'] == 'Y')
					{
						$auth['ssl'] = 'ssl';
					}
					
					$smtp_server = $smtp_config['smtp_server'];
				}
				
				try 
				{
					$this->mail_transport = new Zend_Mail_Transport_Smtp($smtp_server, $auth);
				}
				catch (Exception $e)
				{
					return $e->getMessage();
				}
			break;
			
			case 2:	// Sendmail
				try 
				{
					$this->mail_transport = new Zend_Mail_Transport_Sendmail(get_setting('from_email'));
				}
				catch (Exception $e)
				{
					return $e->getMessage();
				}
			break;
		}
				
		return $this->mail_transport;
	}
	
	public function send_mail($from_name, $to_email, $to_name, $title, $body)
	{		
		if (!$this->mail_transport)
		{
			$this->connect();
		}
		
		if (!$from_email = get_setting('from_email'))
		{
			$from_email = 'localhost';
		}
		
		try 
		{
			$mail = new Zend_Mail($this->charset);
			$mail->setBodyHtml($body);
			$mail->setFrom($from_email, $from_name);
			$mail->addTo($to_email, $to_name);
			$mail->setSubject("=?UTF-8?B?" . base64_encode($title) . "?=");
				
			$mail->send($this->mail_transport);
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}
}