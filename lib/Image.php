<?php

/**
 * Image
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace lib;

class Image
{
    /**
     * 合并多个图片
     */
    public static function merger($image = [], $output, $quality = 75, $run = true)
    {
        $flag = false;
        $i = '';
        foreach ($image as $v) {
            if (file_exists($v)) {
                $i .= " " . $v . " ";
                $flag = true;
            }
        }
        if (!$flag) {
            json_error(['msg' => lang('需要合并的图片不存在')]);
        }
        $dir = get_dir($output);
        create_dir([$dir]);
        $cmd = "convert $i -append -quality " . $quality . "% $output ";
        if ($run) {
            exec($cmd);
        }
    }
    /**
     * 获取图片信息
     */
    public static function getInfo($file)
    {
        $info = getimagesize($file);
        list($width, $height, $type) = $info;
        $data = [
            'width'  => $width,
            'height' => $height,
            'type'   => $type,
            'bits'   => $info['bits'],
            'mime'   => $info['mime'],
        ];
        return $data;
    }
    /**
     * 获取图片位置
     * 2是横版，1是竖版
     */
    public static function getPosition($file)
    {
        $info = self::getInfo($file);
        if ($info['width'] > $info['height']) {
            return 2;
        } else {
            return 1;
        }
    }
}
