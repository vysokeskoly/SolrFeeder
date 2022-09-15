<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Tests\Service;

use MF\Collection\Immutable\Generic\IMap;
use MF\Collection\Immutable\Generic\ListCollection;
use MF\Collection\Immutable\Generic\Map;
use PHPUnit\Framework\TestCase;
use VysokeSkoly\SolrFeeder\Entity\ColumnMapping;
use VysokeSkoly\SolrFeeder\Entity\Config;
use VysokeSkoly\SolrFeeder\Entity\Database;
use VysokeSkoly\SolrFeeder\Entity\Feeding;
use VysokeSkoly\SolrFeeder\Entity\FeedingBatch;
use VysokeSkoly\SolrFeeder\Entity\Solr;
use VysokeSkoly\SolrFeeder\Entity\Timestamp;
use VysokeSkoly\SolrFeeder\Entity\Timestamps;
use VysokeSkoly\SolrFeeder\Service\XmlParser;

/**
 * @group unit
 */
class XmlParserTest extends TestCase
{
    private XmlParser $xmlParser;

    protected function setUp(): void
    {
        $this->xmlParser = new XmlParser();
    }

    public function testShouldParseConfigFile(): void
    {
        $configPath = __DIR__ . '/../Fixtures/config.xml';

        $expectedConfig = new Config(
            'var/tmp/vysokeskoly.txt',
            'var/status/status-report-vysokeskoly.txt',
            new Database(
                'org.postgresql.Driver',
                'postgresql://dbvysokeskoly:5432/vysokeskoly',
                'vysokeskoly',
                'vysokeskoly',
            ),
            new Timestamps(
                'var/timestamp/last-timestamps.xml',
                (new Map())
                    ->set('timestamp', new Timestamp(
                        'timestamp',
                        'ts',
                        '%%LAST_TIMESTAMP%%',
                        '%%CURRENT_TIMESTAMP%%',
                        '1970-01-01 00:00:00',
                    ))
                    ->set('updated', new Timestamp(
                        'updated',
                        'updated',
                        '%%LAST_UPDATED%%',
                        '%%CURRENT_UPDATED%%',
                        '1970-01-01 00:00:00',
                    ))
                    ->set('deleted', new Timestamp(
                        'deleted',
                        'deleted',
                        '%%LAST_DELETED%%',
                        '%%CURRENT_DELETED%%',
                        '1970-01-01 00:00:00',
                    )),
                $this->xmlParser,
            ),
            new Feeding(
                (new Map())
                    ->set('add', new FeedingBatch(
                        'add',
                        'study_id',
                        'SELECT * FROM studies_solr WHERE updated >= %%LAST_UPDATED%% ORDER BY updated ASC',
                        ListCollection::from([
                            new ColumnMapping('study_keyword', 'study_keyword', '|'),
                            new ColumnMapping('study_name', 'study_name'),
                            new ColumnMapping('study_name', 'study_name_str'),
                            new ColumnMapping('updated', '_ignored'),
                        ]),
                    ))
                    ->set('delete', new FeedingBatch(
                        'delete',
                        'study_id',
                        'SELECT study_id, deleted FROM studies_solr WHERE deleted >= %%LAST_DELETED%%',
                        new ListCollection(),
                    )),
            ),
            new Solr(
                'http://solr:8983/solr/vysokeskoly',
                'http',
                200_000,
                100,
            ),
        );

        $config = $this->xmlParser->parseConfig($configPath);

        $this->assertEquals($expectedConfig, $config);
    }

    public function testShouldParseTimestamps(): void
    {
        $path = __DIR__ . '/../Fixtures/timestamps.xml';

        /** @phpstan-var IMap<string, string> $expectedTimestamps */
        $expectedTimestamps = Map::from([
            'deleted' => '2017-07-13 09:08:59.78',
            'updated' => '2017-08-07 04:11:27.855',
            'timestamp' => '1970-01-01 00:00:00.0',
        ]);

        $timestamps = $this->xmlParser->parseTimestampsFile($path);

        $this->assertEquals($expectedTimestamps->toArray(), $timestamps->toArray());
    }
}
