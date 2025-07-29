<?php

/**
 * HTML
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace lib;

class Html
{

    /**
     * 从内容中取本地图片
     */
    public static function getImage($content)
    {
        $preg = '/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i';
        preg_match_all($preg, $content, $out);
        return $out[2]??[];
    }
    /**
     * 从内容中删除图片
     */
    public static function removeImage($content)
    {
        $preg = '/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i';
        return preg_replace($preg, "", $content);
    }
}
