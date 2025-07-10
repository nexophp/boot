<?php

namespace lib;
/*
tx_map_key
gd_map_key
bd_map_key
*/

class Map
{

    public static function get_request($url)
    {
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 3000
            )
        ));
        $res = file_get_contents($url, 0, $context);
        return $res;
    }
    /**
     * 腾讯地图
     * https://lbs.qq.com/dev/console/key/setting
     * @param  [type] $address [description]
     * @return [type]          [description]
     */
    public static function tx($address, $lat = true)
    {
        $key     = get_config('tx_map_key');
        $address = urlencode($address);
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?address=" . $address . "&key=" . $key;
        $res = self::get_request($url);
        $res = json_decode($res, true);
        if ($lat === true) {
            $t = $res['result']['location'];
            $t['data'] = $res;
            return $t;
        }
        return $res;
    }
    /**
     * 根据坐标点显示地址 纬度 经度
     * @param  [type]  $lat  [description]
     * @param  [type]  $lng  [description]
     * @param  boolean $full [description]
     * @return [type]        [description]
     */
    public static function tx_lat($lat, $lng, $full = false)
    {
        $key     = get_config('tx_map_key');
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?location=" . $lat . ',' . $lng . "&key=" . $key;
        $res = self::get_request($url);
        $res = json_decode($res, true);
        if ($full === false) {
            return ['data' => $res['result']['address']];
        }
        return $res;
    }
    /**
     * 高德地图
     * @param  [type]  $address   [description]
     * @param  boolean $show_full [description]
     * @return [type]             [description]
     */
    public static function gaode($address, $show_full = false)
    {
        $key     = get_config('gd_map_key');
        $address = urlencode($address);
        $url = 'https://restapi.amap.com/v3/geocode/geo?address=' . $address . '&output=json&key=' . $key;
        $res = self::get_request($url);
        $res = json_decode($res, true);
        if ($show_full) {
            return $res;
        }
        if ($res['status'] == 1) {
            $str = $res['geocodes'][0]['location'];
            return explode(',', $str);
        }
    }
    /**
     * 通过经纬度取城市 $log.','.$lat
     * @param  [type]  $address   [description]
     * @param  boolean $show_full [description]
     * @return [type]             [description]
     */
    public static function gaode_lat($location, $show_full = false)
    {
        $key     = get_config('gd_map_key');
        $url = 'https://restapi.amap.com/v3/geocode/regeo?location=' . $location . '&output=json&key=' . $key;
        $res = self::get_request($url);
        $res = json_decode($res, true);
        if ($show_full) {
            return $res;
        }
        if ($res['status'] == 1) {
            $c = $res['regeocode']['addressComponent']['province'];
            return $c;
        }
    }

    public static function baidu_ip($ip, $show_full = false)
    {
        $key     = get_config('bd_map_key');
        $url = 'https://api.map.baidu.com/location/ip?ak=' . $ak . '&ip=' . $ip . '&coor=bd09ll';
        $res = self::get_request($url);
        $res = json_decode($res, true);
        if ($show_full) {
            return $res;
        }
        if ($res['status'] == 0) {
            $c = $res['content']['address'];
            return $c;
        }
    }

    /**
     * 计算两点地理坐标之间的距离
     * @param  Decimal $longitude1 起点经度  121.247864
     * @param  Decimal $latitude1  起点纬度  31.465568
     * @param  Decimal $longitude2 终点经度 
     * @param  Decimal $latitude2  终点纬度
     * @param  Int     $unit       单位 1:米 2:公里
     * @param  Int     $decimal    精度 保留小数位数
     * @return Decimal
     */
    public static function get_distance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
    {
        return get_distance($longitude1, $latitude1, $longitude2, $latitude2, $unit, $decimal);
    }
}
