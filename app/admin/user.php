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

class user extends AWS_ADMIN_CONTROLLER
{
    public function list_action()
    {
        if ($_POST['action'] == 'search')
        {
            foreach ($_POST as $key => $val)
            {
                if (in_array($key, array('user_name', 'email')))
                {
                    $val = rawurlencode($val);
                }

                $param[] = $key . '-' . $val;
            }

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/user/list/' . implode('__', $param))
            ), 1, null));
        }

        $where = array();

        if ($_GET['type'] == 'forbidden')
        {
            $where[] = 'forbidden = 1';
        }

        if ($_GET['user_name'])
        {
            $where[] = "user_name LIKE '%" . $this->model('people')->quote($_GET['user_name']) . "%'";
        }

        if ($_GET['email'])
        {
            $where[] = "email = '" . $this->model('people')->quote($_GET['email']) . "'";
        }

        if ($_GET['group_id'])
        {
            $where[] = 'group_id = ' . intval($_GET['group_id']);
        }

        if ($_GET['ip'] AND preg_match('/(\d{1,3}\.){3}(\d{1,3}|\*)/', $_GET['ip']))
        {
            if (substr($_GET['ip'], -2, 2) == '.*')
            {
                $ip_base = ip2long(str_replace('.*', '.0', $_GET['ip']));

                if ($ip_base)
                {
                    $where[] = 'last_ip BETWEEN ' . $ip_base . ' AND ' . ($ip_base + 255);
                }
            }
            else
            {
                $ip_base = ip2long($_GET['ip']);

                if ($ip_base)
                {
                    $where[] = 'last_ip = ' . $ip_base;
                }
            }
        }

        if ($_GET['integral_min'])
        {
            $where[] = 'integral >= ' . intval($_GET['integral_min']);
        }

        if ($_GET['integral_max'])
        {
            $where[] = 'integral <= ' . intval($_GET['integral_max']);
        }

        if ($_GET['reputation_min'])
        {
            $where[] = 'reputation >= ' . intval($_GET['reputation_min']);
        }

        if ($_GET['reputation_max'])
        {
            $where[] = 'reputation <= ' . intval($_GET['reputation_max']);
        }

        if ($_GET['job_id'])
        {
            $where[] = 'job_id = ' . intval($_GET['job_id']);
        }

        if ($_GET['province'])
        {
            $where[] = "province = '" . $this->model('people')->quote($_GET['province']) . "'";
        }

        if ($_GET['city'])
        {
            $where[] = "city = '" . $this->model('people')->quote($_GET['city']) . "'";
        }

        $user_list = $this->model('people')->fetch_page('users', implode(' AND ', $where), 'uid DESC', $_GET['page'], $this->per_page);

        $total_rows = $this->model('people')->found_rows();

        $url_param = array();

        foreach($_GET as $key => $val)
        {
            if (!in_array($key, array('app', 'c', 'act', 'page')))
            {
                $url_param[] = $key . '-' . $val;
            }
        }

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/user/list/') . implode('__', $url_param),
            'total_rows' => $total_rows,
            'per_page' => $this->per_page
        ))->create_links());

        $this->crumb(AWS_APP::lang()->_t('会员列表'), "admin/user/list/");

        TPL::assign('mem_group', $this->model('account')->get_user_group_list(1));
        TPL::assign('system_group', $this->model('account')->get_user_group_list(0));
        TPL::assign('job_list', $this->model('work')->get_jobs_list());
        TPL::assign('total_rows', $total_rows);
        TPL::assign('list', $user_list);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        TPL::output('admin/user/list');
    }

    public function group_list_action()
    {
        $this->crumb(AWS_APP::lang()->_t('用户组管理'), "admin/user/group_list/");

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('mem_group', $this->model('account')->get_user_group_list(1));
        TPL::assign('system_group', $this->model('account')->get_user_group_list(0, 0));
        TPL::assign('custom_group', $this->model('account')->get_user_group_list(0, 1));
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(403));
        TPL::output('admin/user/group_list');
    }

    public function group_edit_action()
    {
        $this->crumb(AWS_APP::lang()->_t('修改用户组'), "admin/user/group_list/");

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        if (! $group = $this->model('account')->get_user_group_by_id($_GET['group_id']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('用户组不存在'), '/admin/user/group_list/');
        }

        TPL::assign('group', $group);
        TPL::assign('group_pms', $group['permission']);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(403));
        TPL::output('admin/user/group_edit');
    }

    public function edit_action()
    {
        if (!$user = $this->model('account')->get_user_info_by_uid($_GET['uid'], TRUE))
        {
            H::redirect_msg(AWS_APP::lang()->_t('用户不存在'), '/admin/user/list/');
        }

        $this->crumb(AWS_APP::lang()->_t('编辑用户资料'), "admin/user/edit/");

        TPL::assign('job_list', $this->model('work')->get_jobs_list());
        TPL::assign('mem_group', $this->model('account')->get_user_group_by_id($user['reputation_group']));
        TPL::assign('system_group', $this->model('account')->get_user_group_list(0));
        TPL::assign('user', $user);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        TPL::output('admin/user/edit');
    }

    public function user_add_action()
    {
        $this->crumb(AWS_APP::lang()->_t('添加用户'), "admin/user/list/user_add/");

        TPL::assign('job_list', $this->model('work')->get_jobs_list());

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        TPL::assign('system_group', $this->model('account')->get_user_group_list(0));

        TPL::output('admin/user/add');
    }

    public function invites_action()
    {
        $this->crumb(AWS_APP::lang()->_t('批量邀请'), "admin/user/invites/");

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(406));
        TPL::output('admin/user/invites');
    }

    public function job_list_action()
    {
        TPL::assign('job_list', $this->model('work')->get_jobs_list());

        $this->crumb(AWS_APP::lang()->_t('职位设置'), "admin/user/job_list/");

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(407));
        TPL::output('admin/user/job_list');
    }

    public function verify_approval_list_action()
    {
        $approval_list = $this->model('verify')->approval_list($_GET['page'], $_GET['status'], $this->per_page);

        $total_rows = $this->model('verify')->found_rows();

        foreach ($approval_list AS $key => $val)
        {
            if (!$uids[$val['uid']])
            {
                $uids[$val['uid']] = $val['uid'];
            }
        }

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/user/verify_approval_list/status-' . $_GET['status']),
            'total_rows' => $total_rows,
            'per_page' => $this->per_page
        ))->create_links());

        $this->crumb(AWS_APP::lang()->_t('认证审核'), 'admin/user/verify_approval_list/');

        TPL::assign('users_info', $this->model('account')->get_user_info_by_uids($uids));
        TPL::assign('approval_list', $approval_list);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(401));

        TPL::output('admin/user/verify_approval_list');
    }

    public function register_approval_list_action()
    {
        if (get_setting('register_valid_type') != 'approval')
        {
            H::redirect_msg(AWS_APP::lang()->_t('未启用新用户注册审核'), '/admin/');
        }

        $user_list = $this->model('people')->fetch_page('users', 'group_id = 3', 'uid ASC', $_GET['page'], $this->per_page);

        $total_rows = $this->model('people')->found_rows();

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/user/register_approval_list/'),
            'total_rows' => $total_rows,
            'per_page' => $this->per_page
        ))->create_links());

        $this->crumb(AWS_APP::lang()->_t('注册审核'), 'admin/user/register_approval_list/');

        TPL::assign('list', $user_list);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(408));

        TPL::output('admin/user/register_approval_list');
    }

    public function verify_approval_edit_action()
    {
        if (!$verify_apply = $this->model('verify')->fetch_apply($_GET['id']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('审核认证不存在'), '/admin/user/register_approval_list/');
        }

        TPL::assign('verify_apply', $verify_apply);
        TPL::assign('user', $this->model('account')->get_user_info_by_uid($_GET['id']));

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(401));

        $this->crumb(AWS_APP::lang()->_t('编辑认证审核资料'), 'admin/user/verify_approval_list/');

        TPL::output('admin/user/verify_approval_edit');
    }

    public function integral_log_action()
    {
        if ($log = $this->model('integral')->fetch_page('integral_log', 'uid = ' . intval($_GET['uid']), 'time DESC', $_GET['page'], 50))
        {
            TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
                'base_url' => get_js_url('/admin/user/integral_log/uid-' . intval($_GET['uid'])),
                'total_rows' => $this->model('integral')->found_rows(),
                'per_page' => 50
            ))->create_links());

            foreach ($log AS $key => $val)
            {
                $parse_items[$val['id']] = array(
                    'item_id' => $val['item_id'],
                    'action' => $val['action']
                );
            }

            TPL::assign('integral_log', $log);
            TPL::assign('integral_log_detail', $this->model('integral')->parse_log_item($parse_items));
        }

        TPL::assign('user', $this->model('account')->get_user_info_by_uid($_GET['uid']));
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        $this->crumb(AWS_APP::lang()->_t('积分日志'), '/admin/user/integral_log/uid-' . $_GET['uid']);

        TPL::output('admin/user/integral_log');
    }
}