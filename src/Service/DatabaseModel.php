<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\ListCollection;
use VysokeSkoly\SolrFeeder\Entity\FeedingBatch;
use VysokeSkoly\SolrFeeder\Entity\Timestamp;
use VysokeSkoly\SolrFeeder\Entity\Timestamps;
use VysokeSkoly\SolrFeeder\Utils\StringHelper;
use VysokeSkoly\SolrFeeder\ValueObject\PrimaryKey;
use VysokeSkoly\SolrFeeder\ValueObject\RowValue;

/**
 * @phpstan-import-type Row from DataMapper
 * @phpstan-import-type MappedRow from DataMapper
 */
class DatabaseModel
{
    public function __construct(
        private readonly DataMapper $dataMapper,
        private readonly StringHelper $stringHelper,
        private readonly Notifier $notifier,
    ) {
    }

    /** @phpstan-return IList<MappedRow> */
    public function getData(\PDO $connection, Timestamps $timestamps, FeedingBatch $batch): IList
    {
        $this->notifier->notifyFetchData();

        $data = $this->fetchData(
            $connection,
            $this->createQuery($batch->getQuery(), $timestamps->getTimestampMap()->values()),
        );

        $this->notifier->notifyFetchedData($data);
        $this->storeCurrentTimestamps($data, $timestamps, $batch->getIdColumn());

        return $this->dataMapper->mapRows($data, $batch->getColumnsMapping());
    }

    /** @phpstan-param IList<Timestamp> $timestampList */
    private function createQuery(string $query, IList $timestampList): string
    {
        return $timestampList->reduce(
            fn (string $query, Timestamp $timestamp) => $timestamp
                ->getPlaceholders()
                ->reduce(
                    fn (string $query, string $placeholder) => $this->stringHelper->contains($query, $placeholder)
                        ? str_replace($placeholder, "'" . $timestamp->getValue($placeholder) . "'", $query)
                        : $query,
                    $query,
                ),
            $query,
        );
    }

    /** @phpstan-return IList<Row> */
    private function fetchData(\PDO $database, string $queryString): IList
    {
        $this->notifier->notifyNote($queryString);

        $query = $database->query($queryString);
        Assertion::isInstanceOf($query, \PDOStatement::class);
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        Assertion::isArray($data);

        return ListCollection::from($data);
    }

    /** @phpstan-param IList<Row> $data */
    private function storeCurrentTimestamps(IList $data, Timestamps $timestamps, string $primaryKeyId): void
    {
        $this->notifier->notifyStoreCurrentTimestamps($data);

        $data->each(function (array $row) use ($primaryKeyId, $timestamps): void {
            $timestamps->getTimestampMap()->each(
                function (Timestamp $timestamp) use ($primaryKeyId, $row, $timestamps): void {
                    $column = $timestamp->getColumn();
                    $currentTimestamp = (new RowValue($row, $column))->getStringValue();

                    if ($currentTimestamp) {
                        $timestamps->setCurrent(new PrimaryKey($row, $primaryKeyId), $currentTimestamp);

                        if ($timestamp->isGreaterThanCurrentValue($currentTimestamp)) {
                            $timestamp->setCurrentValue($currentTimestamp);
                        }
                    }
                },
            );

            $this->notifier->notifyProgress();
        });

        $this->notifier->notifyCurrentTimestampsStored();
    }
}
