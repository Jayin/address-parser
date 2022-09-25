<?php
/**
 * Author: Jayin Taung <tonjayin@gmail.com>
 */

namespace Jayin\AddressParser;

class AddressParser
{
    /**
     * 解析
     * @param string $string 地址
     * @param bool $user 是否解析用户信息
     * @return array
     */
    public static function smart($string, $user = true)
    {
        if ($user) {
            $decompose = self::decompose($string);
            $re = $decompose;
        } else {
            $re['addr'] = $string;
        }

        $fuzz = self::fuzz($re['addr']);
        $parse = self::parse($fuzz['province'], $fuzz['city'], $fuzz['region']);

        $re['province'] = $parse['province'];
        $re['city'] = $parse['city'];
        $re['region'] = $parse['region'];

        $re['street'] = ($fuzz['street']) ?: '';
        $re['street'] = str_replace([$re['region'], $re['city'], $re['province']], ['', '', ''], $re['street']);

        return $re;
    }

    /**
     * 分离手机号(座机)，身份证号，姓名等用户信息
     * @param $string
     * @return array
     */
    public static function decompose($string)
    {
        $compose = array();
        // 过滤掉收货地址中的常用说明字符，排除干扰词
        $search = array('收货地址', '详细地址', '地址', '收货人', '收件人', '收货', '所在地区', '邮编', '电话', '手机号码', '身份证号码', '身份证号', '身份证', '：', ':', '；', ';', '，', ',', '。');
        $replace = array(' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ');
        $string = str_replace($search, $replace, $string);
        // 多个空白字符(包括空格\r\n\t)换成一个空格
        $string = preg_replace('/\s{1,}/', ' ', $string);
        // 去除手机号码中的短横线 如0136-3333-6666 主要针对苹果手机
        $string = preg_replace('/0-|0?(\d{3})-(\d{4})-(\d{4})/', '$1$2$3', $string);
        // 提取中国境内身份证号码
        preg_match('/\d{18}|\d{17}X/i', $string, $match);
        if ($match && $match[0]) {
            $compose['idn'] = strtoupper($match[0]);
            $string = str_replace($match[0], '', $string);
        }
        // 提取11位手机号码或者7位以上座机号
        preg_match('/\d{7,11}|\d{3,4}-\d{6,8}/', $string, $match);
        if ($match && $match[0]) {
            $compose['mobile'] = $match[0];
            $string = str_replace($match[0], '', $string);
        }
        // 提取6位邮编
        preg_match('/\d{6}/', $string, $match);
        if ($match && $match[0]) {
            $compose['postcode'] = $match[0];
            $string = str_replace($match[0], '', $string);
        }
        // 按照空格切分 长度长的为地址 短的为姓名 因为不是基于自然语言分析，所以采取统计学上高概率的方案
        $string = trim(preg_replace('/ {2,}/', ' ', $string));

        $split_arr = explode(' ', $string);
        if (count($split_arr) > 1) {
            $compose['name'] = $split_arr[0];
            foreach ($split_arr as $value) {
                if (strlen($value) < strlen($compose['name'])) {
                    $compose['name'] = $value;
                }
            }
            $string = trim(str_replace($compose['name'], '', $string));
        }

        $compose['addr'] = $string;

        return $compose;
    }

