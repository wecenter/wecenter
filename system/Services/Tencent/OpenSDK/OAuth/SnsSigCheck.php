<?php

/**
 * @author      nemozhang             
 * @filesource
 */

class Services_Tencent_OpenSDK_OAuth_SnsSigCheck
{

	/**
	 * 生成签名
	 *
	 * @param string 	$method 请求方法 "get" or "post"
	 * @param string 	$url_path 
	 * @param array 	$params 表单参数
	 * @param string 	$secret 密钥
	 */
    static public function makeSig($method, $url_path, $params, $secret) 
    {
        $mk = self::makeSource ( $method, $url_path, $params );
        $my_sign = hash_hmac ( "sha1", $mk, strtr ( $secret, '-_', '+/' ), true );
        $my_sign = base64_encode ( $my_sign );                                                                                                                                       
        return $my_sign;
    }
    
	static private function makeSource($method, $url_path, $params) 
    {
        ksort ( $params );
        $strs = strtoupper($method) . '&' . rawurlencode ( $url_path ) . '&';
        $str = ""; 
        foreach ( $params as $key => $val ) { 
            $str .= "$key=$val&";
        }   
        $strc = substr ( $str, 0, strlen ( $str ) - 1 );
        return $strs . rawurlencode ( $strc );
    }
}


