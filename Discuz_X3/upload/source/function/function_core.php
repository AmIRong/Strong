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

function random($length, $numeric = 0) {
    $seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
    if($numeric) {
        $hash = '';
    } else {
        $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
        $length--;
    }
    $max = strlen($seed) - 1;
    for($i = 0; $i < $length; $i++) {
        $hash .= $seed{mt_rand(0, $max)};
    }
    return $hash;
}

function dsetcookie($var, $value = '', $life = 0, $prefix = 1, $httponly = false) {

    global $_G;

    $config = $_G['config']['cookie'];

    $_G['cookie'][$var] = $value;
    $var = ($prefix ? $config['cookiepre'] : '').$var;
    $_COOKIE[$var] = $value;

    if($value == '' || $life < 0) {
        $value = '';
        $life = -1;
    }

    if(defined('IN_MOBILE')) {
        $httponly = false;
    }

    $life = $life > 0 ? getglobal('timestamp') + $life : ($life < 0 ? getglobal('timestamp') - 31536000 : 0);
    $path = $httponly && PHP_VERSION < '5.2.0' ? $config['cookiepath'].'; HttpOnly' : $config['cookiepath'];

    $secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
    if(PHP_VERSION < '5.2.0') {
        setcookie($var, $value, $life, $path, $config['cookiedomain'], $secure);
    } else {
        setcookie($var, $value, $life, $path, $config['cookiedomain'], $secure, $httponly);
    }
}

function ipbanned($onlineip) {
    global $_G;

    if($_G['setting']['ipaccess'] && !ipaccess($onlineip, $_G['setting']['ipaccess'])) {
        return TRUE;
    }

    loadcache('ipbanned');
    if(empty($_G['cache']['ipbanned'])) {
        return FALSE;
    } else {
        if($_G['cache']['ipbanned']['expiration'] < TIMESTAMP) {
            require_once libfile('function/cache');
            updatecache('ipbanned');
        }
        return preg_match("/^(".$_G['cache']['ipbanned']['regexp'].")$/", $onlineip);
    }
}

function getgpc($k, $type='GP') {
    $type = strtoupper($type);
    switch($type) {
        case 'G': $var = &$_GET; break;
        case 'P': $var = &$_POST; break;
        case 'C': $var = &$_COOKIE; break;
        default:
            if(isset($_GET[$k])) {
                $var = &$_GET;
            } else {
                $var = &$_POST;
            }
            break;
    }

    return isset($var[$k]) ? $var[$k] : NULL;

}

function checkmobile() {
    global $_G;
    $mobile = array();
    static $touchbrowser_list =array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
        'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
        'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
        'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
        'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
        'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
        'benq', 'haier', '^lct', '320x320', '240x320', '176x220', 'windows phone');
    static $wmlbrowser_list = array('cect', 'compal', 'ctl', 'lg', 'nec', 'tcl', 'alcatel', 'ericsson', 'bird', 'daxian', 'dbtel', 'eastcom',
        'pantech', 'dopod', 'philips', 'haier', 'konka', 'kejian', 'lenovo', 'benq', 'mot', 'soutec', 'nokia', 'sagem', 'sgh',
        'sed', 'capitel', 'panasonic', 'sonyericsson', 'sharp', 'amoi', 'panda', 'zte');

    static $pad_list = array('ipad');

    $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);

    if(dstrpos($useragent, $pad_list)) {
        return false;
    }
    if(($v = dstrpos($useragent, $touchbrowser_list, true))){
        $_G['mobile'] = $v;
        return '2';
    }
    if(($v = dstrpos($useragent, $wmlbrowser_list))) {
        $_G['mobile'] = $v;
        return '3'; //wml版
    }
    $brower = array('mozilla', 'chrome', 'safari', 'opera', 'm3gate', 'winwap', 'openwave', 'myop');
    if(dstrpos($useragent, $brower)) return false;

    $_G['mobile'] = 'unknown';
    if(isset($_G['mobiletpl'][$_GET['mobile']])) {
        return true;
    } else {
        return false;
    }
}

