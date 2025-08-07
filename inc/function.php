<?php

/**
 * 常用函数
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

/**
 * redis
 */
function predis()
{
    global $config;
    $redis_config = $config['redis'];
    $host = $redis_config['host'];
    $port = $redis_config['port'];
    $auth = $redis_config['auth'];
    static $redis;
    if ($redis) {
        return $redis;
    }
    $redis = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => $host,
        'port'   => $port,
        'password' => $auth,
    ]);
    return $redis;
}
/**
 * 添加位置信息
 * predis_add_geo('places',[
 *     [
 *         'lng'=>'116.397128',
 *         'lat'=>'39.916527',
 *         'title'=>'北京天安门'
 *     ],
 * ]);
 */
function predis_add_geo($key, $arr = [])
{
    $redis = predis();
    $redis->multi();
    foreach ($arr as $v) {
        if ($key && $v['lat'] && $v['lng'] && $v['title']) {
            $redis->geoadd($key, $v['lng'], $v['lat'], $v['title']);
        }
    }
    $redis->exec();
}
/**
 * 删除位置信息
 *
 * predis_delete_geo('places',[
 *   '北京天安门',
 * ]);
 */
function predis_delete_geo($key, $arr = [])
{
    $redis = predis();
    $redis->multi();
    foreach ($arr as $v) {
        if ($key && $v) {
            $redis->zrem($key, $v);
        }
    }
    $redis->exec();
}
/**
 * 返回附近的地理位置
 * pr(predis_get_pager('places', 116.403958, 39.915049));
 * http://redisdoc.com/geo/georadius.html
 */
function predis_get_pager($key, $lat, $lng, $juli = 2, $sort = 'ASC', $to_fixed = 2)
{
    $redis = predis();
    $arr = $redis->georadius($key, $lat, $lng, $juli, 'km', [
        'withdist' => true,
        'sort' => $sort,
    ]);
    $list =  array_to_pager($arr);
    $new_list = [];
    foreach ($list['data'] as $v) {
        $new_list[$v[0]] = bcmul($v[1], 1, $to_fixed);
    }
    $list['data'] = $new_list;
    return $list;
}
/**
 * 分组分页
 */
function array_to_pager($arr)
{
    $page = g('page') ?: 1;
    $per_page = g('per_page') ?: 20;
    $total = count($arr);
    $last_page = ceil($total / $per_page);
    if ($page > $last_page) {
        $page = $last_page;
    }
    $arr   = array_slice($arr, ($page - 1) * $per_page, $per_page);
    $list  = [
        'current_page' => $page,
        'data' => $arr,
        'last_page' => $last_page,
        'per_page' => $per_page,
        'total' => $total,
        'total_cur' => count($arr),
    ];
    return $list;
}
/**
 * 下载文件
 * 建议使用 download_file_safe
 */
function _download_file($url, $contain_http = false)
{
    $host = cdn();
    if (strpos($url, "://") !== false) {
        global $is_local;
        if ($is_local) {
            if (strpos($url, get_root_domain(host())) !== false) {
                return remove_host($url);
            }
        }
        $url = download_remote_file($url);
        if ($contain_http) {
            return $url;
        }
        $url = str_replace($host, '', $url);
    } elseif (strpos($url, WWW_PATH) !== false) {
        $url = str_replace(WWW_PATH, '', $url);
    }
    if ($contain_http) {
        return $host . $url;
    } else {
        return $url;
    }
}
/**
 * 下载资源文件到本地
 */
function download_file($url, $mimes = ['image/*', 'video/*'], $cons = [], $contain_http = false)
{
    $flag = false;
    if ($cons) {
        foreach ($cons as $v) {
            if (strpos($url, $v) !== false) {
                $flag = true;
                break;
            }
        }
    } else {
        $flag = true;
    }
    if ($flag) {
        $content_type = get_mime($url);
        foreach ($mimes as $v) {
            $v = str_replace("*", "", $v);
            if (strpos($content_type, $v) !== 'false') {
                $new_url = _download_file($url, $contain_http);
                if ($new_url) {
                    return $new_url;
                }
            }
        }
    }
}
/**
 * 取后缀
 * add_action("get_ext_by_url",function(&$data){
 *    $url = $data['url'];
 *    $data['ext'] = 'pdf';
 * });
 */
function get_ext_by_url($url)
{
    $data['url'] = $url;
    $data['ext'] = '';
    do_action("get_ext_by_url", $data);
    if ($data['ext']) {
        return $data['ext'];
    }
    $mime = lib\Mime::load();
    $type = get_mime($url);
    if ($type) {
        foreach ($mime as $k => $v) {
            if (is_array($v)) {
                if (in_array($type, $v)) {
                    $find = $k;
                    break;
                }
            } else {
                if ($v == $type) {
                    $find = $k;
                    break;
                }
            }
        }
    }
    return $find ?: get_ext($url);
}
/**
 * 在线查看office文件
 */
