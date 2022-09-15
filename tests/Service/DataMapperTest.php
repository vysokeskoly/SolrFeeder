<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Tests\Service;

use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\ListCollection;
use VysokeSkoly\SolrFeeder\Entity\ColumnMapping;
use VysokeSkoly\SolrFeeder\Service\DatabaseModel;
use VysokeSkoly\SolrFeeder\Service\DataMapper;
use VysokeSkoly\SolrFeeder\Service\Notifier;
use VysokeSkoly\SolrFeeder\Tests\AbstractTestCase;

/** @phpstan-import-type Row from DatabaseModel */
class DataMapperTest extends AbstractTestCase
{
    private DataMapper $dataMapper;

    protected function setUp(): void
    {
        $this->dataMapper = new DataMapper(new Notifier());
    }

    public function testShouldMapRowsByColumnsMapping(): void
    {
        /** @phpstan-var IList<ColumnMapping> $mapping */
        $mapping = ListCollection::from([
            new ColumnMapping('keywords', 'keywords', '|'),
            new ColumnMapping('names', 'names'),
            new ColumnMapping('names', 'names_str', ', '),
            new ColumnMapping('updated', '_ignored'),
        ]);

        /** @phpstan-var IList<Row> $rows */
        $rows = ListCollection::from([
            ['id' => '1', 'names' => null, 'keywords' => null, 'updated' => '2017-08-06 12:22:45'],
            ['id' => '2', 'names' => 'one', 'keywords' => 'k_one', 'updated' => '2017-08-06 12:22:45'],
            ['id' => '3', 'names' => 'one, two', 'keywords' => 'k_one|k_two', 'updated' => '2017-08-06 12:22:45'],
        ]);

        /** @phpstan-var IList<Row> $expectedRows */
        $expectedRows = ListCollection::from([
            ['id' => '1', 'names' => null, 'keywords' => [], 'names_str' => []],
            ['id' => '2', 'names' => 'one', 'keywords' => ['k_one'], 'names_str' => ['one']],
            ['id' => '3', 'names' => 'one, two', 'keywords' => ['k_one', 'k_two'], 'names_str' => ['one', 'two']],
        ]);

        $result = $this->dataMapper->mapRows($rows, $mapping);

        $this->assertEquals($expectedRows->toArray(), $result->toArray());
    }
}
