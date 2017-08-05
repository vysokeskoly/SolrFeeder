<?php

namespace VysokeSkoly\SolrFeeder\Service;

use Solarium\Client;
use VysokeSkoly\SolrFeeder\Entity\Solr;

class SolrFactory
{
    public function createConnection(Solr $solrConfig): Client
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }
}