function online_view_office($url)
{
    $url = str_replace("https://", "", $url);
    $url = str_replace("http://", "", $url);
    $url = urlencode($url);
    return "https://view.officeapps.live.com/op/view.aspx?src=" . $url;
}
/**
 * float不进位，如3.145 返回3.14
 * 进位的有默认round(3.145) 或sprintf("%.2f",3.145);
 */
function float_noup($float_number, $dot = 2)
{
    $p = pow(10, $dot);
    return floor($float_number * $p) / $p;
}
/**
 * 四舍五入
 * @param $mid_val 逢几进位
 */
function float_up($float_number, $dot = 2, $mid_val = 5)
{
    $p = pow(10, $dot);
    if (strpos($float_number, '.') !== false) {
        $a = substr($float_number, strpos($float_number, '.') + 1);
        $a = substr($a, $dot, 1) ?: 0;
        if ($a >= $mid_val) {
            return bcdiv(bcmul($float_number, $p) + 1, $p, $dot);
        } else {
            return bcdiv(bcmul($float_number, $p), $p, $dot);
        }
    }
    $p = pow(10, $dot);
    return floor($float_number * $p) / $p;
}
/**
 * 加载xlsx
 * load_xls([
 *   'file'  => $xls,
 *   'config'=>[
 *       '序号'  =>'index',
 *   ],
 *   'title_line'=>1,
 *   'call'=>function($i,$row,&$d){}
 * ]);
 */
function load_xls($new_arr = [])
{
    $xls_file   = $new_arr['file'];
    $config     = $new_arr['config'];
    $title_line = $new_arr['title_line'] ?: 1;
    $call       = $new_arr['call'];
    $is_full    = $new_arr['is_full'] ?: false;
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($xls_file);
    $worksheet = $spreadsheet->getActiveSheet();
    //总行数
    $rows      = $worksheet->getHighestRow();
    //总列数 A-F
    $columns   = $worksheet->getHighestColumn();
    $index     = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columns);
    $lists = [];
    for ($row = 1; $row <= $rows; $row++) {
        $list = [];
        for ($i = 1; $i <= $index; $i++) {
            $d = $worksheet->getCellByColumnAndRow($i, $row)->getValue();
            if (is_object($d)) {
                $d = $d->__toString();
            }
            if ($call) {
                $call($i, $row, $d);
            }
            $list[] = $d;
        }
        $lists[] = $list;
    }
    $top    = $title_line - 1;
    $titles = $lists[$top];
    $titles = array_flip($titles);
    $new_lists = [];
    foreach ($lists as $i => $v) {
        if ($i > $top) {
            if ($config) {
                $new_list = [];
                foreach ($config as $kk => $vv) {
                    $j = $titles[$kk];
                    $new_list[$vv] = $v[$j];
                }
                $new_lists[] = $new_list;
            }
        }
    }
    if ($new_lists) {
        $lists = $new_lists;
    }
    $ret =  [
        'data'    => $lists,
        //总行数
        'total_r' => $rows,
        //总列数
        'total_c' => $index,
    ];
    if ($is_full) {
        return $ret;
    } else {
        return $ret['data'];
    }
}
/**
 * GBK字符截取
 * 一个中文算2个字符
 */
function gbk_substr($text, $start, $len, $gbk = 'GBK')
{
    $str = mb_strcut(mb_convert_encoding($text, $gbk, "UTF-8"), $start, $len, $gbk);
    $str = mb_convert_encoding($str, "UTF-8", $gbk);
    return $str;
}
/**
 * GBK长宽
 * 2个字符
 */
function get_gbk_len($value, $gbk = 'GBK')
{
    return strlen(iconv("UTF-8", $gbk . "//IGNORE", $value));
}
/**
 * 文字居中
 */
function get_text_center(string $str, int $len)
{
    $cur_len = get_gbk_len($str);
    $less    = $len - $cur_len;
    $s = (int)($less / 2);
    $e = $less - $s;
    $append = '';
    $end    = '';
    for ($i = 0; $i < $s; $i++) {
        $append .= " ";
    }
    for ($i = 0; $i < $e; $i++) {
        $end .= " ";
    }
    return $append . $str . $end;
}
/**
 * 文字排版
 * 左 中 右
 * 左    右
 */
function get_text_left_right(array $arr, int $length, $return_arr = false)

{
    $count  = count($arr);
    $middle = (int)(bcdiv($length, $count));
    $j = 1;
    foreach ($arr as &$v) {
        $cur_len = get_gbk_len($v);
        $less    = $middle - $cur_len;
        $append  = "";
        if ($less > 0) {
            for ($i = 0; $i < $less; $i++) {
                $append .= " ";
            }
            if ($j == $count) {
                $v = $append . $v;
            } else {
                $v = $v . $append;
            }
        } else {
            $v = gbk_substr($v, 0, $middle);
        }
        $j++;
    }
    if ($return_arr) {
        return $return_arr;
    } else {
        return implode("", $arr);
    }
}
/**
 *  处理跨域
 */
