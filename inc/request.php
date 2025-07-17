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
    do_action("xss_clean", $ins);
    return $ins->xss_clean($str);
}
/**
 * 防止xss
 */
function xss_clean($input)
{
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
function get($key = "")
{
    if ($key) {
        return $_GET[$key];
    }
    return $_GET;
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
        return $val;
    } else {
        return $data ?: $input;
    }
}

function g($key = null)
{
    $val = get_post($key);
    if (!$val) {
        $val = $key ? $_GET[$key] : $_GET;
    }
    global $config;
    if ($config['xss_clean'] == true) {
        $val = xss_clean($val);
    }
    return $val;
}

/**
 * 取php://input值
 */
function get_input()
{
    $data = file_get_contents("php://input");
    if (is_json($data)) {
        $data = json_decode($data, true);
        global_trim_inner($data);
    }
    return $data;
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
 * CURL请求
 * 
 * GET
 * $client = guzzle_http();
 * $res    = $client->request('GET', $url);
 * return (string)$res->getBody();  
 * 
 * PUT
 * $body = file_get_contents($local_file);  
 * $request = new \GuzzleHttp\Psr7\Request('PUT', $upload_url, $headers=[], $body);
 * $response = $client->send($request, ['timeout' => 30]);
 * if($response->getStatusCode() == 200){
 *     return true;
 * } 
 * 
 * POST
 * $res    = $client->request('POST', $url,['body'=>]);
 * 
 * 
 * return (string)$res->getBody();  
 * 
 * JSON
 * 
 * $res = $client->request('POST', '/json.php', [
 *     'json' => ['foo' => 'bar']
 * ]);
 * 
 * 发送application/x-www-form-urlencoded POST请求需要你传入form_params
 * 
 * $res = $client->request('POST', $url, [
 *     'form_params' => [
 *         'field_name' => 'abc',
 *         'other_field' => '123',
 *         'nested_field' => [
 *             'nested' => 'hello'
 *         ]
 *     ]
 * ]);
 * 
 * 
 */
function guzzle_http($click_option = [])
{
    $click_option['timeout'] = $click_option['timeout'] ?: 60;
    $client = new \GuzzleHttp\Client($click_option);
    return $client;
}
