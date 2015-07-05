<?php

namespace Ksdev\NBPCurrencyConverter\Test;

use Ksdev\NBPCurrencyConverter\CurrencyConverter;
use Ksdev\NBPCurrencyConverter\ExRatesTableFactory;
use Ksdev\NBPCurrencyConverter\ExRatesTableFinder;
use Mockery;

class CurrencyConverterTest extends \PHPUnit_Framework_TestCase
{
    public function guzzleMock()
    {
        $ratesContent = file_get_contents(__DIR__ . '/test_rates.xml');
        $dirContent = file_get_contents(__DIR__ . '/test_dir.txt');
        $ratesMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('getStatusCode')
            ->andReturn(200)
            ->shouldReceive('getBody')
            ->andReturn($ratesContent)
            ->mock();
        $dirMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('getStatusCode')
            ->andReturn(200)
            ->shouldReceive('getBody')
            ->andReturn($dirContent)
            ->mock();
        $guzzleMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('get')
            ->with('http://www.nbp.pl/kursy/xml/a127z150703.xml')
            ->andReturn($ratesMock)
            ->shouldReceive('get')
            ->with('http://www.nbp.pl/kursy/xml/dir.txt')
            ->andReturn($dirMock)
            ->mock();

        return $guzzleMock;
    }

    public function testConvert()
    {
        $converter = new CurrencyConverter(new ExRatesTableFinder($this->guzzleMock(), new ExRatesTableFactory()));
        $pubDate = new \DateTime('2015-07-03');

        $this->assertEquals('0.2651', $converter->convert('1.0000', 'PLN', 'USD', $pubDate)['amount']);
        $this->assertEquals('3.7726', $converter->convert('1.0000', 'USD', 'PLN', $pubDate)['amount']);
        $this->assertEquals('123.4567', $converter->convert('123.4567', 'PLN', 'PLN', $pubDate)['amount']);
        $this->assertEquals('32.7246', $converter->convert('123.4567', 'PLN', 'USD', $pubDate)['amount']);
        $this->assertEquals('465.7527', $converter->convert('123.4567', 'USD', 'PLN', $pubDate)['amount']);
        $this->assertEquals('9265.0432', $converter->convert('123.4567', 'PLN', 'HUF', $pubDate)['amount']);
        $this->assertEquals('1.6451', $converter->convert('123.4567', 'HUF', 'PLN', $pubDate)['amount']);
        $this->assertEquals('137.1028', $converter->convert('123.4567', 'EUR', 'USD', $pubDate)['amount']);
        $this->assertEquals('111.1688', $converter->convert('123.4567', 'USD', 'EUR', $pubDate)['amount']);
        $this->assertEquals('34953.3018', $converter->convert('123.4567', 'USD', 'HUF', $pubDate)['amount']);
        $this->assertEquals('0.4361', $converter->convert('123.4567', 'HUF', 'USD', $pubDate)['amount']);
        $this->assertEquals('284.0662', $converter->convert('123.4567', 'JPY', 'HUF', $pubDate)['amount']);
        $this->assertEquals('53.6549', $converter->convert('123.4567', 'HUF', 'JPY', $pubDate)['amount']);
        $this->assertEquals('123.4567', $converter->convert('123.4567', 'JPY', 'JPY', $pubDate)['amount']);
        $this->assertEquals('53654981.1634', $converter->convert('123456789.6789', 'HUF', 'JPY', $pubDate)['amount']);
        $this->assertEquals('284066429.3850', $converter->convert('123456789.6789', 'JPY', 'HUF', $pubDate)['amount']);
        $this->assertEquals('32724590.3830', $converter->convert('123456789.6789', 'PLN', 'USD', $pubDate)['amount']);
        $this->assertEquals('465753084.7426', $converter->convert('123456789.6789', 'USD', 'PLN', $pubDate)['amount']);
    }

    public function testInvalidPublicationDate()
    {
        $converter = new CurrencyConverter(new ExRatesTableFinder($this->guzzleMock(), new ExRatesTableFactory()));

        $numExceptions = 0;
        try {
            $pubDate = new \DateTime('2002-01-01');
            $converter->convert('123.4567', 'PLN', 'USD', $pubDate);
        } catch (\Exception $e) {
            $this->assertEquals('Invalid publication date', $e->getMessage());
            $numExceptions++;
        }
        try {
            $pubDate = new \DateTime('2115-01-01');
            $converter->convert('123.4567', 'PLN', 'USD', $pubDate);
        } catch (\Exception $e) {
            $this->assertEquals('Invalid publication date', $e->getMessage());
            $numExceptions++;
        }

        $this->assertEquals(2, $numExceptions);
    }

