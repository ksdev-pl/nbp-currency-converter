# NBP currency converter

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]

Retrieve average currency exchange rates from the NBP website and convert an amount from one currency to another.

## Install

Via Composer

``` bash
$ composer require ksdev/nbp-currency-converter
```

## Usage

``` php
$converter = new Ksdev\NBPCurrencyConverter(new GuzzleHttp\Client(), 'path/to/cache/folder');
try {
    $result = $converter->convert('123.4567', 'PLN', 'USD')
}
catch (Exception $e) {
    //
}
```

## Result structure

``` php
array(
    'publication_date' => '2015-07-03',
    'amount'           => '32.7246',
    'currency'         => 'USD'
);
```

## Testing

``` bash
$ composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/ksdev/nbp-currency-converter.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/ksdev-pl/nbp-currency-converter/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/ksdev-pl/nbp-currency-converter.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/ksdev-pl/nbp-currency-converter.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/ksdev/nbp-currency-converter
[link-travis]: https://travis-ci.org/ksdev-pl/nbp-currency-converter
[link-scrutinizer]: https://scrutinizer-ci.com/g/ksdev-pl/nbp-currency-converter/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/ksdev-pl/nbp-currency-converter
