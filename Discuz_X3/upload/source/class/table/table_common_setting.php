<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_setting.php 30476 2012-05-30 07:05:06Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_setting extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_setting';
		$this->_pk    = 'skey';

		parent::__construct();
	}
	
	public function update($skey, $svalue){
	    return DB::insert($this->_table, array($this->_pk => $skey, 'svalue' => is_array($svalue) ? serialize($svalue) : $svalue), false, true);
	}
}