    public function testInvalidFormatOfAmount()
    {
        $converter = new CurrencyConverter(new ExRatesTableFinder($this->guzzleMock(), new ExRatesTableFactory()));
        $pubDate = new \DateTime('2015-07-03');

        $numExceptions = 0;
        try {
            $converter->convert('123.45', 'PLN', 'USD', $pubDate);
        } catch (\Exception $e) {
            $this->assertEquals('Invalid format of amount', $e->getMessage());
            $numExceptions++;
        }
        try {
            $converter->convert('123,4567', 'PLN', 'USD', $pubDate);
        } catch (\Exception $e) {
            $this->assertEquals('Invalid format of amount', $e->getMessage());
            $numExceptions++;
        }
        try {
            $converter->convert('.4567', 'PLN', 'USD', $pubDate);
        } catch (\Exception $e) {
            $this->assertEquals('Invalid format of amount', $e->getMessage());
            $numExceptions++;
        }
        try {
            $converter->convert('1234567', 'PLN', 'USD', $pubDate);
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
        $converter = new CurrencyConverter(new ExRatesTableFinder($this->guzzleMock(), new ExRatesTableFactory()));
        $pubDate = new \DateTime('2015-07-03');

        $converter->convert('123.4567', 'ABC', 'USD', $pubDate);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid cache path
     */
    public function testInvalidCachePath()
    {
        new CurrencyConverter(new ExRatesTableFinder($this->guzzleMock(), new ExRatesTableFactory(), '/this/folder/doesnotexist'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage String could not be parsed as XML
     */
    public function testInvalidXml()
    {
        $dirContent = file_get_contents(__DIR__ . '/test_dir.txt');
        $ratesMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('getStatusCode')
            ->andReturn(200)
            ->shouldReceive('getBody')
            ->andReturn('Hello! I\'m XML')
            ->mock();
        $dirMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('getStatusCode')
            ->andReturn(200)
            ->shouldReceive('getBody')
            ->andReturn($dirContent)
            ->mock();
        $guzzleMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('get')
            ->with('http://www.nbp.pl/kursy/xml/a127z150703.xml')
            ->andReturn($ratesMock)
            ->shouldReceive('get')
            ->with('http://www.nbp.pl/kursy/xml/dir.txt')
            ->andReturn($dirMock)
            ->mock();

        $converter = new CurrencyConverter(new ExRatesTableFinder($guzzleMock, new ExRatesTableFactory()));
        $pubDate = new \DateTime('2015-07-03');
        $converter->convert('123.4567', 'PLN', 'USD', $pubDate);
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

        $converter = new CurrencyConverter(new ExRatesTableFinder($guzzleMock, new ExRatesTableFactory()));
        $converter->convert('123.4567', 'PLN', 'USD');
    }

    public function testCache()
    {
        $ratesContent = file_get_contents(__DIR__ . '/test_rates.xml');
        $dirContent = file_get_contents(__DIR__ . '/test_dir.txt');
        $ratesMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200)
            ->shouldReceive('getBody')
            ->once()
            ->andReturn($ratesContent)
            ->mock();
        $dirMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200)
            ->shouldReceive('getBody')
            ->once()
            ->andReturn($dirContent)
            ->mock();
        $guzzleMock = Mockery::mock('GuzzleHttp\Client')
            ->shouldReceive('get')
            ->once()
            ->with('http://www.nbp.pl/kursy/xml/a127z150703.xml')
            ->andReturn($ratesMock)
            ->shouldReceive('get')
            ->once()
            ->with('http://www.nbp.pl/kursy/xml/dir.txt')
            ->andReturn($dirMock)
            ->mock();

        $converter = new CurrencyConverter(new ExRatesTableFinder($guzzleMock, new ExRatesTableFactory(), __DIR__));
        $pubDate = new \DateTime('2015-07-03');
        $converter->convert('123.4567', 'PLN', 'USD', $pubDate);

        $pathToFile = __DIR__ . '/a127z150703.xml';
        $this->assertFileExists($pathToFile);

        $converter->convert('123.4567', 'PLN', 'USD', $pubDate);

        unlink($pathToFile);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
