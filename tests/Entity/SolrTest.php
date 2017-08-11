<?php

namespace VysokeSkoly\SolrFeeder\Tests\Entity;

use VysokeSkoly\SolrFeeder\Entity\Solr;
use VysokeSkoly\SolrFeeder\Tests\AbstractTestCase;

class SolrTest extends AbstractTestCase
{
    public function testShouldParseSolrConnectionUrl()
    {
        $solr = new Solr('http://solr:8983/solr/vysokeskoly', 'http', 200000, 100);
        $expectedClientConfig = [
            'endpoint' => [
                'solr' => [
                    'host' => 'solr',
                    'port' => 8983,
                    'path' => '/solr/vysokeskoly',
                    'timeout' => 200,
                ],
            ],
        ];

        $clientConfig = $solr->toClientConfig();

        $this->assertSame($expectedClientConfig, $clientConfig);
    }
}
