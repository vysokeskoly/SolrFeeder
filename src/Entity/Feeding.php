<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Entity;

use MF\Collection\Immutable\Generic\IMap;

class Feeding
{
    /** @var IMap<string, FeedingBatch> */
    private IMap $batchMap;

    public function __construct(IMap $batchMap)
    {
        $this->batchMap = $batchMap;
    }

    /**
     * @return FeedingBatch[]|IMap
     */
    public function getBatchMap(): IMap
    {
        return $this->batchMap;
    }
}
