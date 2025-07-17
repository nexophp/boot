<?php

/**
 * 平台管理员基础控制器
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace core;


class AdminController extends AppController
{
    /**
     * 是否加载admin.css
     */
    protected $with_admin_css = false;
    public function init()
    {
        parent::init();
        /**
         * 加载admin.css
         */
        if ($this->with_admin_css) {
            add_css("/misc/css/admin.css");
            add_js("/misc/js/admin.js");
        }
        /**
         * 没有用户信息跳转到登录页面
         */
        if (!$this->user_info) {
            redirect('/admin/login/index');
        }
        /**
         * 非admin跳转到登录页面
         */
        if ($this->user_info['tag'] != 'admin') {
            redirect('/admin/login/index');
        }
        $this->setSdebar();
    }
    /**
     * 设置侧边栏
     */
    protected function setSdebar()
    {
        $menu_bg = get_config('menu_bg');
        $menu_active = get_config('menu_active');
        $menu_color_active = get_config('menu_color_active');
        if ($menu_bg && $menu_active) {
            add_action('header', function () use ($menu_bg, $menu_active, $menu_color_active) {
                echo <<<HTML
<style>
    .sidebar {
        background-color: {$menu_bg} !important;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
        background-color: {$menu_active} !important;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
        color: {$menu_color_active} !important;
    }
</style>
HTML;
            });
        }
    }
}
