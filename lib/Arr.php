<?php

/**
 * 数组类
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace lib;

class Arr
{
    /**
     * 数组分页
     * @param  [type]  $array    [description]
     * @param  integer $pagesize [description]
     * @return [type]            [description]
     */
    public  static function page($array, $page_size = 5)
    {
        $count      = count($array);
        $total_page = ceil($count / $page_size);
        for ($i = 1; $i <= $total_page; $i++) {
            $start   = ($i - 1) * $page_size;
            $list[]  = array_slice($array, $start, $page_size);
        }
        return [
            'data'  => $list,
            'size'  => $page_size,
            'total' => $count,
            'pages' => $total_page
        ];
    }
    /**
     * 
     * 对二维数组进行group by操作
     * @param 数组 $arr
     * @param group  by 的字段 $group
     */
    public  static function group_by($arr, $groupby = "sid")
    {
        static $array = array();
        static $key = array();
        foreach ($arr as $k => $v) {
            $g = $v[$groupby];
            if (!in_array($g, $key)) {
                $key[$k] = $g;
            }
            $array[$g][] = $v;
        }
        return $array;
    }


    /**
     * 数组排序
     * arr::order_by($row,$order,SORT_DESC);
     */
    public  static function order_by()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                if (!$data) return;
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        if ($args) {
            call_user_func_array('array_multisort', $args);
            return array_pop($args);
        }
        return;
    }
}
