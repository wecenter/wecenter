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

class core_crypt
{
    public function __construct()
    {
        if (!function_exists('mcrypt_module_open'))
        {
            exit('Error: Mcrypt Module not support');
        }
    }

    public function encode($data, $key = null)
    {
        $mcrypt = mcrypt_module_open($this->get_algorithms(), '', MCRYPT_MODE_ECB, '');

        mcrypt_generic_init($mcrypt, $this->get_key($mcrypt, $key), mcrypt_create_iv(mcrypt_enc_get_iv_size($mcrypt), MCRYPT_RAND));

        $result = mcrypt_generic($mcrypt, gzcompress($data));

        mcrypt_generic_deinit($mcrypt);
        mcrypt_module_close($mcrypt);

        return base64_encode($result);
    }

    public function decode($data, $key = null)
    {
        $mcrypt = mcrypt_module_open($this->get_algorithms(), '', MCRYPT_MODE_ECB, '');

        mcrypt_generic_init($mcrypt, $this->get_key($mcrypt, $key), mcrypt_create_iv(mcrypt_enc_get_iv_size($mcrypt), MCRYPT_RAND));

        $result = trim(mdecrypt_generic($mcrypt, base64_decode($data)));

        mcrypt_generic_deinit($mcrypt);
        mcrypt_module_close($mcrypt);

        return gzuncompress($result);
    }

    private function get_key($mcrypt, $key = null)
    {
        if (!$key)
        {
            $key = G_COOKIE_HASH_KEY;
        }

        return substr($key, 0, mcrypt_enc_get_key_size($mcrypt));
    }

    private function get_algorithms()
    {
        $algorithms = mcrypt_list_algorithms();

        foreach ($algorithms AS $algorithm)
        {
            if (strstr($algorithm, '-256'))
            {
                return $algorithm;
            }
        }

        foreach ($algorithms AS $algorithm)
        {
            if (strstr($algorithm, '-128'))
            {
                return $algorithm;
            }
        }

        return end($algorithms);
    }
}
