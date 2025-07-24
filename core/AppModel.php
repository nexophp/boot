<?php

/**
 * 基础模型
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace core;


class AppModel extends \DbModel
{
    protected $user_id;
    protected $uid;
    public function init()
    {
        global $uid;
        $this->user_id = $this->uid = $uid;
    }
}
