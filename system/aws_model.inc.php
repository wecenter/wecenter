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

class AWS_MODEL
{
	public $prefix;
	public $setting;	
	private $_primaryKey;
	private $_tableName;
	private $_currentDb = 'master';
	private $_shutdown_query = array();
	private $_found_rows = 0;
	
	public function __construct()
	{
		$this->prefix = AWS_APP::config()->get('database')->prefix;
		
		$this->setup();
	}
	
	public function setup()
	{}
	
	public function model($model)
	{		
		return AWS_APP::model($model);
	}
	
	// 获取表前缀
	public function get_prefix()
	{
		return $this->prefix;
	}
	
	// 获取表名 (直接写 SQL 的时候要用这个函数, 外部程序使用 get_table() 方法)
	public function get_table($name)
	{
		return $this->get_prefix() . $name;
	}
	
	// db 方法
	public function db()
	{
		return AWS_APP::db($this->_currentDb);
	}
	
	// 切换到主数据库
	public function master()
	{
		if ($this->_currentDb == 'master')
		{
			return $this;
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
		
		AWS_APP::db('master');
		
		if (AWS_APP::config()->get('system')->debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), 'Master DB Seleted');
		}
		
		return $this;
	}
	
	// 切换到次数据库
	public function slave()
	{		
		if (!AWS_APP::config()->get('database')->slave OR $this->_currentDb == 'slave')
		{
			return $this;
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
		
		AWS_APP::db('slave');
		
		if (AWS_APP::config()->get('system')->debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), 'Slave DB Seleted');
		}
		
		return $this;
	}
	
	// 插入数据
	public function insert($table, $data)
	{
		$this->master();
		
		foreach ($data AS $key => $val)
		{
			$debug_data['`' . $key . '`'] = "'" . addslashes($val) . "'";
		}
			
		$sql = 'INSERT INTO `' . $this->get_table($table) . '` (' . implode(', ', array_keys($debug_data)) . ') VALUES (' . implode(', ', $debug_data) . ')';
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
			
		try {
			$rows_affected = $this->db()->insert($this->get_table($table), $data);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
			
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		$last_insert_id = $this->db()->lastInsertId();

		return $last_insert_id;
	}

	// 更新数据
	public function update($table, $data, $where = '')
	{
		$this->master();
		
		if (!$where)
		{
			throw new Zend_Exception('DB Update no where string.');
		}
		
		if ($data)
		{
			foreach ($data AS $key => $val)
			{
				$update_string[] = '`' . $key . "` = '" . addslashes($val) . "'";
			}
		}
		
		$sql = 'UPDATE `' . $this->get_table($table) . '` SET ' . implode(', ', $update_string) . ' WHERE ' . $where;
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
		
		try {
			$rows_affected = $this->db()->update($this->get_table($table), $data, $where);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
			
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $rows_affected;
	}
	
	public function shutdown_query($query)
	{
		$this->_shutdown_query[] = $query;
	}
	
	public function shutdown_update($table, $data, $where = '')
	{
		if (!$where)
		{
			throw new Zend_Exception('DB Update no where string.');
		}
		
		if ($data)
		{
			foreach ($data AS $key => $val)
			{
				$update_string[] = '`' . $key . "` = '" . $val . "'";
			}
		}
		
		$sql = 'UPDATE `' . $this->get_table($table) . '` SET ' . implode(', ', $update_string) . ' WHERE ' . $where;
		
		$this->_shutdown_query[] = $sql;
	}
	
	// 删除数据
	public function delete($table, $where = '')
	{
		$this->master();
		
		if (!$where)
		{
			throw new Exception('DB Delete no where string.');
		}
		
		$sql = 'DELETE FROM `' . $this->get_table($table) . '` WHERE ' . $where;
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
		
		try {
			$rows_affected = $this->db()->delete($this->get_table($table), $where);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $rows_affected;
	}
	
	// Zend Select 对象别名
	public function select()
	{
		$this->slave();
		
		return $this->db()->select();
	}
	
	// 获取查询全部数据
	public function fetch_all($table, $where = null, $order = null, $limit = null, $offset = 0)
	{
		$this->slave();
		
		$select = $this->select();
		
		$select->from($this->get_table($table), '*');

		if ($where)
		{
			$select->where($where);
		}
		
		if ($order)
		{
			if (strstr($order, ','))
			{
				$all_order = explode(',', $order);
				
				foreach ($all_order AS $current_order)
				{
					$select->order($current_order);	
				}
			}
			else
			{
				$select->order($order);	
			}
		}
		
		if ($limit)
		{
			if (strstr($limit, ','))
			{
				$limit = explode(',', $limit);
				
				$select->limit(intval($limit[1]), intval($limit[0]));
			}
			else if ($offset)
			{
				$select->limit($limit, $offset);
			}
			else
			{
				$select->limit($limit);
			}
		}
		
		$sql = $select->__toString();
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
			
		try {
			$result = $this->db()->fetchAll($select);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $result;
	}
	
	// SQL 直查
	public function query($sql, $limit = null, $offset = null, $where = null)
	{
		$this->slave();
		
		if (!$sql)
		{
			throw new Exception('Query was empty.');
		}
		
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}
		
		if ($limit)
		{
			$sql .= ' LIMIT ' . $limit;
		}
		
		if ($offset)
		{
			$sql .= ' OFFSET ' . $limit;
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
		
		try {
			$result = $this->db()->query($sql);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $result;
	}
	
	// 查询一行, 返回组数, key 为 字段名
	public function query_row($sql, $where = null)
	{
		$this->slave();
		
		if (!$sql)
		{
			throw new Exception('Query was empty.');
		}
		
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
		
		try {
			$result = $this->db()->fetchRow($sql);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $result;
	}
	
	// 获取查询全部数据
	public function query_all($sql, $limit = null, $offset = null, $where = null, $group_by = null)
	{
		$this->slave();
		
		if (!$sql)
		{
			throw new Exception('Query was empty.');
		}
		
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}
		
		if ($group_by)
		{
			$sql .= " GROUP BY `" . $this->quote($group_by) . "`";
		}
		
		if ($limit)
		{
			$sql .= ' LIMIT ' . $limit;
		}
		
		if ($offset)
		{
			$sql .= ' OFFSET ' . $limit;
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
		
		try {
			$result = $this->db()->fetchAll($sql);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $result;
	}
	
	public function found_rows()
	{
		//$this->slave();
		//return $this->db()->fetchOne('SELECT FOUND_ROWS()');
		
		return $this->_found_rows;
	}
	
	// 带页码的 fetch_all, 默认从第一页开始
	public function fetch_page($table, $where = null, $order = null, $page = null, $limit = 10)
	{
		$this->slave();
		
		$select = $this->select();
		
		$select->from($this->get_table($table), '*');
		//$select->from($this->get_table($table), array(new Zend_Db_Expr('SQL_CALC_FOUND_ROWS *')));

		if ($where)
		{
			$select->where($where);
		}
		
		if ($order)
		{
			if (strstr($order, ','))
			{
				if ($all_order = explode(',', $order))
				{
					foreach ($all_order AS $current_order)
					{
						$select->order($current_order);	
					}
				}
			}
			else
			{
				$select->order($order);	
			}
		}
		
		if (!$page)
		{
			$page = 1;
		}
		
		if ($limit)
		{
			$select->limitPage($page, $limit);
		}
		
		$sql = $select->__toString();
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
			
		try {
			$result = $this->db()->fetchAll($select);
		} catch (Exception $e) {
				
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		// Found rows
		$this->_found_rows = $this->count($table, $where);
		
		return $result;
	}
	
	// query_row 的面向对象方法
	public function fetch_row($table, $where = null, $order = null)
	{
		$this->slave();
		
		$select = $this->select();
		
		$select->from($this->get_table($table), '*');

		if ($where)
		{
			$select->where($where);
		}
		
		if ($order)
		{
			if (strstr($order, ','))
			{
				if ($all_order = explode(',', $order))
				{
					foreach ($all_order AS $current_order)
					{
						$select->order($current_order);	
					}
				}
			}
			else
			{
				$select->order($order);	
			}
		}
		
		$select->limit(1, 0);
		
		$sql = $select->__toString();
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
			
		try {
			$result = $this->db()->fetchRow($select);
		} catch (Exception $e) {		
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $result;
	}
	
	// 查询单字段
	public function fetch_one($table, $column, $where = null)
	{
		$this->slave();
		
		$select = $this->select();
		
		$select->from($this->get_table($table), $column);

		if ($where)
		{
			$select->where($where);
		}
		
		$select->limit(1, 0);
		
		$sql = $select->__toString();
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
			
		try {
			$result = $this->db()->fetchOne($select);
		} catch (Exception $e) {	
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $result;
	}
	
	// 查询一行, id 为主键
	public function get($table, $id)
	{
		if (!$id)
		{
			return null;
		}
		
		$this->slave();

		$select = $this->select();
		$select->from($this->get_table($table), '*');
		$select->where($this->get_primary_key($table) . ' = ' . (int)$id)->limit(1, 0);
		
		$sql = $select->__toString();
			
		try {
			$result = $this->db()->fetchRow($select);
		} catch (Exception $e) {	
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{			
			AWS_APP::debug_log('database', 0, $sql);
		}
		
		return $result;
	}
	
	// 计数
	public function count($table, $where = '')
	{
		$this->slave();
		
		$select = $this->select();
		$select->from($this->get_table($table), 'COUNT(*) AS n');
		
		if ($where)
		{
			$select->where($where);
		}
		
		$sql = $select->__toString();
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
			
		try {
			$result = $this->db()->fetchRow($select);
		} catch (Exception $e) {		
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $result['n'];
	}
	
	// 计算字段最大值
	public function max($table, $column, $where = '')
	{
		$this->slave();
		
		$select = $this->select();
		$select->from($this->get_table($table), 'MAX(' . $column . ') AS n');
		
		if ($where)
		{
			$select->where($where);
		}
		
		$sql = $select->__toString();
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
			
		try {
			$result = $this->db()->fetchRow($select);
		} catch (Exception $e) {	
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $result['n'];
	}
	
	// 计算字段最小值
	public function min($table, $column, $where = '')
	{
		$this->slave();
		
		$select = $this->select();
		$select->from($this->get_table($table), 'MIN(' . $column . ') AS n');
		
		if ($where)
		{
			$select->where($where);
		}
		
		$row = $this->db()->fetchRow($select);
		
		$sql = $select->__toString();
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
			
		try {
			$result = $this->db()->fetchRow($select);
		} catch (Exception $e) {	
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return $result['n'];
	}
	
	// 计算总数
	public function sum($table, $column, $where = '')
	{
		$this->slave();
		
		$select = $this->select();
		$select->from($this->get_table($table), 'SUM(' . $column . ') AS n');
		
		if ($where)
		{
			$select->where($where);
		}
		
		$sql = $select->__toString();
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
			
		try {
			$result = $this->db()->fetchRow($select);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{	
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}
		
		return intval($result['n']);
	}
	
	// 添加引号防止数据库攻击
	public static function quote($string)
	{
		if (function_exists('mysql_escape_string'))
		{
			$string = @mysql_escape_string($string);
		}
		else
		{
			$string = addslashes($string);
		}

		return $string;
	}
	
	private function get_primary_key($table)
	{
		$this->slave();
		
		if ($this->_primaryKey[$table])
		{
			return $this->_primaryKey[$table];
		}
		
		$r = $this->query('DESCRIBE ' . $this->get_table($table));

		while ($row = mysqli_fetch_array($r))
		{
			if ($row['Key'] == 'PRI')
			{
				$this->_primaryKey[$table] = $row['Field'];
				
				return $row['Field'];
			}
		}
		
		throw new Zend_Exception($this->get_table($table) . ' primaryKey does not exist ..');
	}
	
	public function __destruct()
	{
		$this->master();
		
		foreach ($this->_shutdown_query AS $key => $query)
		{
			$this->query($query);
		}
	}
}