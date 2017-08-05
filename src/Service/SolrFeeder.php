<?php

namespace VysokeSkoly\SolrFeeder\Service;

use MF\Collection\Generic\IList;
use Solarium\Client;
use Symfony\Component\Console\Style\SymfonyStyle;

class SolrFeeder
{
    public function feedSolr(Client $solr, IList $data, SymfonyStyle $io): void
    {
    }
}
