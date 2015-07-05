<?php

namespace Ksdev\NBPCurrencyConverter;

class ExRatesDayTable
{
    /** @var string */
    public $rawContent;

    /** @var array */
    public $parsedContent;

    /**
     * @param string $rawContent Raw xml content
     *
     * @throws \Exception
     */
    public function __construct($rawContent)
    {
        $this->rawContent = $rawContent;
        $this->parsedContent = $this->parseXml($rawContent);
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
