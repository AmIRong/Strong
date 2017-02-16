<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: discuz_table.php 30321 2012-05-22 09:09:35Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


class discuz_table extends discuz_base
{

	public $data = array();

	public $methods = array();

	protected $_table;
	protected $_pk;
	protected $_pre_cache_key;
	protected $_cache_ttl;
	protected $_allowmem;

	public function __construct($para = array()) {
		if(!empty($para)) {
			$this->_table = $para['table'];
			$this->_pk = $para['pk'];
		}
		if(isset($this->_pre_cache_key) && (($ttl = getglobal('setting/memory/'.$this->_table)) !== null || ($ttl = $this->_cache_ttl) !== null) && memory('check')) {
			$this->_cache_ttl = $ttl;
			$this->_allowmem = true;
		}
		$this->_init_extend();
		parent::__construct();
	}
	
	protected function _init_extend() {
	}
	
	public function fetch_all($ids, $force_from_db = false) {
	    $data = array();
	    if(!empty($ids)) {
	        if($force_from_db || ($data = $this->fetch_cache($ids)) === false || count($ids) != count($data)) {
	            if(is_array($data) && !empty($data)) {
	                $ids = array_diff($ids, array_keys($data));
	            }
	            if($data === false) $data =array();
	            if(!empty($ids)) {
	                $query = DB::query('SELECT * FROM '.DB::table($this->_table).' WHERE '.DB::field($this->_pk, $ids));
	                while($value = DB::fetch($query)) {
	                    $data[$value[$this->_pk]] = $value;
	                    $this->store_cache($value[$this->_pk], $value);
	                }
	            }
	        }
	    }
	    return $data;
	}
	
	public function fetch_cache($ids, $pre_cache_key = null) {
	    $data = false;
	    if($this->_allowmem) {
	        if($pre_cache_key === null)	$pre_cache_key = $this->_pre_cache_key;
	        $data = memory('get', $ids, $pre_cache_key);
	    }
	    return $data;
	}
	
	public function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
	    return DB::insert($this->_table, $data, $return_insert_id, $replace, $silent);
	}
	
	public function store_cache($id, $data, $cache_ttl = null, $pre_cache_key = null) {
	    $ret = false;
	    if($this->_allowmem) {
	        if($pre_cache_key === null)	$pre_cache_key = $this->_pre_cache_key;
	        if($cache_ttl === null)	$cache_ttl = $this->_cache_ttl;
	        $ret = memory('set', $id, $data, $cache_ttl, $pre_cache_key);
	    }
	    return $ret;
	}
}