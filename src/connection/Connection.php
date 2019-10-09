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
			$engine       = ($type ? 'write' : 'read');
			$mulConfig    = Config::get('database.'.$engine);
			$this->config = $mulConfig[array_rand($mulConfig)];
			$cacheName    = serialize($this->config);
			
		}
		
		
	}