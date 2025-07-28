<?php

/**
 * 时间类
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace lib;

class Time
{
    /**
     * 取每月
     * every_month('2023-01-01','2023-12-31')
     */
    public static function everyMonth($start_month, $end_month)
    {
        $list = [];
        while ($start_month < $end_month) {
            $time = strtotime($start_month);
            $last_day_of_month = date("Y-m-d", strtotime("last Day of this month 23:59:59", $time));
            $list[] = [$start_month, $last_day_of_month];
            $start_month = date("Y-m-01", strtotime("+1 month", $time));
        }
        return $list;
    }
    /**
     * 多少岁
     * @return string
     */
    public static function age($bornUnix)
    {
        if (strpos($bornUnix, ' ') !== false || strpos($bornUnix, '-') !== false) {
            $bornUnix = strtotime($bornUnix);
        }
        return ceil((time() - $bornUnix) / 86400 / 365);
    }
    /**
     * 计算时间剩余
     * @return string ２天３小时２８分钟１０秒
     */
    public static function lessTime($a, $b = null)
    {
        if (!$b) {
            $time = $a;
        } else {
            $time = $a - $b;
        }
        if ($time <= 0) {
            return -1;
        }
        $days = intval($time / 86400);
        $remain = $time % 86400;
        $hours = intval($remain / 3600);
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        $secs = $remain % 60;
        return ["d" => $days, "h" => $hours, "m" => $mins, "s" => $secs];
    }

    /**
     * 最近30天
     */
    public static function date($day = 30, $separate = "", $op = '-')
    {
        $arr = [];
        for ($i = 0; $i < $day; $i++) {
            $arr[] = date("Y" . $separate . "m" . $separate . "d", strtotime($op . $i . ' days'));
        }
        $arr = array_reverse($arr);
        return $arr;
    }

    /**
     * 最近几天
     */
    public static function neerDays($num = 5, $separate = "", $op = '-')
    {
        $list  = [];
        for ($i = 0; $i < $num; $i++) {
            $list[] = date("Y" . $separate . "m" . $separate . "d", strtotime($op . $i . " day", time()));
        }
        if ($op == '+') {
            return $list;
        }
        $list = array_reverse($list);
        return $list;
    }

    /**
     * 返回最近几月
     */
    public static function neerMonths($num = 5, $separate = "", $op = '-')
    {
        $list  = [];
        for ($i = 0; $i < $num; $i++) {
            $list[] = date("Y" . $separate . "m", strtotime($op . $i . " month", time()));
        }
        if ($op == '+') {
            return $list;
        }
        $list = array_reverse($list);
        return $list;
    }

    /**
     * 返回最近几年
     */
    public static function neerYears($num = 5, $op = '-')
    {
        if ($op == '-') {
            $start = date("Y", strtotime($op . ($num - 1) . " year", time()));
        } else {
            $start = date("Y");
        }
        $list  = [];
        for ($i = 1; $i <= $num; $i++) {
            $list[] = $start++;
        }
        return $list;
    }
    /**
     * 取今日、本周、本月、本年、昨日、上周、上月、上年
     */
    public static function get($key = '', $date_format = false)
    {
        $arr = [
            'today'      => ['today', 'tomorrow'],
            'yesterday'  => ['yesterday', 'today'],
            'week'       => ['this week 00:00:00', 'next week 00:00:00'],
            'lastweek'   => ['last week 00:00:00', 'this week 00:00:00'],
            'month'      => ['first Day of this month 00:00:00', 'first Day of next month 00:00:00'],
            'lastmonth'  => ['first Day of last month 00:00:00', 'first Day of this month 00:00:00'],
            'year'       => ['this year 1/1', 'next year 1/1'],
            'lastyear'   => ['last year 1/1', 'this year 1/1'],
        ];
        if (!$key) {
            $list = [];
            foreach ($arr as $k => $v) {
                $a = strtotime($v[0]);
                $b = strtotime($v[1]);
                if ($date_format) {
                    $a = date('Y-m-d 00:00:00', $a);
                    $b = date('Y-m-d 23:59:59', $b);
                }
                $list[$k] = [$a, $b];
            }
            return $list;
        }
        $data = $arr[$key];
        if ($data) {
            $ret = [
                strtotime($data[0]),
                strtotime($data[1]),
            ];
            if ($date_format) {
                $ret = [
                    date('Y-m-d 00:00:00', $ret[0]),
                    date('Y-m-d 23:59:59', $ret[1]),
                ];
            }
            return $ret;
        }
    }
}
