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
	use pf\config\Config;
	use pf\diropt\Diropt;
	
	class Query
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
		
		public function paginate($row, $pageNum = 10)
		{
			$obj = unserialize(serialize($this));
		}
		
	}