<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
    die;
}

class ajax extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';

        $rule_action['actions'] = array();

        return $rule_action;
    }

    public function setup()
    {
        HTTP::no_cache_header();
    }

    public function publish_action()
    {
        if (!$this->user_info['permission']['publish_ticket'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限发布工单')));
        }

        if (!$_POST['title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入工单标题')));
        }

        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        if (!$this->model('publish')->insert_attach_is_self_upload($_POST['message'], $_POST['attach_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
        }

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        $this->model('draft')->delete_draft(1, 'ticket', $this->user_id);

        $ticket_id = $this->model('publish')->publish_ticket($_POST['title'], $_POST['message'], $this->user_id, $_POST['attach_access_key']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/ticket/' . $ticket_id)
        ), 1, null));
    }

    public function change_priority_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限更改工单')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        if (!$_POST['priority'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单优先级')));
        }

        $ticekt_info = $this->model('ticket')->get_ticket_by_id($_POST['id']);

        if (!$ticekt_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单不存在')));
        }

        $this->model('ticket')->change_priority($ticekt_info['id'], $this->user_id, $_POST['priority']);

        H::ajax_json_output(AWS_APP::RSM(null, -1, null));
    }

    public function change_status_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'] AND $_POST['status'] != 'closed')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限更改工单')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        if (!$_POST['status'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单状态')));
        }

        $ticekt_info = $this->model('ticket')->get_ticket_by_id($_POST['id']);

        if (!$ticekt_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单不存在')));
        }

        if ($ticekt_info['uid'] != $this->user_id AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限更改工单')));
        }

        $this->model('ticket')->change_status($ticekt_info['id'], $this->user_id, $_POST['status']);

        H::ajax_json_output(AWS_APP::RSM(null, -1, null));
    }

    public function change_rating_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限更改工单')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        if (!$_POST['rating'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单评级')));
        }

        $ticekt_info = $this->model('ticket')->get_ticket_by_id($_POST['id']);

        if (!$ticekt_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单不存在')));
        }

        $this->model('ticket')->change_rating($ticekt_info['id'], $this->user_id, $_POST['rating']);

        H::ajax_json_output(AWS_APP::RSM(null, -1, null));
    }

    public function remove_ticket_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限更改工单')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        $this->model('ticket')->remove_ticket($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/ticket/')
        ), 1, null));
    }
}