function allow_cross_origin()
{
    global $config;
    $cross_origin = $config['cross_origin'] ?: '*';
    header('Access-Control-Allow-Origin: ' . $cross_origin);
    header('Access-Control-Allow-Credentials:true');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
    header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
    header('X-Powered-By: WAF/2.0');
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit;
    }
}
/**
 * 字符或数组 转UTF-8
 */
function to_utf8($str)
{
    if (!$str || (!is_array($str) && !is_string($str))) {
        return $str;
    }
    if (is_array($str)) {
        $list = [];
        foreach ($str as $k => $v) {
            $list[$k] = to_utf8($v);
        }
        return $list;
    } else {
        $encoding = mb_detect_encoding($str, "UTF-8, GBK, ISO-8859-1");
        if ($encoding && $encoding != 'UTF-8') {
            $str = iconv($encoding, "UTF-8//IGNORE", $str);
            $str = trim($str);
        }
        return $str;
    }
}
/**
 * 读取CSV
 */
function csv_reader($file)
{
    return lib\Csv::reader($file);
}
/**
 * 写入CSV
 */
function csv_writer($file, $header = [], $content = [])
{
    return lib\Csv::writer($file, $header, $content);
}
/**
 * 基于redis锁
 *
 * global $redis_lock;
 * //锁前缀
 * global $lock_key;
 *
 * $redis_lock = [
 *    'host'=>'',
 *    'port'=>'',
 *    'auth'=>'',
 * ];
 *
 * lock_call('k',functon(){},1);
 * 或
 * if(lock_start('k')){
 *    ..
 *    lock_end();
 * }
 */
function lock_call($key, $call, $time = 10)
{
    global $lock_key;
    $key = $lock_key . $key;
    return lib\Lock::do($key, $call, $time);
}
/**
 * 开始锁
 */
function lock_start($key, $time = 1)
{
    global $lock_key;
    $key = $lock_key . $key;
    return lib\Lock::start($key, $time);
}
/**
 * 释放锁
 */
function lock_end()
{
    return lib\Lock::end();
}

/**
 * 下载远程文件
 * global $remote_to_local_path;
 * $remote_to_local_path = '/uploads/saved/'.date("Y-m-d");
 */
function download_remote_file($url, $path = '')
{
    global $remote_to_local_path;
    $remote_to_local_path = $remote_to_local_path ?: '/uploads/tmp/' . date("Y-m-d");
    $local_url = $remote_to_local_path . '/' . md5($url) . '.' . get_ext($url);
    $file = WWW_PATH . $local_url;
    if (!file_exists($file) || (file_exists($file) && filesize($file) < 10)) {
        $context = curl_get($url);
        $mime = get_mime($url);
        $arr = ['mime' => $mime, 'url' => $url];
        do_action("download", $arr);
        $dir = get_dir($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, $context);
    }
    return cdn() . $local_url;
}
/**
 * 调用阿里云
 */
function curl_aliyun($url, $bodys = '', $method = 'POST')
{
    $curl = curl_init();
    $appcode = get_config('aliyun_m_code');
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . trim($appcode));
    array_push($headers, "Content-Type" . ":" . "application/json; charset=UTF-8");
    $querys = "";
    if ($bodys) {
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        } else {
            if (is_array($bodys)) {
                $str = '';
                foreach ($bodys as $k => $v) {
                    $str .= $k . '=' . $v . "&";
                }
                $str = substr($str, 0, -1);
                if (strpos($url, '?') === false) {
                    $url = $url . '?' . $str;
                } else {
                    $url = $url . "&" . $str;
                }
            } else {
                if (strpos($url, '?') === false) {
                    $url = $url . '?' . $bodys;
                } else {
                    $url = $url . "&" . $bodys;
                }
            }
        }
    }
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    if (1 == strpos("$" . $url, "https://")) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }

    $out_put = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    list($header, $body) = explode("\r\n\r\n", $out_put, 2);
    if ($http_code == 200) {
        if (is_json($body)) {
            $body = json_decode($body, true);
            $body['code'] = 0;
        }
        return $body;
    } else {
        if ($http_code == 400 && strpos($header, "Invalid Param Location") !== false) {
            return ['msg' => "参数错误", 'code' => 250];
        } elseif ($http_code == 400 && strpos($header, "Invalid AppCode") !== false) {
            return ['msg' => "AppCode错误", 'code' => 250];
        } elseif ($http_code == 400 && strpos($header, "Invalid Url") !== false) {
            return ['msg' => "请求的 Method、Path 或者环境错误", 'code' => 250];
        } elseif ($http_code == 403 && strpos($header, "Unauthorized") !== false) {
            return ['msg' => "服务未被授权（或URL和Path不正确）", 'code' => 250];
        } elseif ($http_code == 403 && strpos($header, "Quota Exhausted") !== false) {
            return ['msg' => "套餐包次数用完", 'code' => 250];
        } elseif ($http_code == 403 && strpos($header, "Api Market Subscription quota exhausted") !== false) {
            return ['msg' => "套餐包次数用完，请续购套餐", 'code' => 250];
        } elseif ($http_code == 500) {
            return ['msg' => "API网关错误", 'code' => 250];
        } elseif ($http_code == 0) {
            return ['msg' => "URL错误", 'code' => 250];
        } else {
            $headers = explode("\r\n", $header);
            $headList = array();
            foreach ($headers as $head) {
                $value = explode(':', $head);
                $headList[$value[0]] = $value[1];
            }
            return ['msg' => $headList['x-ca-error-message'], 'http_code' => $http_code, 'code' => 250];
        }
    }
}
/**
 * 通过URL取mime
 * @param $url URL
 */
