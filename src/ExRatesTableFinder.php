<?php

namespace Ksdev\NBPCurrencyConverter;

class ExRatesTableFinder
{
    const NBP_XML_URL = 'http://www.nbp.pl/kursy/xml/';
    const MAX_ONE_TIME_API_REQ = 7;

    /** @var \GuzzleHttp\Client */
    private $guzzle;

    /** @var ExRatesTableFactory */
    private $ratesTableFactory;

    /** @var string */
    private $cachePath;

    /** @var \DateTime */
    private $soughtPubDate;

    /**
     * @param \GuzzleHttp\Client $guzzle
     * @param ExRatesTableFactory $ratesTableFactory
     * @param string $cachePath Optional path to an existing folder where the cache files will be stored
     *
     * @throws \Exception
     */
    public function __construct(
        \GuzzleHttp\Client $guzzle,
        ExRatesTableFactory $ratesTableFactory,
        $cachePath = ''
    ) {
        $this->guzzle = $guzzle;
        $this->ratesTableFactory = $ratesTableFactory;
        if ($cachePath) {
            if (!is_dir($cachePath)) {
                throw new \Exception('Invalid cache path');
            }
            $this->cachePath = rtrim((string)$cachePath, '/') . '/';
        }
    }

    /**
     * Get the ExRatesTable instance
     *
     * @param \DateTime $pubDate Optional rates table publication date
     *
     * @return ExRatesTable
     *
     * @throws \Exception
     */
    public function getExRatesTable(\DateTime $pubDate = null)
    {
        $this->setSoughtPubDate($pubDate);

        $i = 0;
        do {
            // Limit the number of times the loop repeats
            if ($i === self::MAX_ONE_TIME_API_REQ) {
                throw new \Exception('Max request to api limit has been reached');
            }

            // If user doesn't want a specific date, try to get the rates from the last working day
            if (!$pubDate) {
                $this->soughtPubDate = $this->soughtPubDate->sub(new \DateInterval('P1D'));
            }

            // Try to find the file in cache, otherwise download it
            if ($this->cachePath && ($cachedXml = $this->getCachedXml())) {
                $rawContent = $cachedXml;
            } else {
                $rawContent = $this->downloadXml();
            }

            // If a specific date is sought then break, otherwise continue
            if ($pubDate) {
                break;
            }

            $i++;
        } while (!$rawContent);

        if (!$rawContent) {
            throw new \Exception('Exchange rates file not found');
        }

        return $this->ratesTableFactory->getInstance($rawContent);
    }

    /**
     * Set the sought publication date necessary for finder operation
     *
     * @param \DateTime|null $pubDate
     *
     * @throws \Exception
     */
    private function setSoughtPubDate($pubDate)
    {
        if ($pubDate instanceof \DateTime) {
            if (!($pubDate >= new \DateTime('2002-01-02') && $pubDate <= new \DateTime())) {
                throw new \Exception('Invalid publication date');
            }
        } else {
            $pubDate = new \DateTime();
        }
        $this->soughtPubDate = $pubDate;
    }

    /**
     * Get the raw xml content from a cache file
     *
     * @return string|int Content string or 0 if the file doesn't exist
     */
    private function getCachedXml()
    {
        $filesArray = scandir($this->cachePath);
        $filename = $this->matchFilename($filesArray);

        if ($filename) {
            $rawContent = file_get_contents($this->cachePath . $filename);
            return $rawContent;
        }

        return 0;
    }

    /**
     * Get the raw xml content from the NBP api
     *
     * @return string|int Content string or 0 if the file doesn't exist
     *
     * @throws \Exception
     */
    private function downloadXml()
    {
        $filename = $this->findFileInRatesDir();
        if ($filename) {
            $response = $this->guzzle->get(self::NBP_XML_URL . $filename);
            if ($response->getStatusCode() === 200) {
                $rawContent = (string)$response->getBody();
                if ($this->cachePath) {
                    file_put_contents($this->cachePath . $filename, $rawContent);
                }
                return $rawContent;
            } else {
                throw new \Exception(
                    "Invalid response status code: {$response->getStatusCode()} {$response->getReasonPhrase()}"
                );
            }
        } else {
            return 0;
        }
    }

    /**
     * Find the file related to the publication date
     *
     * @return string|int Filename or 0 if the file was not found
     *
     * @throws \Exception
     */
    private function findFileInRatesDir()
    {
        $dirname = $this->constructDirname();

        $response = $this->guzzle->get(self::NBP_XML_URL . $dirname);
        if ($response->getStatusCode() === 200) {
            $rawContent = (string)$response->getBody();
        } else {
            throw new \Exception(
                "Invalid response status code: {$response->getStatusCode()} {$response->getReasonPhrase()}"
            );
        }

        $filesArray = explode("\r\n", $rawContent);
        $filename = $this->matchFilename($filesArray);

        return $filename;
    }

    /**
     * Construct the name of directory containing the files
     *
     * @return string
     */
    private function constructDirname()
    {
        if ($this->soughtPubDate->format('Y') !== (new \DateTime())->format('Y')) {
            $dirname = "dir{$this->soughtPubDate->format('Y')}.txt";
        } else {
            $dirname = 'dir.txt';
        }
        return $dirname;
    }

    /**
     * Searches files array for a match to the publication date
     *
     * @todo Optimize to avoid unnecessary regex
     *
     * @param array $filesArray
     *
     * @return string|int Filename or 0 if the file was not found
     */
    private function matchFilename(array $filesArray)
    {
        foreach ($filesArray as $filename) {
            if (preg_match('/(a\d{3}z' . $this->soughtPubDate->format('ymd') . ')/', $filename, $matches)) {
                $filename = "{$matches[1]}.xml";
                return $filename;
            }
        }

        return 0;
    }
}
