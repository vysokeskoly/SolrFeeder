<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use MF\Collection\Immutable\Generic\IMap;
use MF\Collection\Immutable\Generic\ListCollection;
use MF\Collection\Immutable\Generic\Map;
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
            $this->parseSolr($dataArray)
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

        $timestampMap = new Map('string', Timestamp::class);
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
                new Timestamp($name, $column, $lastValuePlaceholder, $currentValuePlaceholder, $default)
            );
        }

        return new Timestamps($attributes['file'], $timestampMap, $this);
    }

    private function parseFeeding(array $dataArray): Feeding
    {
        [
            'feedingBatch' => $feedingBatch,
            ] = $dataArray['db']['feeding'];

        $batchMap = new Map('string', FeedingBatch::class);

        foreach ($this->normalizeMultiNode($feedingBatch) as $batch) {
            ['name' => $name, 'type' => $type] = $batch[self::ATTR];
            ['idColumn' => $idColumn, 'mainSelect' => $query] = $batch;

            $mapping = empty($batch['columnMap']['map'])
                ? ListCollection::fromT(ColumnMapping::class, [])
                : ListCollection::createT(
                    ColumnMapping::class,
                    $this->normalizeMultiNode($batch['columnMap']['map']),
                    function (array $mapping): ColumnMapping {
                        $attr = $mapping[self::ATTR];
                        ['src' => $column, 'dst' => $destination] = $attr;

                        return new ColumnMapping(
                            $column,
                            $destination,
                            $this->parseSeparator($attr['separator'] ?? null)
                        );
                    }
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
        return empty($separator) ? $separator : str_replace('\\', '', $separator);
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

    public function parseTimestampsFile(string $path): IMap
    {
        $timestamps = new Map('string', 'string');

        $xml = $this->loadXml($path);
        foreach ($xml->timestamp as $timestamp) {
            $timestamps = $timestamps->set($timestamp->attributes()['name']->__toString(), $timestamp->__toString());
        }

        return $timestamps;
    }
}
