<?php
	/**
	 * ----------------------------------------
	 * | Created By pfinal-db                 |
	 * | User: pfinal <lampxiezi@163.com>     |
	 * | Date: 2019/10/8                      |
	 * | Time: 下午2:34                        |
	 * ----------------------------------------
	 * |    _____  ______ _             _     |
	 * |   |  __ \|  ____(_)           | |    |
	 * |   | |__) | |__   _ _ __   __ _| |    |
	 * |   |  ___/|  __| | | '_ \ / _` | |    |
	 * |   | |    | |    | | | | | (_| | |    |
	 * |   |_|    |_|    |_|_| |_|\__,_|_|    |
	 * ----------------------------------------
	 */
	
	namespace pf\db\build;
	
	
	abstract class Build
	{
		//查询实例
		protected $query;
		//查询参数
		protected $params = [];
		
		abstract public function insert();
		
		abstract public function replace();
		
		abstract public function select();
		
		abstract public function update();
		
		abstract public function delete();
		
		public function __construct($query)
		{
			$this->query = $query;
		}
	}