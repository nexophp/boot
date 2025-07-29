<?php

/**
 * 天地图
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

/** 
 * http://lbs.tianditu.gov.cn/server/search2.html
 */

namespace lib;

/**
 * MapTiandi
 */
class MapTiandi
{
    /**
     * 服务器端
     */
    public static function get_tk()
    {
        return get_config("tianditu");
    }
    /**
     * 浏览器端
     */
    public static function get_tk_sever()
    {
        return get_config("tianditu_js");
    }

    public static function get($url, $data = '')
    {
        $client = guzzle_http();
        $res    = $client->request('GET', $url);
        $body = (string)$res->getBody();
        return json_decode($body, true);
    }
    /**
     * 根据lat lng取地址
     */
    public static function getAddress($lat, $lng)
    {
        $url = "http://api.tianditu.gov.cn/geocoder?postStr={'lon':" . $lng . ",'lat':" . $lat . ",'ver':1}&type=geocode&tk=" . self::get_tk();
        $data = self::get($url);
        if ($data['status'] == 0) {
            $res = $data['result'];
            $list = [];
            $list['address'] = $res['formatted_address'];
            $a = $res['addressComponent'];
            $list['parse'] = [
                'nation' => $a['nation'],
                'province' => $a['province'],
                'county' => $a['county'],
                'address' => $a['address'],
            ];
            return $list;
        }
    }
    /**
     * 根据地址取lat lng
     */
    public static function getLat($address)
    {
        $url = 'http://api.tianditu.gov.cn/geocoder?ds={"keyWord":"' . $address . '"}&tk=' . self::get_tk();
        $data = self::get($url);
        if ($data['status'] == 0) {
            $lat = $data['location']['lat'];
            $lng = $data['location']['lon'];
            return self::wgs84Gcj02($lat, $lng);
        }
    }

    /**
     * 不转换坐标
     */
    public static function none($lat, $lng)
    {
        return self::output($lat, $lng);
    }

    /**
     * CGCS2000（WGS84） 转成 GCJ02
     */
    public static function wgs84Gcj02($lat, $lng)
    {
        $PI = 3.1415926535897932384626;
        $a = 6378245.0;
        $ee = 0.00669342162296594323;
        if (self::out_of_china($lng, $lat)) {
            return self::output($lat, $lng);
        } else {
            $dlat = self::transformlat($lng - 105.0, $lat - 35.0);
            $dlng = self::transformlng($lng - 105.0, $lat - 35.0);
            $radlat = $lat / 180.0 * $PI;
            $magic = sin($radlat);
            $magic = 1 - $ee * $magic * $magic;
            $sqrtmagic = sqrt($magic);
            $dlat = ($dlat * 180.0) / (($a * (1 - $ee)) / ($magic * $sqrtmagic) * $PI);
            $dlng = ($dlng * 180.0) / ($a / $sqrtmagic * cos($radlat) * $PI);
            $mglat = $lat + $dlat;
            $mglng = $lng + $dlng;
            return self::output($mglat, $mglng);
        }
    }

    public static function output($lat, $lng)
    {
        return [
            'lat' => round($lat, 6),
            'lng' => round($lng, 6),
        ];
    }

    public static function transformlat($x, $y)
    {
        $PI = 3.1415926535897932384626;
        $ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * $PI) + 20.0 * sin(2.0 * $x * $PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($y * $PI) + 40.0 * sin($y / 3.0 * $PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($y / 12.0 * $PI) + 320 * sin($y * $PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }

    public static function transformlng($x, $y)
    {
        $PI = 3.1415926535897932384626;
        $ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * $PI) + 20.0 * sin(2.0 * $x * $PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($x * $PI) + 40.0 * sin($x / 3.0 * $PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($x / 12.0 * $PI) + 300.0 * sin($x / 30.0 * $PI)) * 2.0 / 3.0;
        return $ret;
    }

    public static function out_of_china($lng, $lat)
    {
        if ($lng < 72.004 || $lng > 137.8347) {
            return true;
        } elseif ($lat < 0.8293 || $lat > 55.8271) {
            return true;
        }
        return false;
    }
}
