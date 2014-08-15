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
+--------------------------------------------------------------------------
*/

if (! defined('IN_ANWSION'))
{
    die;
}

class openid_weixin_third_class extends AWS_MODEL
{
    public function send_message_to_third_party($account_id, $post_data)
    {
        if (!$account_id OR !$post_data)
        {
            return false;
        }

        $rules = $this->get_third_party_api_by_account_id($account_id, true, 'rank ASC');

        if (!$rules)
        {
            return false;
        }

        $timestamp = time();

        $nonce = mt_rand(1000000000, 9999999999);

        foreach ($rules AS $rule)
        {
            if (!$rule['url'] OR !$rule['token'])
            {
                continue;
            }

            $signature = $this->generate_signature($rule['token'], $timestamp, $nonce);

            if (!$signature)
            {
                continue;
            }

            $url = $rule['url'] . '?signature=' . $signature . '&timestamp=' . $timestamp . '&nonce=' . $nonce;

            $response = HTTP::request($url, 'POST', $post_data, 5);

            if ($response)
            {
                return $response;
            }
        }

        return false;
    }

    public function get_third_party_api_by_account_id($account_id, $enabled = null, $order = null)
    {
        if (!is_digits($account_id))
        {
            return false;
        }

        $where[] = 'account_id = ' . $account_id;

        if ($enabled === true)
        {
            $where[] = 'enabled = 1';
        }
        else if ($enabled === false)
        {
            $where[] = 'enabled = 0';
        }

        return $this->fetch_all('weixin_third_party_api', implode(' AND ', $where), $order);
    }

    public function get_third_party_api_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        return $this->fetch_row('weixin_third_party_api', 'id = ' . $id);
    }

    public function remove_third_party_api_by_account_id($account_id)
    {
        if (!is_digits($account_id))
        {
            return false;
        }

        return $this->model('weixin')->delete('weixin_third_party_api', 'account_id = ' . $account_id);
    }

    public function remove_third_party_api_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        return $this->model('weixin')->delete('weixin_third_party_api', 'id = ' . $id);
    }

    public function update_third_party_api($id = null, $action, $url, $token, $enabled = null, $account_id = null, $rank = null)
    {
        if ($action == 'update' AND !is_digits($id) OR $action == 'add' AND !is_digits($account_id))
        {
            return false;
        }

        $to_save_rule = array();

        if ($url)
        {
            $to_save_rule['url'] = $url;
        }

        if ($token)
        {
            $to_save_rule['token'] = $token;
        }

        if ($enabled !== null)
        {
            if ($enabled == 1)
            {
                $to_save_rule['enabled'] = '1';
            }
            else
            {
                $to_save_rule['enabled'] = '0';
            }
        }

        if (is_digits($account_id))
        {
            $to_save_rule['account_id'] = $account_id;
        }

        if (is_digits($rank) AND $rank >= 0 AND $rank <= 99)
        {
            $to_save_rule['rank'] = $rank;
        }

        switch ($action)
        {
            case 'add':
                return $this->insert('weixin_third_party_api', $to_save_rule);

                break;

            case 'update':
                return $this->update('weixin_third_party_api', $to_save_rule, 'id = ' . $id);

                break;

            default:
                return false;

                break;
        }
    }
}
