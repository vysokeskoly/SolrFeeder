<?php

namespace VysokeSkoly\SolrFeeder\Entity;

use VysokeSkoly\SolrFeeder\Constant\Functions as f;
use VysokeSkoly\SolrFeeder\Utils\Curry;
use function Functional\compose;

class Solr
{
    const ENDPOINT = 'solr';

    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var int */
    private $path;

    /** @var string */
    private $connectionType;

    /**
     * In milliseconds
     *
     * @var int
     */
    private $readTimeout;

    /** @var int */
    private $batchSize;

    public function __construct(string $url, string $connectionType, int $readTimeout, int $batchSize)
    {
        list($this->host, $this->port, $this->path) = $this->parseUrl($url);
        $this->connectionType = $connectionType;
        $this->readTimeout = $readTimeout;
        $this->batchSize = $batchSize;
    }

    /**
     * @param string $url http://HOST:PORT/PATH
     * @return array
     */
    private function parseUrl(string $url): array
    {
        $splitBy = Curry::explode();
        $splitUrl = $splitBy('//');
        $splitHostAndPath = $splitBy('/');
        $splitHostAndPort = $splitBy(':');

        list($host, $port) = compose($splitUrl, f::LAST, $splitHostAndPath, f::FIRST, $splitHostAndPort)($url);

        $splitByPort = $splitBy($port);
        $path = compose($splitByPort, f::LAST)($url);

        return [$host, (int) $port, $path];
    }

    public function toClientConfig()
    {
        return [
            'endpoint' => [
                self::ENDPOINT => [
                    'host' => $this->host,
                    'port' => $this->port,
                    'path' => $this->path,
                    'timeout' => $this->readTimeout / 1000, // in seconds
                ],
            ],
        ];
    }

    public function getReadTimeout(): int
    {
        return $this->readTimeout;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }
}
