<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
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

class project_class extends AWS_MODEL
{
    public function get_project_info_by_id($project_id)
    {
        if ($project_info = $this->fetch_row('project', 'id = ' . intval($project_id)))
        {
            $project_info['contact'] = unserialize($project_info['contact']);
        }

        return $project_info;
    }

    public function get_project_info_by_ids($project_ids, $limit = null)
    {
        if (!is_array($project_ids) OR sizeof($project_ids) == 0)
        {
            return false;
        }

        array_walk_recursive($project_ids, 'intval_string');

        if ($projects_list = $this->fetch_all('project', "id IN(" . implode(',', $project_ids) . ")", null, $limit))
        {
            foreach ($projects_list AS $key => $val)
            {
                $val['contact'] = unserialize($val['contact']);

                $result[$val['id']] = $val;
            }
        }

        return $result;
    }

    public function update_project_discuss_count($project_id, $discuss_count)
    {
        return $this->update('project', array(
            'discuss_count' => intval($discuss_count)
        ), 'id = ' . intval($project_id));
    }

    public function remove_project_by_project_id($project_id)
    {
        if (!$project_info = $this->get_project_info_by_id($project_id))
        {
            return false;
        }

        $this->delete('topic_relation', "`type` = 'project' AND item_id = " . intval($project_id));     // 删除话题关联

        // 删除附件
        if ($attachs = $this->model('publish')->get_attach('project', $project_id))
        {
            foreach ($attachs as $key => $val)
            {
                $this->model('publish')->remove_attach($val['id'], $val['access_key']);
            }
        }

        return $this->delete('project', 'id = ' . intval($project_id));
    }


    public function get_projects_list($category_id, $approved, $status = null, $page, $per_page, $order_by = 'add_time DESC')
    {
        $where = array();

        if ($category_id)
        {
            $where[] = 'category_id = ' . intval($category_id);
        }

        if ($approved === 0 OR $approved === 1)
        {
            $where[] = 'approved = ' . intval($approved);
        }

        if ($status)
        {
            $where[] = "`status` = '" . $this->quote($status) . "'";
        }

        return $this->fetch_page('project', implode($where, ' AND '), $order_by, $page, $per_page);
    }

    public function get_projects_list_by_uid($uid, $approved, $status = null, $page, $per_page, $order_by = 'add_time DESC')
    {
        $where = array();

        if ($uid)
        {
            $where[] = 'uid = ' . intval($uid);
        }

        if ($approved === 0 OR $approved === 1)
        {
            $where[] = 'approved = ' . intval($approved);
        }

        if ($status)
        {
            $where[] = "`status` = '" . $this->quote($status) . "'";
        }

        return $this->fetch_page('project', implode($where, ' AND '), $order_by, $page, $per_page);
    }

    public function update_views($project_id)
    {
        if (AWS_APP::cache()->get('update_views_project_' . md5(session_id()) . '_' . intval($project_id)))
        {
            return false;
        }

        AWS_APP::cache()->set('update_views_project_' . md5(session_id()) . '_' . intval($project_id), time(), 60);

        $this->shutdown_query("UPDATE " . $this->get_table('project') . " SET views = views + 1 WHERE id = " . intval($project_id));

        return true;
    }

