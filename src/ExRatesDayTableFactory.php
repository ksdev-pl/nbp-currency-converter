<?php

namespace Ksdev\NBPCurrencyConverter;

class ExRatesDayTableFactory
{
    /**
     * Get new ExRatesDayTable
     *
     * @param string $rawContent Raw xml content
     *
     * @return ExRatesDayTable
     */
    public function getInstance($rawContent)
    {
        return new ExRatesDayTable($rawContent);
    }
}
