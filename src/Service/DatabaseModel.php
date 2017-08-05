<?php

namespace VysokeSkoly\SolrFeeder\Service;

use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\ListCollection;
use Symfony\Component\Console\Style\SymfonyStyle;
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
        if ($io) {
            $this->io = $io;
            $this->dataMapper->setIo($this->io);

            $this->io->section('Fetching data from database...');
        }

        $data = $this->fetchData(
            $connection,
            $this->createQuery($batch->getQuery(), $timestamps->getTimestampList()->values())
        );

        if ($this->io) {
            $this->io->success(sprintf('%d rows fetched.', $data->count()));
        }

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
        if ($this->io) {
            $this->io->note($query);
        }

        $query = $database->query($query);
        $query->execute();

        return ListCollection::ofT('array', $query->fetchAll(\PDO::FETCH_ASSOC));
    }
}
