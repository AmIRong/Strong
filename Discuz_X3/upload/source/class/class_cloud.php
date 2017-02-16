<?php



if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Cloud {

	static private $_loaded = array();

	public static function loadClass($className, $params = null) {

		if (strpos($className, 'Cloud_') !== 0) {
			$className = 'Cloud_' . $className;
		}

		self::loadFile($className);

		$instance = call_user_func_array(array($className, 'getInstance'), (array)$params);

		return $instance;
	}
	
	public static function loadFile($className) {
	
	    $items = explode('_', $className);
	    if ($items[0] == 'Cloud') {
	        unset($items[0]);
	    }
	
	    $loadKey = implode('_', $items);
	    if (isset(self::$_loaded[$loadKey])) {
	        return true;
	    }
	
	    $file = DISCUZ_ROOT . '/source/plugin/manyou/' . implode('/', $items) . '.php';
	
	    if (!is_file($file)) {
	        throw new Cloud_Exception('Cloud file not exists!', 50001);
	    }
	
	    include $file;
	    self::$_loaded[$loadKey] = true;
	
	    return true;
	}
	
}