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

if (!defined('IN_ANWSION'))
{
    die;
}

class openid_twitter_class extends AWS_MODEL
{
    const OAUTH_REQUEST_TOKEN_URL = 'https://api.twitter.com/oauth/request_token';

    const OAUTH_AUTH_URL = 'https://api.twitter.com/oauth/authenticate';

    const OAUTH_TOKEN_URI = 'https://api.twitter.com/oauth/access_token';

    const OAUTH_AUTH_VALIDATION_URL = 'https://api.twitter.com/1.1/account/verify_credentials.json';

    public function build_auth_header($args, $request_url, $request_method)
    {
        if (!$args OR !is_array($args))
        {
            return false;
        }

        $timestamp = time();

        $args['oauth_consumer_key'] = get_setting('twitter_consumer_key');

        $args['oauth_nonce'] = base64_encode(md5($timestamp));

        $args['oauth_signature_method'] = 'HMAC-SHA1';

        $args['oauth_timestamp'] = $timestamp;

        $args['oauth_version'] = '1.0';

        natcasesort($args);

        $parm_str = http_build_query($args, '', '&', PHP_QUERY_RFC3986);

        $sign_base_str = $request_method . '&' . rawurlencode($request_url) . '&' . $parm_str;

        $sign_key = get_setting('twitter_consumer_secret');

        if ($args['oauth_token'])
        {
            $sign_key .= '&' . $args['oauth_token'];
        }

        $args['oauth_signature'] = base64_encode(hash_hmac('sha1', $sign_base_str, $sign_key, true));

        natcasesort($args);

        $auth_header = 'OAuth ';

        foreach ($args AS $key => $value)
        {
            $auth_header .= $key . '="' . rawurlencode($value) . '", ';
        }

        return substr($auth_header, 0, strlen($auth_header) - 2);
    }

    public function get_request_token($oauth_callback)
    {
        $args = array(
            'oauth_callback' => get_js_url($oauth_callback)
        );

        $header['Authorization'] = $this->build_auth_header($args, self::OAUTH_REQUEST_TOKEN_URL, 'POST');

        $result = HTTP::request(self::OAUTH2_USER_INFO_URL, 'POST', null, 10, $header);

        if (!$result)
        {
            return '获取 request token 时，与 Twitter 通信失败';
        }

        $result = json_decode($result, true);

        if ($result['error'])
        {
            return '获取 request token 失败，错误为：' . $result['error']['message'];
        }
    }
}
