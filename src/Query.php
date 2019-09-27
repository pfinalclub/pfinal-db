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
	
	
	use pf\config\Config;
	
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
		
	}