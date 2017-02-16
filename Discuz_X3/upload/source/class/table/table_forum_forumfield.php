<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_forum_forumfield.php 32916 2013-03-22 08:51:36Z zhangjie $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_forumfield extends discuz_table
{
	public function __construct() {

		$this->_table = 'forum_forumfield';
		$this->_pk    = 'fid';

		parent::__construct();
	}
}