    public function publish_project($uid, $project_type, $category_id, $title, $country, $province, $city, $summary, $description, $amount, $start_time, $end_time, $contact, $topics, $video_link)
    {
        if (is_array($contact))
        {
            foreach ($contact AS $key => $val)
            {
                $contact[$key] = htmlspecialchars($val);
            }
        }

        if ($project_id = $this->insert('project', array(
            'uid' => intval($uid),
            'project_type' => htmlspecialchars($project_type),
            'title' => htmlspecialchars($title),
            'country' => htmlspecialchars($country),
            'province' => htmlspecialchars($province),
            'city' => htmlspecialchars($city),
            'summary' => htmlspecialchars($summary),
            'description' => htmlspecialchars($description),
            'start_time' => intval($start_time),
            'end_time' => intval($end_time),
            'amount' => $amount,
            'contact' => serialize($contact),
            'category_id' => intval($category_id),
            'add_time' => time(),
            'status' => 'ONLINE',
            'update_time' => time(),
            'video_link' => strip_tags($video_link)
        )))
        {
            if (is_array($topics))
            {
                foreach ($topics as $key => $topic_title)
                {
                    $topic_id = $this->model('topic')->save_topic($topic_title, $uid, true);

                    $this->model('topic')->save_topic_relation($uid, $topic_id, $project_id, 'project');
                }
            }

            /*$topic_id = $this->model('topic')->save_topic($title, $uid, true);

            $this->model('topic')->save_topic_relation($uid, $topic_id, $project_id, 'project');*/

            $this->update('project', array(
                'topic_id' => intval($topic_id)
            ), 'id = ' . intval($project_id));

            if ($attach_access_key)
            {
                $this->model('publish')->update_attach('project', $project_id, $attach_access_key);
            }
        }

        return $project_id;
    }

    public function update_project($project_id, $title, $category_id, $country, $province, $city, $summary, $description, $amount, $attach_access_key, $topics, $video_link, $start_time, $end_time)
    {
        if (!$project_info = $this->get_project_info_by_id($project_id))
        {
            return false;
        }

        if ($attach_access_key)
        {
            $this->model('publish')->update_attach('project', $project_id, $attach_access_key);
        }

        $this->delete('topic_relation', "`type` = 'project' AND item_id = " . intval($project_id));     // 删除话题关联

        if (is_array($topics))
        {
            foreach ($topics as $key => $topic_title)
            {
                $topic_id = $this->model('topic')->save_topic($topic_title, $project_info['uid'], true);

                $this->model('topic')->save_topic_relation($project_info['uid'], $topic_id, $project_id, 'project');
            }
        }

        /*$topic_id = $this->model('topic')->save_topic($project_info['title'], $project_info['uid'], true);

        $this->model('topic')->save_topic_relation($project_info['uid'], $topic_id, $project_id, 'project');*/

        $this->update('project', array(
            'title' => htmlspecialchars($title),
            'country' => htmlspecialchars($country),
            'province' => htmlspecialchars($province),
            'city' => htmlspecialchars($city),
            'summary' => htmlspecialchars($summary),
            'description' => htmlspecialchars($description),
            'category_id' => intval($category_id),
            'update_time' => time(),
            'topic_id' => intval($topic_id),
            'amount' => $amount,
            'video_link' => strip_tags($video_link),
            'start_time' => intval($start_time),
            'end_time' => intval($end_time),
        ), 'id = ' . intval($project_id));

        if ($project_info['approved'] == 1 AND $project_info['status'] == 'ONLINE')
        {
            $this->model('posts')->set_posts_index($project_id, 'project');
        }

        return true;
    }

    public function add_product($project_id, $title, $amount, $stock, $description)
    {
        if ((!$title AND !$amount) OR !$project_id)
        {
            return false;
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount) OR intval($amount) == 0)
        {
            $amount = '0.00';
        }

