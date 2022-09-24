<?php
/**
 * Author: Jayin Taung <tonjayin@gmail.com>
 */

namespace Tests;

use Jayin\AddressParser\AddressParser;
use PHPUnit\Framework\TestCase;

class MainTest extends TestCase
{
    function test_address_parse()
    {
        // 测试
        $test = array(
            '北京市东城区宵云路36号国航大厦一层',
            '甘肃省东乡族自治县布楞沟村1号',
            '成都市双流区宵云路36号国航大厦一层',
            '内蒙古自治区乌兰察布市公安局交警支队车管所',
            '长春市朝阳区宵云路36号国航大厦一层',
            '成都市高新区天府软件园B区科技大楼',
            '双流区郑通路社保局区52050号',
            '岳市岳阳楼区南湖求索路碧灏花园A座1101',
            '四川省 凉山州美姑县东方网肖小区18号院',
            '重庆攀枝花市东区机场路3中学校',
            '渝北区渝北中学51200街道地址',
            '13566892356天津天津市红桥区水木天成1区临湾路9-3-1101',
            '苏州市昆山市青阳北路时代名苑20号311室',
            '崇州市崇阳镇金鸡万人小区兴盛路105-107',
            '四平市双辽市辽北街道',
            '梧州市奥奇丽路10-9号A幢地层（礼迅贸易有限公司）卢丽丽',
            '江西省抚州市东乡区孝岗镇恒安东路125号1栋3单元502室 13511112222 吴刚',
            '清远市清城区石角镇美林湖大东路口佰仹公司 郑万顺 15345785872',
            '广东省广州市黄埔区思成路35号',
        );

        foreach ($test as $v) {
            $r = AddressParser::smart($v, true);
            print_r($r);
            $this->assertArrayHasKey('province', $r);
            $this->assertArrayHasKey('city', $r);
        }
    }
}