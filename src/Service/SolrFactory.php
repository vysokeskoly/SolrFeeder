<?php

namespace VysokeSkoly\SolrFeeder\Service;

use Solarium\Client;
use Symfony\Component\Console\Style\SymfonyStyle;
use VysokeSkoly\SolrFeeder\Entity\Solr;
use function Functional\with;

class SolrFactory
{
    public function createConnection(Solr $solrConfig, SymfonyStyle $io = null): Client
    {
        $this->notifySolariumVersion($io);

        return new Client($solrConfig->toClientConfig());
    }

    private function notifySolariumVersion(?SymfonyStyle $io)
    {
        with($io, function (SymfonyStyle $io) {
            $io->note('Solarium version: ' . Client::VERSION);
        });
    }
}
