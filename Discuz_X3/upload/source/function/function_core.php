<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_core.php 35335 2015-06-17 01:57:38Z hypowang $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('DISCUZ_CORE_FUNCTION', true);

function adshow($parameter) {
    global $_G;
    if($_G['inajax'] || $_G['group']['closead']) {
        return;
    }
    if(isset($_G['config']['plugindeveloper']) && $_G['config']['plugindeveloper'] == 2) {
        return '<hook>[ad '.$parameter.']</hook>';
    }
    $params = explode('/', $parameter);
    $customid = 0;
    $customc = explode('_', $params[0]);
    if($customc[0] == 'custom') {
        $params[0] = $customc[0];
        $customid = $customc[1];
    }
    $adcontent = null;
    if(empty($_G['setting']['advtype']) || !in_array($params[0], $_G['setting']['advtype'])) {
        $adcontent = '';
    }
    if($adcontent === null) {
        loadcache('advs');
        $adids = array();
        $evalcode = &$_G['cache']['advs']['evalcode'][$params[0]];
        $parameters = &$_G['cache']['advs']['parameters'][$params[0]];
        $codes = &$_G['cache']['advs']['code'][$_G['basescript']][$params[0]];
        if(!empty($codes)) {
            foreach($codes as $adid => $code) {
                $parameter = &$parameters[$adid];
                $checked = true;
                @eval($evalcode['check']);
                if($checked) {
                    $adids[] = $adid;
                }
            }
            if(!empty($adids)) {
                $adcode = $extra = '';
                @eval($evalcode['create']);
                if(empty($notag)) {
                    $adcontent = '<div'.($params[1] != '' ? ' class="'.$params[1].'"' : '').$extra.'>'.$adcode.'</div>';
                } else {
                    $adcontent = $adcode;
                }
            }
        }
    }
    $adfunc = 'ad_'.$params[0];
    $_G['setting']['pluginhooks'][$adfunc] = null;
    hookscript('ad', 'global', 'funcs', array('params' => $params, 'content' => $adcontent), $adfunc);
    if(!$_G['setting']['hookscript']['global']['ad']['funcs'][$adfunc]) {
        hookscript('ad', $_G['basescript'], 'funcs', array('params' => $params, 'content' => $adcontent), $adfunc);
    }
    return $_G['setting']['pluginhooks'][$adfunc] === null ? $adcontent : $_G['setting']['pluginhooks'][$adfunc];
}

