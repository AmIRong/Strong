<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_session.php 28051 2012-02-21 10:36:56Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_session extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_session';
		$this->_pk    = 'sid';

		parent::__construct();
	}
	
	public function count($type = 0) {
	    $condition = $type == 1 ? ' WHERE uid>0 ' : ($type == 2 ? ' WHERE uid=0 ' : '');
	    return DB::result_first("SELECT count(*) FROM ".DB::table($this->_table).$condition);
	
	}
	
	public function delete_by_session($session, $onlinehold, $guestspan) {
	    if(!empty($session) && is_array($session)) {
	        $onlinehold = time() - $onlinehold;
	        $guestspan = time() - $guestspan;
	        $session = daddslashes($session);
	
	        $condition = " sid='{$session[sid]}' ";
	        $condition .= " OR lastactivity<$onlinehold ";
	        $condition .= " OR (uid='0' AND ip1='{$session['ip1']}' AND ip2='{$session['ip2']}' AND ip3='{$session['ip3']}' AND ip4='{$session['ip4']}' AND lastactivity>$guestspan) ";
	        $condition .= $session['uid'] ? " OR (uid='{$session['uid']}') " : '';
	        DB::delete('common_session', $condition);
	    }
	}
	
	public function fetch_member($ismember = 0, $invisible = 0, $start = 0, $limit = 0) {
	    $sql = array();
	    if($ismember === 1) {
	        $sql[] = 'uid > 0';
	    } elseif($ismember === 2) {
	        $sql[] = 'uid = 0';
	    }
	    if($invisible === 1) {
	        $sql[] = 'invisible = 1';
	    } elseif($invisible === 2) {
	        $sql[] = 'invisible = 0';
	    }
	    $wheresql = !empty($sql) && is_array($sql) ? ' WHERE '.implode(' AND ', $sql) : '';
	    $sql = 'SELECT * FROM %t '.$wheresql.' ORDER BY lastactivity DESC'.DB::limit($start, $limit);
	    return DB::fetch_all($sql, array($this->_table), $this->_pk);
	}
}