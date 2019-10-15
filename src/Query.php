<?php
	/**
	 * ----------------------------------------
	 * | Created By pfinal-db                 |
	 * | User: pfinal <lampxiezi@163.com>     |
	 * | Date: 2019/9/27                      |
	 * | Time: 下午2:58                        |
	 * ----------------------------------------
	 * |    _____  ______ _             _     |
	 * |   |  __ \|  ____(_)           | |    |
	 * |   | |__) | |__   _ _ __   __ _| |    |
	 * |   |  ___/|  __| | | '_ \ / _` | |    |
	 * |   | |    | |    | | | | | (_| | |    |
	 * |   |_|    |_|    |_|_| |_|\__,_|_|    |
	 * ----------------------------------------
	 */
	
	namespace pf\db;
	
	
	use mysql_xdevapi\Exception;
	use pf\arr\PFarr;
	use pf\config\Config;
	use pf\diropt\Diropt;
	use pf\page\Page;
	
	class Query implements \ArrayAccess, \Iterator
	{
		use ArrayAccessIterator;
		protected $data = [];
		protected $table;
		protected $fields;
		protected $primaryKey;
		protected $connection;
		protected $build;
		protected $sql;
		protected $model;
		
		/**
		 * 根据驱动创建数据库链接对象
		 * @return $this
		 */
		public function connection()
		{
			$driver = ucfirst(Config::get('database.driver'));
			$this->setConnection($driver);
			$this->setBuild($driver);
			
			return $this;
		}
		
		/**
		 * 设置链接对象
		 * @param $driver
		 */
		public function setConnection($driver)
		{
			$class = '\pf\db\connection\\'.$driver;
			$this->connection = new $class($this);
		}
		
		/**
		 * 获取链接对象
		 * @return mixed
		 */
		public function getConnection()
		{
			return $this->connection;
		}
		
		
		public function setBuild($driver)
		{
			$build = '\pf\db\build\\'.$driver;
			$this->build = new $build($this);
		}
		
		public function getBuild()
		{
			return $this->build;
		}
		
		public function getSql()
		{
			return $this->sql;
		}
		
		public function setSql($sql)
		{
			$this->sql = $sql;
		}
		
		public function getPrefix()
		{
			return Config::get('database.prefix');
		}
		
		public function table($table, $full = false)
		{
			$this->table = $this->table ?: ($full ? $table : Config::get('database.prefix').$table);
			$this->fields = $this->getFields();
			//获取表主键
			$this->primaryKey = $this->getPrimaryKey();
			
			return $this;
		}
		
		public function getModel()
		{
			return $this->model;
		}
		
		public function setModel($model)
		{
			$this->model = $model;
		}
		
		public function getTable()
		{
			return $this->table;
		}
		
		public function filterTableField(array $data)
		{
			$new = [];
			if (is_array($data)) {
				foreach ((array)$data as $name => $value) {
					if (key_exists($name, $this->fields)) {
						$new[$name] = $value;
					}
				}
			}
			
			return $new;
		}
		
		
		public function getFields()
		{
			static $cache = [];
			if (empty($this->table)) {
				return [];
			}
			
			if (!empty($cache[$this->table])) {
				return $cache[$this->table];
			}
			
			// 缓存字段
			$data = Config::get('app.debug') ? [] : $this->cache($this->table);
			if (empty($data)) {
				$sql = "show columns from ".$this->table;
				if (!$result = $this->connection->query($sql)) {
					throw new \Exception("获取{$this->table}表字段信息失败");
				}
				$data = [];
				foreach ((array)$result as $res) {
					$f['field'] = $res['Field'];
					$f['type'] = $res['Type'];
					$f['null'] = $res['Null'];
					$f['field'] = $res['Field'];
					$f['key'] = ($res['Key'] == "PRI" && $res['Extra']) || $res['Key'] == "PRI";
					$f['default'] = $res['Default'];
					$f['extra'] = $res['Extra'];
					$data[$res['Field']] = $f;
				}
				$this->cache($this->table, $data);
			}
			$cache[$this->table] = $data;
			
			return $data;
		}
		
		public function getPrimaryKey()
		{
			static $cache = [];
			if (isset($cache[$this->table])) {
				return $cache[$this->table];
			}
			$fields = $this->getFields($this->table);
			foreach ($fields as $v) {
				if ($v['key'] == 1) {
					return $cache[$this->table] = $v['field'];
				}
			}
		}
		
		public function cache($name, $data = null)
		{
			$dir = Config::get('database.cache_dir');
			Diropt::create($dir);
			$file = $dir.'/'.($name).'.php';
			if (is_null($data)) {
				$result = [];
				if (is_file($file)) {
					$result = unserialize(file_get_contents($file));
				}
				
				return is_array($result) ? $result : [];
			} else {
				return file_put_contents($file, serialize($data));
			}
		}
		
		public function data($data)
		{
			$this->data = $data;
			
			return $this;
		}
		
		public function toArray()
		{
			return $this->data;
		}
		
		/**
		 * 插入并获取自增主键
		 * @param $data
		 * @param string $action
		 * @return bool
		 */
		public function insertGetId($data, $action = 'insert')
		{
			if ($result = $this->insert($data, $action)) {
				return $this->connection->getInsertId();
			} else {
				return false;
			}
		}
		
		/**
		 * 分页查询
		 * @param $row
		 * @param int $pageNum
		 * @return $this
		 */
		public function paginate($row, $pageNum = 10)
		{
			$obj = unserialize(serialize($this));
			Page::row($row)->pageNum($pageNum)->make($obj->count());
			$res = $this->limit(Page::limit())->get();
			$this->data($res ?: []);
			
			return $this;
		}
		
		/**
		 * 前台显示页码样式
		 *
		 * @return mixed
		 */
		public function links()
		{
			return new Page();
		}
		
		public function execute($sql, array $params = [])
		{
			self::setSql($sql);
			// TODO Middleware
			// Middleware::web('database_execute', $this);
			$result = $this->connection->execute($sql, $params, $this);
			$this->build->reset();
			
			return $result;
		}
		
		public function query($sql, array $params = [])
		{
			self::setSql($sql);
			// TODO Middleware
			// Middleware::web('database_query');
			$data = $this->connection->query($sql, $params, $this);
			$this->build->reset();
			
			return $data;
		}
		
		
		public function increment($field, $dec = 1)
		{
			$where = $this->build->parseWhere();
			if (empty($where)) {
				throw new Exception('缺少更新条件');
			}
			$sql = "UPDATE ".$this->getTable()." SET {$field}={$field}+$dec ".$where;
			
			return $this->execute($sql, $this->build->getUpdateParams());
		}
		
		public function decrement($field, $dec = 1)
		{
			$where = $this->build->parseWhere();
			if (empty($where)) {
				throw new Exception('缺少更新条件');
			}
			$sql = "UPDATE ".$this->getTable()." SET {$field}={$field}-$dec ".$where;
			
			return $this->execute($sql, $this->build->getUpdateParams());
		}
		
		public function update($data)
		{
			$data = $this->filterTableField($data);
			if (empty($data)) {
				throw new Exception('缺少更新数据');
			}
			foreach ((array)$data as $k => $v) {
				$this->build->bindExpression('set', $k);
				$this->build->bindParams('values', $v);
			}
			if (!$this->build->getBindExpression('where')) {
				$pri = $this->getPrimaryKey();
				if (isset($data[$pri])) {
					$this->where($pri, $data[$pri]);
				}
			}
			if (!$this->build->getBindExpression('where')) {
				throw new Exception('没有更新条件不允许更新');
			}
			//$this->execute($this->build->update(), $this->build->getUpdateParams());
			//var_dump($this->connection->getQueryLog());exit;
			return $this->execute($this->build->update(), $this->build->getUpdateParams());
		}
		
		public function delete($id = [])
		{
			if (!empty($id)) {
				$this->whereIn($this->getPrimaryKey(), is_array($id) ? $id : explode(',', $id));
			}
			//必须有条件才可以删除
			if ($this->build->getBindExpression('where')) {
				return $this->execute($this->build->delete(), $this->build->getDeleteParams());
			}
			
			return false;
		}
		
		public function firstOrCreate($param, $data)
		{
			if (!$this->where(key($param), current($param))->first()) {
				return $this->insert($data);
			} else {
				return false;
			}
		}
		
		public function insert($data, $action = 'insert')
		{
			$data = $this->filterTableField($data);
			if (empty($data)) {
				throw new Exception('没有数据用于插入');
			}
			foreach ($data as $k => $v) {
				$this->build->bindExpression('field', "`$k`");
				$this->build->bindExpression('values', '?');
				$this->build->bindParams('values', $v);
			}
			
			return $this->execute($this->build->$action(), $this->build->getInsertParams());
		}
		
		public function where()
		{
			$args = func_get_args();
			
			if (is_array($args[0])) {
				foreach ($args as $v) {
					call_user_func_array([$this, 'where'], $v);
				}
			} else {
				switch (count($args)) {
					case 1:
						$this->logic('AND')->build->bindExpression('where', $args[0]);
						break;
					case 2:
						$this->logic('AND')->build->bindExpression('where', "{$args[0]} = ?");
						$this->build->bindParams('where', $args[1]);
						break;
					case 3:
						$this->logic('AND')->build->bindExpression('where', "{$args[0]} {$args[1]} ?");
						$this->build->bindParams('where', $args[2]);
						break;
				}
			}
			
			return $this;
		}
		
		
		public function logic($logic)
		{
			$expression = $this->build->getBindExpression('where');
			if (empty($expression) || preg_match('/^\s*(OR|AND)\s*$/i', array_pop($expression))) {
				return $this;
			}
			$this->build->bindExpression('where', trim($logic));
			
			return $this;
		}
		
		/**
		 * 替换数据
		 * @param $data
		 * @return mixed
		 */
		public function replace($data)
		{
			return $this->insert($data, 'replace');
		}
		
		/**
		 * 根据主键查找一条数据
		 * @param $id
		 * @return mixed
		 */
		public function find($id)
		{
			if ($id) {
				$this->where($this->getPrimaryKey(), $id);
				if ($data = $this->query($this->build->select(), $this->build->getSelectParams())) {
					return $data ? $data[0] : [];
				}
			}
		}
		
		/**
		 * 查找一条数据
		 * @return array
		 */
		public function first()
		{
			$this->limit(1);
			$data = $this->query($this->build->select(), $this->build->getSelectParams());
			
			return $data ? $data[0] : [];
		}
		
		/**
		 * 查找一个字段
		 * @param $field
		 * @return mixed
		 */
		public function pluck($field)
		{
			$data = $this->query(
				$this->build->select(),
				$this->build->getSelectParams()
			);
			$result = $data ? $data[0] : [];
			if (!empty($result)) {
				return $result[$field];
			}
		}
		
		/**
		 * 查找集合
		 * @param array $field
		 * @return mixed
		 */
		public function get(array $field = [])
		{
			if (!empty($field)) {
				$this->field($field);
			}
			
			return $this->query($this->build->select(), $this->build->getSelectParams());
		}
		
		/**
		 * 获取字段列表
		 * @param $field
		 * @return array
		 */
		public function lists($field)
		{
			$result = $this->query($this->build->select(), $this->build->getSelectParams());
			$data = [];
			if ($result) {
				$field = explode(',', $field);
				switch (count($field)) {
					case 1:
						foreach ($result as $row) {
							$data[] = $row[$field[0]];
						}
						break;
					case 2:
						foreach ($result as $v) {
							$data[$v[$field[0]]] = $v[$field[1]];
						}
						break;
					default:
						foreach ($result as $v) {
							foreach ($field as $f) {
								$data[$v[$field[0]]][$f] = $v[$f];
							}
						}
						break;
				}
			}
			
			return $data;
		}
		
		/**
		 * 设置结果集字段
		 * @param string|array $field 字段列表
		 * @return $this
		 */
		public function field($field)
		{
			$field = is_array($field) ? $field : explode(',', $field);
			foreach ((array)$field as $k => $v) {
				$this->build->bindExpression('field', $v);
			}
			
			return $this;
		}
		
		public function limit()
		{
			$args = func_get_args();
			$this->build->bindExpression(
				'limit',
				$args[0]." ".(empty($args[1]) ? '' : ",{$args[1]}")
			);
			
			return $this;
		}
		
		public function groupBy()
		{
			$this->build->bindExpression('groupBy', func_get_arg(0));
			
			return $this;
		}
		
		public function having()
		{
			$args = func_get_args();
			$this->build->bindExpression('having', $args[0].$args[1].' ? ');
			$this->build->bindParams('having', $args[2]);
			
			return $this;
		}
		
		public function orderBy()
		{
			$args = func_get_args();
			$this->build->bindExpression(
				'orderBy',
				$args[0]." ".(empty($args[1]) ? ' ASC ' : " $args[1]")
			);
			
			return $this;
		}
		
		public function lock()
		{
			$this->build->bindExpression('lock', ' FOR UPDATE ');
			
			return $this;
		}
		
		public function count($field = '*')
		{
			$this->build->bindExpression('field', "count($field) AS m");
			//有分组时统计
			if ($this->build->getBindExpression('groupBy')) {
				return count($this->get());
			}
			$data = $this->get();
			
			return $data ? $data[0]['m'] : 0;
		}
		
		public function max($field)
		{
			$this->build->bindExpression('field', "max({$field}) AS m");
			$data = $this->first();
			return intval($data ? $data['m'] : 0);
		}
		public function min($field)
		{
			$this->build->bindExpression('field', "min({$field}) AS m");
			$data = $this->first();
			return intval($data ? $data['m'] : 0);
		}
		public function avg($field)
		{
			$this->build->bindExpression('field', "avg({$field}) AS m");
			$data = $this->first();
			return intval($data ? $data['m'] : 0);
		}
		public function sum($field)
		{
			$this->build->bindExpression('field', "sum({$field}) AS m");
			$data = $this->first();
			return intval($data ? $data['m'] : 0);
		}
		/**
		 * 设置条件
		 *
		 * @return $this
		 */
		public function whereNotEmpty()
		{
			$args = func_get_args();
			if (is_array($args[0])) {
				foreach ($args as $v) {
					call_user_func_array([$this, 'whereNotEmpty'], $v);
				}
			} else {
				switch (count($args)) {
					case 1:
						if ( ! empty($args[0])) {
							$this->logic('AND')->build->bindExpression('where', $args[0]);
						}
						break;
					case 2:
						if ( ! empty($args[1])) {
							$this->logic('AND')->build->bindExpression('where', "{$args[0]} = ?");
							$this->build->bindParams('where', $args[1]);
						}
						break;
					case 3:
						if ( ! empty($args[2])) {
							$this->logic('AND')->build->bindExpression('where', "{$args[0]} {$args[1]} ?");
							$this->build->bindParams('where', $args[2]);
						}
						break;
				}
			}
			return $this;
		}
		/**
		 * 预准备whereRaw
		 *
		 * @param       $sql
		 * @param array $params
		 *
		 * @return $this
		 */
		public function whereRaw($sql, array $params = [])
		{
			$this->logic('AND');
			$this->build->bindExpression('where', $sql);
			foreach ($params as $p) {
				$this->build->bindParams('where', $p);
			}
			return $this;
		}
		/**
		 * 查询或
		 *
		 * @return $this
		 */
		public function orWhere()
		{
			$this->logic('OR');
			call_user_func_array([$this, 'where'], func_get_args());
			return $this;
		}
		/**
		 * 查询与
		 *
		 * @return $this
		 */
		public function andWhere()
		{
			$this->build->bindExpression('where', ' AND ');
			call_user_func_array([$this, 'where'], func_get_args());
			return $this;
		}
		public function whereNull($field)
		{
			$this->logic('AND');
			$this->build->bindExpression('where', "$field IS NULL");
			return $this;
		}
		public function whereNotNull($field)
		{
			$this->logic('AND');
			$this->build->bindExpression('where', "$field IS NOT NULL");
			return $this;
		}
		/**
		 * in 查询
		 *
		 * @param $field
		 * @param $params
		 *
		 * @return $this
		 * @throws \Exception
		 */
		public function whereIn($field, $params)
		{
			if ( ! is_array($params) || empty($params)) {
				throw  new Exception('whereIn 参数错误');
			}
			$this->logic('AND');
			$where = '';
			foreach ($params as $value) {
				$where .= '?,';
				$this->build->bindParams('where', $value);
			}
			$this->build->bindExpression(
				'where',
				" $field IN (".substr($where, 0, -1).")"
			);
			return $this;
		}
		public function whereNotIn($field, $params)
		{
			if ( ! is_array($params) || empty($params)) {
				throw  new Exception('whereIn 参数错误');
			}
			$this->logic('AND');
			$where = '';
			foreach ($params as $value) {
				$where .= '?,';
				$this->build->bindParams('where', $value);
			}
			$this->build->bindExpression('where', " $field NOT IN (".substr($where, 0, -1).")");
			return $this;
		}
		public function whereBetween($field, $params)
		{
			if ( ! is_array($params) || empty($params)) {
				throw  new Exception('whereIn 参数错误');
			}
			$this->logic('AND');
			$this->build->bindExpression('where', " $field BETWEEN  ? AND ? ");
			$this->build->bindParams('where', $params[0]);
			$this->build->bindParams('where', $params[1]);
			return $this;
		}
		public function whereNotBetween($field, $params)
		{
			if ( ! is_array($params) || empty($params)) {
				throw  new Exception('whereIn 参数错误');
			}
			$this->logic('AND');
			$this->build->bindExpression('where', " $field NOT BETWEEN  ? AND ? ");
			$this->build->bindParams('where', $params[0]);
			$this->build->bindParams('where', $params[1]);
			return $this;
		}
		/**
		 * 多表内连接
		 *
		 * @return $this
		 */
		public function join()
		{
			$args = func_get_args();
			$this->build->bindExpression('join', " INNER JOIN ".$this->getPrefix()."{$args[0]} {$args[0]} ON {$args[1]} {$args[2]} {$args[3]}");
			return $this;
		}
		/**
		 * 多表左外连接
		 *
		 * @return $this
		 */
		public function leftJoin()
		{
			$args = func_get_args();
			$this->build->bindExpression('join', " LEFT JOIN ".$this->getPrefix()."{$args[0]} {$args[0]} ON {$args[1]} {$args[2]} {$args[3]}");
			return $this;
		}
		/**
		 * 多表右外连接
		 *
		 * @return $this
		 */
		public function rightJoin()
		{
			$args = func_get_args();
			$this->build->bindExpression('join', " RIGHT JOIN ".$this->getPrefix()."{$args[0]} {$args[0]} ON {$args[1]} {$args[2]} {$args[3]}");
			return $this;
		}
		/**
		 * 魔术方法
		 *
		 * @param $method
		 * @param $params
		 *
		 * @return mixed
		 */
		public function __call($method, $params)
		{
			if (substr($method, 0, 5) == 'getBy') {
				$field = preg_replace('/.[A-Z]/', '_\1', substr($method, 5));
				$field = strtolower($field);
				return $this->where($field, current($params))->first();
			}
			return call_user_func_array([$this->connection, $method], $params);
		}
		/**
		 * 获取查询参数
		 *
		 * @param $type where field等
		 *
		 * @return mixed
		 */
		public function getQueryParams($type)
		{
			return $this->build->getBindExpression($type);
		}
		
		
	}