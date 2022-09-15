<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\IMap;
use MF\Collection\Immutable\Generic\ListCollection;
use MF\Collection\Immutable\Generic\Map;
use MF\Collection\Immutable\Generic\Seq;
use VysokeSkoly\SolrFeeder\Entity\ColumnMapping;
use VysokeSkoly\SolrFeeder\Entity\Config;
use VysokeSkoly\SolrFeeder\Entity\Database;
use VysokeSkoly\SolrFeeder\Entity\Feeding;
use VysokeSkoly\SolrFeeder\Entity\FeedingBatch;
use VysokeSkoly\SolrFeeder\Entity\Solr;
use VysokeSkoly\SolrFeeder\Entity\Timestamp;
use VysokeSkoly\SolrFeeder\Entity\Timestamps;

class XmlParser
{
    public const ATTR = '@attributes';

    public function parseConfig(string $configPath): Config
    {
        $dataArray = $this->loadXmlAsArray($configPath);

        return new Config(
            $this->parseLockFile($dataArray),
            $this->parseStatusReport($dataArray),
            $this->parseDatabase($dataArray),
            $this->parseTimestamps($dataArray),
            $this->parseFeeding($dataArray),
            $this->parseSolr($dataArray),
        );
    }

    private function loadXmlAsArray(string $configPath): array
    {
        $xml = $this->loadXml($configPath);

        return json_decode((string) json_encode($xml), true);
    }

    private function loadXml(string $configPath): \SimpleXMLElement
    {
        Assertion::file($configPath);
        $xmlContent = file_get_contents($configPath);
        $xml = simplexml_load_string((string) $xmlContent, \SimpleXMLElement::class, \LIBXML_NOCDATA);
        Assertion::isInstanceOf($xml, \SimpleXMLElement::class);

        return $xml;
    }

    private function parseLockFile(array $dataArray): string
    {
        return $dataArray['lockFile'];
    }

    private function parseStatusReport(array $dataArray): string
    {
        return $dataArray['statusReportFile'];
    }

    private function parseDatabase(array $dataArray): Database
    {
        [
            'driver' => $driver,
            'connection' => $connection,
            'user' => $user,
            'password' => $password,
        ] = $dataArray['db'];

        $dsn = str_replace('jdbc:', '', $connection);
        $password = empty($password) ? '' : $password;

        return new Database($driver, $dsn, $user, $password);
    }

    private function parseTimestamps(array $dataArray): Timestamps
    {
        [
            self::ATTR => $attributes,
            'timestamp' => $timestamps,
        ] = $dataArray['db']['timestamps'];

        /** @phpstan-var IMap<string, Timestamp> $timestampMap */
        $timestampMap = new Map();
        foreach ($timestamps as $timestamp) {
            [
                'type' => $type,
                'name' => $name,
                'column' => $column,
                'lastValuePlaceholder' => $lastValuePlaceholder,
                'currValuePlaceholder' => $currentValuePlaceholder,
                'default' => $default
            ] = $timestamp[self::ATTR];

            Assertion::same('datetime', $type, 'Only available type is "datetime" now.');

            $timestampMap = $timestampMap->set(
                $name,
                new Timestamp($name, $column, $lastValuePlaceholder, $currentValuePlaceholder, $default),
            );
        }

        return new Timestamps($attributes['file'], $timestampMap, $this);
    }

    private function parseFeeding(array $dataArray): Feeding
    {
        [
            'feedingBatch' => $feedingBatch,
        ] = $dataArray['db']['feeding'];

        /** @phpstan-var IMap<string, FeedingBatch> $batchMap */
        $batchMap = new Map();

        foreach ($this->normalizeMultiNode($feedingBatch) as $batch) {
            ['name' => $name, 'type' => $type] = $batch[self::ATTR];
            ['idColumn' => $idColumn, 'mainSelect' => $query] = $batch;

            /** @phpstan-var IList<ColumnMapping> $mapping */
            $mapping = empty($batch['columnMap']['map'])
                ? new ListCollection()
                : ListCollection::create(
                    $this->normalizeMultiNode($batch['columnMap']['map']),
                    function (array $mapping): ColumnMapping {
                        $attr = $mapping[self::ATTR];
                        ['src' => $column, 'dst' => $destination] = $attr;

                        return new ColumnMapping(
                            $column,
                            $destination,
                            $this->parseSeparator($attr['separator'] ?? null),
                        );
                    },
                );

            $batchMap = $batchMap->set($name, new FeedingBatch($type, $idColumn, $query, $mapping));
        }

        return new Feeding($batchMap);
    }

    private function normalizeMultiNode(array $node): array
    {
        return isset($node[self::ATTR])
            ? [$node]
            : $node;
    }

    private function parseSeparator(?string $separator): ?string
    {
        return empty($separator)
            ? $separator
            : str_replace('\\', '', $separator);
    }

    private function parseSolr(array $dataArray): Solr
    {
        [
            'url' => $url,
            'connectionType' => $connectionType,
            'readTimeout' => $readTimeout,
            'batchSizeDocs' => $batchSize,
        ] = $dataArray['feeder']['solr'];

        return new Solr($url, $connectionType, (int) $readTimeout, (int) $batchSize);
    }

    /** @phpstan-return IMap<string, string> */
    public function parseTimestampsFile(string $path): IMap
    {
        return Seq::init(function () use ($path) {
            $xml = $this->loadXml($path);

            yield from $xml->timestamp;
        })
        ->reduce(
            fn (IMap $timestamps, \SimpleXMLElement $timestamp) => $timestamps->set(
                (string) ($timestamp->attributes()['name'] ?? ''),
                $timestamp->__toString(),
            ),
            new Map(),
        );
    }
}
