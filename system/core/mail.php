<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

class core_mail
{
    private $transport;
    private $transport_error;

    private $master_transport;
    private $master_config;
    private $master_transport_error;

    private $slave_transport;
    private $slave_config;
    private $slave_transport_error;

    private $sae_option;

    public function __construct()
    {
        $this->master_config = get_setting('mail_config');
        $this->slave_config = get_setting('slave_mail_config');

        switch ($this->master_config['transport'])
        {
            case 'smtp':
                if (defined('IN_SAE'))
                {
                    $this->sae_option['smtp_username'] = $this->master_config['username'];
                    $this->sae_option['smtp_password'] = $this->master_config['password'];

                    if ($this->master_config['port'])
                    {
                        $this->sae_option['smtp_port'] = $this->master_config['port'];
                    }

                    $this->sae_option['smtp_host'] = $this->master_config['server'];

                    $this->transport = new SaeMail();
                }
                else
                {
                    $master_smtp_auth = array(
                        'auth' => 'login',
                        'username' => $this->master_config['username'],
                        'password' => $this->master_config['password']
                    );

                    if ($this->master_config['port'])
                    {
                        $master_smtp_auth['port'] = $this->master_config['port'];
                    }

                    if ($this->master_config['ssl'])
                    {
                        $master_smtp_auth['ssl'] = 'ssl';
                    }

                    try
                    {
                        $this->master_transport = new Zend_Mail_Transport_Smtp($this->master_config['server'], $master_smtp_auth);
                    }
                    catch (Exception $e)
                    {
                        $this->master_transport_error = $e->getMessage();
                    }

                    if (!$this->slave_config['server'])
                    {
                        $this->slave_config = $this->master_config;
                    }

                    $slave_smtp_auth = array(
                        'auth' => 'login',
                        'username' => $this->slave_config['username'],
                        'password' => $this->slave_config['password']
                    );

                    if ($this->slave_config['port'])
                    {
                        $slave_smtp_auth['port'] = $this->slave_config['port'];
                    }

                    if ($this->slave_config['ssl'])
                    {
                        $slave_smtp_auth['ssl'] = 'ssl';
                    }

                    try
                    {
                        $this->slave_transport = new Zend_Mail_Transport_Smtp($this->slave_config['server'], $slave_smtp_auth);
                    }
                    catch (Exception $e)
                    {
                        $this->slave_transport_error = $e->getMessage();
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

    public function send($address, $title, $body, $from_name = null, $to_name = null, $server = 'master')
    {
        if ($this->transport)
        {
            $transport_error = $this->transport_error;
            $transport = $this->transport;

            $mail_config = $this->master_config;
        }
        else
        {
            switch ($server)
            {
                case 'master':
                    $transport_error = $this->master_transport_error;

                    $mail_config = $this->master_config;
                    $transport = $this->master_transport;
                    break;

                case 'slave':
                    $transport_error = $this->slave_transport_error;

                    $mail_config = $this->slave_config;
                    $transport = $this->slave_transport;
                    break;
            }
        }

        if ($transport_error)
        {
            return $transport_error;
        }

        if (strtoupper($mail_config['charset']) != 'UTF-8')
        {
            $from_name = convert_encoding($from_name, 'UTF-8', $mail_config['charset']);
            $to_name = convert_encoding($to_name, 'UTF-8', $mail_config['charset']);
            $title = convert_encoding($title, 'UTF-8', $mail_config['charset']);
            $body = convert_encoding($body, 'UTF-8', $mail_config['charset']);
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
                $zend_mail = new Zend_Mail($mail_config['charset']);
                $zend_mail->setBodyHtml($body);
                $zend_mail->setFrom(get_setting('from_email'), $from_name);
                $zend_mail->addTo($address, $to_name);
                $zend_mail->setSubject($title);
                $zend_mail->send($transport);
            }
            catch (Exception $e)
            {
                return $e->getMessage();
            }
        }
    }
}