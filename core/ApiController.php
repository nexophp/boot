<?php

/**
 * API控制器
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace core;


class ApiController extends AppController
{
    /**
     * 是否需要登录
     */
    protected $need_login = true;
    protected $is_api = true;
    /**
     * 初始化
     */
    public function init()
    {  
        parent::init();
        do_action('ApiController.init');
    }
}