function lang($file, $langvar = null, $vars = array(), $default = null) {
    global $_G;
    $fileinput = $file;
    list($path, $file) = explode('/', $file);
    if(!$file) {
        $file = $path;
        $path = '';
    }
    if(strpos($file, ':') !== false) {
        $path = 'plugin';
        list($file) = explode(':', $file);
    }

    if($path != 'plugin') {
        $key = $path == '' ? $file : $path.'_'.$file;
        if(!isset($_G['lang'][$key])) {
            include DISCUZ_ROOT.'./source/language/'.($path == '' ? '' : $path.'/').'lang_'.$file.'.php';
            $_G['lang'][$key] = $lang;
        }
        if(defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
            include DISCUZ_ROOT.'./source/language/mobile/lang_template.php';
            $_G['lang'][$key] = array_merge($_G['lang'][$key], $lang);
        }
        if($file != 'error' && !isset($_G['cache']['pluginlanguage_system'])) {
            loadcache('pluginlanguage_system');
        }
        if(!isset($_G['hooklang'][$fileinput])) {
            if(isset($_G['cache']['pluginlanguage_system'][$fileinput]) && is_array($_G['cache']['pluginlanguage_system'][$fileinput])) {
                $_G['lang'][$key] = array_merge($_G['lang'][$key], $_G['cache']['pluginlanguage_system'][$fileinput]);
            }
            $_G['hooklang'][$fileinput] = true;
        }
        $returnvalue = &$_G['lang'];
    } else {
        if(empty($_G['config']['plugindeveloper'])) {
            loadcache('pluginlanguage_script');
        } elseif(!isset($_G['cache']['pluginlanguage_script'][$file]) && preg_match("/^[a-z]+[a-z0-9_]*$/i", $file)) {
            if(@include(DISCUZ_ROOT.'./data/plugindata/'.$file.'.lang.php')) {
                $_G['cache']['pluginlanguage_script'][$file] = $scriptlang[$file];
            } else {
                loadcache('pluginlanguage_script');
            }
        }
        $returnvalue = & $_G['cache']['pluginlanguage_script'];
        $key = &$file;
    }
    $return = $langvar !== null ? (isset($returnvalue[$key][$langvar]) ? $returnvalue[$key][$langvar] : null) : $returnvalue[$key];
    $return = $return === null ? ($default !== null ? $default : $langvar) : $return;
    $searchs = $replaces = array();
    if($vars && is_array($vars)) {
        foreach($vars as $k => $v) {
            $searchs[] = '{'.$k.'}';
            $replaces[] = $v;
        }
    }
    if(is_string($return) && strpos($return, '{_G/') !== false) {
        preg_match_all('/\{_G\/(.+?)\}/', $return, $gvar);
        foreach($gvar[0] as $k => $v) {
            $searchs[] = $v;
            $replaces[] = getglobal($gvar[1][$k]);
        }
    }
    $return = str_replace($searchs, $replaces, $return);
    return $return;
}

