<?php



if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('DISCUZ_CORE_FUNCTION', true);

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val{strlen($val)-1});
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

function checkrobot($useragent = '') {
    static $kw_spiders = array('bot', 'crawl', 'spider' ,'slurp', 'sohu-search', 'lycos', 'robozilla');
    static $kw_browsers = array('msie', 'netscape', 'opera', 'konqueror', 'mozilla');

    $useragent = strtolower(empty($useragent) ? $_SERVER['HTTP_USER_AGENT'] : $useragent);
    if(strpos($useragent, 'http://') === false && dstrpos($useragent, $kw_browsers)) return false;
    if(dstrpos($useragent, $kw_spiders)) return true;
    return false;
}

function dstrpos($string, $arr, $returnvalue = false) {
    if(empty($string)) return false;
    foreach((array)$arr as $v) {
        if(strpos($string, $v) !== false) {
            $return = $returnvalue ? $v : true;
            return $return;
        }
    }
    return false;
}

function dhtmlspecialchars($string, $flags = null) {
    if(is_array($string)) {
        foreach($string as $key => $val) {
            $string[$key] = dhtmlspecialchars($val, $flags);
        }
    } else {
        if($flags === null) {
            $string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
            if(strpos($string, '&amp;#') !== false) {
                $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
            }
        } else {
            if(PHP_VERSION < '5.4.0') {
                $string = htmlspecialchars($string, $flags);
            } else {
                if(strtolower(CHARSET) == 'utf-8') {
                    $charset = 'UTF-8';
                } else {
                    $charset = 'ISO-8859-1';
                }
                $string = htmlspecialchars($string, $flags, $charset);
            }
        }
    }
    return $string;
}

function setglobal($key , $value, $group = null) {
    global $_G;
    $key = explode('/', $group === null ? $key : $group.'/'.$key);
    $p = &$_G;
    foreach ($key as $k) {
        if(!isset($p[$k]) || !is_array($p[$k])) {
            $p[$k] = array();
        }
        $p = &$p[$k];
    }
    $p = $value;
    return true;
}

function getglobal($key, $group = null) {
    global $_G;
    $key = explode('/', $group === null ? $key : $group.'/'.$key);
    $v = &$_G;
    foreach ($key as $k) {
        if (!isset($v[$k])) {
            return null;
        }
        $v = &$v[$k];
    }
    return $v;
}

function loadcache($cachenames, $force = false) {
    global $_G;
    static $loadedcache = array();
    $cachenames = is_array($cachenames) ? $cachenames : array($cachenames);
    $caches = array();
    foreach ($cachenames as $k) {
        if(!isset($loadedcache[$k]) || $force) {
            $caches[] = $k;
            $loadedcache[$k] = true;
        }
    }

    if(!empty($caches)) {
        $cachedata = C::t('common_syscache')->fetch_all($caches);
        foreach($cachedata as $cname => $data) {
            if($cname == 'setting') {
                $_G['setting'] = $data;
            } elseif($cname == 'usergroup_'.$_G['groupid']) {
                $_G['cache'][$cname] = $_G['group'] = $data;
            } elseif($cname == 'style_default') {
                $_G['cache'][$cname] = $_G['style'] = $data;
            } elseif($cname == 'grouplevels') {
                $_G['grouplevels'] = $data;
            } else {
                $_G['cache'][$cname] = $data;
            }
        }
    }
    return true;
}

function memory($cmd, $key='', $value='', $ttl = 0, $prefix = '') {
    if($cmd == 'check') {
        return  C::memory()->enable ? C::memory()->type : '';
    } elseif(C::memory()->enable && in_array($cmd, array('set', 'get', 'rm', 'inc', 'dec'))) {
        if(defined('DISCUZ_DEBUG') && DISCUZ_DEBUG) {
            if(is_array($key)) {
                foreach($key as $k) {
                    C::memory()->debug[$cmd][] = ($cmd == 'get' || $cmd == 'rm' ? $value : '').$prefix.$k;
                }
            } else {
                C::memory()->debug[$cmd][] = ($cmd == 'get' || $cmd == 'rm' ? $value : '').$prefix.$key;
            }
        }
        switch ($cmd) {
            case 'set': return C::memory()->set($key, $value, $ttl, $prefix); break;
            case 'get': return C::memory()->get($key, $value); break;
            case 'rm': return C::memory()->rm($key, $value); break;
            case 'inc': return C::memory()->inc($key, $value ? $value : 1); break;
            case 'dec': return C::memory()->dec($key, $value ? $value : -1); break;
        }
    }
    return null;
}


?>