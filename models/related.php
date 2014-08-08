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

class related_class extends AWS_MODEL
{
	public function add_related_link($uid, $item_type, $item_id, $link)
	{
		return $this->insert('related_links', array(
			'uid' => intval($uid),
			'item_type' => $item_type,
			'item_id' => $item_id,
			'link' => htmlspecialchars($link),
			'add_time' => time()
		));
	}

	public function remove_related_link($id, $item_id)
	{
		return $this->delete('related_links', 'id = ' . intval($id) . ' AND item_id = ' . intval($item_id));
	}

	public function get_related_links($item_type, $item_id)
	{
		return $this->fetch_all('related_links', "item_type = '" . $this->quote($item_type) . "' AND item_id = " . intval($item_id));
	}
}