        return $this->insert('project_product', array(
            'project_id' => intval($project_id),
            'title' => htmlspecialchars($title),
            'amount' => $amount,
            'stock' => intval($stock),
            'description' => htmlspecialchars($description)
        ));
    }

    public function get_products_by_project_id($project_id)
    {
        return $this->fetch_all('project_product', 'project_id = ' . intval($project_id), 'id ASC');
    }

    public function get_product_order_by_id($id)
    {
        return $this->fetch_row('product_order', 'id = ' . intval($id));
    }

    public function get_product_orders_by_product_id($product_id)
    {
        return $this->fetch_all('product_order', 'product_id = ' . intval($product_id), 'id ASC');
    }

    public function remove_project_product_by_id($product_id)
    {
        return $this->delete('project_product', 'id = ' . intval($product_id));
    }

    public function get_project_list_by_topic_ids($topic_ids, $order_by = 'add_time DESC', $page = 1, $per_page = 10)
    {
        if (!is_array($topic_ids))
        {
            return false;
        }

        array_walk_recursive($topic_ids, 'intval_string');

        $result_cache_key = 'project_list_by_topic_ids_' . implode('_', $topic_ids) . '_' . md5($answer_count . $category_id . $order_by . $is_recommend . $page . $per_page . $post_type);

        $found_rows_cache_key = 'project_list_by_topic_ids_found_rows_' . implode('_', $topic_ids) . '_' . md5($answer_count . $category_id . $is_recommend . $per_page . $post_type);

        $where[] = 'topic_relation.topic_id IN(' . implode(',', $topic_ids) . ')';

        $on_query[] = 'project.id = topic_relation.item_id';
        $on_query[] = "topic_relation.type = 'project'";

        $on_query[] = 'project.approved = 1';

        $on_query[] = "project.status = 'ONLINE'";

        if (!$found_rows = AWS_APP::cache()->get($found_rows_cache_key))
        {
            $_found_rows = $this->query_row('SELECT COUNT(DISTINCT project.id) AS count FROM ' . $this->get_table('project') . ' AS project LEFT JOIN ' . $this->get_table('topic_relation') . " AS topic_relation ON " . implode(' AND ', $on_query) . " WHERE " . implode(' AND ', $where));

            $found_rows = $_found_rows['count'];

            AWS_APP::cache()->set($found_rows_cache_key, $found_rows, get_setting('cache_level_high'));
        }

        $this->project_list_total = $found_rows;

        if (!$result = AWS_APP::cache()->get($result_cache_key))
        {
            $result = $this->query_all('SELECT project.* FROM ' . $this->get_table('project') . ' AS project LEFT JOIN ' . $this->get_table('topic_relation') . " AS topic_relation ON " . implode(' AND ', $on_query) . " WHERE " . implode(' AND ', $where) . ' GROUP BY project.id ORDER BY project.' . $order_by, calc_page_limit($page, $per_page));

            AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_high'));
        }

        return $result;
    }

    public function get_project_list_total()
    {
        return $this->project_list_total;
    }

    public function get_product_info_by_id($product_id)
    {
        return $this->fetch_row('project_product', 'id = ' . intval($product_id));
    }

    public function add_project_order($uid, $product_id, $shipping_name, $shipping_province, $shipping_city, $shipping_address, $shipping_zipcode, $shipping_mobile, $is_donate, $note, $amount = null)
    {
        if (!$product_info = $this->get_product_info_by_id($product_id))
        {
            return false;
        }

        if (!$project_info = $this->get_project_info_by_id($product_info['project_id']))
        {
            return false;
        }

        if ($product_info['stock'] == 0)
        {
            return false;
        }
        else if ($product_info['stock'] > 0)
        {
            $this->query("UPDATE " . get_table('project_product') . " SET stock = stock - 1 WHERE id = " . $product_info['id']);
        }

        if (intval($product_info['amount']) != 0)
        {
            $amount = $product_info['amount'];
        }

        return $this->insert('product_order', array(
            'uid' => intval($uid),
            'project_type' => $project_info['project_type'],
            'product_id' => intval($product_id),
            'project_id' => $product_info['project_id'],
            'project_title' => $project_info['title'],
            'product_title' => $product_info['title'],
            'amount' => $amount,
            'description' => $product_info['description'],
            'shipping_name' => htmlspecialchars($shipping_name),
            'shipping_province' => htmlspecialchars($shipping_province),
            'shipping_city' => htmlspecialchars($shipping_city),
            'shipping_address' => htmlspecialchars($shipping_address),
            'shipping_mobile' => htmlspecialchars($shipping_mobile),
            'shipping_zipcode' => htmlspecialchars($shipping_zipcode),
            'is_donate' => intval($is_donate),
            'note' => htmlspecialchars($note),
            'add_time' => time()
        ));
    }

    public function add_project_event($project_id, $uid, $amount, $name, $mobile, $email, $address)
    {
        if (!$project_info = $this->get_project_info_by_id($project_id))
        {
            return false;
        }

        switch ($project_info['project_type'])
        {
            case 'EVENT':
                $this->query("UPDATE " . get_table('project') . " SET paid = paid + 1 WHERE id = " . intval($project_id));
            break;

            case 'STOCK':
                $this->query("UPDATE " . get_table('project') . " SET paid = paid + " . round($amount, 2) . " WHERE id = " . intval($project_id));
            break;
        }


        return $this->insert('product_order', array(
            'uid' => intval($uid),
            'project_type' => $project_info['project_type'],
            'product_id' => 0,
            'project_id' => $project_info['id'],
            'project_title' => $project_info['title'],
            'amount' => $amount,
            'description' => '',
            'shipping_name' => htmlspecialchars($name),
            'shipping_province' => '',
            'shipping_city' => '',
            'shipping_address' => htmlspecialchars($email),
            'shipping_mobile' => htmlspecialchars($mobile),
            'shipping_zipcode' => '',
            'is_donate' => 1,
            'note' => '',
            'add_time' => time(),
            'address' => htmlspecialchars($address)
        ));
    }

    public function get_project_order_info_by_id($id)
    {
        return $this->fetch_row('product_order', 'id = ' . intval($id));
    }

    public function get_single_project_order_by_uid($uid, $project_id)
    {
        $order_list = $this->fetch_all('product_order', 'project_id = ' . intval($project_id) . ' AND uid = ' . intval($uid), 'add_time DESC');

        foreach ($order_list AS $key => $order_info)
        {
            if ($order_info['project_type'] == 'EVENT' AND $order_info['has_attach'] == 1)
            {
                $attach = reset($this->model('publish')->get_attach('product_order', $order_info['id'], null));

                if ($attach)
                {
                    $order_list[$key]['attach'] = $attach;
                }
            }
        }

        return $order_list;
    }

    public function get_sponsored_order_list($filter, $uid, $page, $per_page)
    {
        switch ($filter)
        {
            default:
                $where[] = "cancel_time = 0";
            break;

            case 'preparing':
                $where[] = "payment_time > 0 AND is_donate = 0 AND refund_time = 0 AND track_no = '' AND project_type = 'DEFAULT'";
            break;

            case 'shipped':
                $where[] = "payment_time > 0 AND is_donate = 0 AND refund_time = 0 AND track_no != '' AND project_type = 'DEFAULT'";
            break;

            case 'refunded':
                $where[] = "payment_time > 0 AND refund_time > 0 AND project_type = 'DEFAULT'";
            break;

            case 'donate':
                $where[] = "payment_time > 0 AND is_donate = 1 AND refund_time = 0 AND project_type = 'DEFAULT'";
            break;

            case 'pay':
                $where[] = "cancel_time = 0 AND payment_time = 0 AND project_type = 'DEFAULT'";
            break;

            case 'event':
                $where[] = "project_type = 'EVENT'";
            break;

            case 'stock':
                $where[] = "project_type = 'STOCK'";
            break;
        }

        if ($uid)
        {
            $where[] = 'uid = ' . intval($uid);
        }

        return $this->fetch_page('product_order', implode($where, ' AND '), 'id DESC', $page, $per_page);
    }

    public function get_order_list($filter, $page, $per_page)
    {
        switch ($filter)
        {
            case 'preparing':
                $where[] = "payment_order_id > 0 AND is_donate = 0 AND refund_time = 0 AND track_no = ''";
            break;

            case 'shipped':
                $where[] = "payment_order_id > 0 AND is_donate = 0 AND refund_time = 0 AND track_no != ''";
            break;

            case 'refunded':
                $where[] = "payment_order_id > 0 AND refund_time > 0";
            break;

            case 'donate':
                $where[] = "payment_order_id > 0 AND is_donate = 1 AND refund_time = 0";
            break;

            case 'pay':
                $where[] = "payment_order_id = 0";
            break;
        }

        return $this->fetch_page('product_order', implode($where, ' AND '), 'id DESC', $page, $per_page);
    }

    public function get_project_publisher_order_list($uid, $filter, $page, $per_page)
    {
        if (!$publisher_projects = $this->query_all("SELECT id FROM " . get_table('project') . " WHERE uid = " . intval($uid)))
        {
            return false;
        }

        foreach ($publisher_projects AS $key => $val)
        {
            $project_ids[] = $val['id'];
        }

        $where[] = 'project_id IN (' . implode(',', $project_ids) . ')';

        switch ($filter)
        {
            case 'preparing':
                $where[] = "payment_order_id > 0 AND is_donate = 0 AND refund_time = 0 AND track_no = '' AND project_type = 'DEFAULT'";
            break;

            case 'shipped':
                $where[] = "payment_order_id > 0 AND is_donate = 0 AND refund_time = 0 AND track_no != '' AND project_type = 'DEFAULT'";
            break;

            case 'refunded':
                $where[] = "payment_order_id > 0 AND refund_time > 0 AND project_type = 'DEFAULT'";
            break;

            case 'donate':
                $where[] = "payment_order_id > 0 AND is_donate = 1 AND refund_time = 0 AND project_type = 'DEFAULT'";
            break;

            case 'pay':
                $where[] = "payment_order_id = 0 AND project_type = 'DEFAULT'";
            break;

            case 'event':
                $where[] = "project_type = 'EVENT'";
            break;

            case 'stock':
                $where[] = "project_type = 'STOCK'";
            break;
        }

        $order_list = $this->fetch_page('product_order', implode($where, ' AND '), 'id DESC', $page, $per_page);

        foreach ($order_list AS $key => $order_info)
        {
            if ($order_info['project_type'] == 'EVENT' AND $order_info['has_attach'] == 1)
            {
                $attach = reset($this->model('publish')->get_attach('product_order', $order_info['id'], null));

                if ($attach)
                {
                    $order_list[$key]['attach'] = $attach;
                }
            }
        }

        return $order_list;
    }

    public function get_order_status($order_info)
    {
        if (!$order_info['payment_time'])
        {
            return 'pay';
        }

        if ($order_info['refund_time'])
        {
            return 'refunded';
        }

        if ($order_info['track_no'])
        {
            return 'shipped';
        }

        if ($order_info['is_donate'])
        {
            return 'donate';
        }

        return 'preparing';
    }

    public function get_sponsored_users($project_id, $sponsored_users = null, $project_type = null)
    {
        if ($project_type == 'DEFAULT')
        {
            $order_uids = $this->query_all("SELECT DISTINCT uid FROM " . get_table('product_order') . " WHERE payment_time > 0 AND project_id = " . intval($project_id));
        }
        else
        {
            $order_uids = $this->query_all("SELECT DISTINCT uid FROM " . get_table('product_order') . " WHERE project_id = " . intval($project_id));
        }

        if ($order_uids)
        {
            if (isset($sponsored_users) AND $sponsored_users != sizeof($order_uids))
            {
                $this->update('project', array(
                    'sponsored_users' => sizeof($order_uids)
                ), 'id = ' . intval($project_id));
            }

            foreach ($order_uids AS $key => $val)
            {
                $uids[] = $val['uid'];
            }

            return $this->model('account')->get_user_info_by_uids($uids);
        }
    }

    public function add_order_shipping($order_id, $track_branch, $track_no)
    {
        return $this->update('product_order', array(
            'track_branch' => htmlspecialchars($track_branch),
            'track_no' => htmlspecialchars($track_no),
            'shipping_time' => time()
        ), 'id = ' . intval($order_id));
    }

    public function set_order_cancel_time_by_id($id)
    {
        return $this->update('product_order', array(
            'cancel_time' => time()
        ), 'id = ' . intval($id));
    }

    public function cancel_project_order_by_id($id)
    {
        if (!$order_info = $this->get_project_order_info_by_id($id))
        {
            return false;
        }

        if (!$product_info = $this->get_product_info_by_id($order_info['product_id']))
        {
            return false;
        }

        if ($order_info['payment_time'])
        {
            // 已经被支付
            return false;
        }

        if ($order_info['refund_time'])
        {
            // 已经被退款
            return false;
        }

        if ($order_info['payment_order_id'])
        {
            $this->model('payment_alipay')->closeTrade($order_info['payment_order_id']);
        }

        if ($product_info['stock'] >= 0)
        {
            // 回增库存
            $this->query("UPDATE " . get_table('project_product') . " SET stock = stock + 1 WHERE id = " . $product_info['id']);
        }

        return $this->set_order_cancel_time_by_id($id);
    }

    public function set_project_approval($project_id)
    {
        if (!$project_info = $this->get_project_info_by_id($project_id))
        {
            return false;
        }

        if ($product_info['status'] == 'ONLINE')
        {
            $this->model('posts')->set_posts_index($project_id, 'project');
        }

        return $this->update('project', array(
            'approved' => 1
        ), 'id = ' . intval($project_id));
    }

    public function set_project_decline($project_id)
    {
        if (!$project_info = $this->get_project_info_by_id($project_id))
        {
            return false;
        }

        $this->model('posts')->remove_posts_index($project_id, 'project');

        return $this->update('project', array(
            'approved' => -1
        ), 'id = ' . intval($project_id));
    }

    public function set_project_status($project_id, $status)
    {
        if (!$project_info = $this->get_project_info_by_id($project_id))
        {
            return false;
        }

        if ($project_info['approved'] == 1)
        {
            switch ($status)
            {
                case 'ONLINE':
                    $this->model('posts')->set_posts_index($project_id, 'project');
                break;

                case 'OFFLINE':
                    $this->model('posts')->remove_posts_index($project_id, 'project');
                break;
            }
        }

        return $this->update('project', array(
            'status' => htmlspecialchars($status)
        ), 'id = ' . intval($project_id));
    }


    public function set_project_payment_order_id($order_id, $payment_order_id)
    {
        return $this->update('product_order', array(
            'payment_order_id' => $payment_order_id
        ), 'id = ' . intval($order_id));
    }

    public function get_like_status_by_uid($product_id, $uid)
    {
        return $this->fetch_row('project_like', 'project_id = ' . intval($product_id) . ' AND uid = ' . intval($uid));
    }

    public function set_project_like($project_id, $uid)
    {
        if (!$project_id OR !$uid)
        {
            return false;
        }

        if ($this->get_like_status_by_uid($project_id, $uid))
        {
            return false;
        }

        $like_id = $this->insert('project_like', array(
            'project_id' => intval($project_id),
            'uid' => intval($uid),
            'add_time' => time()
        ));

        $this->update('project', array(
            'like_count' => $this->count('project_like', 'project_id = ' . intval($project_id))
        ), 'id = ' . intval($project_id));

        // Modify by wecenter
        ACTION_LOG::save_action($uid, $project_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_LIKE_PROJECT);

        return $like_id;
    }

    public function unset_project_like($project_id, $uid)
    {
        if (!$project_id OR !$uid)
        {
            return false;
        }

        $this->delete('project_like', 'project_id = ' . intval($project_id) . ' AND uid = ' . intval($uid));

        $this->update('project', array(
            'like_count' => $this->count('project_like', 'project_id = ' . intval($project_id))
        ), 'id = ' . intval($project_id));

        return true;
    }

    public function get_random_project($topic_ids = NULL, $total = null)
    {
        $now = time();

        $project_list = array();

        if (is_array($topic_ids) AND $topic_ids)
        {
            $topics_relation = $this->fetch_all('topic_relation', 'topic_id IN(' . implode(',', $topic_ids) . ") AND type = 'project'");

            if ($topics_relation)
            {
                foreach ($topics_relation AS $topic_relation)
                {
                    $project_ids[] = $topic_relation['item_id'];
                }

                $project_list = $this->fetch_all('project', 'id IN(' . implode(',', $project_ids) . ') AND approved = 1 AND status = "ONLINE" AND start_time < ' . $now . ' AND end_time > ' . $now);
            }
        }

        if (!$project_list)
        {
            for ($i=0; $i<20; $i++)
            {
                $cached_project_info = AWS_APP::cache()->get('random_project_' . $i);

                if (!$cached_project_info)
                {
                    break;
                }

                $project_list[] = $cached_project_info;
            }
        }

        if (!$project_list)
        {
            $project_list = $this->fetch_all('project', 'approved = 1 AND status = "ONLINE" AND start_time < ' . $now . ' AND end_time > ' . $now, 'rand()', 20);

            for ($i=0; $i<count($project_list); $i++)
            {
                AWS_APP::cache()->set('random_project_' . $i, $project_list[$i], get_setting('cache_level_low'));
            }
        }

        shuffle($project_list);

        if ($total)
        {
            return array_slice($project_list, 0 , intval($total));
        }

        return $project_list;
    }

    public function send_project_open_close_notify()
    {
        if ($open_project = $this->fetch_all('project', 'open_notify = 0 AND start_time > ' . time()))
        {
            foreach ($open_project AS $key => $val)
            {
                $this->update('project', array(
                    'open_notify' => time()
                ), 'id = ' . intval($val['id']));

                if ($project_like_users = $this->fetch_all('project_like', 'project_id = ' . intval($val['id'])))
                {
                    foreach ($project_like_users AS $k => $v)
                    {
                        $this->model('notify')->send(0, $v['uid'], notify_class::TYPE_CONTEXT, notify_class::CATEGORY_CONTEXT, 'PROJO_' . $val['id'], array(
                            'content' => '<a href="project/' . $val['id'] . '">活动: ' . $val['title'] . ' 进行中</a>'
                        ));
                    }
                }
            }
        }

        if ($close_project = $this->fetch_all('project', 'close_notify = 0 AND end_time < ' . (time() + 86400)))
        {
            foreach ($open_project AS $key => $val)
            {
                $this->update('project', array(
                    'close_notify' => time()
                ), 'id = ' . intval($val['id']));

                if ($project_like_users = $this->fetch_all('project_like', 'project_id = ' . intval($val['id'])))
                {
                    foreach ($project_like_users AS $k => $v)
                    {
                        $this->model('notify')->send(0, $v['uid'], notify_class::TYPE_CONTEXT, notify_class::CATEGORY_CONTEXT, 'PROJC_' . $val['id'], array(
                            'content' => '<a href="project/' . $val['id'] . '">活动: ' . $val['title'] . ' 将于 24 小时内结束</a>'
                        ));
                    }
                }
            }
        }
    }

    public function update_order($order_id, $order_info)
    {
        if (!is_digits($order_id))
        {
            return false;
        }

        $to_update_order = array(
            'shipping_address' => htmlspecialchars($order_info['shipping_address']),
            'shipping_name' => htmlspecialchars($order_info['shipping_name']),
            'shipping_province' => htmlspecialchars($order_info['shipping_province']),
            'shipping_city' => htmlspecialchars($order_info['shipping_city']),
            'shipping_mobile' => htmlspecialchars($order_info['shipping_mobile']),
            'shipping_zipcode' => htmlspecialchars($order_info['shipping_zipcode']),
            'track_no' => htmlspecialchars($order_info['track_no']),
            'track_branch' => htmlspecialchars($order_info['track_branch']),
            'note' => htmlspecialchars($order_info['note']),
            'address' => htmlspecialchars($order_info['address'])
        );

        return $this->update('product_order', $to_update_order, 'id = ' . $order_id);
    }
}