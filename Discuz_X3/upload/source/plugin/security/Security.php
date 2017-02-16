<?php

/**
 *		[Discuz!] (C)2001-2099 Comsenz Inc.
 *		This is NOT a freeware, use is subject to license terms
 *
 *		$Id: Security.php 30564 2012-06-04 05:38:45Z songlixin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Cloud_Service_Security {
	protected static $debug = 0;
	protected static $_secClient;
	protected static $_secStatus;
	protected static $postAction = array('newThread', 'newPost', 'editPost', 'editThread');
	protected static $userAction = array('register', 'login');
	protected static $delPostAction = array('delThread', 'delPost');
	protected static $delUserAction = array('banUser');
	protected static $retryLimit = 8;
	protected static $specialType = array('1' => 'poll', '2' => 'trade', '3' => 'reward', '4' => 'activity', '5' => 'debate');
	protected static $_instance;
	
	private function _setClient() {
		if (!self::$_secStatus) {
			return false;
		}

		self::$_secClient = Cloud::loadClass('Service_Client_Security');
	}
	
	public static function getInstance() {
	
	    if (!(self::$_instance instanceof self)) {
	        self::$_instance = new self();
	        $cloudAppService = Cloud::loadClass('Service_App');
	        self::$_secStatus = $cloudAppService->getCloudAppStatus('security');
	        self::$_instance->_setClient();
	    }
	
	    return self::$_instance;
	}
}