<?php

/**
 * 尝试多次运行
 * $times 运行次数
 * $usleep_time 毫秒
 */
function call_retry($func, $times = 3, $usleep_time = 1000)
{
    $res = $func();
    if (is_array($res) && strtoupper($res['flag']) == 'OK') {
        return;
    }
    $times--;
    if ($times > 0) {
        usleep($usleep_time * 1000);
        call_retry($func, $times, $usleep_time);
    }
}
/**
 * 数组转tree
 *
 * 输入$list
 * [
 *   {id:1,pid:0,其他字段},
 *   {id:2,pid:1,其他字段},
 *   {id:3,pid:1,其他字段},
 * ]
 * 输出
 * [
 *   [
 *      id:1,
 *      pid:0,
 *      其他字段,
 *      children:[
 *           {id:2,pid:1,其他字段},
 *           {id:3,pid:1,其他字段},
 *      ]
 *   ]
 * ]
 *
 */
function array_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0, $my_id = '')
{
    $tree = array();
    if (is_array($list)) {
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[$data[$pk]] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    if ($my_id && $my_id == $list[$key]['id']) {
                    } else {
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
    }
    return $tree;
}

/**
 * 数组或字符输出，方便查看
 */
function pr($str)
{
    print_r("<pre>");
    print_r($str);
    print_r("</pre>");
}
/**
 * 添加动作 
 */
function add_action($name, $call, $level = 20)
{
    global $_app;
    if (strpos($name, '|') !== false) {
        $arr = explode('|', $name);
        foreach ($arr as $v) {
            add_action($v, $call, $level);
        }
        return;
    }
    $_app['actions'][$name][] = ['func' => $call, 'level' => $level];
}
/**
 * 执行动作 
 */
function do_action($name, &$par = null)
{
    global $_app;
    if (!is_array($_app)) {
        return;
    }
    $calls  = $_app['actions'][$name];
    $calls  = lib\Arr::order_by($calls, 'level', SORT_DESC);
    if ($calls) {
        foreach ($calls as $v) {
            $func = $v['func'];
            $func($par);
        }
    }
}



/**
 * 跳转
 *
 * @param string $url
 * @return void
 */
function jump($url)
{
    if (strpos($url, '://') === false && substr($url, 0, 1) != '/') {
        $url = '/' . $url;
    }
    header("Location: " . $url);
    exit;
}
/**
 * CDN地址
 */
function cdn()
{
    global $config;
    $host = $config['host'];
    $arr  = $config['cdn_url'] ?: [];
    $n    = count($arr);
    if ($n > 0) {
        $i    = mt_rand(0, $n - 1);
        return $arr[$i] ?: '/';
    } else {
        return $host;
    }
}
/**
 * json输出
 */
function json($data)
{
    global $config;
    $config['is_json'] = true;
    //JSON输出前
    do_action('json', $data);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
/**
 * 域名
 */
function host()
{
    global $config;
    static $_host;
    if ($_host) {
        return $_host;
    }
    $_host = $config['host'];
    return $_host;
}
/**
 * 判断是命令行下
 */
function is_cli()
{
    return PHP_SAPI == 'cli' ? true : false;
}
/**
 * 是否是POST请求
 */
function is_post()
{
    if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
        return true;
    }
}
/**
 * 判断是否为json
 */
function is_json($data, $assoc = false)
{
    $data = json_decode($data, $assoc);
    if ($data && (is_object($data)) || (is_array($data) && !empty(current($data)))) {
        return $data;
    }
    return false;
}



/**
 * 加载css
 */
function css($file, $is_output = true)
{
    global $_app;
    $key = md5($file);
    if (isset($_app['css'][$key])) {
        return;
    }
    if ($is_output) {
        echo '<link href="' . $file . '" rel="stylesheet">' . "\n";
        $_app['css'][$key] = true;
    } else {
        $_app['css'][$key] = $file;
    }
}
/**
 * 加载 JS文件
 */
function js($file, $is_output = true)
{
    global $_app;
    $key = md5($file);
    if (isset($_app['js'][$key])) {
        return;
    }
    if ($is_output) {
        echo '<script src="' . $file . '"></script>' . "\n";
        $_app['js'][$key] = true;
    } else {
        $_app['js'][md5($file)] = $file;
    }
}
/**
 * 数组转对象
 *
 * @param array $arr 数组
 * @return object
 */
function array_to_object($arr)
{
    if (gettype($arr) != 'array') {
        return;
    }
    foreach ($arr as $k => $v) {
        if (gettype($v) == 'array' || getType($v) == 'object') {
            $arr[$k] = (object) array_to_object($v);
        }
    }
    return (object) $arr;
}

/**
 * 对象转数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj)
{
    $obj = (array) $obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array) object_to_array($v);
        }
    }
    return $obj;
}
/**
 * 取目录名 
 */
function get_dir($name)
{
    return substr($name, 0, strrpos($name, '/'));
}
/**
 * 取后缀 
 */
function get_ext($name)
{
    if (strpos($name, '?') !== false) {
        $name = substr($name, 0, strpos($name, '?'));
    }
    $name =  substr($name, strrpos($name, '.'));
    return strtolower(substr($name, 1));
}
/**
 * 取文件名 
 */
function get_name($name)
{
    $name = substr($name, strrpos($name, '/'));
    $name = substr($name, 0, strrpos($name, '.'));
    $name = substr($name, 1);
    return $name;
}

/**
 * 创建目录
 */
function create_dir_if_not_exists($arr)
{
    if (is_string($arr)) {
        $v = $arr;
        if (!is_dir($v)) {
            mkdir($v, 0777, true);
        }
    } elseif (is_array($arr)) {
        foreach ($arr as $v) {
            if (!is_dir($v)) {
                mkdir($v, 0777, true);
            }
        }
    }
}
/**
 * 是否是本地环境 
 */
function is_local()
{
    return in_array(get_ip(), ['127.0.0.1', '::1']) ? true : false;
}
/**
 * 取IP
 */
function get_ip($type = 0, $adv = false)
{
    $type      = $type ? 1 : 0;
    static $ip = null;
    if (null !== $ip) {
        return $ip[$type];
    }

    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }

            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    if (!$ip) {
        return;
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}
/**
 * 当前时间
 */
function now()
{
    return date('Y-m-d H:i:s', time());
}
/**
 * 计算两点地理坐标之间的距离
 * @param  Decimal $longitude1 起点经度
 * @param  Decimal $latitude1  起点纬度
 * @param  Decimal $longitude2 终点经度
 * @param  Decimal $latitude2  终点纬度
 * @param  Int     $unit       单位 1:米 2:公里
 * @param  Int     $decimal    精度 保留小数位数
 * @return Decimal
 */
function get_distance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
{
    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $PI = 3.1415926;
    $radLat1 = $latitude1 * $PI / 180.0;
    $radLat2 = $latitude2 * $PI / 180.0;
    $radLng1 = $longitude1 * $PI / 180.0;
    $radLng2 = $longitude2 * $PI / 180.0;
    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;
    $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $distance = $distance * $EARTH_RADIUS * 1000;
    if ($unit == 2) {
        $distance = $distance / 1000;
    }
    return round($distance, $decimal);
}
/**
 * 判断是否是ssl
 */
function is_ssl()
{
    global $config;
    return  strpos($config['host'], 'https://') !== false ? true : false;
}

/**
 * 设置、获取cookie
 * cookie_local 时禁用SSL
 * @param string $name
 * @param string $value
 * @param integer $expire
 * @return void
 */
function cookie($name, $value = '', $expire = 0)
{
    global  $config;
    $name   = $config['cookie_prefix'] . $name;
    $path   = $config['cookie_path'] ?: '/';
    $domain = $config['cookie_domain'] ?: '';
    if ($value === null) {
        return cookie_delete($name);
    }
    if ($name && $value) {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $bool = is_ssl() ? true : false;
        $opt = [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $bool,
            'httponly' => $bool,
            'samesite' => 'None',
        ];
        if (!$bool) {
            unset($opt['secure'], $opt['httponly'], $opt['samesite']);
        }
        setcookie($name, $value, $opt);
        $_COOKIE[$name] = $value;
    } else {
        $value = $_COOKIE[$name];
        if (is_json($value)) {
            $value = json_decode($value, true);
        }
        return $value;
    }
}
/**
 * 删除COOKIE
 */
function cookie_delete($name)
{
    global  $config;
    $name   = $config['cookie_prefix'] . $name;
    $path   = $config['cookie_path'] ?: '/';
    $domain = $config['cookie_domain'] ?: '';
    $bool = is_ssl() ? true : false;
    $opt = [
        'expires' => time() - 100,
        'path'    => $path,
        'domain'  => $domain,
        'secure'  => $bool,
        'httponly' => $bool,
        'samesite' => 'None',
    ];
    if (!$bool) {
        unset($opt['secure'], $opt['httponly'], $opt['samesite']);
    }
    setcookie($name, '', $opt);
    $_COOKIE[$name] = '';
}


/**
 * 路径列表，支持文件夹下的子所有文件夹 
 */
function get_deep_dir($path)
{
    $arr = array();
    $arr[] = $path;
    if (is_file($path)) {
    } else {
        if (is_dir($path)) {
            $data = scandir($path);
            if (!empty($data)) {
                foreach ($data as $value) {
                    if ($value != '.' && $value != '..') {
                        $sub_path = $path . "/" . $value;
                        $temp = get_deep_dir($sub_path);
                        $arr  = array_merge($temp, $arr);
                    }
                }
            }
        }
    }
    return $arr;
}

/**
 * 显示2位小数 
 */
function price_format($yuan, $dot = 2)
{
    return bcmul($yuan, 1, $dot);
}

/**
 * 返回错误信息，JSON格式
 */
function json_error($arr = [])
{
    $arr['code'] = $arr['code'] ?: 250;
    $arr['type'] = $arr['type'] ?: 'error';
    return json($arr);
}
/**
 * 返回成功信息，JSON格式
 */
function json_success($arr = [])
{
    $arr['code'] = $arr['code'] ?: 0;
    $arr['type'] = $arr['type'] ?: 'success';
    return json($arr);
}
/**
 * yaml转数组
 */
function yaml_load($str)
{
    return Symfony\Component\Yaml\Yaml::parse($str);
}
/**
 * 数组转yaml
 */
function yaml_dump($array, $line = 3)
{
    return Symfony\Component\Yaml\Yaml::dump($array, $line);
}
/**
 * yaml转数组，数组转yaml格式
 */
function yaml($str)
{
    if (is_string($str)) {
        return yaml_load($str);
    } else {
        return yaml_dump($str);
    }
}

/**
 * 验证数据
 * https://github.com/vlucas/valitron
 *
 * 事例代码
 *
 * $data    = g();
 * $vali    = validate([
 *     'company_title'   => '客户名',
 *     'email'   => '邮件地址',
 *     'active_plugins'  => '系统',
 *     'exp_time' => '过期时间',
 * ],$data,[
 *     'required' => [
 *         ['company_title'],
 *         ['email'],
 *         ['active_plugins'],
 *         ['exp_time'],
 *     ],
 *     'email'=>[
 *         ['email']
 *     ]
 * ]);
 * if($vali){
 *     json($vali);
 * }

 * 更多规则

 * required - Field is required
 * requiredWith - Field is required if any other fields are present
 * requiredWithout - Field is required if any other fields are NOT present
 * equals - Field must match another field (email/password confirmation)
 * different - Field must be different than another field
 * accepted - Checkbox or Radio must be accepted (yes, on, 1, true)
 * numeric - Must be numeric
 * integer - Must be integer number
 * boolean - Must be boolean
 * array - Must be array
 * length - String must be certain length
 * lengthBetween - String must be between given lengths
 * lengthMin - String must be greater than given length
 * lengthMax - String must be less than given length
 * min - Minimum
 * max - Maximum
 * listContains - Performs in_array check on given array values (the other way round than in)
 * in - Performs in_array check on given array values
 * notIn - Negation of in rule (not in array of values)
 * ip - Valid IP address
 * ipv4 - Valid IP v4 address
 * ipv6 - Valid IP v6 address
 * email - Valid email address
 * emailDNS - Valid email address with active DNS record
 * url - Valid URL
 * urlActive - Valid URL with active DNS record
 * alpha - Alphabetic characters only
 * alphaNum - Alphabetic and numeric characters only
 * ascii - ASCII characters only
 * slug - URL slug characters (a-z, 0-9, -, _)
 * regex - Field matches given regex pattern
 * date - Field is a valid date
 * dateFormat - Field is a valid date in the given format
 * dateBefore - Field is a valid date and is before the given date
 * dateAfter - Field is a valid date and is after the given date
 * contains - Field is a string and contains the given string
 * subset - Field is an array or a scalar and all elements are contained in the given array
 * containsUnique - Field is an array and contains unique values
 * creditCard - Field is a valid credit card number
 * instanceOf - Field contains an instance of the given class
 * optional - Value does not need to be included in data array. If it is however, it must pass validation.
 * arrayHasKeys - Field is an array and contains all specified keys.
 */
function validate($labels, $data, $rules, $show_array = false)
{
    $v = new \lib\Validate($data);
    $v->rules($rules);
    $v->labels($labels);
    $v->validate();
    $error = $v->errors();
    if ($error) {
        if (!$show_array) {
            foreach ($error as $k => $v) {
                $error = $v[0];
                break;
            }
        }
        return ['code' => 250, 'msg' => $error, 'type' => 'error', 'key' => $k];
    } else {
        return;
    }
}
/**
 * 取文件信息
 */
function get_file($id)
{
    static $obj;
    $key = $id;
    if (is_array($id)) {
        $key = md5(json_encode($id));
    }
    $data = $obj[$key];
    if ($data) {
        return $data;
    }
    $f = db_get("upload", "*", ['id' => $id]);
    $obj[$key] = $f;
    return $f;
}
/**
 * 设置配置
 */
function set_config($title, $body)
{
    if (in_array($title, [
        '_timestamp',
        '_signature',
    ])) {
        return;
    }
    $title = strtolower($title);
    $one = db_get_one("config", "*", ['title' => $title]);
    if (!$one) {
        db_insert("config", ['title' => $title, 'body' => $body]);
    } else {
        db_update("config", ['body' => $body], ['id' => $one['id']]);
    }
}
/**
 * 优先取数据库，未找到后取配置文件
 */
function get_config($title)
{
    global $config;
    if (is_array($title)) {
        $list = [];
        $in = [];
        foreach ($title as $kk) {
            $in[] = strtolower($kk);
        }
        $all  = db_get("config", "*", ['title' => $in]);
        foreach ($all as $one) {
            $body = $one['body'];
            $key  = $one['title'];
            $list[$key] = $body ?: $config[$key];
        }
        return $list;
    } else {
        $title = strtolower($title);
        $one   = db_get_one("config", "*", ['title' => $title]);
        $body = $one['body'];
        if (!$body) {
            return $config[$title];
        }
        return $body;
    }
}
/**
 * 每页显示多少条记录
 */
function page_size($name)
{
    $key  = 'page_size_' . $name;
    $time = time() + 86400 * 365 * 10;
    $size = cookie($key);
    if (get_post('page_size')) {
        $size = (int)get_post('page_size');
        cookie($key, $size, $time);
        $size = cookie($key);
    }
    return $size ?: 20;
}

/**
 * AES加密
 */
function aes_encode($data, $key = '', $iv = '', $type = 'AES-128-CBC', $options = '')
{
    if (is_array($data)) {
        $data = json_encode($data);
    }
    $obj = new \lib\Aes($key, $iv, $type, $options);
    return base64_encode($obj->encrypt($data));
}
/**
 * AES解密
 */
function aes_decode($data, $key = '', $iv = '', $type = 'AES-128-CBC', $options = '')
{
    $data = base64_decode($data);
    $obj = new \lib\Aes($key, $iv, $type, $options);
    $data = $obj->decrypt($data);
    if (is_json($data)) {
        return json_decode($data, true);
    } else {
        return $data;
    }
}

/**
 * 多语言
 */
function lang($name, $val = [], $pre = 'app')
{
    return lib\Lang::trans($name, $val, $pre);
}

/**
 * 搜索替换\n , ，空格
 * @param string $name
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 * @return array
 */
function string_to_array($name, $array = '')
{
    if (!$name) {
        return [];
    }
    $array = $array ?: [
        "\n",
        "，",
        "、",
        "|",
        ",",
        " ",
        chr(10),
    ];
    foreach ($array as $str) {
        if (strpos($name, $str) !== false) {
            $name = str_replace($str, ',', $name);
        }
    }
    if (strpos($name, ",") !== false) {
        $arr = explode(",", $name);
    }
    if ($arr) {
        $arr = array_filter($arr);
        foreach ($arr as $k => $v) {
            if (!is_array($v)) {
                $arr[$k] = trim($v);
            } else {
                $arr[$k] = $v;
            }
        }
    } else {
        $arr = [trim($name)];
    }
    return $arr;
}


/**
 * 返回两个时间点间的日期数组
 *
 * @param string $start 时间格式 Y-m-d
 * @param string $end   时间格式 Y-m-d
 * @return void
 */
function get_dates($start, $end)
{
    $dt_start = strtotime($start);
    $dt_end   = strtotime($end);
    while ($dt_start <= $dt_end) {
        $list[] = date('Y-m-d', $dt_start);
        $dt_start = strtotime('+1 day', $dt_start);
    }
    return $list;
}
/**
 * 当前时间是周几
 */
function get_date_china($date)
{
    $weekarray = array("日", "一", "二", "三", "四", "五", "六");
    return $weekarray[date("w", strtotime($date))];
}


/**
 * 多少时间之前
 */
function timeago($time)
{
    if (strpos($time, '-') !== false) {
        $time = strtotime($time);
    }
    $rtime = date("m-d H:i", $time);
    $top   = date("Y-m-d H:i", $time);
    $htime = date("H:i", $time);
    $time  = time() - $time;
    if ($time < 60) {
        $str = '刚刚';
    } elseif ($time < 60 * 60) {
        $min = floor($time / 60);
        $str = $min . '分钟前';
    } elseif ($time < 60 * 60 * 24) {
        $h   = floor($time / (60 * 60));
        $str = $h . '小时前 ' . $htime;
    } elseif ($time < 60 * 60 * 24 * 3) {
        $d = floor($time / (60 * 60 * 24));
        if ($d == 1) {
            $str = '昨天 ' . $rtime;
        } else {
            $str = '前天 ' . $rtime;
        }
    } else {
        $str = $top;
    }
    return $str;
}

/**
 * 请求是否是AJAX
 */
function is_ajax()
{
    if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
        return true;
    } else {
        return false;
    }
}

