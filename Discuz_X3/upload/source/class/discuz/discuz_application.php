<?php



if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class discuz_application extends discuz_base{


	var $mem = null;

	var $session = null;

	var $config = array();

	var $var = array();

	var $cachelist = array();

	var $init_db = true;
	var $init_setting = true;
	var $init_user = true;
	var $init_session = true;
	var $init_cron = true;
	var $init_misc = true;
	var $init_mobile = true;

	var $initated = false;

	var $superglobal = array(
		'GLOBALS' => 1,
		'_GET' => 1,
		'_POST' => 1,
		'_REQUEST' => 1,
		'_COOKIE' => 1,
		'_SERVER' => 1,
		'_ENV' => 1,
		'_FILES' => 1,
	);
	
	static function &instance() {
	    static $object;
	    if(empty($object)) {
	        $object = new self();
	    }
	    return $object;
	}
	
	public function __construct() {
	    $this->_init_env();
	    $this->_init_config();
	    $this->_init_input();
	    $this->_init_output();
	}
	
	private function _init_env() {
	
	    error_reporting(E_ERROR);
	    if(PHP_VERSION < '5.3.0') {
	        set_magic_quotes_runtime(0);
	    }
	
	    define('MAGIC_QUOTES_GPC', function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc());
	    define('ICONV_ENABLE', function_exists('iconv'));
	    define('MB_ENABLE', function_exists('mb_convert_encoding'));
	    define('EXT_OBGZIP', function_exists('ob_gzhandler'));
	
	    define('TIMESTAMP', time());
	    $this->timezone_set();
	
	    if(!defined('DISCUZ_CORE_FUNCTION') && !@include(DISCUZ_ROOT.'./source/function/function_core.php')) {
	        exit('function_core.php is missing');
	    }
	
	    if(function_exists('ini_get')) {
	        $memorylimit = @ini_get('memory_limit');
	        if($memorylimit && return_bytes($memorylimit) < 33554432 && function_exists('ini_set')) {
	            ini_set('memory_limit', '128m');
	        }
	    }
	
	    define('IS_ROBOT', checkrobot());
	
	    foreach ($GLOBALS as $key => $value) {
	        if (!isset($this->superglobal[$key])) {
	            $GLOBALS[$key] = null; unset($GLOBALS[$key]);
	        }
	    }
	
	    global $_G;
	    $_G = array(
	        'uid' => 0,
	        'username' => '',
	        'adminid' => 0,
	        'groupid' => 1,
	        'sid' => '',
	        'formhash' => '',
	        'connectguest' => 0,
	        'timestamp' => TIMESTAMP,
	        'starttime' => microtime(true),
	        'clientip' => $this->_get_client_ip(),
	        'remoteport' => $_SERVER['REMOTE_PORT'],
	        'referer' => '',
	        'charset' => '',
	        'gzipcompress' => '',
	        'authkey' => '',
	        'timenow' => array(),
	        'widthauto' => 0,
	        'disabledwidthauto' => 0,
	
	        'PHP_SELF' => '',
	        'siteurl' => '',
	        'siteroot' => '',
	        'siteport' => '',
	
	        'pluginrunlist' => !defined('PLUGINRUNLIST') ? array() : explode(',', PLUGINRUNLIST),
	
	        'config' => array(),
	        'setting' => array(),
	        'member' => array(),
	        'group' => array(),
	        'cookie' => array(),
	        'style' => array(),
	        'cache' => array(),
	        'session' => array(),
	        'lang' => array(),
	        'my_app' => array(),
	        'my_userapp' => array(),
	
	        'fid' => 0,
	        'tid' => 0,
	        'forum' => array(),
	        'thread' => array(),
	        'rssauth' => '',
	
	        'home' => array(),
	        'space' => array(),
	
	        'block' => array(),
	        'article' => array(),
	
	        'action' => array(
	            'action' => APPTYPEID,
	            'fid' => 0,
	            'tid' => 0,
	        ),
	
	        'mobile' => '',
	        'notice_structure' => array(
	            'mypost' => array('post','pcomment','activity','reward','goods','at'),
	            'interactive' => array('poke','friend','wall','comment','click','sharenotice'),
	            'system' => array('system','myapp','credit','group','verify','magic','task','show','group','pusearticle','mod_member','blog','article'),
	            'manage' => array('mod_member','report','pmreport'),
	            'app' => array(),
	        ),
	        'mobiletpl' => array('1' => 'mobile', '2' => 'touch', '3' => 'wml', 'yes' => 'mobile'),
	    );
	    $_G['PHP_SELF'] = dhtmlspecialchars($this->_get_script_url());
	    $_G['basescript'] = CURSCRIPT;
	    $_G['basefilename'] = basename($_G['PHP_SELF']);
	    $sitepath = substr($_G['PHP_SELF'], 0, strrpos($_G['PHP_SELF'], '/'));
	    if(defined('IN_API')) {
	        $sitepath = preg_replace("/\/api\/?.*?$/i", '', $sitepath);
	    } elseif(defined('IN_ARCHIVER')) {
	        $sitepath = preg_replace("/\/archiver/i", '', $sitepath);
	    }
	    $_G['isHTTPS'] = ($_SERVER['HTTPS'] && strtolower($_SERVER['HTTPS']) != 'off') ? true : false;
	    $_G['siteurl'] = dhtmlspecialchars('http'.($_G['isHTTPS'] ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$sitepath.'/');
	
	    $url = parse_url($_G['siteurl']);
	    $_G['siteroot'] = isset($url['path']) ? $url['path'] : '';
	    $_G['siteport'] = empty($_SERVER['SERVER_PORT']) || $_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ':'.$_SERVER['SERVER_PORT'];
	
	    if(defined('SUB_DIR')) {
	        $_G['siteurl'] = str_replace(SUB_DIR, '/', $_G['siteurl']);
	        $_G['siteroot'] = str_replace(SUB_DIR, '/', $_G['siteroot']);
	    }
	
	    $this->var = & $_G;
	
	}
	
	private function _get_client_ip() {
	    $ip = $_SERVER['REMOTE_ADDR'];
	    if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
	        $ip = $_SERVER['HTTP_CLIENT_IP'];
	    } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
	        foreach ($matches[0] AS $xip) {
	            if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
	                $ip = $xip;
	                break;
	            }
	        }
	    }
	    return $ip;
	}
	

}