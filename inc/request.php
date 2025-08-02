<?php

/**
 * 请求
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */
/**
 * 添加xss过滤
 */
function get_xss_clean_ins()
{
    static $antiXss;
    if (!$antiXss) {
        $antiXss = new voku\helper\AntiXSS();
    }
    return $antiXss;
}
/**
 * 防止xss
 */
function xss_clean_str($str)
{
    $ins = get_xss_clean_ins();
    return $ins->xss_clean($str);
}
/**
 * 防止xss
 */
function xss_clean($input)
{
    if (!$input) {
        return;
    }
    if (is_array($input)) {
        foreach ($input as $k => &$v) {
            if ($v && is_string($v)) {
                $v = xss_clean_str($v);
            }
        }
        return $input;
    } else if ($input && is_string($input)) {
        return xss_clean_str($input);
    }
    return $input;
}
/**
 * 有\'内容转成'显示，与addslashes相反 stripslashes
 */
/**
 * 对 GET POST COOKIE REQUEST请求字段串去除首尾空格
 */
function global_trim()
{
    $in = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    global_trim_inner($in);
}
/**
 * 对数组递归去除首尾空格
 */
function global_trim_inner(&$in)
{
    foreach ($in as $k => &$v) {
        if (is_array($v)) {
            global_trim_inner($v);
        } else {
            $in[$k] = is_string($v) ? trim($v) : $v;
        }
    }
}
/**
 * 取GET
 */
function get_query($key = "")
{
    if ($key) {
        return xss_clean($_GET[$key]);
    }
    return xss_clean($_GET);
}
/**
 * 取POST值
 */
function get_post($key = "")
{
    $input = get_input();
    $data  = $_POST;
    if ($key) {
        $val = $data[$key];
        if ($input && is_array($input) && !$val) {
            $val = $input[$key];
        }
        return xss_clean($val);
    } else {
        return xss_clean($data ?: $input);
    }
}
/**
 * 取GET POST
 */
function g($key = null)
{
    $val = get_post($key);
    if (!$val) {
        $val = $key ? $_GET[$key] : $_GET;
    }
    return xss_clean($val);
}

/**
 * 取php://input值
 */
function get_input($key = '')
{
    $data = file_get_contents("php://input");
    if (is_json($data)) {
        $data = json_decode($data, true);
        global_trim_inner($data);
    }
    if ($key) {
        return xss_clean($data[$key]);
    }
    return xss_clean($data);
}
/**
 * 取当前URL完整地址 
 */
function get_full_url($with_http = false)
{
    $url = $_SERVER['REQUEST_URI'];
    if ($with_http) {
        $url = host() . substr($url, 1);
    }
    return $url;
}

/**
 * curl get
 */
function curl_get($url, $params = [], $click_option = [])
{
    $click_option['timeout'] = $click_option['timeout'] ?: 60;
    $client = new \GuzzleHttp\Client($click_option); 
    try {
        if($params){
            $res = $client->request('GET', $url, ['query' => $params]);
        }else{
            $res = $client->request('GET', $url);
        }
        $str = (string)$res->getBody();
        if(is_json($str)){
            return json_decode($str, true);
        }
        return $str; 
    } catch (\Throwable $th) {
        $err = $th->getMessage();
        add_log("CURL GET异常".$url,$err,'error');
    }
    
}
/**
 * curl post
 * @param string $url
 * @param array $params 发送的参数 ['json'=>[],'form_params'=>[]]
 * @param array $click_option
 * @return array|string
 */
function curl_post($url, $params = [], $click_option = [])
{
    $click_option['timeout'] = $click_option['timeout'] ?: 60;
    $client = new \GuzzleHttp\Client($click_option);
    try {
        $res = $client->request('POST', $url, $params);
        $str = (string)$res->getBody();
        if(is_json($str)){
            return json_decode($str, true);
        }
        return $str;
    } catch (\Throwable $th) {
        $err = $th->getMessage();
        add_log("CURL POST异常".$url,$err,'error');
    }
    
}
/**
 * curl put
 * @param string $url
 * @param array $params 发送的参数 ['json'=>[],'form_params'=>[]]
 * @param array $click_option
 * @return array|string
 */
function curl_put($upload_url, $local_file, $timeout = 300)
{
    $click_option['timeout'] = $timeout;
    $client = new \GuzzleHttp\Client($click_option);
    $body = file_get_contents($local_file);
    $request = new \GuzzleHttp\Psr7\Request('PUT', $upload_url, $headers = [], $body);
    try {
        $res = $client->send($request, ['timeout' => $timeout]);
        $str = (string)$res->getBody();
        if(is_json($str)){
            return json_decode($str, true);
        }
        return $str;
    } catch (\Throwable $th) {
        $err = $th->getMessage();
        add_log("CURL PUT异常".$upload_url,$err,'error');
    } 
}

/**
 * 当前模块 控制器 方法
 */
function get_uri(){
    return Route::getActionString(); 
}