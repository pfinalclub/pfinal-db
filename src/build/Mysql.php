<?php
	/**
	 * ----------------------------------------
	 * | Created By pfinal-db                 |
	 * | User: pfinal <lampxiezi@163.com>     |
	 * | Date: 2019/10/8                      |
	 * | Time: 下午2:33                        |
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
	
	
	class Mysql extends Build
	{
		public function select()
		{
			return str_replace(
				[
					'%field%',
					'%table%',
					'%join%',
					'%where%',
					'%groupBy%',
					'%having%',
					'%orderBy%',
					'%limit%',
					'%lock%',
				],
				[
					$this->parseField(),
					$this->parseTable(),
					$this->parseJoin(),
					$this->parseWhere(),
					$this->parseGroupBy(),
					$this->parseHaving(),
					$this->parseOrderBy(),
					$this->parseLimit(),
					$this->parseLock(),
				],
				'SELECT %field% FROM %table% %join% %where% %groupBy% %having% %orderBy% %limit% %lock%'
			);
		}
		
		public function insert()
		{
			return str_replace(
				[
					'%table%',
					'%field%',
					'%values%',
				],
				[
					$this->parseTable(),
					$this->parseField(),
					$this->parseValues(),
				],
				"INSERT INTO %table% (%field%) VALUES(%values%)"
			);
		}
		
		public function replace()
		{
			return str_replace(
				[
					'%table%',
					'%field%',
					'%values%',
				],
				[
					$this->parseTable(),
					$this->parseField(),
					$this->parseValues(),
				],
				"REPLACE INTO %table% (%field%) VALUES(%values%)"
			);
		}
		
		public function update()
		{
			return str_replace(
				[
					'%table%',
					'%set%',
					'%where%',
				],
				[
					$this->parseTable(),
					$this->parseSet(),
					$this->parseWhere(),
				],
				"UPDATE %table% %set% %where%"
			);
		}
		
		public function delete()
		{
			return str_replace(
				[
					'%table%',
					'%using%',
					'%where%',
					'%orderBy%',
					'%limit%',
				],
				[
					$this->parseTable(),
					$this->parseUsing(),
					$this->parseWhere(),
					$this->parseOrderBy(),
					$this->parseLimit(),
				],
				"DELETE FROM %table% %using% %where% %orderBy% %limit%"
			);
		}
		
	}