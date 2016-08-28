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

class export extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';

        return $rule_action;
    }

    public function setup()
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="order_list.xlsx"');
        header('Cache-Control: max-age=0');

        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');
    }

    public function index_action()
    {
        $publisher_projects = $this->model('project')->query_all('SELECT id FROM ' . get_table('project') . ' WHERE uid = ' . intval($this->user_id));

        if (!$publisher_projects)
        {
            exit();
        }

        foreach ($publisher_projects AS $value)
        {
            $project_ids[] = $value['id'];
        }

        $order_list = $this->model('project')->fetch_all('product_order', 'project_id IN (' . implode(',', $project_ids) . ')', 'id ASC');

        if (!$order_list)
        {
            exit();
        }

        foreach ($order_list AS $order_info)
        {
            $uids[] = $order_info['uid'];
        }

        $users = $this->model('account')->get_user_info_by_uids($uids);

        $objPHPExcel = new PHPExcel();

        $objPHPExcel->getProperties()->setTitle('活动列表');

        $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', '活动 ID')
                    ->setCellValue('B1', '联系人')
                    ->setCellValue('C1', '报名时间')
                    ->setCellValue('D1', '手机号')
                    ->setCellValue('E1', '邮箱')
                    ->setCellValue('F1', '收货地址')
                    ->setCellValue('G1', '邮编')
                    ->setCellValue('H1', '支持金额')
                    ->setCellValue('I1', '状态');

        $i = 1;

        foreach ($order_list AS $order_info)
        {
            $i++;

            $order_info['order_status'] = $this->model('project')->get_order_status($order_info);

            switch ($order_info['order_status'])
            {
                case 'pay':
                    $order_info['order_status'] = '未支付';

                    break;

                case 'refunded':
                    $order_info['order_status'] = '已退款';

                    break;

                case 'shipped':
                    $order_info['order_status'] = '已回报';

                    break;

                case 'donate':
                    $order_info['order_status'] = '无需回报';

                    break;

                default:
                    $order_info['order_status'] = '等待回报';

                    break;
            }

            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $i, ($order_info['project_id']) ?: '')
                        ->setCellValue('B' . $i, ($order_info['shipping_name']) ?: '')
                        ->setCellValue('C' . $i, (date('Y-m-d H:i:s', $order_info['add_time'])) ?: '')
                        ->setCellValue('D' . $i, ($order_info['shipping_mobile']) ?: '')
                        ->setCellValue('E' . $i, ($order_info['project_type'] == 'DEFAULT') ? $users[$order_info['uid']]['email'] : $order_info['shipping_address'])
                        ->setCellValue('F' . $i, ($order_info['project_type'] == 'DEFAULT') ? $order_info['shipping_province'] . $order_info['shipping_city'] . $order_info['shipping_address'] : $order_info['address'])
                        ->setCellValue('G' . $i, ($order_info['shipping_zipcode']) ?: '')
                        ->setCellValue('H' . $i, ($order_info['amount']) ?: '')
                        ->setCellValue('I' . $i, $order_info['order_status']);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        $objWriter->save('php://output');

        exit();
    }
}
