<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: mobile.class.php 35933 2016-05-13 05:56:41Z nemohou $
 */

define("MOBILE_PLUGIN_VERSION", "4");
define("REQUEST_METHOD_DOMAIN", 'http://wsq.discuz.com');

class base_plugin_mobile {

	function common() {
		global $_G;
		if(!defined('IN_MOBILE_API')) {
			return;
		}
		if(!$_G['setting']['mobile']['allowmobile']) {
			mobile_core::result(array('error' => 'mobile_is_closed'));
		}
		if(!empty($_GET['tpp'])) {
			$_G['tpp'] = intval($_GET['tpp']);
		}
		if(!empty($_GET['ppp'])) {
			$_G['ppp'] = intval($_GET['ppp']);
		}
		$_G['pluginrunlist'] = array('mobile', 'qqconnect', 'wechat');
		$_G['siteurl'] = preg_replace('/api\/mobile\/$/', '', $_G['siteurl']);
		$_G['setting']['msgforward'] = '';
		$_G['setting']['cacheindexlife'] = $_G['setting']['cachethreadlife'] = false;
		if(!$_G['setting']['mobile']['nomobileurl'] && function_exists('diconv') && !empty($_GET['charset'])) {
			$_GET = mobile_core::diconv_array($_GET, $_GET['charset'], $_G['charset']);
		}
		if($_GET['_auth']) {
			require_once DISCUZ_ROOT.'./source/plugin/wechat/wsq.class.php';
			$uid = wsq::decodeauth($_GET['_auth']);
			$disablesec = false;
			if($uid) {
				require_once libfile('function/member');
				$member = getuserbyuid($uid, 1);
				if($_GET['module'] != 'login') {
					setloginstatus($member, 1296000);
					$disablesec = true;
				} else {
					$disablesec = logincheck($member['username']);
				}
			} elseif($_GET['module'] == 'login') {
				$disablesec = logincheck($_GET['username']);
			}
			if($disablesec) {
				$_G['setting']['seccodedata'] = array();
				$_G['setting']['seccodestatus'] = 0;
				$_G['setting']['secqaa'] = array();
				unset($_GET['force']);
				define('IN_MOBILE_AUTH', $uid);
				if($_SERVER['REQUEST_METHOD'] == 'POST') {
					$_GET['formhash'] = $_G['formhash'];
				}
			}
		}
		if(class_exists('mobile_api', false) && method_exists('mobile_api', 'common')) {
			mobile_api::common();
		}
	}
	
	function global_mobile() {
	    if(!defined('IN_MOBILE_API')) {
	        return;
	    }
	    if(class_exists('mobile_api', false) && method_exists('mobile_api', 'output')) {
	        mobile_api::output();
	    }
	}

}