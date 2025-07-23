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
    public function init()
    {
        $show_error = $this->show_error;
        if (!$this->need_login) {
            $show_error = false;
        }
        $uid = cookie('uid');
        if (!$uid) {
            $jwt = get_authorization($show_error);
            $uid = $jwt['user_id'] ?? 0;
        }
        if (!$uid) {
            json_error(['msg' => lang('未经许可的访问')]);
        }
        $user = get_user_info($uid);
        $this->uid = $this->user_id = $uid;
        $this->user_info = $user;
        do_action('ApiController.init');
    }
}
