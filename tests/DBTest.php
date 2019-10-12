<?php
	/**
	 * ----------------------------------------
	 * | Created By pfinal-db                 |
	 * | User: pfinal <lampxiezi@163.com>     |
	 * | Date: 2019/10/8                      |
	 * | Time: 下午12:55                        |
	 * ----------------------------------------
	 * |    _____  ______ _             _     |
	 * |   |  __ \|  ____(_)           | |    |
	 * |   | |__) | |__   _ _ __   __ _| |    |
	 * |   |  ___/|  __| | | '_ \ / _` | |    |
	 * |   | |    | |    | | | | | (_| | |    |
	 * |   |_|    |_|    |_|_| |_|\__,_|_|    |
	 * ----------------------------------------
	 */
	
	namespace tests;
	
	use pf\db\DB;
	
	class DBTest extends Migrate
	{
		/**
		 * @test
		 */
		public function query()
		{
			$d = DB::query("select * from news");
			$this->assertInternalType('array', $d);
			
			$d = Db::query("select * from news where id=:id", [':id' => 1]);
			$this->assertNotEmpty($d);
			
			$d = Db::query("select * from news where title like ?", ['%社区%']);
			$this->assertNotEmpty($d);
		}
		
		/**
		 * @test
		 */
		public function insert()
		{
			$res = DB::table('news')->insert(['title' => '南丞']);
			$this->assertTrue($res);
		}
		
		/**
		 * @test
		 */
		public function update()
		{
			$res = DB::table('news')->where("id", 1)->update(
				['title' => 'PF']
			);
			$this->assertTrue($res);
		}
		
		/**
		 * @test
		 */
		public function delete()
		{
			$res = DB::table('news')->where('id', 1)->delete();
			$this->assertTrue($res);
		}
		
		/**
		 * @test
		 */
		public function increment()
		{
			$res = DB::table("news")->where('id', 2)->increment(
				'click',
				20
			);
			$this->assertTrue($res);
		}
	}