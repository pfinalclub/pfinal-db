<?php
	/**
	 * ----------------------------------------
	 * | Created By pfinal-db                 |
	 * | User: pfinal <lampxiezi@163.com>     |
	 * | Date: 2019/10/8                      |
	 * | Time: 下午2:24                        |
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
	
	class Mysql implements DBInterface
	{
		use Connection;
		
		public function getDns()
		{
			return $dns = 'mysql:dbname='.$this->config['database'].';host='.$this->config['host'].';port='.$this->config['port'];
		}
		
	}