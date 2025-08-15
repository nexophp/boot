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
    /**
     * 初始化
     */
    protected function init()
    {
        parent::init();
        do_action('ApiController.init');
        if ($this->need_login && !$this->uid) {
            json_error(['msg' => lang('请先登录')]);
        }
    }
}
