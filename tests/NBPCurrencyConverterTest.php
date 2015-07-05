<?php

namespace Ksdev\NBPCurrencyConverter\Test;

use Ksdev\NBPCurrencyConverter\NBPCurrencyConverter;
use Mockery;

class NBPCurrencyConverterTest extends \PHPUnit_Framework_TestCase
{
    public function guzzleMock()
    {
        $rawContent = file_get_contents(__DIR__ . '/test_rates.xml');
        $guzzleMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('get')
            ->andReturn(Mockery::self())
            ->shouldReceive('getStatusCode')
            ->andReturn(200)
            ->shouldReceive('getBody')
            ->andReturn($rawContent)
            ->mock();

        return $guzzleMock;
    }

    public function testConvert()
    {
        $converter = new NBPCurrencyConverter($this->guzzleMock(), null, 0);

        $this->assertEquals('0.2651', $converter->convert('1.0000', 'PLN', 'USD')['amount']);
        $this->assertEquals('3.7726', $converter->convert('1.0000', 'USD', 'PLN')['amount']);
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
        $this->assertEquals('53654981.1634', $converter->convert('123456789.6789', 'HUF', 'JPY')['amount']);
        $this->assertEquals('284066429.3850', $converter->convert('123456789.6789', 'JPY', 'HUF')['amount']);
        $this->assertEquals('32724590.3830', $converter->convert('123456789.6789', 'PLN', 'USD')['amount']);
        $this->assertEquals('465753084.7426', $converter->convert('123456789.6789', 'USD', 'PLN')['amount']);
    }

    public function testInvalidFormatOfAmount()
    {
        $converter = new NBPCurrencyConverter($this->guzzleMock(), null, 0);

        $numExceptions = 0;
        try {
            $converter->convert('123.45', 'PLN', 'USD');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid format of amount', $e->getMessage());
            $numExceptions++;
        }
        try {
            $converter->convert('123,4567', 'PLN', 'USD');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid format of amount', $e->getMessage());
            $numExceptions++;
        }
        try {
            $converter->convert('.4567', 'PLN', 'USD');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid format of amount', $e->getMessage());
            $numExceptions++;
        }
        try {
            $converter->convert('1234567', 'PLN', 'USD');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid format of amount', $e->getMessage());
            $numExceptions++;
        }

        $this->assertEquals(4, $numExceptions);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid currency code
     */
    public function testInvalidCurrencyCode()
    {
        $converter = new NBPCurrencyConverter($this->guzzleMock(), null, 0);
        $converter->convert('123.4567', 'ABC', 'USD');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid cache path
     */
    public function testInvalidCachePath()
    {
        new NBPCurrencyConverter($this->guzzleMock(), '/this/folder/doesnotexist');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage String could not be parsed as XML
     */
    public function testInvalidXml()
    {
        $guzzleMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('get')
            ->andReturn(Mockery::self())
            ->shouldReceive('getStatusCode')
            ->andReturn(200)
            ->shouldReceive('getBody')
            ->andReturn('Hello! I\'m XML')
            ->mock();

        $converter = new NBPCurrencyConverter($guzzleMock, null, 0);
        $converter->convert('123.4567', 'PLN', 'USD');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid response status code: 500 A good reason
     */
    public function testInvalidResponseStatusCode()
    {
        $guzzleMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('get')
            ->andReturn(Mockery::self())
            ->shouldReceive('getStatusCode')
            ->andReturn(500)
            ->shouldReceive('getReasonPhrase')
            ->andReturn('A good reason')
            ->mock();

        $converter = new NBPCurrencyConverter($guzzleMock, null, 0);
        $converter->convert('123.4567', 'PLN', 'USD');
    }

    public function testCache()
    {
        $rawContent = file_get_contents(__DIR__ . '/test_rates.xml');
        $guzzleMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('get')
            ->once()
            ->andReturn(Mockery::self())
            ->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200)
            ->shouldReceive('getBody')
            ->once()
            ->andReturn($rawContent)
            ->mock();

        $converter = new NBPCurrencyConverter($guzzleMock, __DIR__);
        $converter->convert('123.4567', 'PLN', 'USD');

        $pathToFile = __DIR__ . '/exchange_rates.xml';
        $this->assertFileExists($pathToFile);

        $converter->convert('123.4567', 'PLN', 'USD');

        unlink($pathToFile);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
