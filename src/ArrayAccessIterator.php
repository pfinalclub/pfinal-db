<?php
	/**
	 * ----------------------------------------
	 * | Created By pfinal-db                 |
	 * | User: pfinal <lampxiezi@163.com>     |
	 * | Date: 2019/9/27                      |
	 * | Time: 下午3:02                        |
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
	
	
	trait ArrayAccessIterator
	{
		public function offsetSet($key, $value)
		{
			$this->original[$key] = $value;
		}
		
		public function offsetGet($key)
		{
			return isset($this->data[$key]) ? $this->data[$key] : null;
		}
		
		public function offsetExists($key)
		{
			return isset($this->data[$key]);
		}
		
		public function offsetUnset($key)
		{
			if (isset($this->original[$key])) {
				unset($this->original[$key]);
			}
		}
		
		function rewind()
		{
			reset($this->data);
		}
		
		public function current()
		{
			return current($this->data);
		}
		
		public function next()
		{
			return next($this->data);
		}
		
		public function key()
		{
			return key($this->data);
		}
		
		public function valid()
		{
			return current($this->data);
		}
	}