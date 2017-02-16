<?php



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