function get_mime($url)
{
    if (strpos($url, '://') !== false) {
        $type = get_headers($url, true)['Content-Type'];
    } else {
        $type = mime_content_type($url);
    }
    return $type;
}
/**
 * 取mime
 * @param $content 文件内容，可以是通过file_get_contents取到的
 */
function get_mime_content($content, $just_return_ext = false)
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_buffer($finfo, $content);
    finfo_close($finfo);
    if ($just_return_ext && $mime_type) {
        return substr($mime_type, strpos($mime_type, '/') + 1);
    }
    return $mime_type;
}
/**
 * 移除主域名部分
 */
function remove_host($url)
{
    $url = substr($url, strpos($url, '://') + 3);
    $url = substr($url, strpos($url, '/'));
    return $url;
}
/**
 * 取lat lng
 */
function predis_geo_pos($key, $title = [], $to_fixed = 6)
{
    $redis = predis();
    $res = $redis->geoPos($key, $title);
    $list = [];
    foreach ($res as $i => $v) {
        $vv = [
            'lng' => bcmul($v[0], 1, $to_fixed),
            'lat' => bcmul($v[1], 1, $to_fixed),
        ];
        $list[$title[$i]] = $vv;
    }
    return $list;
}
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
    $calls  = array_order_by($calls, 'level', SORT_DESC);
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
    $arr  = $config['cdn'] ?: [];
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
    header('Content-Type: application/json; charset=utf-8');
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
function create_dir($arr)
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
        $value = aes_encode($value);
        setcookie($name, $value, $opt);
        $_COOKIE[$name] = $value;
    } else {
        $value = $_COOKIE[$name];
        $value = aes_decode($value);
        return $value;
    }
}
/**
 * 删除COOKIE
 */
function  cookie_delete($name)
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
function json_error($arr)
{
    if (!is_array($arr)) {
        $arr = ['msg' => $arr];
    }
    global $token;
    if ($token) {
        $arr['data']['token'] = $token;
    }
    $arr['code'] = $arr['code'] ?: 250;
    $arr['type'] = $arr['type'] ?: 'error';
    return json($arr);
}
/**
 * 返回成功信息，JSON格式
 */
function json_success($arr)
{
    if (!is_array($arr)) {
        $arr = ['msg' => $arr];
    }
    global $token;
    if ($token) {
        $arr['data']['token'] = $token;
    }
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
    global $config;
    if (is_array($data)) {
        $data = json_encode($data);
    }
    $key = $key ?: $config['aes']['key'];
    $iv  = $iv ?: $config['aes']['iv'];
    $obj = new \lib\Aes($key, $iv, $type, $options);
    return base64_encode($obj->encrypt($data));
}
/**
 * AES解密
 */
