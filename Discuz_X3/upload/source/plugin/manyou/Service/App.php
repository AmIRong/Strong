<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: App.php 34007 2013-09-18 06:43:17Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

Cloud::loadFile('Service_AppException');

class Cloud_Service_App {

	protected static $_instance;

	public static function getInstance() {
	
	    if (!(self::$_instance instanceof self)) {
	        self::$_instance = new self();
	    }
	
	    return self::$_instance;
	}

	public function __construct() {

	}

	public function getCloudApps($cache = true) {
	
	    $apps = array();
	
	    if($cache) {
	        global $_G;
	        $apps = $_G['setting']['cloud_apps'];
	    } else {
	        $apps = C::t('common_setting')->fetch('cloud_apps', true);
	    }
	
	    if (!$apps) {
	        $apps = array();
	    }
	    if (!is_array($apps)) {
	        $apps = dunserialize($apps);
	    }
	
	    unset($apps[0]);
	
	    return $apps;
	}
	
	public function getCloudAppStatus($appName, $cache = true) {
	
	    $res = false;
	
	    $apps = $this->getCloudApps($cache);
	    if ($apps && $apps[$appName]) {
	        $res = ($apps[$appName]['status'] == 'normal');
	    }
	
	    return $res;
	}
}