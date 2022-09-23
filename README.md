# PHP Package
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

AddressParser::smart($v, true);
```


## License

MIT
