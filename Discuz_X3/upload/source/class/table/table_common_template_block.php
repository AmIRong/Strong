<?php



if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_template_block extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_template_block';
		$this->_pk    = '';

		parent::__construct();
	}
	
	public function delete_by_targettplname($tpl, $tpldirectory = NULL) {
	    $add = $tpldirectory !== NULL ? ' AND '.DB::field('tpldirectory', $tpldirectory) : '';
	    return $tpl ? DB::delete($this->_table, DB::field('targettplname', $tpl).$add) : false;
	}
}