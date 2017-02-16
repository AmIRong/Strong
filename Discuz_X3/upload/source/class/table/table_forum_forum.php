<?php



if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_forum extends discuz_table
{
	public function __construct() {

		$this->_table = 'forum_forum';
		$this->_pk    = 'fid';

		parent::__construct();
	}

	public function fetch_all_by_status($status, $orderby = 1) {
		$status = $status ? 1 : 0;
		$ordersql = $orderby ? 'ORDER BY t.type, t.displayorder' : '';
		return DB::fetch_all('SELECT * FROM '.DB::table($this->_table)." t WHERE t.status='$status' $ordersql");
	}
}