<?php

include(AWS_PATH . 'config/alipay.php');

//商户的私钥（后缀是.pen）文件相对路径
//如果签名方式设置为“0001”时，请设置该参数
$config['private_key_path']	= AWS_PATH . 'config/alipay_rsa_private_key.pem';

//支付宝公钥（后缀是.pen）文件相对路径
//如果签名方式设置为“0001”时，请设置该参数
$config['ali_public_key_path'] = AWS_PATH . 'config/alipay_public_key.pem';

//签名方式
//$config['sign_type']    = '0001';