function aes_decode($data, $key = '', $iv = '', $type = 'AES-128-CBC', $options = '')
{
    global $config;
    $key = $key ?: $config['aes']['key'];
    $iv  = $iv ?: $config['aes']['iv'];
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
function set_lang($lang = 'zh-cn')
{
    global $config;
    $config['_lang'] = $lang;
    lib\Lang::set($lang);
    lib\Validate::lang($lang);
}
/**
 * 获取当前语言
 */
function get_lang()
{
    global $config;
    return $config['_lang'];
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
    if (!file_exists($local_file)) {
        return false;
    }
    $zippy = Alchemy\Zippy\Zippy::load();
    $archive = $zippy->open($local_file);
    if (!is_dir($extract_local_dir)) {
        create_dir([$extract_local_dir]);
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
        create_dir([$dir]);
    }
    $zippy = Alchemy\Zippy\Zippy::load();
    $zippy->create($local_zip_file, $files, true);
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
 * 添加JS
 */
function add_js($code)
{
    global $_app;
    //判断换行数量 
    if (substr_count($code, "\n") > 1) {
        $_app['js_code'][] = $code;
        return;
    }
    $_app['js'][md5($code)] = $code;
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
        $ret = do_action("js_code", $all);
        if ($ret) {
            return $ret;
        }
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
        $ret = do_action("js_files", $all);
        if ($ret) {
            return $ret;
        }
        foreach ($all as $v) {
            if (is_string($v)) {
                if (strpos($v, '://') === false) {
                    $v = cdn() . $v;
                }
                echo '<script type="text/javascript" src="' . $v . '"></script>' . "\n";
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
    //判断换行数量 
    if (substr_count($code, "\n") > 1) {
        $_app['css_code'][] = $code;
        return;
    }
    $_app['css'][md5($code)] = $code;
}
/**
 * 输出JS
 */
function render_css()
{
    global $_app;
    $css = get_block('css');
    $all = $_app['css_code'] ?: [];
    if ($css) {
        $all = array_merge($all, $css);
    }
    if ($all) {
        $ret = do_action("css_code", $all);
        if ($ret) {
            return $ret;
        }
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
        $ret = do_action("css_files", $all);
        if ($ret) {
            return $ret;
        }
        foreach ($all as $v) {
            if (is_string($v)) {
                if (strpos($v, '://') === false) {
                    $v = cdn() . $v;
                }
                echo '<link href="' . $v . '" rel="stylesheet">' . "\n";
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
/**
 * 刷新图表
 */
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
/**
 * 发布消息
 * redis_pub("demo","welcome man");
 * redis_pub("demo",['title'=>'yourname']);
 */
function redis_pub($channel, $message)
{
    $redis = predis();
    if (is_array($message)) {
        $message = json_encode($message, JSON_UNESCAPED_UNICODE);
    }
    $res = $redis->publish($channel, $message);
    if (function_exists('is_cli') && is_cli()) {
        echo "消息已发布给 {$res} 个订阅者。";
    }
}

/**
 * 取订阅消息
 * redis_sub("demo",function($channel,$message){
 *   echo "channel ".$channel."\n";
 *   print_r($message);
 *  });
 */
function redis_sub($channel, $call, $unsubscribe = false)
{
    $redis = predis();
    // 创建订阅者对象
    $sub = $redis->pubSubLoop();
    // 订阅指定频道
    $sub->subscribe($channel);
    foreach ($sub as $message) {
        // 当接收到消息时，处理消息内容
        if ($message->kind === 'message') {
            $channel = $message->channel;
            $payload = $message->payload;
            if (function_exists("is_json") && is_json($payload)) {
                $payload = json_decode($payload, true);
            }
            $call($channel, $payload);
            if ($unsubscribe) {
                $sub->unsubscribe($channel);
            }
        }
    }
}
/**
 * 压缩字符串
 */
function gz_encode($arr_or_str)
{
    if (is_array($arr_or_str)) {
        $arr_or_str = json_encode($arr_or_str, JSON_UNESCAPED_UNICODE);
    }
    return gzencode($arr_or_str);
}

/**
 * 解压缩字符串
 */
function gz_decode($str)
{
    $str = gzdecode($str);
    if (is_json($str)) {
        return json_decode($str, true);
    } else {
        return $str;
    }
}

/**
 * https://github.com/picqer/php-barcode-generator/blob/main/src/BarcodeGenerator.php
 * C128 C128A C128B C128C C93 EAN13 EAN8 EAN2
 */
function get_barcode($code, $type = 'C128', $widthFactor = 2, $height = 30, $foregroundColor = [0, 0, 0])
{
    $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
    return "data:image/png;base64," . base64_encode($generator->getBarcode($code, $type, $widthFactor, $height, $foregroundColor));
}
/**
 * 文本添加br
 */
function text_add_br($text, $w, $br = '<br>')
{
    if (!$text) {
        return;
    }
    $len = get_gbk_len($text);
    if ($len > $w) {
        $total = ceil($len / $w);
        $new_text = '';
        for ($i = 0; $i < $total; $i++) {
            $j = $i * $w;
            $new_text .= gbk_substr($text, $j, $w) . $br;
        }
        $text = $new_text;
        if (strpos($text, $br) !== false) {
            $sub = 0 - strlen($br);
            $text = substr($text, 0, $sub);
        }
    }
    return $text;
}

/**
 * 取server headers
 * host connection cache-control sec-ch-ua-platform user-agent accept accept-encoding accept-language cookie
 */
function get_server_headers($name = '')
{
    static $header;
    if ($header) {
        return $name ? $header[strtolower($name)] : $header;
    }
    if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
        $header = [];
        foreach ($result as $k => $v) {
            $header[strtolower($k)] = $v;
        }
    } else {
        $header = [];
        $server = $_SERVER;
        foreach ($server as $key => $val) {
            if (str_starts_with($key, 'HTTP_')) {
                $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                $header[$key] = $val;
            }
        }
        if (isset($server['CONTENT_TYPE'])) {
            $header['content-type'] = $server['CONTENT_TYPE'];
        }
        if (isset($server['CONTENT_LENGTH'])) {
            $header['content-length'] = $server['CONTENT_LENGTH'];
        }
    }
    return $name ? $header[strtolower($name)] : $header;
}
/**
 * 优化数量显示
 * 1.10显示为1.1
 * 1.05显示为1.05
 * 1.00显示为1
 */
function show_number($num)
{
    return rtrim(rtrim($num, '0'), '.');
}
/**
 * 取字符中的数字
 */
function get_str_number($input)
{
    $pattern = '/(\d+(\.\d+)?)/';
    preg_match_all($pattern, $input, $matches);
    return $matches[1];
}
/**
 * 图片复制
 * <img id='myImage' src='data:image/png;base64, />
 * copy_base64_data(data); 
 */
function  copy_base64_data()
{
    global $vue;
    $str = " 
        location.origin.includes(`https://`) || Message.error(`图片复制功能不可用`);
        data = data.split(';base64,'); let type = data[0].split('data:')[1]; data = data[1]; 
        let bytes = atob(data), ab = new ArrayBuffer(bytes.length), ua = new Uint8Array(ab);
        [...Array(bytes.length)].forEach((v, i) => ua[i] = bytes.charCodeAt(i));
        let blob = new Blob([ab], { type }); 
        navigator.clipboard.write([new ClipboardItem({ [type]: blob })]); 
    ";
    if ($vue) {
        $vue->method("copy_base64_data(data)", $str);
        return;
    } else {
        return $str;
    }
}
/**
 * 是否是图片
 */
function is_image($url)
{
    $ext = get_ext($url);
    $allow = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
    if ($ext && in_array($ext, $allow)) {
        return true;
    } else {
        return false;
    }
}
/**
 * 是否是视频
 */
function is_video($url)
{
    $ext = get_ext($url);
    $allow = ['mp4', 'mkv', 'avi', 'mov', 'rm', 'rmvb', 'webp'];
    if ($ext && in_array($ext, $allow)) {
        return true;
    } else {
        return false;
    }
}
/**
 * 是否是音频
 */
function is_audio($url)
{
    $ext = get_ext($url);
    $allow = ['wav', 'mp3', 'm4a'];
    if ($ext && in_array($ext, $allow)) {
        return true;
    } else {
        return false;
    }
}
/**
 * 数组转xml 
 */
function array2xml($arr, $root = '')
{
    return Spatie\ArrayToXml\ArrayToXml::convert($arr, $root);
}
/**
 * xml转数组 
 */
function xml2array($xml_content)
{
    $doc = new \DOMDocument();
    $doc->loadXML($xml_content);
    $root = $doc->documentElement;
    $output = (array) _xml2array_node($root);
    $output['@root'] = $root->tagName;
    return $output ?? [];
}

function _xml2array_node($node)
{
    $output = [];
    switch ($node->nodeType) {
        case 4:
        case 3:
            $output = trim($node->textContent);
            break;
        case 1:
            for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                $child = $node->childNodes->item($i);
                $v =  _xml2array_node($child);
                if (isset($child->tagName)) {
                    $t = $child->tagName;
                    if (!isset($output[$t])) {
                        $output[$t] = [];
                    }
                    if (is_array($v) && empty($v)) {
                        $v = '';
                    }
                    $output[$t][] = $v;
                } elseif ($v || $v === '0') {
                    $output = (string) $v;
                }
            }
            if ($node->attributes->length && !is_array($output)) {
                $output = ['@content' => $output];
            }
            if (is_array($output)) {
                if ($node->attributes->length) {
                    $attr = [];
                    foreach ($node->attributes as $name => $node) {
                        $attr[$name] = (string) $node->value;
                    }
                    $output['@attributes'] = $attr;
                }
                foreach ($output as $t => $v) {
                    if ($t !== '@attributes' && is_array($v) && count($v) === 1) {
                        $output[$t] = $v[0];
                    }
                }
            }
            break;
    }
    return $output;
}



/**
 * 从文件中安装SQL
 * install_sql(local_sql_file,function($sql){
 *  $db->query($sql);
 * })
 */
function install_sql($file, $call)
{
    $fp =  fopen($file, "r");
    while ($sql = install_sql_get_next($fp)) {
        $sql = trim($sql);
        if ($sql) {
            $call($sql);
        }
    }
    fclose($fp);
}
/** 
 * 从文件中逐条取sql 
 */
function install_sql_get_next($fp)
{
    $sql = "";
    while ($line = @fgets($fp, 40960)) {
        $line = trim($line);
        $line = str_replace("////", "//", $line);
        $line = str_replace("/", "'", $line);
        $line = str_replace("//r//n", "chr(13).chr(10)", $line);
        $line = stripcslashes($line);
        if (strlen($line) > 1) {
            if ($line[0] == '-' && $line[1] == "-") {
                continue;
            }
        }
        $sql .= $line . chr(13) . chr(10);
        if (strlen($line) > 0) {
            if ($line[strlen($line) - 1] == ";") {
                break;
            }
        }
    }
    return $sql;
}
/**
 * 文件缓存
 */
function file_cache($key, $data = '', $second = null)
{
    global $config;
    $pre = $config['cache_pre'] ?: 'www';
    $key = $pre . $key;
    $file = PATH . '/runtime/cache/' . $key.'.cache';
    if ($data === null) {
        @unlink($file);
        return;
    }
    if ($data !== '') {
        $new_data = [
            'data' => $data, 
        ];
        if($second){
            $new_data['time'] = time() + $second;
        }
        $value = json_encode($new_data, JSON_UNESCAPED_UNICODE);
        @mkdir(dirname($file), 0755, true);
        @file_put_contents($file, $value); 
    } else {
        $data = @file_get_contents($file); 
         
        if ($data) {
            $data = json_decode($data, true);  
            if($data['time']){
                if($data['time'] > time()){ 
                    return $data['data'];
                }else {
                    @unlink($file);
                }
            }else{
                return $data['data'];
            }
        } 
    }
}
/**
 * 缓存删除
 */
function cache_delete($key)
{
    global $config;
    $ori_key = $key;
    $pre = $config['cache_pre'] ?: 'www';
    $key = $pre . $key;
    $cache_drive = $config['cache_drive'] ?: 'redis';
    if ($cache_drive == 'file') {
        return file_cache($ori_key, null);
    }
    predis()->del($key);
}
/**
 * 缓存设置|获取
 * @param string $key 缓存键
 * @param mixed $data 要存储的数据（null表示删除）
 * @param int|null $second 过期时间(秒)
 */
function cache($key, $data = '', $second = null)
{
    $ori_key = $key;
    global $config;
    $pre = $config['cache_pre'] ?: 'www';
    $key = $pre . $key;

    $cache_drive = $config['cache_drive'] ?: 'redis';
    if ($cache_drive == 'file') {
         return file_cache($ori_key, $data, $second);
    }
    $redis = predis();
    $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
    if ($data === null) {
        $redis->del($key);
        return;
    }
    if ($data !== '') {
        // 存储数据
        $value = is_array($data) || is_object($data)
            ? json_encode($data, JSON_UNESCAPED_UNICODE)
            : $data;

        if ($second) {
            $redis->setex($key, $second, $value);
        } else {
            $redis->set($key, $value);
        }
    } else {
        // 获取数据
        $data = $redis->get($key);
        return $data && ($decoded = json_decode($data, true)) ? $decoded : $data;
    }
}
/**
 * 数组排序
 * array_order_by($row,$order,SORT_DESC);
 */
function array_order_by()
{
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = array();
            if (!$data) {
                return;
            }
            foreach ($data as $key => $row) {
                $tmp[$key] = $row[$field];
            }
            $args[$n] = $tmp;
        }
    }
    $args[] = &$data;
    if ($args) {
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
    return;
}

/**
 * 取浏览器当前语言
 */
function get_browser_lang()
{
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/Language\/([a-z]{2}_[A-Z]{2})/i', $ua, $matches)) {
            $lang =  strtolower($matches[1]);
            if ($lang == 'zh_cn') {
                $lang = 'zh-cn';
            }
            return $lang;
        }
    }
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $firstLang = trim(explode(';', $langs[0])[0]);
        return strtolower($firstLang);
    }
    return 'en';
}

/**
 * 查找文件
 */
function find_files($arr, $return_file = false)
{
    foreach ($arr as $v) {
        if (file_exists($v)) {
            if ($return_file) {
                return $v;
            }
            return include $v;
        }
    }
}
/**
 * 跳转
 */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}
/**
 * 应用容器 - 支持带参数的类实例化
 * @param string $class_name 类名
 * @param array $params 构造参数
 * @return object 类实例
 */
function app($class_name, $params = [])
{
    static $app = [];
    // 生成带参数的唯一键
    $key = md5($class_name . serialize($params));
    if (!isset($app[$key])) {
        // 支持带参数实例化
        $app[$key] = new $class_name(...(array)$params);
    }
    return $app[$key];
}
/**
 * 生成URL
 */
function url($url, $par = [])
{
    $url = create_new_url($url);
    return Route::url($url, $par);
}

/**
 * 生成URL 
 */
function create_new_url($url)
{
    $slashCount = substr_count($url, '/');
    if ($slashCount == 2) {
        $url = $url . '/index';
    } else if ($slashCount == 1) {
        $url = $url . '/site/index';
    }
    return $url;
}
/**
 * 定义 assets 发布模块下的资源
 * @param string $module 模块名
 * @param string $module_dir 模块目录
 */
function publish_assets($module, $module_dir)
{
    if (!is_local()) {
        return;
    }
    /**
     * 复制assets下文件到PATH.'/public/assets/模块名/
     */
    $assets = PATH . '/public/assets/' . $module . '/';
    $src = $module_dir . '/assets/';
    /**
     * 判断assets目录是否存在
     */
    if (!is_dir($assets)) {
        mkdir($assets);
        copy_dir($src, $assets);
    }
}
/**
 * 复制目录
 */
function copy_dir($src, $dst)
{
    if (is_dir($src)) {
        if (!is_dir($dst)) {
            mkdir($dst);
        }
        $files = scandir($src);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $src_file = $src . '/' . $file;
                $dst_file = $dst . '/' . $file;
                if (is_dir($src_file)) {
                    copy_dir($src_file, $dst_file);
                } else {
                    copy($src_file, $dst_file);
                }
            }
        }
    }
}
/**
 * 运行命令,不等待命令执行完成
 * @param string $cmd 命令
 */
