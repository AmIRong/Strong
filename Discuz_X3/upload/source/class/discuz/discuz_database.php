<?php



if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
class discuz_database {

	public static $db;

	public static $driver;


	public static function checkquery($sql) {
		return discuz_database_safecheck::checkquery($sql);
	}
	
	public static function table($table) {
	    return self::$db->table_name($table);
	}
	
	public static function fetch($resourceid, $type = MYSQL_ASSOC) {
	    return self::$db->fetch_array($resourceid, $type);
	}
	
	public static function fetch_all($sql, $arg = array(), $keyfield = '', $silent=false) {
	
	    $data = array();
	    $query = self::query($sql, $arg, $silent, false);
	    while ($row = self::$db->fetch_array($query)) {
	        if ($keyfield && isset($row[$keyfield])) {
	            $data[$row[$keyfield]] = $row;
	        } else {
	            $data[] = $row;
	        }
	    }
	    self::$db->free_result($query);
	    return $data;
	}
	
	public static function field($field, $val, $glue = '=') {
	
	    $field = self::quote_field($field);
	
	    if (is_array($val)) {
	        $glue = $glue == 'notin' ? 'notin' : 'in';
	    } elseif ($glue == 'in') {
	        $glue = '=';
	    }
	
	    switch ($glue) {
	        case '=':
	            return $field . $glue . self::quote($val);
	            break;
	        case '-':
	        case '+':
	            return $field . '=' . $field . $glue . self::quote((string) $val);
	            break;
	        case '|':
	        case '&':
	        case '^':
	            return $field . '=' . $field . $glue . self::quote($val);
	            break;
	        case '>':
	        case '<':
	        case '<>':
	        case '<=':
	        case '>=':
	            return $field . $glue . self::quote($val);
	            break;
	
	        case 'like':
	            return $field . ' LIKE(' . self::quote($val) . ')';
	            break;
	
	        case 'in':
	        case 'notin':
	            $val = $val ? implode(',', self::quote($val)) : '\'\'';
	            return $field . ($glue == 'notin' ? ' NOT' : '') . ' IN(' . $val . ')';
	            break;
	
	        default:
	            throw new DbException('Not allow this glue between field and value: "' . $glue . '"');
	    }
	}
	
	
	public static function init($driver, $config) {
	    self::$driver = $driver;
	    self::$db = new $driver;
	    self::$db->set_config($config);
	    self::$db->connect();
	}
	

	
	public static function query($sql, $arg = array(), $silent = false, $unbuffered = false) {
	    if (!empty($arg)) {
	        if (is_array($arg)) {
	            $sql = self::format($sql, $arg);
	        } elseif ($arg === 'SILENT') {
	            $silent = true;
	
	        } elseif ($arg === 'UNBUFFERED') {
	            $unbuffered = true;
	        }
	    }
	    self::checkquery($sql);
	
	    $ret = self::$db->query($sql, $silent, $unbuffered);
	    if (!$unbuffered && $ret) {
	        $cmd = trim(strtoupper(substr($sql, 0, strpos($sql, ' '))));
	        if ($cmd === 'SELECT') {
	
	        } elseif ($cmd === 'UPDATE' || $cmd === 'DELETE') {
	            $ret = self::$db->affected_rows();
	        } elseif ($cmd === 'INSERT') {
	            $ret = self::$db->insert_id();
	        }
	    }
	    return $ret;
	}
	
	public static function quote($str, $noarray = false) {
	
	    if (is_string($str))
	        return '\'' . mysql_escape_string($str) . '\'';
	
	        if (is_int($str) or is_float($str))
	            return '\'' . $str . '\'';
	
	            if (is_array($str)) {
	                if($noarray === false) {
	                    foreach ($str as &$v) {
	                        $v = self::quote($v, true);
	                    }
	                    return $str;
	                } else {
	                    return '\'\'';
	                }
	            }
	
	            if (is_bool($str))
	                return $str ? '1' : '0';
	
	                return '\'\'';
	}
	
