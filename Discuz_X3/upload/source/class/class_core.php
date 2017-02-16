<?php



error_reporting(E_ALL);

define('IN_DISCUZ', true);
define('DISCUZ_ROOT', substr(dirname(__FILE__), 0, -12));
define('DISCUZ_CORE_DEBUG', false);
define('DISCUZ_TABLE_EXTENDABLE', false);

set_exception_handler(array('core', 'handleException'));

if(DISCUZ_CORE_DEBUG) {
	set_error_handler(array('core', 'handleError'));
	register_shutdown_function(array('core', 'handleShutdown'));
}

if(function_exists('spl_autoload_register')) {
	spl_autoload_register(array('core', 'autoload'));
} else {
	function __autoload($class) {
		return core::autoload($class);
	}
}

C::creatapp();

class core
{
	private static $_tables;
	private static $_imports;
	private static $_app;
	private static $_memory;
	
	public static function app() {
	    return self::$_app;
	}
	
	public static function autoload($class) {
	    $class = strtolower($class);
	    if(strpos($class, '_') !== false) {
	        list($folder) = explode('_', $class);
	        $file = 'class/'.$folder.'/'.substr($class, strlen($folder) + 1);
	    } else {
	        $file = 'class/'.$class;
	    }
	
	    try {
	
	        self::import($file);
	        return true;
	
	    } catch (Exception $exc) {
	
	        $trace = $exc->getTrace();
	        foreach ($trace as $log) {
	            if(empty($log['class']) && $log['function'] == 'class_exists') {
	                return false;
	            }
	        }
	        discuz_error::exception_error($exc);
	    }
	}
	
	public static function creatapp() {
	    if(!is_object(self::$_app)) {
	        self::$_app = discuz_application::instance();
	    }
	    return self::$_app;
	}
	
	public static function import($name, $folder = '', $force = true) {
	    $key = $folder.$name;
	    if(!isset(self::$_imports[$key])) {
	        $path = DISCUZ_ROOT.'/source/'.$folder;
	        if(strpos($name, '/') !== false) {
	            $pre = basename(dirname($name));
	            $filename = dirname($name).'/'.$pre.'_'.basename($name).'.php';
	        } else {
	            $filename = $name.'.php';
	        }
	
	        if(is_file($path.'/'.$filename)) {
	            include $path.'/'.$filename;
	            self::$_imports[$key] = true;
	
	            return true;
	        } elseif(!$force) {
	            return false;
	        } else {
	            throw new Exception('Oops! System file lost: '.$filename);
	        }
	    }
	    return true;
	}
	
	public static function handleException($exception) {
	    discuz_error::exception_error($exception);
	}
}

class C extends core {}
class DB extends discuz_database {}