    /**
     * 根据统计规律分析出二三级地址
     * @param $addr
     * @return array
     */
    public static function fuzz($addr)
    {
        $addr_origin = $addr;
        $addr = str_replace([' ', ','], ['', ''], $addr);
        $addr = str_replace('自治区', '省', $addr);
        $addr = str_replace('自治州', '州', $addr);

        $addr = str_replace('小区', '', $addr);
        $addr = str_replace('校区', '', $addr);

        $province = '';
        $city = '';
        $region = '';
        $street = '';

        if (mb_strpos($addr, '县') !== false && mb_strpos($addr, '县') < floor((mb_strlen($addr) / 3) * 2) || (mb_strpos($addr, '区') !== false && mb_strpos($addr, '区') < floor((mb_strlen($addr) / 3) * 2)) || mb_strpos($addr, '旗') !== false && mb_strpos($addr, '旗') < floor((mb_strlen($addr) / 3) * 2)) {

            if (mb_strstr($addr, '旗')) {
                $deep3_keyword_pos = mb_strpos($addr, '旗');
                $region = mb_substr($addr, $deep3_keyword_pos - 1, 2);
            }
            if (mb_strstr($addr, '区')) {
                $deep3_keyword_pos = mb_strpos($addr, '区');

                if (mb_strstr($addr, '市')) {
                    $city_pos = mb_strpos($addr, '市');
                    $zone_pos = mb_strpos($addr, '区');
                    $region = mb_substr($addr, $city_pos + 1, $zone_pos - $city_pos);
                } else {
                    $region = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                }
            }
            if (mb_strstr($addr, '县')) {
                $deep3_keyword_pos = mb_strpos($addr, '县');

                if (mb_strstr($addr, '市')) {
                    $city_pos = mb_strpos($addr, '市');
                    $zone_pos = mb_strpos($addr, '县');
                    $region = mb_substr($addr, $city_pos + 1, $zone_pos - $city_pos);
                } else {

                    if (mb_strstr($addr, '自治县')) {
                        $region = mb_substr($addr, $deep3_keyword_pos - 6, 7);
                        if (in_array(mb_substr($region, 0, 1), ['省', '市', '州'])) {
                            $region = mb_substr($region, 1);
                        }
                    } else {
                        $region = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    }
                }
            }
            $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
        } else {
            if (mb_strripos($addr, '市')) {

                if (mb_substr_count($addr, '市') == 1) {
                    $deep3_keyword_pos = mb_strripos($addr, '市');
                    $region = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
                } else if (mb_substr_count($addr, '市') >= 2) {
                    $deep3_keyword_pos = mb_strripos($addr, '市');
                    $region = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
                }
            } else {
                $region = '';
                $street = $addr;
            }
        }

        if (mb_strpos($addr, '市') || mb_strstr($addr, '盟') || mb_strstr($addr, '州')) {
            if ($tmp_pos = mb_strpos($addr, '市')) {
                $city = mb_substr($addr, $tmp_pos - 2, 3);
            } else if ($tmp_pos = mb_strpos($addr, '盟')) {
                $city = mb_substr($addr, $tmp_pos - 2, 3);
            } else if ($tmp_pos = mb_strpos($addr, '州')) {
                if ($tmp_pos = mb_strpos($addr, '自治州')) {
                    $city = mb_substr($addr, $tmp_pos - 4, 5);
                } else {
                    $city = mb_substr($addr, $tmp_pos - 2, 3);
                }
            }
        } else {
            $city = '';
        }

        return array(
            'province' => $province,
            'city' => $city,
            'region' => $region,
            'street' => $street,
        );
    }

    /**
     * 智能解析出省市区+街道地址
     * @param $a1
     * @param $a2
     * @param $a3
     * @return array
     */
    public static function parse($a1, $a2, $a3)
    {
        $a3_data = require __DIR__ . DIRECTORY_SEPARATOR . 'data/region.php';
        $a2_data = require __DIR__ . DIRECTORY_SEPARATOR . 'data/city.php';
        $a1_data = require __DIR__ . DIRECTORY_SEPARATOR . 'data/province.php';

        $r = array();

        if ($a3 != '') {
            $area3_matches = array();
            foreach ($a3_data as $id => $v) {
                if (mb_strpos($v['name'], $a3) !== false) {
                    $area3_matches[$id] = $v;
                }
            }

            if ($area3_matches && count($area3_matches) > 1) {
                if ($a2) {
                    $area2_matches = [];
                    foreach ($a2_data as $id => $v) {
                        if (mb_strpos($v['name'], $a2) !== false) {
                            $area2_matches[$id] = $v;
                        }
                    }

                    if ($area2_matches) {
                        foreach ($area3_matches as $id => $v) {

                            if (isset($area2_matches[$v['pid']])) {
                                $r['city'] = $area2_matches[$v['pid']]['name'];
                                $r['region'] = $v['name'];
                                $sheng_id = $area2_matches[$v['pid']]['pid'];
                                $r['province'] = $a1_data[$sheng_id]['name'];
                            }
                        }
                    }
                } else {
                    $r['province'] = '';
                    $r['city'] = '';
                    $r['region'] = $a3;
                }
            } else if ($area3_matches && count($area3_matches) == 1) {
                foreach ($area3_matches as $id => $v) {
                    $city_id = $v['pid'];
                    $r['region'] = $v['name'];
                }
                $city = $a2_data[$city_id];
                $province = $a1_data[$city['pid']];

                $r['province'] = $province['name'];
                $r['city'] = $city['name'];
            } else if (empty($area3_matches) && $a2 == $a3) {
                $sheng_id = '';
                foreach ($a2_data as $id => $v) {
                    if (mb_strpos($v['name'], $a2) !== false) {
                        $area2_matches[$id] = $v;
                        $sheng_id = $v['pid'];
                        $r['city'] = $v['name'];
                    }
                }

                $r['province'] = $a1_data[$sheng_id]['name'] ?? '';
                $r['region'] = '';
            }
        }

        return $r;
    }
}