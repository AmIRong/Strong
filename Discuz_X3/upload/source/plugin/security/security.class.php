<?php

/**
 *		[Discuz! X] (C)2001-2099 Comsenz Inc.
 *		This is NOT a freeware, use is subject to license terms
 *
 *		$Id: security.class.php 33945 2013-09-05 01:48:02Z nemohou $
 */


if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_security {
	const DEBUG = 0;
	protected static $postReportAction = array('post_newthread_succeed', 'post_edit_succeed', 'post_reply_succeed',
							'post_newthread_mod_succeed', 'post_newthread_mod_succeed', 'post_reply_mod_succeed',
							'edit_reply_mod_succeed', 'edit_newthread_mod_succeed');
	protected static $userReportAction = array('login_succeed', 'register_succeed', 'location_login_succeed_mobile',
							'location_login_succeed', 'register_succeed_location', 'register_email_verify',
							'register_manual_verify', 'login_succeed_inactive_member');
	protected static $hookMoudle = array('post', 'logging', 'register');
	protected static $isAdminGroup = 0;
	protected static $cloudAppService;
	protected static $securityService;
	protected static $securityStatus;

	public function __construct() {
		self::$cloudAppService = Cloud::loadClass('Service_App');
		self::$securityStatus = self::$cloudAppService->getCloudAppStatus('security');
		self::$securityService = Cloud::loadClass('Service_Security');
	}
	
	public function common() {
	    global $_G;
	    if (self::$securityStatus != TRUE) {
	        return false;
	    }
	    if ($_G['uid']) {
	        $lastCookieReportTime = $this->_decodeReportTime($_G['cookie']['security_cookiereport']);
	        if ($lastCookieReportTime < strtotime('today')) {
	            $this->_reportLoginUser(array('uid' => $_G['uid']));
	        }
	    }
	
	    if ($_G['adminid'] > 0) {
	        self::$isAdminGroup = 1;
	    }
	
	    return true;
	}
	
	public function global_footer() {
	    global $_G, $_GET;
	    if (self::$securityStatus != TRUE) {
	        return false;
	    }
	
	    $ajaxReportScript = '';
	    $formhash = formhash();
	    if($_G['member']['allowadmincp'] == 1) {
	        $processName = 'securityOperate';
	        if (self::$isAdminGroup && !discuz_process::islocked($processName, 30)) {
	            $ajaxReportScript .= <<<EOF
					<script type='text/javascript'>
					var url = SITEURL + '/plugin.php?id=security:sitemaster';
					var x = new Ajax();
					x.post(url, 'formhash=$formhash', function(s){});
					</script>
EOF;
	        }
	        $processName = 'securityNotice';
	        if (self::$isAdminGroup && !discuz_process::islocked($processName, 30)) {
	            $ajaxReportScript .= <<<EOF
					<div class="focus plugin" id="evil_notice"></div>
					<script type='text/javascript'>
					var url = SITEURL + '/plugin.php?id=security:evilnotice&formhash=$formhash';
					ajaxget(url, 'evil_notice', '');
					</script>
EOF;
	        }
	    }
	
	    $processName = 'securityRetry';
	    $time = 10;
	    if (!discuz_process::islocked($processName, $time)) {
	        if (C::t('#security#security_failedlog')->count()) {
	            $ajaxRetryScript = <<<EOF
					<script type='text/javascript'>
					var urlRetry = SITEURL + '/plugin.php?id=security:job';
					var ajaxRetry = new Ajax();
					ajaxRetry.post(urlRetry, 'formhash=$formhash', function(s){});
					</script>
EOF;
	        }
	    }
	
	    return $ajaxReportScript . $ajaxRetryScript;
	}
	
	function global_footerlink() {
	    return '&nbsp;<a href="http://discuz.qq.com/service/security" target="_blank" title="'.lang('plugin/security', 'title').'"><img src="static/image/common/security.png"></a>';
	}
}