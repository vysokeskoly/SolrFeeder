<?php

namespace VysokeSkoly\SolrFeeder\Entity;

use MF\Collection\Immutable\Generic\IMap;

class Timestamps
{
    /** @var string */
    private $filePath;

    /** @var IMap<string, Timestamp> */
    private $timestampList;

    public function __construct(string $filePath, IMap $timestampList)
    {
        $this->filePath = $filePath;
        $this->timestampList = $timestampList;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return IMap|Timestamp[]
     */
    public function getTimestampList(): IMap
    {
        return $this->timestampList;
    }
}
