<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Tests\Entity;

use MF\Collection\Immutable\Generic\Map;
use PHPUnit\Framework\TestCase;
use VysokeSkoly\SolrFeeder\Entity\Timestamp;
use VysokeSkoly\SolrFeeder\Service\XmlParser;

class TimestampsTest extends TestCase
{
    public function testShouldLoadDefaultTimestamps(): void
    {
        $configPath = __DIR__ . '/../Fixtures/config.xml';

        $expectedTimestampList = Map::fromKT('string', Timestamp::class, [
            'timestamp' => new Timestamp(
                'timestamp',
                'ts',
                '%%LAST_TIMESTAMP%%',
                '%%CURRENT_TIMESTAMP%%',
                '1970-01-01 00:00:00'
            ),
            'updated' => new Timestamp(
                'updated',
                'updated',
                '%%LAST_UPDATED%%',
                '%%CURRENT_UPDATED%%',
                '1970-01-01 00:00:00'
            ),
            'deleted' => new Timestamp(
                'deleted',
                'deleted',
                '%%LAST_DELETED%%',
                '%%CURRENT_DELETED%%',
                '1970-01-01 00:00:00'
            ),
        ]);

        $timestamps = (new XmlParser())->parseConfig($configPath)->getTimestamps();
        $timestampList = $timestamps->getTimestampMap();

        $this->assertEquals($expectedTimestampList, $timestampList);
    }

    public function testShouldLoadTimestampsFromFile(): void
    {
        $configPath = __DIR__ . '/../Fixtures/config_with_timestamps.xml';

        $expectedValues = [
            'deleted' => [
                '%%LAST_DELETED%%' => '2017-07-13 09:08:59.78',
                '%%CURRENT_DELETED%%' => '1970-01-01 00:00:00',
            ],
            'updated' => [
                '%%LAST_UPDATED%%' => '2017-08-07 04:11:27.855',
                '%%CURRENT_UPDATED%%' => '1970-01-01 00:00:00',
            ],
        ];

        $timestamps = (new XmlParser())->parseConfig($configPath)->getTimestamps();
        $timestampMap = $timestamps->getTimestampMap();

        foreach ($expectedValues as $type => $expected) {
            /** @var Timestamp $timestamp */
            $timestamp = $timestampMap->get($type);

            foreach ($expected as $placeholder => $expectedValue) {
                $this->assertSame(
                    $expectedValue,
                    $timestamp->getValue($placeholder),
                    sprintf('Asserting type:"%s" by placeholder: "%s".', $type, $placeholder)
                );
            }
        }
    }
}
