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
use Ksdev\NBPCurrencyConverter\CurrencyConverter;
use Ksdev\NBPCurrencyConverter\ExRatesTableFinder;
use Ksdev\NBPCurrencyConverter\ExRatesTableFactory;
use GuzzleHttp\Client;

$converter = new CurrencyConverter(
    new ExRatesTableFinder(
        new Client(),
        new ExRatesTableFactory(),
        'path/to/cache/folder'
    )
);
try {
    $result = $converter->convert('123.4567', 'PLN', 'USD');
    $avgExRates = $converter->averageExchangeRates();
}
catch (Exception $e) {
    //
}
```

###### $result

``` php
array(
    'publication_date' => '2015-07-03',
    'amount'           => '32.7246',
    'currency'         => 'USD'
);
```

###### $avgExRates

```php
array(
    'numer_tabeli'    => '127/A/NBP/2015',
    'data_publikacji' => '2015-07-03',
    'waluty'          =>
        array(
            'PLN' =>
                array(
                    'nazwa_waluty' => 'złoty polski',
                    'przelicznik'  => '1',
                    'kurs_sredni'  => '1',
                ),
            'THB' =>
                array(
                    'nazwa_waluty' => 'bat (Tajlandia)',
                    'przelicznik'  => '1',
                    'kurs_sredni'  => '0,1117',
                ),
            'USD' =>
                array(
                    'nazwa_waluty' => 'dolar amerykański',
                    'przelicznik'  => '1',
                    'kurs_sredni'  => '3,7726',
                ),
            'AUD' =>
                array(...)
        )
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