function run_cmd($cmd)
{
    $tmp = PATH . '/runtime/cmd';
    if (!is_dir($tmp)) {
        mkdir($tmp, 0777, true);
    }
    $cmd = 'nohup ' . $cmd . ' > ' . $tmp . '/cmd.log 2>&1 & echo $!';
    exec($cmd, $output, $return_var);
    return $return_var === 0 ? trim(implode("\n", $output)) : false; // 返回PID
}
/**
 * 图片类处理INIT
 * @return \Intervention\Image\ImageManager
 */
function image_drive()
{
    static $image_drive;
    if (!$image_drive) {
        $imageDrive = get_config('image_drive') ?: 'Gd';
        $drive = "\Intervention\Image\Drivers\\" . $imageDrive . "\Driver";
        $driveClass = new $drive();
        $image_drive = new \Intervention\Image\ImageManager($driveClass);
    }
    return $image_drive;
}
/**
 * 把URL中的域名部分移除
 */
function remove_http($url)
{
    if (strpos($url, '://') !== false) {
        $url = str_replace('http://', '', $url);
        $url = str_replace('https://', '', $url);
        if (strpos($url, '/') !== false) {
            $url = substr($url, strpos($url, '/'));
        }
    }
    return $url;
}
/**
 * 数组添加域名
 */
