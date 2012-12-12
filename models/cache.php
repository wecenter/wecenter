<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class cache_class extends AWS_MODEL
{
	public function remove($key)
	{
		return $this->delete('cache', "`key` = '" . $this->quote($key) . "'");
	}
	
	public function save($key, $data, $life_time = 300)
	{
		if ($this->fetch_row('cache', "`key` = '" . $this->quote($key) . "'"))
		{
			return $this->update('cache', array(
				'data' => serialize($data),
				'expire' => (time() + $life_time)
			), "`key` = '" . $this->quote($key) . "'");
		}
		else
		{
			if (!is_array($data) AND !$data)
			{
				return false;
			}
			
			return $this->insert('cache', array(
				'data' => serialize($data),
				'key' => $key,
				'expire' => (time() + $life_time)
 			));
		}
	}
	
	public function load($key)
	{
		$data = $this->fetch_row('cache', "`key` = '" . $this->quote($key) . "'");
		
		if ($data['data'] AND $data['expire'] >= time())
		{
			return unserialize($data['data']);
		}
		
		if ($data['expire'] < time())
		{
			$this->remove($key);
		}
		
		return false;
	}
	
	public function clean_expire()
	{
		return $this->delete('cache', '`expire` < ' . time());
	}
	
	public function clean()
	{
		return $this->query('DELETE FROM ' . $this->get_table('cache'));
	}
}