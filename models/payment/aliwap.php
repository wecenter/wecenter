<?php

class payment_aliwap_class
{

    private $alipay_gateway_new = 'http://wappaygw.alipay.com/service/rest.htm?';

    /**
     * HTTPS形式消息验证地址
     */
    private $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';

    /**
     * HTTP形式消息验证地址
     */
    private $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

    private $cacert = AWS_PATH . 'config/alipay_cacert.pem';

    /**
     * 生成签名结果
     *
     * @param $para_sort 已排序要签名的数组
     * @return 签名结果字符串
     */
    public function buildRequestMysign($para_sort)
    {
        // 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        switch (get_setting('alipay_sign_type')) {
            case "MD5":
                $mysign = $this->md5Sign($prestr, get_setting('alipay_private_key'));

                break;

            case "RSA":
            case "0001":
                $mysign = $this->rsaSign($prestr, get_setting('alipay_private_key'));

                break;

            default:
                $mysign = '';
        }

        return $mysign;
    }

    /**
     * 生成要请求给支付宝的参数数组
     *
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    public function buildRequestPara($para_temp)
    {
        // 除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        // 对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        // 生成签名结果
        $mysign = $this->buildRequestMysign($para_sort);

        // 签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;

        if ($para_sort['service'] != 'alipay.wap.trade.create.direct' && $para_sort['service'] != 'alipay.wap.auth.authAndExecute') {
            $para_sort['sign_type'] = get_setting('alipay_sign_type');
        }

        return $para_sort;
    }

    /**
     * 生成要请求给支付宝的参数数组
     *
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组字符串
     */
    public function buildRequestParaToString($para_temp)
    {
        // 待请求参数数组
        $para = $this->buildRequestPara($para_temp);

        // 把参数组中所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
        $request_data = $this->createLinkstringUrlencode($para);

        return $request_data;
    }

    /**
     * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果
     *
     * @param $para_temp 请求参数数组
     * @return 支付宝处理结果
     */
    public function buildRequestHttp($para_temp)
    {
        $sResult = '';

        // 待请求参数数组字符串
        $request_data = $this->buildRequestPara($para_temp);

        // 远程获取数据
        $sResult = $this->getHttpResponsePOST($this->alipay_gateway_new, $this->cacert, $request_data, get_setting('alipay_input_charset'));

        return $sResult;
    }

    /**
     * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果，带文件上传功能
     *
     * @param $para_temp 请求参数数组
     * @param $file_para_name 文件类型的参数名
     * @param $file_name 文件完整绝对路径
     * @return 支付宝返回处理结果
     */
    public function buildRequestHttpInFile($para_temp, $file_para_name, $file_name)
    {

        // 待请求参数数组
        $para = $this->buildRequestPara($para_temp);
        $para[$file_para_name] = "@" . $file_name;

        // 远程获取数据
        $sResult = $this->getHttpResponsePOST($this->alipay_gateway_new, $this->cacert, $para, get_setting('alipay_input_charset'));

        return $sResult;
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @param $method 提交方式。两个值可选：post、get
     * @param $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
    function buildRequestForm($para_temp, $method, $button_name)
    {
        //待请求参数数组
        $para = $this->buildRequestPara($para_temp);

        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway_new."_input_charset=".get_setting('alipay_input_charset')."' method='".$method."'>";
        while (list ($key, $val) = each ($para)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='".$button_name."'></form>";

        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

        return $sHtml;
    }

    /**
     * 解析远程模拟提交后返回的信息
     *
     * @param $str_text 要解析的字符串
     * @return 解析结果
     */
    public function parseResponse($str_text)
    {
        // 以“&”字符切割字符串
        $para_split = explode('&', $str_text);
        // 把切割后的字符串数组变成变量与数值组合的数组
        foreach ($para_split as $item) {
            // 获得第一个=字符的位置
            $nPos = strpos($item, '=');
            // 获得字符串长度
            $nLen = strlen($item);
            // 获得变量名
            $key = substr($item, 0, $nPos);
            // 获得数值
            $value = substr($item, $nPos + 1, $nLen - $nPos - 1);
            // 放入数组中
            $para_text[$key] = $value;
        }

        if ($para_text['res_data']) {
            // 解析加密部分字符串
            if (get_setting('alipay_sign_type') == '0001') {
                $para_text['res_data'] = $this->rsaDecrypt($para_text['res_data'], get_setting('alipay_private_key'));
            }

            // token从res_data中解析出来（也就是说res_data中已经包含token的内容）
            $doc = new DOMDocument();
            $doc->loadXML($para_text['res_data']);
            $para_text['request_token'] = $doc->getElementsByTagName("request_token")->item(0)->nodeValue;
        }

        return $para_text;
    }

    /**
     * 用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
     * 注意：该功能PHP5环境及以上支持，因此必须服务器、本地电脑中装有支持DOMDocument、SSL的PHP配置环境。建议本地调试时使用PHP开发软件
     * return 时间戳字符串
     */
    function query_timestamp()
    {
        $url = $this->alipay_gateway_new . 'service=query_timestamp&partner=' . get_setting('alipay_partner') . '&_input_charset=' . get_setting('alipay_input_charset');
        $encrypt_key = '';

        $doc = new DOMDocument();
        $doc->load($url);
        $itemEncrypt_key = $doc->getElementsByTagName('encrypt_key');
        $encrypt_key = $itemEncrypt_key->item(0)->nodeValue;

        return $encrypt_key;
    }

    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     *
     * @return 验证结果
     */
    function verifyNotify()
    {
        if (!$_POST) { // 判断POST来的数组是否为空
            return false;

        // 对notify_data解密
        $decrypt_post_para = $_POST;
        if (get_setting('alipay_sign_type') == '0001') {
            $decrypt_post_para['notify_data'] = $this->rsaDecrypt($decrypt_post_para['notify_data'], get_setting('alipay_private_key'));
        }

        // notify_id从decrypt_post_para中解析出来（也就是说decrypt_post_para中已经包含notify_id的内容）
        $doc = new DOMDocument();
        $doc->loadXML($decrypt_post_para['notify_data']);
        $notify_id = $doc->getElementsByTagName("notify_id")->item(0)->nodeValue;

        // 获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
        $responseTxt = 'true';
        if ($notify_id) {
            $responseTxt = $this->getResponse($notify_id);
        }

        // 生成签名结果
        $isSign = $this->getSignVeryfy($decrypt_post_para, $_POST["sign"], false);

        // 写日志记录
        // if ($isSign) {
        // $isSignStr = 'true';
        // }
        // else {
        // $isSignStr = 'false';
        // }
        // $log_text = "responseTxt=".$responseTxt."\n notify_url_log:isSign=".$isSignStr.",";
        // $log_text = $log_text.createLinkString($_POST);
        // logResult($log_text);

        // 验证
        // $responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
        // isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
        if (!preg_match("/true$/i", $responseTxt) && $isSign) {
            return false;
        }

        return true;
    }

    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     *
     * @return 验证结果
     */
    function verifyReturn()
    {
        // 删除 Anwsion 系统参数
        unset($_GET['c'], $_GET['app'], $_GET['act']);

        if (empty($_GET)) { // 判断GET来的数组是否为空
            return false;
        } else {
            // 生成签名结果
            $isSign = $this->getSignVeryfy($_GET, $_GET["sign"], true);

            // 写日志记录
            // if ($isSign) {
            // $isSignStr = 'true';
            // }
            // else {
            // $isSignStr = 'false';
            // }
            // $log_text = "return_url_log:isSign=".$isSignStr.",";
            // $log_text = $log_text.createLinkString($_GET);
            // logResult($log_text);

            // 验证
            // $responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            // isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if ($isSign) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 解密
     *
     * @param $input_para 要解密数据
     * @return 解密后结果
     */
    function decrypt($prestr)
    {
        return $this->rsaDecrypt($prestr, get_setting('alipay_private_key'));
    }

    /**
     * 异步通知时，对参数做固定排序
     *
     * @param $para 排序前的参数组
     * @return 排序后的参数组
     */
    function sortNotifyPara($para)
    {
        $para_sort['service'] = $para['service'];
        $para_sort['v'] = $para['v'];
        $para_sort['sec_id'] = $para['sec_id'];
        $para_sort['notify_data'] = $para['notify_data'];
        return $para_sort;
    }

    /**
     * 获取返回时的签名验证结果
     *
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @param $isSort 是否对待签名数组排序
     * @return 签名验证结果
     */
    function getSignVeryfy($para_temp, $sign, $isSort)
    {
        // 除去待签名参数数组中的空值和签名参数
        $para = $this->paraFilter($para_temp);

        // 对待签名参数数组排序
        if ($isSort) {
            $para = $this->argSort($para);
        } else {
            $para = $this->sortNotifyPara($para);
        }

        // 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para);

        $isSgin = false;

        switch (get_setting('alipay_sign_type')) {
            case 'MD5':
                $isSgin = $this->md5Verify($prestr, $sign, get_setting('alipay_key'));

                break;

            case 'RSA':
            case '0001':
                $isSgin = $this->rsaVerify($prestr, get_setting('alipay_ali_public_key'), $sign);

                break;

            default:
                $isSgin = false;

                break;
        }

        return $isSgin;
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     *
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果 验证结果集：
     *         invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
     *         true 返回正确信息
     *         false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    function getResponse($notify_id)
    {
        $transport = get_setting('alipay_transport');
        $partner = get_setting('alipay_private_key');
        $veryfy_url = '';
        if ($transport == 'https') {
            $veryfy_url = $this->https_verify_url;
        } else {
            $veryfy_url = $this->http_verify_url;
        }
        $veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt = $this->getHttpResponseGET($veryfy_url, $this->cacert);

        return $responseTxt;
    }

    /**
     * 签名字符串
     *
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     *            return 签名结果
     */
    function md5Sign($prestr, $key)
    {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * 验证签名
     *
     * @param $prestr 需要签名的字符串
     * @param $sign 签名结果
     * @param $key 私钥
     *            return 签名结果
     */
    function md5Verify($prestr, $sign, $key)
    {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);

        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * RSA签名
     *
     * @param $data 待签名数据
     * @param $private_key 商户私钥
     * @return 签名结果
     */
    function rsaSign($data, $private_key)
    {
        $res = openssl_get_privatekey($private_key);

        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        // base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * RSA验签
     *
     * @param $data 待签名数据
     * @param $ali_public_key 支付宝公钥
     * @param $sign 要校对的的签名结果
     *            return 验证结果
     */
    function rsaVerify($data, $ali_public_key, $sign)
    {
        $res = openssl_get_publickey($ali_public_key);
        $result = (bool) openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result;
    }

    /**
     * RSA解密
     *
     * @param $content 需要解密的内容，密文
     * @param $private_key 商户私钥
     *            return 解密后内容，明文
     */
    function rsaDecrypt($content, $private_key)
    {
        $res = openssl_get_privatekey($private_key);
        // 用base64将内容还原成二进制
        $content = base64_decode($content);
        // 把需要解密的内容，按128位拆开解密
        $result = '';
        for ($i = 0; $i < strlen($content) / 128; $i ++) {
            $data = substr($content, $i * 128, 128);
            openssl_private_decrypt($data, $decrypt, $res);
            $result .= $decrypt;
        }
        openssl_free_key($res);
        return $result;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     *
     * @param $para 需要拼接的数组
     *            return 拼接完成以后的字符串
     */
    function createLinkstring($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        // 去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        // 如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     *
     * @param $para 需要拼接的数组
     *            return 拼接完成以后的字符串
     */
    function createLinkstringUrlencode($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . urlencode($val) . "&";
        }
        // 去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        // 如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 除去数组中的空值和签名参数
     *
     * @param $para 签名参数组
     *            return 去掉空值与签名参数后的新签名参数组
     */
    function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "")
                continue;
            else
                $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     *
     * @param $para 排序前的数组
     *            return 排序后的数组
     */
    function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 远程获取数据，POST模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     *
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * @param $para 请求的数据
     * @param $input_charset 编码格式。默认值：空值
     *            return 远程输出的数据
     */
    function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '')
    {
        if (trim($input_charset) != '') {
            $url = $url . "_input_charset=" . $input_charset;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); // 证书地址
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_POST, true); // post传输数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $para); // post传输数据
        $responseText = curl_exec($curl);
        // var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }

    /**
     * 远程获取数据，GET模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     *
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     *            return 远程输出的数据
     */
    function getHttpResponseGET($url, $cacert_url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); // 证书地址
        $responseText = curl_exec($curl);
        // var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }
}