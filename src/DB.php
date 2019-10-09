<?php
	/**
	 * ----------------------------------------
	 * | Created By pfinal-db                 |
	 * | User: pfinal <lampxiezi@163.com>     |
	 * | Date: 2019/9/27                      |
	 * | Time: 下午2:55                        |
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
	
	class DB
	{
		protected $link;
		
		protected function driver()
		{
			$this->config();
			$this->link = new Query();
			$this->link->connection();
		}
		
		public function config()
		{
			static $isLoad = false;
			if ($isLoad === false) {
				$config = Config::getExtName('database', ['write', 'read']);
				if (empty($config['write'])) {
					$config['write'][] = Config::getExtName('database', ['write', 'read']);
				}
				if (empty($config['read'])) {
					$config['read'][] = Config::getExtName('database', ['write', 'read']);
				}
				//重设配置
				Config::set('database', $config);
				$isLoad = true;
			}
			
			return $this;
		}
		
		public function __call($method, $params)
		{
			if (is_null($this->link)) {
				$this->driver();
			}
			
			return call_user_func_array([$this->link, $method], $params);
		}
		
		public static function __callStatic($name, $arguments)
		{
			return call_user_func_array([new static(), $name], $arguments);
		}
		
	}