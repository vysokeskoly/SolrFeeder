<?php

namespace VysokeSkoly\SolrFeeder\Service;

use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\ListCollection;
use VysokeSkoly\SolrFeeder\Entity\FeedingBatch;
use VysokeSkoly\SolrFeeder\Entity\Timestamp;
use VysokeSkoly\SolrFeeder\Entity\Timestamps;
use VysokeSkoly\SolrFeeder\Utils\StringHelper;

class DatabaseModel
{
    /** @var DataMapper */
    private $dataMapper;

    /** @var StringHelper */
    private $stringHelper;

    /** @var Notifier */
    private $notifier;

    public function __construct(DataMapper $dataMapper, StringHelper $stringHelper, Notifier $notifier)
    {
        $this->dataMapper = $dataMapper;
        $this->stringHelper = $stringHelper;
        $this->notifier = $notifier;
    }

    public function getData(\PDO $connection, Timestamps $timestamps, FeedingBatch $batch): IList
    {
        $this->notifier->notifyFetchData();

        $data = $this->fetchData(
            $connection,
            $this->createQuery($batch->getQuery(), $timestamps->getTimestampMap()->values())
        );

        $this->notifier->notifyFetchedData($data);
        $this->storeCurrentTimestamps($data, $timestamps, $batch->getIdColumn());

        return $this->dataMapper->mapRows($data, $batch->getColumnsMapping());
    }

    private function createQuery(string $query, IList $timestampList): string
    {
        return $timestampList->reduce(
            function (string $query, Timestamp $timestamp): string {
                return $timestamp
                    ->getPlaceholders()
                    ->reduce(
                        function (string $query, string $placeholder) use ($timestamp): string {
                            return $this->stringHelper->contains($query, $placeholder)
                                ? str_replace($placeholder, "'" . $timestamp->getValue($placeholder) . "'", $query)
                                : $query;
                        },
                        $query
                    );
            },
            $query
        );
    }

    private function fetchData(\PDO $database, string $query): IList
    {
        $this->notifier->notifyNote($query);

        $query = $database->query($query);
        $query->execute();

        return ListCollection::ofT('array', $query->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function storeCurrentTimestamps(IList $data, Timestamps $timestamps, string $primaryKeyId): void
    {
        $this->notifier->notifyStoreCurrentTimestamps($data);

        $data->each(function (array $row) use ($primaryKeyId, $timestamps) {
            $timestamps->getTimestampMap()->each(
                function (Timestamp $timestamp) use ($primaryKeyId, $row, $timestamps) {
                    $column = $timestamp->getColumn();
                    $currentTimestamp = $row[$column] ?? null;

                    if ($currentTimestamp) {
                        $timestamps->setCurrent($row[$primaryKeyId], $currentTimestamp);

                        if ($timestamp->isGreaterThanCurrentValue($currentTimestamp)) {
                            $timestamp->setCurrentValue($currentTimestamp);
                        }
                    }
                }
            );

            $this->notifier->notifyProgress();
        });

        $this->notifier->notifyCurrentTimestampsStored();
    }
}
