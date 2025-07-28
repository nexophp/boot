<?php

/**
 * 基础控制器
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace core;

use Route;


class AppController
{
    /**
     * 渲染到视图的data数据
     */
    protected  $view_data = [];
    /**
     * 请求 module controller action
     */
    protected  $actions = [];
    /**
     * POST数据
     */
    protected $post_data = [];
    /**
     * INPUT 数据
     */
    protected $input_data = [];
    /**
     * 用户信息
     */
    protected $user_info = [];
    /**
     * 用户id
     */
    protected $user_id = '';
    protected $uid     = '';
    /**
     * 是否是api请求，仅当api请求时获取getHttpAuth
     */
    protected $is_api = false;
    /**
     * 模型
     */
    protected $model;
    /**
     * 构造函数 
     */
    public function __construct()
    {
        $this->post_data = get_post() ?: [];
        $this->input_data = get_input() ?: [];
        $this->actions = Route::getActions();
        $this->init();
        if ($this->model) {
            $model = $this->model;
            $this->model = (object)[];
            if (is_array($model)) {
                foreach ($model as $key => $value) {
                    $this->model->$key = new $value();
                }
            } else {
                $this->model = new $model();
            }
        }
    }
    /**
     * 取当前用户权限 
     */
    public function checkPermissions()
    {
        $str = $this->actions['module'] . '/' . $this->actions['controller'] . '/' . $this->actions['action'];
        if (has_access($str)) {
            return true;
        } else {
            do_action('access_deny');
            json_error(['msg' => '您没有权限访问']);
        }
    }
    /**
     * 初始化
     */
    protected function init()
    {
        $this->_loadAssets();
        try {
            $this->_loadLang();
            $this->user_info = $this->getUserInfo();
        } catch (\Throwable $th) {
        }
        /**
         * 基类控制器HOOK
         */
        do_action('AppController.init');
    }
    /**
     * 获取用户信息
     */
    protected function getUserInfo()
    {
        global $uid, $user_id;
        $uid = cookie('uid');
        if (!$uid) {
            if ($this->is_api) {
                $uid = $this->getHttpAuth();
            }
            if (!$uid) {
                return [];
            }
        }
        $user_id = $uid;
        $user = get_user($uid);
        if (!$user) {
            return [];
        }
        $this->uid = $this->user_id = $user['id'];
        return $user;
    }
    /**
     * 获取HTTP认证信息
     */
    protected function getHttpAuth()
    {
        $author = $_SERVER['HTTP_AUTHORIZATION'];
        if (!$author) {
            return '';
        }
        if (strpos($author, 'Bearer') !== false) {
            $author = substr($author, 7);
            $author = trim($author);
        }
        if (!$author) {
            return '';
        }
        $decode = \lib\Jwt::decode($author);
        if (!$decode) {
            return '';
        }
        $user_id = $decode->user_id;
        if (!$user_id) {
            return '';
        }
        $device = $decode->device;
        $exp = $decode->exp;
        $res = db_get_one("user_login", '*', [
            'user_id' => $user_id,
            'token' => md5($author),
            'device' => $device,
        ]);
        if (!$res) {
            return '';
        }
        db_update("user_login", ['last_time' => time()], ['id' => $res['id']]);
        if ($exp < time()) {
            global $token;
            //刷新token,让老token失效
            $token = \lib\Jwt::encode(['user_id' => $user_id, 'time' => time()]);
            db_update("user_login", [
                'token' => $token,
            ], [
                'id' => $res['id'],
            ]);
        }
        return $user_id;
    }
    /**
     * 加载语言包
     */
    protected function _loadLang()
    {
        $default_lang = get_config('default_lang');
        if ($default_lang && $default_lang != 'auto') {
            set_lang($default_lang);
            return;
        }
        $res = Route::getActions();
        $lang = $res['lang'] ?? cookie('lang');
        if (!$lang) {
            $lang = get_browser_lang();
        }
        if ($lang) {
            set_lang($lang);
        }
    }
    /**
     * 路由请求后
     */
    public function after(&$data)
    {
        $action = $this->actions['action'];
        if (!$data) {
            $data = view($action, $this->view_data);
        }
    }
    /**
     * 加载资源文件
     */
    protected function _loadAssets()
    {
        global $vue;
        $vue = new \Vue;
        add_js("/misc/js/jquery.js");
        add_js("/misc/js/vue.js");
        add_js('/misc/select2/js/select2.min.js');
        add_js("/misc/js/jquery.cookie.js");
        add_js("/misc/element-ui/index.js");
        add_js("/misc/bs5/js/bootstrap.bundle.min.js");
        add_js("/misc/layui/layui.js");
        add_js("/misc/sortable/sortable.js");
        add_js("/misc/sortable/vuedraggable.umd.js");
        add_js("/misc/js/vue-tags-input.js");
        add_js("/misc/js/reconnecting-websocket.js");
        add_js("/misc/js/file-saver.js");
        add_js("/misc/js/xlsx.js");
        add_js("/misc/wangeditor/index.js");
        add_css("/misc/wangeditor/css/style.css");
        add_js("/misc/js/app.js");

        add_css('/misc/select2/css/select2.min.css');
        add_css("/misc/bs5/css/bootstrap.min.css");
        add_css("/misc/bootstrap-icons/font/bootstrap-icons.min.css");
        add_css("/misc/element-ui/default/index.css");
        add_css("/misc/layui/css/layui.css");
        add_css("/misc/css/app.css");

        add_action("content", function () {
            element_vue();
        });
    }
}
