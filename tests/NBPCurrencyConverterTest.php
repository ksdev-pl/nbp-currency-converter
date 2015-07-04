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

        $this->assertEquals('123.4567', $converter->convert('123.4567', 'PLN', 'PLN')['amount']);
        $this->assertEquals('32.7246', $converter->convert('123.4567', 'PLN', 'USD')['amount']);
        $this->assertEquals('465.7527', $converter->convert('123.4567', 'USD', 'PLN')['amount']);
        $this->assertEquals('9265.0432', $converter->convert('123.4567', 'PLN', 'HUF')['amount']);
        $this->assertEquals('1.6451', $converter->convert('123.4567', 'HUF', 'PLN')['amount']);
        $this->assertEquals('137.1028', $converter->convert('123.4567', 'EUR', 'USD')['amount']);
        $this->assertEquals('111.1688', $converter->convert('123.4567', 'USD', 'EUR')['amount']);
        $this->assertEquals('34953.3018', $converter->convert('123.4567', 'USD', 'HUF')['amount']);
        $this->assertEquals('0.4361', $converter->convert('123.4567', 'HUF', 'USD')['amount']);
        $this->assertEquals('284.0662', $converter->convert('123.4567', 'JPY', 'HUF')['amount']);
        $this->assertEquals('53.6549', $converter->convert('123.4567', 'HUF', 'JPY')['amount']);
        $this->assertEquals('123.4567', $converter->convert('123.4567', 'JPY', 'JPY')['amount']);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
