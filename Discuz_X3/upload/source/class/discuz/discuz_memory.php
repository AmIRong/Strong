<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: discuz_memory.php 31432 2012-08-28 03:04:18Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class discuz_memory extends discuz_base
{
	private $config;
	private $extension = array();
	private $memory;
	private $prefix;
	private $userprefix;
	public $type;
	public $enable = false;
	public $debug = array();

	public function __construct() {
		$this->extension['redis'] = extension_loaded('redis');
		$this->extension['memcache'] = extension_loaded('memcache');
		$this->extension['apc'] = function_exists('apc_cache_info') && @apc_cache_info();
		$this->extension['xcache'] = function_exists('xcache_get');
		$this->extension['eaccelerator'] = function_exists('eaccelerator_get');
		$this->extension['wincache'] = function_exists('wincache_ucache_meminfo') && wincache_ucache_meminfo();
	}
	public function init($config) {
	    $this->config = $config;
	    $this->prefix = empty($config['prefix']) ? substr(md5($_SERVER['HTTP_HOST']), 0, 6).'_' : $config['prefix'];
	
	
	    if($this->extension['redis'] && !empty($config['redis']['server'])) {
	        $this->memory = new memory_driver_redis();
	        $this->memory->init($this->config['redis']);
	        if(!$this->memory->enable) {
	            $this->memory = null;
	        }
	    }
	
	    if($this->extension['memcache'] && !empty($config['memcache']['server'])) {
	        $this->memory = new memory_driver_memcache();
	        $this->memory->init($this->config['memcache']);
	        if(!$this->memory->enable) {
	            $this->memory = null;
	        }
	    }
	
	    foreach(array('apc', 'eaccelerator', 'xcache', 'wincache') as $cache) {
	        if(!is_object($this->memory) && $this->extension[$cache] && $this->config[$cache]) {
	            $class_name = 'memory_driver_'.$cache;
	            $this->memory = new $class_name();
	            $this->memory->init(null);
	        }
	    }
	
	    if(is_object($this->memory)) {
	        $this->enable = true;
	        $this->type = str_replace('memory_driver_', '', get_class($this->memory));
	    }
	
	}
}