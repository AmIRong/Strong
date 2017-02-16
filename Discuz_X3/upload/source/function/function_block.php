<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_block.php 32895 2013-03-21 04:18:15Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function update_template_block($targettplname, $tpldirectory, $blocks) {
	if(!empty($targettplname)) {
		if(empty($blocks)) {
			C::t('common_template_block')->delete_by_targettplname($targettplname, $tpldirectory);
		} else {
			$oldbids = array();
			$oldbids = array_keys(C::t('common_template_block')->fetch_all_by_targettplname($targettplname, $tpldirectory));
			$newaddbids = array_diff($blocks, $oldbids);
			C::t('common_template_block')->delete_by_targettplname($targettplname, $tpldirectory);
			if($tpldirectory === './template/default') {
				C::t('common_template_block')->delete_by_targettplname($targettplname, '');
			}
			$blocks = array_unique($blocks);
			C::t('common_template_block')->insert_batch($targettplname, $tpldirectory, $blocks);
			if(!empty($newaddbids)) {
				require_once libfile('class/blockpermission');
				$tplpermission = & template_permission::instance();
				$tplpermission->add_blocks($targettplname, $newaddbids);
			}
		}
	}
}