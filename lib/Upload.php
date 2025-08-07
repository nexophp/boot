<?php

/**
 * 文件上传类
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace lib;

class Upload
{
    public $domain;
    /**
     * 是否将上传记录写入数据库
     */
    public static $db = true;
    public $user_id;

    /**
     * 构造函数，初始化上传配置
     */
    public function __construct()
    {
        global $config;
        $host = $config['host'];
        if (!$host) {
            exit(lang('请配置域名'));
        }
        $host = str_replace(["https://", "http://", ":", "."], ["", "", "_", "_"], $host);
        $this->domain = $host;
        // 触发上传初始化钩子
        do_action("upload.init", $this);
    }

    /**
     * 批量上传文件
     * @return array 上传成功的文件URL列表
     */
    public function all()
    {
        $list = [];
        foreach ($_FILES as $k => $v) {
            $_POST['file_key'] = $k;
            $list[] = $this->one();
        }
        return $list;
    }

    /**
     * 单个文件上传
     * @param array $http_opt 额外的HTTP选项
     * @return array 上传结果
     */
    public function one($http_opt = [])
    {
        // 触发上传前钩子
        do_action("upload.before", $this);

        $file_key = g('file_key') ?: 'file';
        $sub_dir = g('sub_dir') ? g('sub_dir') . '/' : '';
        $user_id = $this->user_id;

        $url = '/uploads/' . $this->domain . '/' . $sub_dir . $user_id . '/' . date('Ymd');
        $url = str_replace("//", "/", $url);

        $path = g('is_private')
            ? PATH . '/data/private/' . $url . '/'
            : WWW_PATH . '/' . $url . '/';

        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            json_error(['msg' => lang('无法创建目录')]);
        }

        if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] === UPLOAD_ERR_NO_FILE) {
            json_error(['msg' => lang('没有上传文件')]);
        }

        $file = $_FILES[$file_key];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            json_error(['msg' => $this->getUploadError($file['error'])]);
        }

        $new_filename = $this->user_id . Str::uuid();

        $ori_name = pathinfo($file['name'], PATHINFO_FILENAME);
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $destination = $path . $new_filename . '.' . $file_ext;
        $url = $url . '/' . $new_filename . '.' . $file_ext;

        // 获取文件信息
        $md5 = md5_file($file['tmp_name']);
        $size = $file['size'];
        $mime = mime_content_type($file['tmp_name']);

        do_action("upload.mime", $mime);
        do_action("upload.size", $size);
        do_action("upload.ext", $file_ext);

        if (self::$db) {
            $data = db_get_one('upload', '*', ['hash' => $md5]);
            if(!db_get_one('upload_user','*',['user_id'=>$user_id,'hash'=>$md5])){
                $new_data = $data;
                $new_data['user_id'] = $user_id;
                unset($new_data['id']);
                db_insert('upload_user',$new_data);
            }
            if ($data) {
                goto Success;
            }
        }

        try {
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                json_error(['msg' => lang('文件移动失败')]);
            }

            $insert = [
                'url' => $url,
                'hash' => $md5,
                'user_id' => $user_id,
                'mime' => $mime,
                'size' => $size,
                'ext' => $file_ext,
                'name' => $http_opt['name'] ?? $ori_name,
                'created_at' => date('Y-m-d H:i:s')
            ];

            if (self::$db) {
                $id = db_insert('upload', $insert);
                db_insert('upload_user',$insert);
                $data = db_get_one('upload', '*', ['id' => $id]);
            } else {
                $data = $insert;
            }
        } catch (\Exception $e) {
            json_error(['msg' => [lang('上传失败') . ': ' . $e->getMessage()]]);
        }
        Success:
        $data['size_to'] = Str::size((int)$data['size']);
        $data['http_url'] = host() . $data['url'];
        do_action("upload.success", $data);
        return $data;
    }

    /**
     * 获取上传错误信息
     * @param int $error_code 错误代码
     * @return string 错误信息
     */
    private function getUploadError($error_code)
    {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return lang('上传文件超过服务器最大限制');
            case UPLOAD_ERR_FORM_SIZE:
                return lang('上传文件超过表单最大限制');
            case UPLOAD_ERR_PARTIAL:
                return lang('文件上传不完整');
            case UPLOAD_ERR_NO_TMP_DIR:
                return lang('缺少临时文件夹');
            case UPLOAD_ERR_CANT_WRITE:
                return lang('无法写入磁盘');
            case UPLOAD_ERR_EXTENSION:
                return lang('PHP扩展阻止了文件上传');
            default:
                return lang('未知的上传错误');
        }
    }

    /**
     * 获取已上传文件的总大小（单位：MB）
     * @return float 总大小
     */
    public static function  getSize()
    {
        $size = db_get_sum("upload", "size") ?: 0;
        if ($size <= 0) {
            return 0;
        }
        return bcdiv($size, bcmul(1024, 1024), 2);
    }
}
