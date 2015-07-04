<?php

namespace Ksdev\NBPCurrencyConverter;

class NBPCurrencyConverter
{
    const CACHE_FILENAME = 'exchange_rates.xml';
    const NBP_XML_URL = 'http://www.nbp.pl/kursy/xml/LastA.xml';

    /** @var \GuzzleHttp\Client */
    private $guzzle;

    /** @var string */
    private $cachePath;

    /** @var int */
    private $cacheTime;

    /**
     * @param \GuzzleHttp\Client $guzzle
     * @param string $cachePath Path to an existing folder where the cache file will be stored.
     *     If cacheTime == 0 then cachePath is ignored
     * @param int $cacheTime Lifetime of the cache file in minutes; pass 0 to disable caching
     *
     * @throws \Exception
     */
    public function __construct(\GuzzleHttp\Client $guzzle, $cachePath, $cacheTime = 360)
    {
        $this->guzzle = $guzzle;
        $this->cacheTime = (int)$cacheTime;
        if ($this->cacheTime && !is_dir($cachePath)) {
            throw new \Exception('Invalid cache path');
        }
        $this->cachePath = rtrim((string)$cachePath, '/') . '/';
    }

    /**
     * Get the average exchange rates
     *
     * @return array
     *
     * @throws \Exception
     */
    public function averageExchangeRates()
    {
        if ($this->cacheTime && ($cachedXml = $this->cachedXml())) {
            $rawContent = $cachedXml;
        } else {
            $rawContent = $this->downloadedXml();
        }
        return $this->parseXml($rawContent);
    }

    /**
     * Convert amount from one currency to another
     *
     * @param string $fromAmount Amount with four digits after decimal point, e.g. '123.0000'
     * @param string $fromCurrency E.g. 'USD' or 'EUR'
     * @param string $toCurrency E.g. 'USD' or 'EUR'
     *
     * @return array
     *
     * @throws \Exception
     */
    public function convert($fromAmount, $fromCurrency, $toCurrency)
    {
        if (!preg_match('/^\d+\.(\d{4})$/', $fromAmount)) {
            throw new \Exception('Invalid format of amount');
        }

        $rates = $this->averageExchangeRates();

        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);
        if (!isset($rates['waluty'][$fromCurrency]) || !isset($rates['waluty'][$toCurrency])) {
            throw new \Exception('Invalid currency code');
        }

        $fromMultiplier = str_replace(',', '.', $rates['waluty'][$fromCurrency]['przelicznik']);
        $fromAverageRate = str_replace(',', '.', $rates['waluty'][$fromCurrency]['kurs_sredni']);
        $toMultiplier = str_replace(',', '.', $rates['waluty'][$toCurrency]['przelicznik']);
        $toAverageRate = str_replace(',', '.', $rates['waluty'][$toCurrency]['kurs_sredni']);

        bcscale(6);
        $plnAmount = bcdiv(bcmul($fromAmount, $fromAverageRate), $fromMultiplier);
        $resultAmount = bcdiv(bcmul($plnAmount, $toMultiplier), $toAverageRate);
        $roundedResult = BCMathHelper::bcround($resultAmount, 4);

        return [
            'publication_date' => $rates['data_publikacji'],
            'result'           => [
                'amount'   => $roundedResult,
                'currency' => $toCurrency
            ]
        ];
    }

    /**
     * Get the raw xml content from a cache file
     *
     * @return string|int Content string or 0 if the file doesn't exist or is obsolete
     */
    private function cachedXml()
    {
        try {
            $cache = new \SplFileObject($this->cachePath . self::CACHE_FILENAME);
        } catch (\Exception $e) {
            $cache = null;
        }
        if (($cache instanceof \SplFileObject) && ($cache->getMTime() > strtotime("-{$this->cacheTime} minutes"))) {
            return $cache->fread($cache->getSize());
        } else {
            return 0;
        }
    }

    /**
     * Get the raw xml content from the NBP api
     *
     * @return string
     *
     * @throws \Exception
     */
    private function downloadedXml()
    {
        $response = $this->guzzle->get(self::NBP_XML_URL);
        if ($response->getStatusCode() === 200) {
            $rawContent = (string)$response->getBody();
            if ($this->cacheTime) {
                file_put_contents($this->cachePath . self::CACHE_FILENAME, $rawContent);
            }
            return $rawContent;
        } else {
            throw new \Exception(
                "Invalid response status code: {$response->getStatusCode()} {$response->getReasonPhrase()}"
            );
        }
    }

    /**
     * Transform the raw xml content into an array
     *
     * @param string $rawContent
     *
     * @return array
     *
     * @throws \Exception
     */
    private function parseXml($rawContent)
    {
        $xml = new \SimpleXMLElement($rawContent);
        if (empty($xml->numer_tabeli) || empty($xml->data_publikacji) || empty($xml->pozycja)) {
            throw new \Exception('Invalid xml response content');
        }
        $rates = [
            'numer_tabeli'    => (string)$xml->numer_tabeli,
            'data_publikacji' => (string)$xml->data_publikacji,
            'waluty'          => [
                'PLN' => [
                    'nazwa_waluty' => 'złoty polski',
                    'przelicznik'  => '1',
                    'kurs_sredni'  => '1'
                ]
            ]
        ];
        foreach ($xml->pozycja as $pozycja) {
            $rates['waluty'] += [
                (string)$pozycja->kod_waluty => [
                    'nazwa_waluty' => (string)$pozycja->nazwa_waluty,
                    'przelicznik'  => (string)$pozycja->przelicznik,
                    'kurs_sredni'  => (string)$pozycja->kurs_sredni
                ]
            ];
        }
        return $rates;
    }
}
