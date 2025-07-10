<?php

namespace lib;

use Upload\Storage\FileSystem;
use Upload\File;

/*
use lib\Upload;
//是否总是上传
Upload::$db = false;
//是否把上传记录保存到数据库
Upload::$db = true;
//是否可上传
Upload::$allow_upload = true;
*/

class Upload
{
    public $domain;
    /**
     * 写入数据库
     */
    public static $db = true;
    public static $allow_upload = false;
    public $user_id;
    public $base_dir = 'uploads';
    public function __construct()
    {
        global $config;
        $host = $config['host'];
        if (!$host) {
            exit('请配置域名');
        }
        $host = str_replace("https://", "", $host);
        $host = str_replace("http://", "", $host);
        $host = str_replace(":", "_", $host);
        $host = str_replace(".", "_", $host);
        $this->domain  = $host;
        //上传初始化
        do_action("upload.init", $this);
    }
    /**
     * 批量上传
     */
    public function all()
    {
        $list = [];
        foreach ($_FILES as $k => $v) {
            $_POST['file_key'] = $k;
            $ret = $this->one();
            if ($ret['url']) {
                $new_url = $ret['url'];
                $new_url = host() . $new_url;
                $list[] = [
                    'url' => $new_url
                ];
            }
        }
        return $list;
    }

    /**
     * 返回参数
     */
    public function return_params(&$model)
    {
        unset($_POST['file']);
        do_action("upload", $model);
        $model['post'] = $_POST ?: [];
        $model['get']  = $_GET ?: [];
        if (!$model['data']) {
            $model['data'] = cdn() . $model['url'];
        }
    }

    /**
     *  单个文件上传
     *  do_action("upload.mime", $mime);
     */
    public function one($http_opt = [])
    {
        if (!self::$allow_upload) {
            if (cookie('can_upload') > 0) {
                self::$allow_upload = true;
            } else {
                $user_id = cookie('uid') ?: api(false)['user_id'];
                if ($user_id) {
                    self::$allow_upload = true;
                }
            }
        }
        if (!self::$allow_upload) {
            json_error(['msg' => '上传文件被拦截，不支持当前用户上传文件']);
        }
        global $config;
        //上传文件前
        do_action("upload.before", $this);
        $file_key =  g('file_key') ?: 'file';
        $sub_dir  = g('sub_dir');
        if ($sub_dir) {
            $sub_dir = $sub_dir . '/';
        }
        $user_id = $this->user_id ?: $user_id;
        $url =  $this->base_dir . '/' . $this->domain . '/' . $sub_dir . $user_id . '/' . date('Ymd');
        $url = str_replace("//", "/", $url);
        $path  = PATH . $url . '/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $storage = new FileSystem($path);
        $file    = new File($file_key, $storage);
        $new_filename = uniqid();
        $ori_name = $file->getNameWithExtension();
        $file->setName($new_filename);
        $name = $file->getName();
        $md5  = $file->getMd5();
        $size = $file->getSize();
        $mime = $file->getMimetype();
        $file_ext = $file->getExtension();
        do_action("upload.mime", $mime);
        do_action("upload.size", $size);
        do_action("upload.ext", $file_ext);
        if (self::$db) {
            $f  = db_get_one('upload', '*', ['hash' => $md5]);
            if ($f) {
                $f['local_path'] = PATH . $f['url'];
                //上传成功后
                do_action("upload.after", $f);
                $this->return_params($f);
                return $f;
            }
        }
        try {
            $url = $url . '/' . $name . "." . $file_ext;
            $file->upload();
            $insert = [];
            $insert['url']      = $url;
            $insert['hash']     = $md5;
            $insert['user_id']  = $user_id;
            $insert['mime']     = $mime;
            $insert['size']     = $size;
            $insert['ext']      = $file_ext;
            $insert['name']     = $http_opt['name'] ?: $ori_name;
            $insert['created_at'] = date('Y-m-d H:i:s');
            if (self::$db) {
                $id = db_insert('upload', $insert);
                $f  = db_get_one('upload', '*', ['id' => $id]);
            } else {
                $f = $insert;
            }
            $this->return_params($f);
            $f['local_path'] = PATH . $url;
            //上传成功后
            do_action("upload.after", $f);
            return $f;
        } catch (\Exception $e) {
            $errors = $file->getErrors();
            return ['error' => $errors];
        }
    }
    /**
     * 返回已上传文件总大小，单位M
     */
    public static function getSize()
    {
        $size = db_get_sum("upload", "size");
        return bcdiv($size, bcmul(1024, 1024), 2);
    }
}
