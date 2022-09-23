# Address Parser
> 本项目参考了[pupuk/address](https://github.com/pupuk/address)

收货地址智能解析

1、把字符串解析成姓名、收货电话、邮编、身份证号、收货地址
2、把收货地址解析成省、市、区县、街道地址


## Install

```shell
composer require jayin/address-parser -vvv
```

## Usage

```php
use Jayin\AddressParser\AddressParser;

AddressParser::smart('江西省抚州市东乡区孝岗镇恒安东路125号1栋3单元502室 13511112222 吴刚', true);
```
返回
```json
(
    [mobile] => 13511112222
    [name] => 吴刚
    [addr] => 江西省抚州市东乡区孝岗镇恒安东路125号1栋3单元502室
    [province] => 江西省
    [city] => 抚州市
    [region] => 东乡区
    [street] => 孝岗镇恒安东路125号1栋3单元502室
)
```


## License

MIT
