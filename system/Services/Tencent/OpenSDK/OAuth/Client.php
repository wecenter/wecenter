<?php

/**
 * OAuth协议接口
 *
 * 依赖：
 * PHP 5 >= 5.1.2, PECL hash >= 1.1 (no need now)
 * 
 * @ignore
 * @author icehu@vip.qq.com
 *
 */

class Services_Tencent_OpenSDK_OAuth_Client
{
	/**
	 * 签名的url标签
	 * @var string
	 */
	public $oauth_signature_key = 'oauth_signature';

	/**
	 * app secret
	 * @var string
	 */
	private $_app_secret = '';

	/**
	 * token secret
	 * @var string
	 */
	private $_token_secret = '';

	/**
	 * 上一次请求返回的Httpcode
	 * @var number
	 */
	private $_httpcode = null;

	/**
	 * 是否debug
	 * @var bool
	 */
	private $_debug = false;

	public function  __construct( $appsecret='' , $debug=false)
	{
		$this->_app_secret = $appsecret;
		$this->_debug = $debug;
	}
	/**
	 * 设置App secret
	 * @param string $appsecret
	 */
	public function setAppSecret($appsecret)
	{
		$this->_app_secret = $appsecret;
	}

	/**
	 * 设置token secret
	 * @param string $tokensecret
	 */
	public function setTokenSecret($tokensecret)
	{
		$this->_token_secret = $tokensecret;
	}

	/**
	 * 组装参数签名并请求接口
	 *
	 * @param string $url
	 * @param array $params
	 * @param string $method
	 * @param false|array $multi false:普通post array: array ( '{fieldname}' =>array('type'=>'mine','name'=>'filename','data'=>'filedata') ) 文件上传
	 * @return string
	 */
	public function request( $url, $method, $params, $multi = false, $is_oauth=true)
	{
		if($is_oauth){ 	//使用oauth参数集调用接口
			$oauth_signature = $this->sign($url, $method, $params);
			$params[$this->oauth_signature_key] = $oauth_signature;
		}
		else{	//使用openod&openkey参数集调用接口
		
			//去除 oauth 的所有认证参数，使用openid&openkey 参数集合
			unset($params['oauth_token']);
			unset($params['oauth_nonce']);
			unset($params['oauth_consumer_key']);
			unset($params['oauth_signature_method']);
			unset($params['oauth_version']);
			unset($params['oauth_timestamp']);
			unset($params['oauth_signature']);	
			
			// 生成签名
			require_once("sns_sig_check.php");
			$urls = @parse_url($url);
			$sig = Services_Tencent_OpenSDK_OAuth_SnsSigCheck::makeSig($method, $urls['path'], $params, $this->_app_secret.'&');
			$params['sig'] = $sig;
		}		

		return $this->http($url, $params, $method, $multi);
	}

	/**
	 * OAuth 协议的签名
	 *
	 * @param string $url
	 * @param string $method
	 * @param array $params
	 * @return string
	 */
	private function sign( $url , $method, $params )
	{
		uksort($params, 'strcmp');
		$pairs = array();
        foreach($params as $key => $value)
        {
			$key = Services_Tencent_OpenSDK_Util::urlencode_rfc3986($key);
            if(is_array($value))
            {
                // If two or more parameters share the same name, they are sorted by their value
                // Ref: Spec: 9.1.1 (1)
                natsort($value);
                foreach($value as $duplicate_value)
                {
                    $pairs[] = $key . '=' . Services_Tencent_OpenSDK_Util::urlencode_rfc3986($duplicate_value);
                }
            }
            else
            {
                $pairs[] = $key . '=' . Services_Tencent_OpenSDK_Util::urlencode_rfc3986($value);
            }
        }
		
        $sign_parts = Services_Tencent_OpenSDK_Util::urlencode_rfc3986(implode('&', $pairs));
		
		$base_string = implode('&', array( strtoupper($method) , Services_Tencent_OpenSDK_Util::urlencode_rfc3986($url) , $sign_parts ));

        $key_parts = array(Services_Tencent_OpenSDK_Util::urlencode_rfc3986($this->_app_secret), Services_Tencent_OpenSDK_Util::urlencode_rfc3986($this->_token_secret));

        $key = implode('&', $key_parts);
        $sign = base64_encode(Services_Tencent_OpenSDK_Util::hash_hmac('sha1', $base_string, $key, true));
		if($this->_debug)
		{
			echo 'base_string: ' , $base_string , "\n";
			echo 'sign key: ', $key , "\n";
			echo 'sign: ' , $sign , "\n";
		}
		return $sign;
	}

