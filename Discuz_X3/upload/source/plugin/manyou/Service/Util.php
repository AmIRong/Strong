<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: Util.php 31728 2012-09-25 09:03:42Z zhouxiaobo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Cloud_Service_Util {

	protected static $_instance;

	public static function getInstance() {

		if (!(self::$_instance instanceof self)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

	}
	
	public function getApiVersion() {
	
	    return '0.6';
	}
	
	public function isfounder($user) {
	    global $_G;
	    $founders = str_replace(' ', '', $_G['config']['admincp']['founder']);
	    if(!$user['uid'] || $user['groupid'] != 1 || $user['adminid'] != 1) {
	        return false;
	    } elseif(empty($founders)) {
	        return false;
	    } elseif(strexists(",$founders,", ",$user[uid],")) {
	        return true;
	    } elseif(!is_numeric($user['username']) && strexists(",$founders,", ",$user[username],")) {
	        return true;
	    } else {
	        return FALSE;
	    }
	}
	
}