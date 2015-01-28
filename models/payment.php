<?php

class payment_class extends AWS_MODEL
{
	public function create($uid, $amount, $note = '', $source = null)
    {
    	$order_insert_id = $this->insert('payment', array(
    		'uid' => intval($uid),
    		'amount' => $amount,
    		'time' => time(),
    		'note' => $note,
    		'source' => htmlspecialchars($source)
    	));
    	
    	$order_id = date('ymd', time()) . str_repeat('0', (11 - strlen($order_insert_id))) . (int)$order_insert_id;
    	
    	$this->update('payment', array(
    		'order_id' => $order_id,
    	), 'id = ' . $order_insert_id);
    	
    	return $order_id;
    }
    
    public function get_order($order_id)
    {
    	return $this->fetch_row('payment', "order_id = '" . $this->quote($order_id) . "'");
    }
    
    public function set_order_terrace_id($terrace_id, $order_id)
    {
    	return $this->update('payment', array(
    		'terrace_id' => $this->quote($terrace_id),
    	), "order_id = '" . $this->quote($order_id) . "'");
    }
    
    public function set_payment_id($payment_id, $order_id)
    {
    	return $this->update('payment', array(
    		'payment_id' => $payment_id,
    	), "order_id = '" . $this->quote($order_id) . "'");
    }
    
    public function set_order_payment_time($project_product_order_id)
    {
		return $this->update('product_order', array(
	    	//'payment_order_id' => $order_id
			'payment_time' => time()
	    ), 'id = ' . intval($project_product_order_id));
    }
    
    public function set_extra_param($order_id, $extra_param)
    {
	    return $this->update('payment', array(
	    	'extra_param' => serialize($extra_param)
	    ), "order_id = '" . $this->quote($order_id) . "'");
    }
    
    /****** 支付处理逻辑 ******/
    public function pay_to_project_order_id($order_id, $project_product_order_id)
    {
    	if (!$order_info = $this->get_order($order_id))
		{
			return false;
		}
		
		if (!$project_order_info = $this->model('project')->get_project_order_info_by_id($project_product_order_id))
		{
			return false;
		}
		
		$this->query("UPDATE " . get_table('project') . " SET paid = paid + " . $order_info['amount'] . " WHERE id = " . intval($project_order_info['project_id']));
    	
	    return $this->set_order_payment_time($project_product_order_id);
    }
}