function check_diy_perm($topic = array(), $flag = '') {
    static $ret;
    if(!isset($ret)) {
        global $_G;
        $common = !empty($_G['style']['tplfile']) || $_GET['inajax'];
        $blockallow = getstatus($_G['member']['allowadmincp'], 4) || getstatus($_G['member']['allowadmincp'], 5) || getstatus($_G['member']['allowadmincp'], 6);
        $ret['data'] = $common && $blockallow;
        $ret['layout'] = $common && ($_G['group']['allowdiy'] || (
            CURMODULE === 'topic' && ($_G['group']['allowmanagetopic'] || $_G['group']['allowaddtopic'] && $topic && $topic['uid'] == $_G['uid'])
            ));
    }
    return empty($flag) ? $ret['data'] || $ret['layout'] : $ret[$flag];
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

function checkrobot($useragent = '') {
    static $kw_spiders = array('bot', 'crawl', 'spider' ,'slurp', 'sohu-search', 'lycos', 'robozilla');
    static $kw_browsers = array('msie', 'netscape', 'opera', 'konqueror', 'mozilla');

    $useragent = strtolower(empty($useragent) ? $_SERVER['HTTP_USER_AGENT'] : $useragent);
    if(strpos($useragent, 'http://') === false && dstrpos($useragent, $kw_browsers)) return false;
    if(dstrpos($useragent, $kw_spiders)) return true;
    return false;
}

function checktplrefresh($maintpl, $subtpl, $timecompare, $templateid, $cachefile, $tpldir, $file) {
    static $tplrefresh, $timestamp, $targettplname;
    if($tplrefresh === null) {
        $tplrefresh = getglobal('config/output/tplrefresh');
        $timestamp = getglobal('timestamp');
    }

    if(empty($timecompare) || $tplrefresh == 1 || ($tplrefresh > 1 && !($timestamp % $tplrefresh))) {
        if(empty($timecompare) || @filemtime(DISCUZ_ROOT.$subtpl) > $timecompare) {
            require_once DISCUZ_ROOT.'/source/class/class_template.php';
            $template = new template();
            $template->parse_template($maintpl, $templateid, $tpldir, $file, $cachefile);
            if($targettplname === null) {
                $targettplname = getglobal('style/tplfile');
                if(!empty($targettplname)) {
                    include_once libfile('function/block');
                    $targettplname = strtr($targettplname, ':', '_');
                    update_template_block($targettplname, getglobal('style/tpldirectory'), $template->blocks);
                }
                $targettplname = true;
            }
            return TRUE;
        }
    }
    return FALSE;
}

function daddslashes($string, $force = 1) {
    if(is_array($string)) {
        $keys = array_keys($string);
        foreach($keys as $key) {
            $val = $string[$key];
            unset($string[$key]);
            $string[addslashes($key)] = daddslashes($val, $force);
        }
    } else {
        $string = addslashes($string);
    }
    return $string;
}

function debuginfo() {
    global $_G;
    if(getglobal('setting/debug')) {
        $db = & DB::object();
        $_G['debuginfo'] = array(
            'time' => number_format((microtime(true) - $_G['starttime']), 6),
            'queries' => $db->querynum,
            'memory' => ucwords(C::memory()->type)
        );
        if($db->slaveid) {
            $_G['debuginfo']['queries'] = 'Total '.$db->querynum.', Slave '.$db->slavequery;
        }
        return TRUE;
    } else {
        return FALSE;
    }
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

function dintval($int, $allowarray = false) {
    $ret = intval($int);
    if($int == $ret || !$allowarray && is_array($int)) return $ret;
    if($allowarray && is_array($int)) {
        foreach($int as &$v) {
            $v = dintval($v, true);
        }
        return $int;
    } elseif($int <= 0xffffffff) {
        $l = strlen($int);
        $m = substr($int, 0, 1) == '-' ? 1 : 0;
        if(($l - $m) === strspn($int,'0987654321', $m)) {
            return $int;
        }
    }
    return $ret;
}

function dnumber($number) {
    return abs($number) > 10000 ? '<span title="'.$number.'">'.intval($number / 10000).lang('core', '10k').'</span>' : $number;
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

function formhash($specialadd = '') {
    global $_G;
    $hashadd = defined('IN_ADMINCP') ? 'Only For Discuz! Admin Control Panel' : '';
    return substr(md5(substr($_G['timestamp'], 0, -7).$_G['username'].$_G['uid'].$_G['authkey'].$hashadd.$specialadd), 8, 8);
}

function get_seosetting($page, $data = array(), $defset = array()) {
    return helper_seo::get_seosetting($page, $data, $defset);
}

function getcurrentnav() {
    global $_G;
    if(!empty($_G['mnid'])) {
        return $_G['mnid'];
    }
    $mnid = '';
    $_G['basefilename'] = $_G['basefilename'] == $_G['basescript'] ? $_G['basefilename'] : $_G['basescript'].'.php';
    if(isset($_G['setting']['navmns'][$_G['basefilename']])) {
        if($_G['basefilename'] == 'home.php' && $_GET['mod'] == 'space' && (empty($_GET['do']) || in_array($_GET['do'], array('follow', 'view')))) {
            $_GET['mod'] = 'follow';
        }
        foreach($_G['setting']['navmns'][$_G['basefilename']] as $navmn) {
            if($navmn[0] == array_intersect_assoc($navmn[0], $_GET) || ($navmn[0]['mod'] == 'space' && $_GET['mod'] == 'spacecp' && ($navmn[0]['do'] == $_GET['ac'] || $navmn[0]['do'] == 'album' && $_GET['ac'] == 'upload'))) {
                $mnid = $navmn[1];
            }
        }

    }
    if(!$mnid && isset($_G['setting']['navdms'])) {
        foreach($_G['setting']['navdms'] as $navdm => $navid) {
            if(strpos(strtolower($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']), $navdm) !== false && strpos(strtolower($_SERVER['HTTP_HOST']), $navdm) === false) {
                $mnid = $navid;
                break;
            }
        }
    }
    if(!$mnid && isset($_G['setting']['navmn'][$_G['basefilename']])) {
        $mnid = $_G['setting']['navmn'][$_G['basefilename']];
    }
    return $mnid;
}

function getfocus_rand($module) {
    global $_G;

    if(empty($_G['setting']['focus']) || !array_key_exists($module, $_G['setting']['focus']) || !empty($_G['cookie']['nofocus_'.$module]) || !$_G['setting']['focus'][$module]) {
        return null;
    }
    loadcache('focus');
    if(empty($_G['cache']['focus']['data']) || !is_array($_G['cache']['focus']['data'])) {
        return null;
    }
    $focusid = $_G['setting']['focus'][$module][array_rand($_G['setting']['focus'][$module])];
    return $focusid;
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

function getstatus($status, $position) {
    $t = $status & pow(2, $position - 1) ? 1 : 0;
    return $t;
}

function hookscript($script, $hscript, $type = 'funcs', $param = array(), $func = '', $scriptextra = '') {
    global $_G;
    static $pluginclasses;
    if($hscript == 'home') {
        if($script == 'space') {
            $scriptextra = !$scriptextra ? $_GET['do'] : $scriptextra;
            $script = 'space'.(!empty($scriptextra) ? '_'.$scriptextra : '');
        } elseif($script == 'spacecp') {
            $scriptextra = !$scriptextra ? $_GET['ac'] : $scriptextra;
            $script .= !empty($scriptextra) ? '_'.$scriptextra : '';
        }
    }
    if(!isset($_G['setting'][HOOKTYPE][$hscript][$script][$type])) {
        return;
    }
    if(!isset($_G['cache']['plugin'])) {
        loadcache('plugin');
    }
    foreach((array)$_G['setting'][HOOKTYPE][$hscript][$script]['module'] as $identifier => $include) {
        if($_G['pluginrunlist'] && !in_array($identifier, $_G['pluginrunlist'])) {
            continue;
        }
        $hooksadminid[$identifier] = !$_G['setting'][HOOKTYPE][$hscript][$script]['adminid'][$identifier] || ($_G['setting'][HOOKTYPE][$hscript][$script]['adminid'][$identifier] && $_G['adminid'] > 0 && $_G['setting']['hookscript'][$hscript][$script]['adminid'][$identifier] >= $_G['adminid']);
        if($hooksadminid[$identifier]) {
            @include_once DISCUZ_ROOT.'./source/plugin/'.$include.'.class.php';
        }
    }
    if(@is_array($_G['setting'][HOOKTYPE][$hscript][$script][$type])) {
        $_G['inhookscript'] = true;
        $funcs = !$func ? $_G['setting'][HOOKTYPE][$hscript][$script][$type] : array($func => $_G['setting'][HOOKTYPE][$hscript][$script][$type][$func]);
        foreach($funcs as $hookkey => $hookfuncs) {
            foreach($hookfuncs as $hookfunc) {
                if($hooksadminid[$hookfunc[0]]) {
                    $classkey = (HOOKTYPE != 'hookscriptmobile' ? '' : 'mobile').'plugin_'.($hookfunc[0].($hscript != 'global' ? '_'.$hscript : ''));
                    if(!class_exists($classkey, false)) {
                        continue;
                    }
                    if(!isset($pluginclasses[$classkey])) {
                        $pluginclasses[$classkey] = new $classkey;
                    }
                    if(!method_exists($pluginclasses[$classkey], $hookfunc[1])) {
                        continue;
                    }
                    $return = $pluginclasses[$classkey]->$hookfunc[1]($param);

                    if(substr($hookkey, -7) == '_extend' && !empty($_G['setting']['pluginhooks'][$hookkey])) {
                        continue;
                    }

                    if(is_array($return)) {
                        if(!isset($_G['setting']['pluginhooks'][$hookkey]) || is_array($_G['setting']['pluginhooks'][$hookkey])) {
                            foreach($return as $k => $v) {
                                $_G['setting']['pluginhooks'][$hookkey][$k] .= $v;
                            }
                        } else {
                            foreach($return as $k => $v) {
                                $_G['setting']['pluginhooks'][$hookkey][$k] = $v;
                            }
                        }
                    } else {
                        if(!is_array($_G['setting']['pluginhooks'][$hookkey])) {
                            $_G['setting']['pluginhooks'][$hookkey] .= $return;
                        } else {
                            foreach($_G['setting']['pluginhooks'][$hookkey] as $k => $v) {
                                $_G['setting']['pluginhooks'][$hookkey][$k] .= $return;
                            }
                        }
                    }
                }
            }
        }
    }
    $_G['inhookscript'] = false;
}

function hookscriptoutput($tplfile) {
    global $_G;
    if(!empty($_G['hookscriptoutput'])) {
        return;
    }
    hookscript('global', 'global');
    if(defined('CURMODULE')) {
        $param = array('template' => $tplfile, 'message' => $_G['hookscriptmessage'], 'values' => $_G['hookscriptvalues']);
        hookscript(CURMODULE, $_G['basescript'], 'outputfuncs', $param);
    }
    $_G['hookscriptoutput'] = true;
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

function libfile($libname, $folder = '') {
    $libpath = '/source/'.$folder;
    if(strstr($libname, '/')) {
        list($pre, $name) = explode('/', $libname);
        $path = "{$libpath}/{$pre}/{$pre}_{$name}";
    } else {
        $path = "{$libpath}/{$libname}";
    }
    return preg_match('/^[\w\d\/_]+$/i', $path) ? realpath(DISCUZ_ROOT.$path.'.php') : false;
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

function output() {

    global $_G;


    if(defined('DISCUZ_OUTPUTED')) {
        return;
    } else {
        define('DISCUZ_OUTPUTED', 1);
    }

    if(!empty($_G['blockupdate'])) {
        block_updatecache($_G['blockupdate']['bid']);
    }

    if(defined('IN_MOBILE')) {
        mobileoutput();
    }
    if(!defined('IN_MOBILE') && !defined('IN_ARCHIVER')) {
        $tipsService = Cloud::loadClass('Service_DiscuzTips');
        $tipsService->show();
    }
    $havedomain = implode('', $_G['setting']['domain']['app']);
    if($_G['setting']['rewritestatus'] || !empty($havedomain)) {
        $content = ob_get_contents();
        $content = output_replace($content);


        ob_end_clean();
        $_G['gzipcompress'] ? ob_start('ob_gzhandler') : ob_start();

        echo $content;
    }

    if(isset($_G['makehtml'])) {
        helper_makehtml::make_html();
    }

    if($_G['setting']['ftp']['connid']) {
        @ftp_close($_G['setting']['ftp']['connid']);
    }
    $_G['setting']['ftp'] = array();

    if(defined('CACHE_FILE') && CACHE_FILE && !defined('CACHE_FORBIDDEN') && !defined('IN_MOBILE') && !checkmobile()) {
        if(diskfreespace(DISCUZ_ROOT.'./'.$_G['setting']['cachethreaddir']) > 1000000) {
            if($fp = @fopen(CACHE_FILE, 'w')) {
                flock($fp, LOCK_EX);
                fwrite($fp, empty($content) ? ob_get_contents() : $content);
            }
            @fclose($fp);
            chmod(CACHE_FILE, 0777);
        }
    }

    if(defined('DISCUZ_DEBUG') && DISCUZ_DEBUG && @include(libfile('function/debug'))) {
        function_exists('debugmessage') && debugmessage();
    }
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

function runhooks($scriptextra = '') {
    if(!defined('HOOKTYPE')) {
        define('HOOKTYPE', !defined('IN_MOBILE') ? 'hookscript' : 'hookscriptmobile');
    }
    if(defined('CURMODULE')) {
        global $_G;
        if($_G['setting']['plugins']['func'][HOOKTYPE]['common']) {
            hookscript('common', 'global', 'funcs', array(), 'common');
        }
        hookscript(CURMODULE, $_G['basescript'], 'funcs', array(), '', $scriptextra);
    }
}

function savecache($cachename, $data) {
    C::t('common_syscache')->insert($cachename, $data);
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

function strexists($string, $find) {
    return !(strpos($string, $find) === FALSE);
}

function template($file, $templateid = 0, $tpldir = '', $gettplfile = 0, $primaltpl='') {
    global $_G;

    static $_init_style = false;
    if($_init_style === false) {
        C::app()->_init_style();
        $_init_style = true;
    }
    $oldfile = $file;
    if(strpos($file, ':') !== false) {
        $clonefile = '';
        list($templateid, $file, $clonefile) = explode(':', $file);
        $oldfile = $file;
        $file = empty($clonefile) ? $file : $file.'_'.$clonefile;
        if($templateid == 'diy') {
            $indiy = false;
            $_G['style']['tpldirectory'] = $tpldir ? $tpldir : (defined('TPLDIR') ? TPLDIR : '');
            $_G['style']['prefile'] = '';
            $diypath = DISCUZ_ROOT.'./data/diy/'.$_G['style']['tpldirectory'].'/'; //DIY模板文件目录
            $preend = '_diy_preview';
            $_GET['preview'] = !empty($_GET['preview']) ? $_GET['preview'] : '';
            $curtplname = $oldfile;
            $basescript = $_G['mod'] == 'viewthread' && !empty($_G['thread']) ? 'forum' : $_G['basescript'];
            if(isset($_G['cache']['diytemplatename'.$basescript])) {
                $diytemplatename = &$_G['cache']['diytemplatename'.$basescript];
            } else {
                if(!isset($_G['cache']['diytemplatename'])) {
                    loadcache('diytemplatename');
                }
                $diytemplatename = &$_G['cache']['diytemplatename'];
            }
            $tplsavemod = 0;
            if(isset($diytemplatename[$file]) && file_exists($diypath.$file.'.htm') && ($tplsavemod = 1) || empty($_G['forum']['styleid']) && ($file = $primaltpl ? $primaltpl : $oldfile) && isset($diytemplatename[$file]) && file_exists($diypath.$file.'.htm')) {
                $tpldir = 'data/diy/'.$_G['style']['tpldirectory'].'/';
                !$gettplfile && $_G['style']['tplsavemod'] = $tplsavemod;
                $curtplname = $file;
                if(isset($_GET['diy']) && $_GET['diy'] == 'yes' || isset($_GET['diy']) && $_GET['preview'] == 'yes') { //DIY模式或预览模式下做以下判断
                    $flag = file_exists($diypath.$file.$preend.'.htm');
                    if($_GET['preview'] == 'yes') {
                        $file .= $flag ? $preend : '';
                    } else {
                        $_G['style']['prefile'] = $flag ? 1 : '';
                    }
                }
                $indiy = true;
            } else {
                $file = $primaltpl ? $primaltpl : $oldfile;
            }
            $tplrefresh = $_G['config']['output']['tplrefresh'];
            if($indiy && ($tplrefresh ==1 || ($tplrefresh > 1 && !($_G['timestamp'] % $tplrefresh))) && filemtime($diypath.$file.'.htm') < filemtime(DISCUZ_ROOT.$_G['style']['tpldirectory'].'/'.($primaltpl ? $primaltpl : $oldfile).'.htm')) {
                if (!updatediytemplate($file, $_G['style']['tpldirectory'])) {
                    unlink($diypath.$file.'.htm');
                    $tpldir = '';
                }
            }

            if (!$gettplfile && empty($_G['style']['tplfile'])) {
                $_G['style']['tplfile'] = empty($clonefile) ? $curtplname : $oldfile.':'.$clonefile;
            }

            $_G['style']['prefile'] = !empty($_GET['preview']) && $_GET['preview'] == 'yes' ? '' : $_G['style']['prefile'];

        } else {
            $tpldir = './source/plugin/'.$templateid.'/template';
        }
    }

    $file .= !empty($_G['inajax']) && ($file == 'common/header' || $file == 'common/footer') ? '_ajax' : '';
    $tpldir = $tpldir ? $tpldir : (defined('TPLDIR') ? TPLDIR : '');
    $templateid = $templateid ? $templateid : (defined('TEMPLATEID') ? TEMPLATEID : '');
    $filebak = $file;

    if(defined('IN_MOBILE') && !defined('TPL_DEFAULT') && strpos($file, $_G['mobiletpl'][IN_MOBILE].'/') === false || (isset($_G['forcemobilemessage']) && $_G['forcemobilemessage'])) {
        if(IN_MOBILE == 2) {
            $oldfile .= !empty($_G['inajax']) && ($oldfile == 'common/header' || $oldfile == 'common/footer') ? '_ajax' : '';
        }
        $file = $_G['mobiletpl'][IN_MOBILE].'/'.$oldfile;
    }

    if(!$tpldir) {
        $tpldir = './template/default';
    }
    $tplfile = $tpldir.'/'.$file.'.htm';

    $file == 'common/header' && defined('CURMODULE') && CURMODULE && $file = 'common/header_'.$_G['basescript'].'_'.CURMODULE;

    if(defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
        if(strpos($tpldir, 'plugin')) {
            if(!file_exists(DISCUZ_ROOT.$tpldir.'/'.$file.'.htm') && !file_exists(DISCUZ_ROOT.$tpldir.'/'.$file.'.php')) {
                $url = $_SERVER['REQUEST_URI'].(strexists($_SERVER['REQUEST_URI'], '?') ? '&' : '?').'mobile=no';
                showmessage('mobile_template_no_found', '', array('url' => $url));
            } else {
                $mobiletplfile = $tpldir.'/'.$file.'.htm';
            }
        }
        !$mobiletplfile && $mobiletplfile = $file.'.htm';
        if(strpos($tpldir, 'plugin') && (file_exists(DISCUZ_ROOT.$mobiletplfile) || file_exists(substr(DISCUZ_ROOT.$mobiletplfile, 0, -4).'.php'))) {
            $tplfile = $mobiletplfile;
        } elseif(!file_exists(DISCUZ_ROOT.TPLDIR.'/'.$mobiletplfile) && !file_exists(substr(DISCUZ_ROOT.TPLDIR.'/'.$mobiletplfile, 0, -4).'.php')) {
            $mobiletplfile = './template/default/'.$mobiletplfile;
            if(!file_exists(DISCUZ_ROOT.$mobiletplfile) && !$_G['forcemobilemessage']) {
                $tplfile = str_replace($_G['mobiletpl'][IN_MOBILE].'/', '', $tplfile);
                $file = str_replace($_G['mobiletpl'][IN_MOBILE].'/', '', $file);
                define('TPL_DEFAULT', true);
            } else {
                $tplfile = $mobiletplfile;
            }
        } else {
            $tplfile = TPLDIR.'/'.$mobiletplfile;
        }
    }

    $cachefile = './data/template/'.(defined('STYLEID') ? STYLEID.'_' : '_').$templateid.'_'.str_replace('/', '_', $file).'.tpl.php';
    if($templateid != 1 && !file_exists(DISCUZ_ROOT.$tplfile) && !file_exists(substr(DISCUZ_ROOT.$tplfile, 0, -4).'.php')
        && !file_exists(DISCUZ_ROOT.($tplfile = $tpldir.$filebak.'.htm'))) {
            $tplfile = './template/default/'.$filebak.'.htm';
        }

        if($gettplfile) {
            return $tplfile;
        }
        checktplrefresh($tplfile, $tplfile, @filemtime(DISCUZ_ROOT.$cachefile), $templateid, $cachefile, $tpldir, $file);
        return DISCUZ_ROOT.$cachefile;
}

function updatesession() {
    return C::app()->session->updatesession();
}

function userappprompt() {
    global $_G;

    if($_G['setting']['my_app_status'] && $_G['setting']['my_openappprompt'] && empty($_G['cookie']['userappprompt'])) {
        $sid = $_G['setting']['my_siteid'];
        $ts = $_G['timestamp'];
        $key = md5($sid.$ts.$_G['setting']['my_sitekey']);
        $uchId = $_G['uid'] ? $_G['uid'] : 0;
        echo '<script type="text/javascript" src="http://notice.uchome.manyou.com/notice/userNotice?sId='.$sid.'&ts='.$ts.'&key='.$key.'&uchId='.$uchId.'" charset="UTF-8"></script>';
    }
}

function widthauto() {
    global $_G;
    if($_G['disabledwidthauto']) {
        return 0;
    }
    if(!empty($_G['widthauto'])) {
        return $_G['widthauto'] > 0 ? 1 : 0;
    }
    if($_G['setting']['switchwidthauto'] && !empty($_G['cookie']['widthauto'])) {
        return $_G['cookie']['widthauto'] > 0 ? 1 : 0;
    } else {
        return $_G['setting']['allowwidthauto'] ? 0 : 1;
    }
}


?>