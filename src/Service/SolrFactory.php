<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Solarium\Client;
use Solarium\Core\Client\Adapter\Psr18Adapter;
use VysokeSkoly\SolrFeeder\Entity\Solr;

class SolrFactory
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function createConnection(Solr $solrConfig): Client
    {
        $this->notifier->notifyNote('Solarium version ' . Client::getVersion());

        $httpFactory = new HttpFactory();
        $adapter = new Psr18Adapter(new \GuzzleHttp\Client(), $httpFactory, $httpFactory);

        return new Client($adapter, $this->eventDispatcher, $solrConfig->toClientConfig());
    }
}
