<?php

namespace Ksdev\NBPCurrencyConverter;

class CurrencyConverter
{
    private $ratesTableFinder;

    public function __construct(ExRatesTableFinder $ratesTableFinder)
    {
        $this->ratesTableFinder = $ratesTableFinder;
    }

    /**
     * Get the average exchange rates
     *
     * @param \DateTime $pubDate Optional rates table publication date
     *
     * @return array
     *
     * @throws \Exception
     */
    public function averageExchangeRates(\DateTime $pubDate = null)
    {
        $ratesTable = $this->ratesTableFinder->getExRatesTable($pubDate);
        return $ratesTable->parsedContent;
    }

    /**
     * Convert amount from one currency to another
     *
     * @param string $fromAmount Amount with four digits after decimal point, e.g. '123.0000'
     * @param string $fromCurrency E.g. 'USD' or 'EUR'
     * @param string $toCurrency E.g. 'USD' or 'EUR'
     * @param \DateTime $pubDate Optional rates table publication date
     *
     * @return array
     *
     * @throws \Exception
     */
    public function convert($fromAmount, $fromCurrency, $toCurrency, \DateTime $pubDate = null)
    {
        if (!preg_match('/^\d+\.(\d{4})$/', $fromAmount)) {
            throw new \Exception('Invalid format of amount');
        }

        $rates = $this->averageExchangeRates($pubDate);

        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);
        if (!isset($rates['waluty'][$fromCurrency]) || !isset($rates['waluty'][$toCurrency])) {
            throw new \Exception('Invalid currency code');
        }

        $fromMultiplier = str_replace(',', '.', $rates['waluty'][$fromCurrency]['przelicznik']);
        $fromAverageRate = str_replace(',', '.', $rates['waluty'][$fromCurrency]['kurs_sredni']);
        $toMultiplier = str_replace(',', '.', $rates['waluty'][$toCurrency]['przelicznik']);
        $toAverageRate = str_replace(',', '.', $rates['waluty'][$toCurrency]['kurs_sredni']);

        bcscale(20);
        $plnAmount = bcdiv(bcmul($fromAmount, $fromAverageRate), $fromMultiplier);
        $resultAmount = bcdiv(bcmul($plnAmount, $toMultiplier), $toAverageRate);
        $roundedResult = BCMathHelper::bcround($resultAmount, 4);

        return [
            'publication_date' => $rates['data_publikacji'],
            'amount'   => $roundedResult,
            'currency' => $toCurrency
        ];
    }
}