	public static function quote_field($field) {
	    if (is_array($field)) {
	        foreach ($field as $k => $v) {
	            $field[$k] = self::quote_field($v);
	        }
	    } else {
	        if (strpos($field, '`') !== false)
	            $field = str_replace('`', '', $field);
	            $field = '`' . $field . '`';
	    }
	    return $field;
	}
	
}
	
class discuz_database_safecheck {

    protected static $checkcmd = array('SEL'=>1, 'UPD'=>1, 'INS'=>1, 'REP'=>1, 'DEL'=>1);
    protected static $config;

    public static function checkquery($sql) {
        if (self::$config === null) {
            self::$config = getglobal('config/security/querysafe');
        }
        if (self::$config['status']) {
            $check = 1;
            $cmd = strtoupper(substr(trim($sql), 0, 3));
            if(isset(self::$checkcmd[$cmd])) {
                $check = self::_do_query_safe($sql);
            } elseif(substr($cmd, 0, 2) === '/*') {
                $check = -1;
            }
    
            if ($check < 1) {
                throw new DbException('It is not safe to do this query', 0, $sql);
            }
        }
        return true;
    }
	
	private static function _do_query_safe($sql) {
	    $sql = str_replace(array('\\\\', '\\\'', '\\"', '\'\''), '', $sql);
	    $mark = $clean = '';
	    if (strpos($sql, '/') === false && strpos($sql, '#') === false && strpos($sql, '-- ') === false && strpos($sql, '@') === false && strpos($sql, '`') === false) {
	        $clean = preg_replace("/'(.+?)'/s", '', $sql);
	    } else {
	        $len = strlen($sql);
	        $mark = $clean = '';
	        for ($i = 0; $i < $len; $i++) {
	            $str = $sql[$i];
	            switch ($str) {
	                case '`':
	                    if(!$mark) {
	                        $mark = '`';
	                        $clean .= $str;
	                    } elseif ($mark == '`') {
	                        $mark = '';
	                    }
	                    break;
	                case '\'':
	                    if (!$mark) {
	                        $mark = '\'';
	                        $clean .= $str;
	                    } elseif ($mark == '\'') {
	                        $mark = '';
	                    }
	                    break;
	                case '/':
	                    if (empty($mark) && $sql[$i + 1] == '*') {
	                        $mark = '/*';
	                        $clean .= $mark;
	                        $i++;
	                    } elseif ($mark == '/*' && $sql[$i - 1] == '*') {
	                        $mark = '';
	                        $clean .= '*';
	                    }
	                    break;
	                case '#':
	                    if (empty($mark)) {
	                        $mark = $str;
	                        $clean .= $str;
	                    }
	                    break;
	                case "\n":
	                    if ($mark == '#' || $mark == '--') {
	                        $mark = '';
	                    }
	                    break;
	                case '-':
	                    if (empty($mark) && substr($sql, $i, 3) == '-- ') {
	                        $mark = '-- ';
	                        $clean .= $mark;
	                    }
	                    break;
	
	                default:
	
	                    break;
	            }
	            $clean .= $mark ? '' : $str;
	        }
	    }
	
	    if(strpos($clean, '@') !== false) {
	        return '-3';
	    }
	
	    $clean = preg_replace("/[^a-z0-9_\-\(\)#\*\/\"]+/is", "", strtolower($clean));
	
	    if (self::$config['afullnote']) {
	        $clean = str_replace('/**/', '', $clean);
	    }
	
	    if (is_array(self::$config['dfunction'])) {
	        foreach (self::$config['dfunction'] as $fun) {
	            if (strpos($clean, $fun . '(') !== false)
	                return '-1';
	        }
	    }
	
	    if (is_array(self::$config['daction'])) {
	        foreach (self::$config['daction'] as $action) {
	            if (strpos($clean, $action) !== false)
	                return '-3';
	        }
	    }
	
	    if (self::$config['dlikehex'] && strpos($clean, 'like0x')) {
	        return '-2';
	    }
	
	    if (is_array(self::$config['dnote'])) {
	        foreach (self::$config['dnote'] as $note) {
	            if (strpos($clean, $note) !== false)
	                return '-4';
	        }
	    }
	
	    return 1;
	}
}