/**
 * 防止重复执行
 * @param $argv 命令行数组
 * @param $find 查找的命令
 */
function run_cmd_unique($argv, $find = 'php cmd.php')
{
    $cmd_line = "php";
    $str = '';
    foreach ($argv as $v) {
        $str .= " " . $v;
    }
    $cmd_line = $cmd_line . $str;
    exec("ps aux|grep '" . $cmd_line . "'", $arr);
    $list = [];
    foreach ($arr as $v) {
        if ($v) {
            $v = str_replace('  ', '', $v);
            preg_match('(' . $find . '.*)', $v, $output);
            $new = $output[0];
            if ($new) {
                $list[] = trim($new);
            }
        }
    }
    $new_list = [];
    foreach ($list as $v => $k) {
        if (!$new_list[$k]) {
            $new_list[$k] = 1;
        } else {
            $new_list[$k]++;
        }
    }
    if ($new_list && $new_list[$cmd_line] > 2) {
        echo "程序已在运行，不能重复执行！\n";
        exit();
    }
}

/**
 * 包含文件
 */
function import($file, $vars = [], $check_vars = false)
{
    static $obj;
    $key = md5(str_replace('\\', '/', $file));
    if ($vars && $check_vars) {
        $md5 = md5(json_encode($vars));
        $key = $key . $md5;
    }
    if ($vars) {
        extract($vars);
    }
    if (!isset($obj[$key])) {
        if (file_exists($file)) {
            include $file;
            $obj[$key] = true;
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}

/**
 * 取reffer
 */
function get_reffer($refer = '')
{
    $refer = $refer ?: $_SERVER['HTTP_REFERER'];
    $refer = str_replace("http://", '', $refer);
    $refer = str_replace("https://", '', $refer);
    return $refer;
}
/**
 * 取主域名，如 admin.baidu.com返回baidu.com
 */
function get_root_domain($host = '')
{
    $host = $host ?: host();
    preg_match("#\.(.*)#i", $host, $match);
    $host = $match[1];
    return str_replace("/", '', $host);
}
/**
 * 取子域名，如admin.baidu.com返回admin
 */
function get_sub_domain($host = '')
{
    $host = $host ?: host();
    preg_match("#(http://|https://)(.*?)\.#i", $host, $match);
    $host = $match[2];
    return str_replace("/", '', $host);
}

/**
 * 格式化金额
 */
function format_money($money, $len = 2, $sign = '￥')
{
    $negative = $money >= 0 ? '' : '-';
    $int_money = intval(abs($money));
    $len = intval(abs($len));
    $decimal = ''; //小数
    if ($len > 0) {
        $decimal = '.' . substr(sprintf('%01.' . $len . 'f', $money), -$len);
    }
    $tmp_money = strrev($int_money);
    $strlen = strlen($tmp_money);
    $format_money = '';
    for ($i = 3; $i < $strlen; $i += 3) {
        $format_money .= substr($tmp_money, 0, 3) . ',';
        $tmp_money = substr($tmp_money, 3);
    }
    $format_money .= $tmp_money;
    $format_money = strrev($format_money);
    return $sign . $negative . $format_money . $decimal;
}

/**
 * 生成签名
 * 签名生成的通用步骤如下：
 * 第一步：将参与签名的参数按照键值(key)进行字典排序
 * 第二步：将排序过后的参数，进行key和value字符串拼接
 * 第三步：将拼接后的字符串首尾加上app_secret秘钥，合成签名字符串
 * 第四步：对签名字符串进行MD5加密，生成32位的字符串
 * 第五步：将签名生成的32位字符串转换为大写
 */
function create_sign($params, $secret = '', $array_encode = false)
{
    if (!$secret) {
        $secret = get_config('sign_secret') ?: '123456789';
    }
    $str = '';
    //将参与签名的参数按照键值(key)进行字典排序
    ksort($params);
    foreach ($params as $k => $v) {
        //将排序过后的参数，进行key和value字符串拼接
        if (is_array($v) && $array_encode) {
            $v = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $str .= "$k=$v";
    }
    //将拼接后的字符串首尾加上app_secret秘钥，合成签名字符串
    $str .= $secret;
    //对签名字符串进行MD5加密，生成32位的字符串
    $str = md5($str);
    //将签名生成的32位字符串转换为大写
    return strtoupper($str);
}
/**
 * 多语言
 */
function set_lang($lang = 'zh-cn')
{
    lib\Lang::set($lang);
    lib\Validate::lang($lang);
}

/**
 * 生成URL
 */
function create_url($url)
{
    $host = host();
    if (substr($url, 0, 1) == '/') {
        $url = substr($url, 1);
    }
    return $host . $url;
}

/***
 * 页面BLOCK实现
 */
global $_core_block;
global $_core_block_name;
/**
 * 清空BLOCK
 */
function block_clean()
{
    global $_core_block;
    $_core_block = [];
}
/**
 * BLOCK开始
 */
function block_start($name)
{
    global $_core_block;
    global $_core_block_name;
    ob_start();
    $_core_block_name = $name;
}
/**
 * BLOCK结束
 */
function block_end($is_muit = false)
{
    global $_core_block;
    global $_core_block_name;
    $content = ob_get_contents();
    ob_end_clean();
    if ($is_muit) {
        return $_core_block[$_core_block_name][] = $content;
    } else {
        return $_core_block[$_core_block_name] = $content;
    }
}
/**
 * 获取BLOCK
 */
function get_block($name = '')
{
    global $_core_block;
    if ($name) {
        return $_core_block[$name];
    } else {
        unset($_core_block['js']);
        unset($_core_block['css']);
        return $_core_block;
    }
}
/**
 * 所本地文件解压到指定目录
 */
function zip_extract($local_file, $extract_local_dir)
{
    if (strpos($local_file, '/uploads/') !== false && strpos($local_file, '://') !== false) {
        $local_file = PATH . substr($local_file, strpos($local_file, '/uploads/') + 1);
    }
    if (!file_exists($local_file)) {
        return false;
    }
    $zippy = Alchemy\Zippy\Zippy::load();
    $archive = $zippy->open($local_file);
    if (!is_dir($extract_local_dir)) {
        create_dir_if_not_exists([$extract_local_dir]);
    }
    $archive->extract($extract_local_dir);
}
/**
 * 生成ZIP
 * @param $local_zip_file 本地zip文件
 * @param $files 包含的文件
 */
function zip_create($local_zip_file, $files = [])
{
    $dir = get_dir($local_zip_file);
    if (!is_dir($dir)) {
        create_dir_if_not_exists([$dir]);
    }
    $zippy = Alchemy\Zippy\Zippy::load();
    $archive = $zippy->create($local_zip_file, $files, true);
    return str_replace(PATH, '', $local_zip_file);
}

/**
 * 获取本地include文件内容
 */
function get_include_content($local_file)
{
    if (!file_exists($local_file)) {
        return;
    }
    ob_start();
    include $local_file;
    $data = ob_get_contents();
    ob_end_clean();
    return $data;
}
/**
 * 避免重复调用
 * get_ins('key',function(){
 *    my_function();
 * });
 */
function get_ins($key, $call)
{
    global $_ins;
    $key = "ins_function_" . $key;
    if ($_ins[$key]) {
        return;
    } else {
        $_ins[$key] = 1;
        $call();
    }
}

/**
 * 判断是JSON请求
 */
function is_json_request()
{
    if (
        is_ajax() || $_SERVER['HTTP_CONTENT_TYPE'] == 'application/json'
        || $_SERVER['CONTENT_TYPE'] == 'application/json'
    ) {
        return true;
    } else {
        return false;
    }
}
/**
 * 输出HTML错误页面
 */
function html_error($all)
{
    if (is_array($all)) {
        $html = '<div class="alert alert-danger" role="alert">';
        foreach ($all as $k => $v) {
            $html .= " <p>" . $k . $v . "</p>";
        }
        $html .= '</div>';
    } elseif (is_string($all)) {
        $html = '<div class="alert alert-danger" role="alert">';
        $html .= " <p>" . $all . "</p>";
        $html .= '</div>';
    }
    if (is_json_request()) {
    } else {
        return $html;
    }
}
/**
 * 数组转el-select
 */
function array_to_el_select($all, $v, $k)
{
    $list = [];
    foreach ($all as $vv) {
        $list[] = ['label' => $vv[$k], 'value' => $vv[$v]];
    }
    return $list;
}
/**
 * 传入带http的URL返回 uploads/...这种类型的URL
 */
function get_upload_url($f)
{
    if (strpos($f, '://') !== false) {
        $f = substr($f, strpos($f, '://') + 3);
        $f = substr($f, strpos($f, '/') + 1);
        return $f;
    } elseif (substr($f, 0, 1) == '/') {
        return $f;
    } else {
        return $f;
    }
}

/**
 * 页面渲染底部
 */
add_action("view.end", function () {
    render_css_file();
    render_css();
    render_js_file();
    render_js();
});

/**
 * 添加JS
 */
function add_js($code)
{
    global $_app;
    $_app['js_code'][] = $code;
}
/**
 * 输出JS
 */
function render_js()
{
    global $_app;
    $js = get_block('js');
    $all = $_app['js_code'] ?: [];
    if ($js) {
        $all = array_merge($all, $js);
    }
    if ($all) {
        echo '<script type="text/javascript">
        $(function(){';
        foreach ($all as $v) {
            echo $v . "\n";
        }
        echo '});
        </script>';
    }
}
/**
 * 输出JS文件
 */
function render_js_file()
{
    global $_app;
    $all = $_app['js'] ?: [];
    if ($all) {
        foreach ($all as $v) {
            if (is_string($v)) {
                echo '<script type="text/javascript" src="' . $v . '"></script>';
            }
        }
    }
}
/**
 * 添加JS
 */
function add_css($code)
{
    global $_app;
    $_app['css_code'][] = $code;
}
/**
 * 输出JS
 */
function render_css()
{
    global $_app;
    $js = get_block('css');
    $all = $_app['css_code'] ?: [];
    if ($js) {
        $all = array_merge($all, $js);
    }
    if ($all) {
        echo '<style type="text/css">';
        foreach ($all as $v) {
            echo $v . "\n";
        }
        echo '</style>';
    }
}
/**
 * 输出css文件
 */
function render_css_file()
{
    global $_app;
    $all = $_app['css'] ?: [];
    if ($all) {
        foreach ($all as $v) {
            if (is_string($v)) {
                echo '<link href="' . $v . '" rel="stylesheet">';
            }
        }
    }
}
/**
 * 生成图表
 * https://echarts.apache.org/handbook/zh/how-to/chart-types/line/area-line
 *
 * echats(['id'=>'main1','width'=>600,'height'=>400],[
 *   'title'=>[
 *       'text'=>'ECharts 入门示例'
 *   ],
 *   'yAxis'=>"js:{}",
 *   'legend'=>[
 *       'data'=>['销量']
 *   ],
 *   'xAxis'=>[
 *       'data'=>['衬衫', '羊毛衫', '雪纺衫', '裤子', '高跟鞋', '袜子']
 *   ],
 *   'series'=>[
 *       [
 *           'name'=>'销量',
 *           'type'=>'bar',
 *           'data'=>[5, 20, 36, 10, 10, 20]
 *       ]
 *   ]
 * ]);
 */
function echats($ele, $options = [])
{
    global $vue;
    global $vue_echats;
    $ele_id = $ele['id'];
    $width  = $ele['width'];
    $height = $ele['height'];
    $class  = $ele['class'];
    $let = 'let ';
    if ($vue) {
        $let = '';
        $vue->data("echart_" . $ele_id, '');
        $top = "app.";
    }
    $echats = $let . $top . "echart_" . $ele_id . " = echarts.init(document.getElementById('" . $ele_id . "'));\n
    " . $top . "echart_" . $ele_id . ".setOption(" . php_to_js($options) . ");";
    $out['js'] = $echats;
    $out['html'] = '<div id="' . $ele_id . '" class="' . $class . '" style="width: ' . $width . 'px;height:' . $height . 'px;"></div>' . "\n";
    if ($vue) {
        $vue_echats["echart_" . $ele_id] = $options;
    }
    return $out;
}
function echats_reload()
{
    global $vue_echats;
    if ($vue_echats) {
        $js = '';
        foreach ($vue_echats as $k => $v) {
            $js .= "app." . $k . ".clear();\n";
            $js .= "app." . $k . ".setOption(" . php_to_js($v) . ");\n";
        }
        return $js;
    }
}
function echarts($ele, $options = [])
{
    return echats($ele, $options);
}
function echarts_reload()
{
    echats_reload();
}