	/**
	 * Http请求接口
	 *
	 * @param string $url
	 * @param array $params
	 * @param string $method 支持 GET / POST / DELETE
	 * @param false|array $multi false:普通post array: array ( 'fieldname'=>array('type'=>'mine','name'=>'filename','data'=>'filedata') ) 文件上传
	 * @return string
	 */
	private function http( $url , $params , $method='GET' , $multi=false )
	{
		//for test ,print all send params
//		ksort($params);
//		print_r($params);
		
		$method = strtoupper($method);
		$postdata = '';
		$urls = @parse_url($url);
		$httpurl = $urlpath = $urls['path'] . ($urls['query'] ? '?' . $urls['query'] : '');
		if( !$multi )
		{
			$parts = array();
			foreach ($params as $key => $val)
			{
				$parts[] = urlencode($key) . '=' . urlencode($val);
			}
			if ($parts)
			{
				$postdata = implode('&', $parts);
				$httpurl = $httpurl . (strpos($httpurl, '?') ? '&' : '?') . $postdata;
			}
			else
			{
			}
		}
		
		$host = $urls['host'];
		$port = $urls['port'] ? $urls['port'] : 80;
		$version = '1.1';
		if($urls['scheme'] === 'https')
        {
            $port = 443;
        }
		$headers = array();
		if($method == 'GET')
		{
			$headers[] = "GET $httpurl HTTP/$version";
		}
		else if($method == 'DELETE')
		{
			$headers[] = "DELETE $httpurl HTTP/$version";
		}
		else
		{
			$headers[] = "POST $urlpath HTTP/$version";
		}
		$headers[] = 'Host: ' . $host;
		$headers[] = 'User-Agent: OpenSDK-OAuth';
		$headers[] = 'Connection: Close';

		if($method == 'POST')
		{
			if($multi)
			{
				$boundary = uniqid('------------------');
				$MPboundary = '--' . $boundary;
				$endMPboundary = $MPboundary . '--';
				$multipartbody = '';
				$headers[]= 'Content-Type: multipart/form-data; boundary=' . $boundary;
				foreach($params as $key => $val)
				{
					$multipartbody .= $MPboundary . "\r\n";
					$multipartbody .= 'Content-Disposition: form-data; name="' . $key . "\"\r\n\r\n";
					$multipartbody .= $val . "\r\n";
				}
				foreach($multi as $key => $data)
				{
					$multipartbody .= $MPboundary . "\r\n";
					$multipartbody .= 'Content-Disposition: form-data; name="' . $key . '"; filename="' . $data['name'] . '"' . "\r\n";
					$multipartbody .= 'Content-Type: ' . $data['type'] . "\r\n\r\n";
					$multipartbody .= $data['data'] . "\r\n";
				}
				$multipartbody .= $endMPboundary . "\r\n";
				$postdata = $multipartbody;
			}
			else
			{
				$headers[]= 'Content-Type: application/x-www-form-urlencoded';
			}
		}
        $ret = '';
        $fp = fsockopen($host, $port, $errno, $errstr, 5);

        if(! $fp)
        {
            $error = 'Open Socket Error';
			return '';
        }
        else
        {
			if( $method != 'GET' && $postdata )
			{
				$headers[] = 'Content-Length: ' . strlen($postdata);
			}
            $this->fwrite($fp, implode("\r\n", $headers));
			$this->fwrite($fp, "\r\n\r\n");
			if( $method != 'GET' && $postdata )
			{
				$this->fwrite($fp, $postdata);
			}
			//skip headers
            while(! feof($fp))
            {
                $ret .= fgets($fp, 1024);
            }
			if($this->_debug)
			{
				echo $ret;
			}
			fclose($fp);
			$pos = strpos($ret, "\r\n\r\n");
			if($pos)
			{
				$rt = trim(substr($ret , $pos+1));
				$responseHead = trim(substr($ret, 0 , $pos));
				$responseHeads = explode("\r\n", $responseHead);
				$httpcode = explode(' ', $responseHeads[0]);
				$this->_httpcode = $httpcode[1];
				if(strpos( substr($ret , 0 , $pos), 'Transfer-Encoding: chunked'))
				{
					$response = explode("\r\n", $rt);
					$t = array_slice($response, 1, - 1);

					return implode('', $t);
				}
				return $rt;
			}
			return '';
        }
	}

	/**
	 * 返回上一次请求的httpCode
	 * @return number 
	 */
	public function getHttpCode()
	{
		return $this->_httpcode;
	}

	private function fwrite($handle,$data)
	{
		fwrite($handle, $data);
		if($this->_debug)
		{
			echo $data;
		}
	}
}
