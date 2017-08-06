<?php

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\ListCollection;
use Symfony\Component\Console\Style\SymfonyStyle;
use VysokeSkoly\SolrFeeder\Entity\FeedingBatch;
use VysokeSkoly\SolrFeeder\Entity\Timestamp;
use VysokeSkoly\SolrFeeder\Entity\Timestamps;
use VysokeSkoly\SolrFeeder\Utils\StringHelper;
use function Functional\with;

class DatabaseModel
{
    /** @var DataMapper */
    private $dataMapper;

    /** @var StringHelper */
    private $stringHelper;

    /** @var SymfonyStyle|null */
    private $io;

    public function __construct(DataMapper $dataMapper, StringHelper $stringHelper)
    {
        $this->dataMapper = $dataMapper;
        $this->stringHelper = $stringHelper;
    }

    public function getData(
        \PDO $connection,
        Timestamps $timestamps,
        FeedingBatch $batch,
        SymfonyStyle $io = null
    ): IList {
        $this->io = $io;
        $this->notifyFetchData();

        $data = $this->fetchData(
            $connection,
            $this->createQuery($batch->getQuery(), $timestamps->getTimestampList()->values())
        );

        $this->notifyFetchedData($data);

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

    private function notifyFetchData()
    {
        with($this->io, function (SymfonyStyle $io) {
            $this->dataMapper->setIo($io);

            $io->section('Fetching data from database...');
        });
    }

    private function notifyFetchedData(IList $data)
    {
        with($this->io, function (SymfonyStyle $io) use ($data) {
            $io->success(sprintf('%d rows fetched.', $data->count()));
        });
    }

    private function fetchData(\PDO $database, string $query): IList
    {
        $this->notifyQuery($query);

        $query = $database->query($query);
        $query->execute();

        return ListCollection::ofT('array', $query->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * @param string $query
     */
    private function notifyQuery(string $query)
    {
        with($this->io, function (SymfonyStyle $io) use ($query) {
            $io->note($query);
        });
    }
}
