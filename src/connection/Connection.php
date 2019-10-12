<?php
	/**
	 * ----------------------------------------
	 * | Created By pfinal-db                 |
	 * | User: pfinal <lampxiezi@163.com>     |
	 * | Date: 2019/10/8                      |
	 * | Time: 下午2:27                        |
	 * ----------------------------------------
	 * |    _____  ______ _             _     |
	 * |   |  __ \|  ____(_)           | |    |
	 * |   | |__) | |__   _ _ __   __ _| |    |
	 * |   |  ___/|  __| | | '_ \ / _` | |    |
	 * |   | |    | |    | | | | | (_| | |    |
	 * |   |_|    |_|    |_|_| |_|\__,_|_|    |
	 * ----------------------------------------
	 */
	
	namespace pf\db\connection;
	
	
	use pf\config\Config;
	use PDO;
	use Closure;
	
	trait Connection
	{
		# 数据库配置
		protected $config;
		# 本次查询影响的条数
		protected $affectedRow;
		# 查询语句日志
		protected static $queryLogs = [];
		
		public function link($type = true)
		{
			static $links = [];
			$engine = ($type ? 'write' : 'read');
			$mulConfig = Config::get('database.'.$engine);
			$this->config = $mulConfig[array_rand($mulConfig)];
			$cacheName = serialize($this->config);
			if (!isset($links[$cacheName])) {
				$links[$cacheName] = new PDO(
					$this->getDns(),
					$this->config['user'],
					$this->config['password'],
					[PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"]
				);
				$links[$cacheName]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->execute("SET sql_mode = ''");
			}
			
			return $links[$cacheName];
		}
		
		/**
		 * 没有返回的执行
		 * @param $sql
		 * @param array $params
		 * @return bool
		 */
		public function execute($sql, array $params = [])
		{
			$sth = $this->link(true)->prepare($sql);
			$params = $this->setParamsSort($params);
			if (count($params) > 0) {
				foreach ((array)$params as $key => $value) {
					$sth->bindParam(
						$key,
						$params[$key],
						is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR
					);
				}
			}
			try {
				//执行查询
				$sth->execute();
				$this->affectedRow = $sth->rowCount();
				//记录查询日志
				self::$queryLogs[] = $sql.var_export($params, true);
				
				return true;
			} catch (Exception $e) {
				$error = $sth->errorInfo();
				throw new Exception(
					$sql." ;BindParams:".var_export($params, true).implode(
						';',
						$error
					)
				);
			}
		}
		
		/**
		 * 绑定参数
		 * @param array $params
		 * @return array
		 */
		public function setParamsSort(array $params)
		{
			if (is_numeric(key($params)) && key($params) == 0) {
				$tmp = [];
				foreach ($params as $key => $value) {
					$tmp[$key + 1] = $value;
				}
				$params = $tmp;
			}
			
			return $params;
		}
		
		/**
		 * 返回数据查询
		 * @param $sql
		 * @param array $params
		 * @return array
		 */
		public function query($sql, array $params = [])
		{
			$sth = $this->link(false)->prepare($sql);
			//设置保存数据
			$sth->setFetchMode(PDO::FETCH_ASSOC);
			//绑定参数
			if (count($params) > 0) {
				$params = $this->setParamsSort($params);
				foreach ((array)$params as $key => $value) {
					$sth->bindParam($key, $params[$key], is_numeric($params[$key]) ? PDO::PARAM_INT : PDO::PARAM_STR);
				}
			}
			try {
				//执行查询
				$sth->execute();
				$this->affectedRow = $sth->rowCount();
				//记录日志
				self::$queryLogs[] = $sql.var_export($params, true);
				
				return $sth->fetchAll() ?: [];
			} catch (Exception $e) {
				$error = $sth->errorInfo();
				throw new Exception($sql." ;BindParams:".var_export($params, true).implode(';', $error));
			}
		}
		
		/**
		 * 返回受影响函数
		 * @return mixed
		 */
		public function getAffectedRow()
		{
			return $this->affectedRow;
		}
		
		/**
		 * 事物处理
		 * @param Closure $closure
		 * @return $this
		 */
		public function transaction(Closure $closure)
		{
			try {
				$this->beginTransaction();
				//执行事务
				call_user_func($closure);
				$this->commit();
			} catch (Exception $e) {
				//回滚事务
				$this->rollback();
			}
			
			return $this;
		}
		
		/**
		 * 开启一个事务
		 *
		 * @return $this
		 */
		public function beginTransaction()
		{
			$this->link()->beginTransaction();
			
			return $this;
		}
		
		/**
		 * 开启事务
		 *
		 * @return $this
		 */
		public function rollback()
		{
			$this->link()->rollback();
			
			return $this;
		}
		
		/**
		 * 开启事务
		 *
		 * @return $this
		 */
		public function commit()
		{
			$this->link()->commit();
			
			return $this;
		}
		
		/**
		 * 获取自增主键
		 *
		 * @return mixed
		 */
		public function getInsertId()
		{
			return intval($this->link()->lastInsertId());
		}
		
		/**
		 * 获得查询SQL语句
		 *
		 * @return array
		 */
		public function getQueryLog()
		{
			return self::$queryLogs;
		}
		
		
	}