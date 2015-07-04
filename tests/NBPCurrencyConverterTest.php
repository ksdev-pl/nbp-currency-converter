<?php

namespace Ksdev\NBPCurrencyConverter\Test;

use Ksdev\NBPCurrencyConverter\NBPCurrencyConverter;
use Mockery;

class NBPCurrencyConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $rawContent = file_get_contents(__DIR__ . '/exchange_rates.xml');

        $guzzleMock = Mockery::mock('GuzzleHttp\Client');
        $guzzleMock->shouldReceive('get')
            ->andReturn(Mockery::self());
        $guzzleMock->shouldReceive('getStatusCode')
            ->andReturn(200);
        $guzzleMock->shouldReceive('getBody')
            ->andReturn($rawContent);

        $converter = new NBPCurrencyConverter($guzzleMock, null, 0);

        $this->assertEquals('123.4567', $converter->convert('123.4567', 'PLN', 'PLN')['result']['amount']);
        $this->assertEquals('32.7246', $converter->convert('123.4567', 'PLN', 'USD')['result']['amount']);
        $this->assertEquals('465.7527', $converter->convert('123.4567', 'USD', 'PLN')['result']['amount']);
        $this->assertEquals('9265.0432', $converter->convert('123.4567', 'PLN', 'HUF')['result']['amount']);
        $this->assertEquals('1.6451', $converter->convert('123.4567', 'HUF', 'PLN')['result']['amount']);
        $this->assertEquals('137.1028', $converter->convert('123.4567', 'EUR', 'USD')['result']['amount']);
        $this->assertEquals('111.1688', $converter->convert('123.4567', 'USD', 'EUR')['result']['amount']);
        $this->assertEquals('34953.3018', $converter->convert('123.4567', 'USD', 'HUF')['result']['amount']);
        $this->assertEquals('0.4361', $converter->convert('123.4567', 'HUF', 'USD')['result']['amount']);
        $this->assertEquals('284.0662', $converter->convert('123.4567', 'JPY', 'HUF')['result']['amount']);
        $this->assertEquals('53.6549', $converter->convert('123.4567', 'HUF', 'JPY')['result']['amount']);
        $this->assertEquals('123.4567', $converter->convert('123.4567', 'JPY', 'JPY')['result']['amount']);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
