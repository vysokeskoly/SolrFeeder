<?php

namespace VysokeSkoly\SolrFeeder\Service;

use Solarium\Client;
use VysokeSkoly\SolrFeeder\Entity\Solr;

class SolrFactory
{
    /** @var Notifier */
    private $notifier;

    public function __construct(Notifier $notifier)
    {
        $this->notifier = $notifier;
    }

    public function createConnection(Solr $solrConfig): Client
    {
        $this->notifier->notifyNote('Solarium version ' . Client::VERSION);

        return new Client($solrConfig->toClientConfig());
    }
}
