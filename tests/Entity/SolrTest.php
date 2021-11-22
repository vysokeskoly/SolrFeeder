<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Tests\Entity;

use Mockery as m;
use Solarium\Client;
use Solarium\Core\Client\Adapter\AdapterInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use VysokeSkoly\SolrFeeder\Entity\Solr;
use VysokeSkoly\SolrFeeder\Tests\AbstractTestCase;

class SolrTest extends AbstractTestCase
{
    /** @dataProvider provideUrls */
    public function testShouldParseSolrConnectionUrl(string $url, array $expectedConfig): void
    {
        $solr = new Solr($url, 'http', 200000, 100);
        $expectedClientConfig = [
            'endpoint' => [
                'solr' => $expectedConfig,
            ],
        ];

        $clientConfig = $solr->toClientConfig();

        $this->assertSame($expectedClientConfig, $clientConfig);
    }

    public function provideUrls(): array
    {
        return [
            // url, expected
            'without path' => [
                'http://solr:8983/solr/vysokeskoly',
                [
                    'scheme' => 'http',
                    'host' => 'solr',
                    'port' => 8983,
                    'path' => '/',
                    'core' => 'vysokeskoly',
                    'collection' => 'vysokeskoly',
                    'timeout' => 200,
                ],
            ],
            'with path' => [
                'http://solr:8983/solr/some-path/collection',
                [
                    'scheme' => 'http',
                    'host' => 'solr',
                    'port' => 8983,
                    'path' => '/some-path',
                    'core' => 'collection',
                    'collection' => 'collection',
                    'timeout' => 200,
                ],
            ],
        ];
    }

    /** @dataProvider provideUrls */
    public function testShouldCreateEndpointFromSolrConfig(string $url, array $expectedConfig): void
    {
        $solr = new Solr($url, 'http', 200000, 100);

        /** @var AdapterInterface $adapter */
        $adapter = m::mock(AdapterInterface::class);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = m::mock(EventDispatcher::class);
        $solrClient = new Client($adapter, $dispatcher);

        ['endpoint' => ['solr' => $config]] = $solr->toClientConfig();
        $endpoint = $solrClient->createEndpoint($config);

        $expectedPath = $expectedConfig['path'] === '/'
            ? ''
            : $expectedConfig['path'];

        $this->assertSame($expectedConfig['scheme'], $endpoint->getScheme());
        $this->assertSame($expectedConfig['host'], $endpoint->getHost());
        $this->assertSame($expectedConfig['port'], $endpoint->getPort());
        $this->assertSame($expectedPath, $endpoint->getPath());
        $this->assertSame($expectedConfig['core'], $endpoint->getCore());
        $this->assertSame($expectedConfig['collection'], $endpoint->getCollection());
    }
}
