<?php

namespace VysokeSkoly\SolrFeeder\Entity;

class Solr
{
    /** @var string */
    private $url;

    /** @var string */
    private $connectionType;

    /** @var int */
    private $readTimeout;

    /** @var int */
    private $batchSize;

    public function __construct(string $url, string $connectionType, int $readTimeout, int $batchSize)
    {
        $this->url = $url;
        $this->connectionType = $connectionType;
        $this->readTimeout = $readTimeout;
        $this->batchSize = $batchSize;
    }
}
