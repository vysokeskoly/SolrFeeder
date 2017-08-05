<?php

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
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
    const ATTR = '@attributes';

    public function parseConfig(string $configPath): Config
    {
        Assertion::file($configPath);

        $xmlContent = file_get_contents($configPath);
        $xml = simplexml_load_string($xmlContent, null, LIBXML_NOCDATA);
        $dataArray = json_decode(json_encode($xml), true);

        return new Config(
            $this->parseLockFile($dataArray),
            $this->parseStatusReport($dataArray),
            $this->parseDatabase($dataArray),
            $this->parseTimestamps($dataArray),
            $this->parseFeeding($dataArray),
            $this->parseSolr($dataArray)
        );
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
        list(
            'driver' => $driver,
            'connection' => $connection,
            'user' => $user,
            'password' => $password,
            ) = $dataArray['db'];

        $dsn = str_replace('jdbc:', '', $connection);
        $password = empty($password) ? '' : $password;

        return new Database($driver, $dsn, $user, $password);
    }

    private function parseTimestamps(array $dataArray): Timestamps
    {
        list(
            self::ATTR => $attributes,
            'timestamp' => $timestamps,
            ) = $dataArray['db']['timestamps'];

        $timestampMap = new Map('string', Timestamp::class);
        foreach ($timestamps as $timestamp) {
            list(
                'type' => $type,
                'name' => $name,
                'column' => $column,
                'lastValuePlaceholder' => $lastValuePlaceholder,
                'currValuePlaceholder' => $currentValuePlaceholder,
                'default' => $default
                ) = $timestamp[self::ATTR];

            $timestampMap = $timestampMap->set(
                $name,
                new Timestamp($type, $column, $lastValuePlaceholder, $currentValuePlaceholder, $default)
            );
        }

        return new Timestamps($attributes['file'], $timestampMap);
    }

    private function parseFeeding(array $dataArray): Feeding
    {
        list(
            'feedingBatch' => $feedingBatch,
            ) = $dataArray['db']['feeding'];

        $batchMap = new Map('string', FeedingBatch::class);
        foreach ($feedingBatch as $batch) {
            list('name' => $name, 'type' => $type) = $batch[self::ATTR];
            list('idColumn' => $idColumn, 'mainSelect' => $query) = $batch;

            $mapping = empty($batch['columnMap']['map'])
                ? null
                : ListCollection::ofT('array', $batch['columnMap']['map'])
                    ->map(
                        function (array $mapping): ColumnMapping {
                            $attr = $mapping[self::ATTR];
                            list('src' => $column, 'dst' => $destination) = $attr;

                            return new ColumnMapping(
                                $column,
                                $destination,
                                $this->parseSeparator($attr['separator'] ?? null)
                            );
                        },
                        ColumnMapping::class
                    );

            $batchMap = $batchMap->set($name, new FeedingBatch($type, $idColumn, $query, $mapping));
        }

        return new Feeding($batchMap);
    }

    private function parseSeparator(?string $separator): ?string
    {
        return empty($separator) ? $separator : str_replace("\\", '', $separator);
    }

    private function parseSolr(array $dataArray): Solr
    {
        list(
            'url' => $url,
            'connectionType' => $connectionType,
            'readTimeout' => $readTimeout,
            'batchSizeDocs' => $batchSize,
            ) = $dataArray['feeder']['solr'];

        return new Solr($url, $connectionType, $readTimeout, $batchSize);
    }
}
