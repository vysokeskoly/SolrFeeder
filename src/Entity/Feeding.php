<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Entity;

use MF\Collection\Immutable\Generic\IMap;

class Feeding
{
    /** @phpstan-param IMap<string, FeedingBatch> $batchMap */
    public function __construct(private readonly IMap $batchMap)
    {
    }

    /** @phpstan-return IMap<string, FeedingBatch> */
    public function getBatchMap(): IMap
    {
        return $this->batchMap;
    }
}
