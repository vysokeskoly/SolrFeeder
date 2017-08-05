<?php

namespace VysokeSkoly\SolrFeeder\Service;

use MF\Collection\Generic\IList;
use Symfony\Component\Console\Style\SymfonyStyle;
use VysokeSkoly\SolrFeeder\Entity\Feeding;

class DatabaseModel
{
    /** @var DataMapper */
    private $dataMapper;

    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    public function getData(\PDO $database, Feeding $feeding, SymfonyStyle $io): IList
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }
}