function array_add_domain($arr)
{
    foreach ($arr as $k => $v) {
        $arr[$k] = cdn() . $v;
    }
    return $arr;
}
/**
 * 数据库缓存数据
 */
function cache_data($key, $data = null, $expire = 0)
{
    $res = db_get_one('cache_data', 'id', ['key' => $key]);
    if ($data === null) {
        if ($res['expire'] > 0 && $res['expire'] < time()) {
            return $res['data'];
        } else {
            return;
        }
    }
    if ($res) {
        db_update('cache_data', ['id' => $res], ['data' => $data, 'expire' => $expire, 'updated_at' => time()]);
    } else {
        if ($expire > 0) {
            $expire = time() + $expire;
        }
        db_insert('cache_data', [
            'key' => $key,
            'data' => $data,
            'expire' => $expire,
            'created_at' => time(),
        ]);
    }
}
/**
 * 删除数据库缓存数据
 */
function cache_data_delete($key)
{
    db_delete('cache_data', ['key' => $key]);
}
/**
 * 解压文件
 * @param string $input 输入文件, 支持7z, zip, rar, tar, bz2, gz, tar
 * @param string $output_base 输出目录
 * @return string
 */
function unzip($input)
{
    $output_base = PATH . '/runtime/unzip/';
    $output_dir = $output_base . md5($input);
    $ext = get_ext($input);
    $cmd = "";
    $tar = "tar -xvf " . $input . " -C " . $output_dir;
    if (is_dir($output_dir)) {
        run_cmd("rm -rf " . $output_dir);
    }
    create_dir($output_dir);
    switch ($ext) {
        case '7z':
            $cmd = "7za x " . $input . " -o" . $output_dir;
            break;
        case 'zip':
            $cmd = "unzip " . $input . " -d " . $output_dir;
            break;
        case 'rar':
            $cmd = "unar  " . $input . " -o " . $output_dir;
            break;
        case 'bz2':
            $cmd = $tar;
            break;
        case 'gz':
            $cmd = $tar;
            break;
        case 'tar':
            $cmd = $tar;
            break;
    }
    if ($cmd) {
        run_cmd($cmd);
        return $output_dir;
    }
}
/**
 * 创建订单号
 */
function create_order_num($center_id = 0, $work_id = 0)
{
    return lib\Str::sonyFlakeId($center_id, $work_id);
}
/**
 * 获取设备信息
 */
function get_device()
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return $ua;
}
/**
 * 获取首页路由
 * Route::all('/', function () { 
 *   return get_home_route();
 * });
 * //设置首页路由
 * set_config('home_class','app\site\controller\siteController');
 */
function get_home_route()
{
    try {
        $homeClass = get_config('home_class');
    } catch (\Exception $e) {
    }
    $homeClass = $homeClass ?: 'app\site\controller\SiteController';
    $homeClass = str_replace("/", "\\", $homeClass);
    return Route::runController($homeClass, 'actionIndex');
}
/**
 * 是否api接口
 */
function is_api()
{
    $controller = Route::getActions()['controller'] ?? '';
    if (strpos($controller, 'api') !== false) {
        return true;
    }
}
