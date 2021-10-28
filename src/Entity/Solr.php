<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Entity;

use function Functional\compose;
use VysokeSkoly\SolrFeeder\Constant\Functions as f;
use VysokeSkoly\SolrFeeder\Utils\Curry;

class Solr
{
    public const ENDPOINT = 'solr';

    /** @var string */
    private $host;
    /** @var int */
    private $port;
    /** @var int */
    private $path;
    /** @var string */
    private $connectionType;
    /** @var string */
    private $collection;
    /**
     * In milliseconds
     *
     * @var int
     */
    private $readTimeout;
    /** @var int */
    private $batchSize;

    public function __construct(
        string $url,
        string $connectionType,
        int $readTimeout,
        int $batchSize
    ) {
        [$this->host, $this->port, $this->path, $this->collection] = $this->parseUrl($url);
        $this->connectionType = $connectionType;
        $this->readTimeout = $readTimeout;
        $this->batchSize = $batchSize;
    }

    /**
     * @param string $url http://HOST:PORT/solr/PATH/COLLECTION
     * @example http://solr:8983/solr/vysokeskoly
     */
    private function parseUrl(string $url): array
    {
        $splitBy = Curry::explode();
        $joinBy = Curry::implode();
        $replace = Curry::replace();
        $trim = Curry::trim();

        $splitUrl = $splitBy('//');
        $splitBySlash = $splitBy('/');
        $splitHostAndPort = $splitBy(':');

        [$host, $port] = compose($splitUrl, f::LAST, $splitBySlash, f::FIRST, $splitHostAndPort)($url);

        $splitByPort = $splitBy($port);
        $fullPath = compose($splitByPort, f::LAST)($url);

        $parts = compose($replace('/solr', ''), $splitBySlash)($fullPath);

        $collection = array_pop($parts);
        $path = compose($joinBy('/'), $trim('/'))($parts);

        return [$host, (int) $port, '/' . $path, $collection];
    }

    public function toClientConfig(): array
    {
        return [
            'endpoint' => [
                self::ENDPOINT => [
                    'scheme' => $this->connectionType,
                    'host' => $this->host,
                    'port' => $this->port,
                    'path' => $this->path,
                    'core' => $this->collection,
                    'collection' => $this->collection,
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
