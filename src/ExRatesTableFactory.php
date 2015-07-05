<?php

namespace Ksdev\NBPCurrencyConverter;

class ExRatesTableFactory
{
    /**
     * Get new ExRatesTable
     *
     * @param string $rawContent Raw xml content
     *
     * @return ExRatesTable
     */
    public function getInstance($rawContent)
    {
        return new ExRatesTable($rawContent);
    }
}