function dgmdate($timestamp, $format = 'dt', $timeoffset = '9999', $uformat = '') {
    global $_G;
    $format == 'u' && !$_G['setting']['dateconvert'] && $format = 'dt';
    static $dformat, $tformat, $dtformat, $offset, $lang;
    if($dformat === null) {
        $dformat = getglobal('setting/dateformat');
        $tformat = getglobal('setting/timeformat');
        $dtformat = $dformat.' '.$tformat;
        $offset = getglobal('member/timeoffset');
        $sysoffset = getglobal('setting/timeoffset');
        $offset = $offset == 9999 ? ($sysoffset ? $sysoffset : 0) : $offset;
        $lang = lang('core', 'date');
    }
    $timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
    $timestamp += $timeoffset * 3600;
    $format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
    if($format == 'u') {
        $todaytimestamp = TIMESTAMP - (TIMESTAMP + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
        $s = gmdate(!$uformat ? $dtformat : $uformat, $timestamp);
        $time = TIMESTAMP + $timeoffset * 3600 - $timestamp;
        if($timestamp >= $todaytimestamp) {
            if($time > 3600) {
                $return = intval($time / 3600).'&nbsp;'.$lang['hour'].$lang['before'];
            } elseif($time > 1800) {
                $return = $lang['half'].$lang['hour'].$lang['before'];
            } elseif($time > 60) {
                $return = intval($time / 60).'&nbsp;'.$lang['min'].$lang['before'];
            } elseif($time > 0) {
                $return = $time.'&nbsp;'.$lang['sec'].$lang['before'];
            } elseif($time == 0) {
                $return = $lang['now'];
            } else {
                $return = $s;
            }
            if($time >=0 && !defined('IN_MOBILE')) {
                $return = '<span title="'.$s.'">'.$return.'</span>';
            }
        } elseif(($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
            if($days == 0) {
                $return = $lang['yday'].'&nbsp;'.gmdate($tformat, $timestamp);
            } elseif($days == 1) {
                $return = $lang['byday'].'&nbsp;'.gmdate($tformat, $timestamp);
            } else {
                $return = ($days + 1).'&nbsp;'.$lang['day'].$lang['before'];
            }
            if(!defined('IN_MOBILE')) {
                $return = '<span title="'.$s.'">'.$return.'</span>';
            }
        } else {
            $return = $s;
        }
        return $return;
    } else {
        return gmdate($format, $timestamp);
    }
}

function formhash($specialadd = '') {
    global $_G;
    $hashadd = defined('IN_ADMINCP') ? 'Only For Discuz! Admin Control Panel' : '';
    return substr(md5(substr($_G['timestamp'], 0, -7).$_G['username'].$_G['uid'].$_G['authkey'].$hashadd.$specialadd), 8, 8);
}

function periodscheck($periods, $showmessage = 1) {
    global $_G;
    if(($periods == 'postmodperiods' || $periods == 'postbanperiods') && ($_G['setting']['postignorearea'] || $_G['setting']['postignoreip'])) {
        if($_G['setting']['postignoreip']) {
            foreach(explode("\n", $_G['setting']['postignoreip']) as $ctrlip) {
                if(preg_match("/^(".preg_quote(($ctrlip = trim($ctrlip)), '/').")/", $_G['clientip'])) {
                    return false;
                    break;
                }
            }
        }
        if($_G['setting']['postignorearea']) {
            $location = $whitearea = '';
            require_once libfile('function/misc');
            $location = trim(convertip($_G['clientip'], "./"));
            if($location) {
                $whitearea = preg_quote(trim($_G['setting']['postignorearea']), '/');
                $whitearea = str_replace(array("\\*"), array('.*'), $whitearea);
                $whitearea = '.*'.$whitearea.'.*';
                $whitearea = '/^('.str_replace(array("\r\n", ' '), array('.*|.*', ''), $whitearea).')$/i';
                if(@preg_match($whitearea, $location)) {
                    return false;
                }
            }
        }
    }
    if(!$_G['group']['disableperiodctrl'] && $_G['setting'][$periods]) {
        $now = dgmdate(TIMESTAMP, 'G.i', $_G['setting']['timeoffset']);
        foreach(explode("\r\n", str_replace(':', '.', $_G['setting'][$periods])) as $period) {
            list($periodbegin, $periodend) = explode('-', $period);
            if(($periodbegin > $periodend && ($now >= $periodbegin || $now < $periodend)) || ($periodbegin < $periodend && $now >= $periodbegin && $now < $periodend)) {
                $banperiods = str_replace("\r\n", ', ', $_G['setting'][$periods]);
                if($showmessage) {
                    showmessage('period_nopermission', NULL, array('banperiods' => $banperiods), array('login' => 1));
                } else {
                    return TRUE;
                }
            }
        }
    }
    return FALSE;
}


?>