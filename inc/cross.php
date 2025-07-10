<?php

/**
 *  处理跨域
 */
$cross_origin = $config['cross_origin'];
if (!$cross_origin) {
    $cross_origin = '*';
}
header('Access-Control-Allow-Origin: ' . $cross_origin);
header('Access-Control-Allow-Credentials:true');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
header('X-Powered-By: WAF/2.0');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}
