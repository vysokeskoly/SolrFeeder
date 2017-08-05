<?php

namespace VysokeSkoly\SolrFeeder\Entity;

use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\ListCollection;

class Timestamp
{
    /** @var string */
    private $type;

    /** @var string */
    private $column;

    /** @var string */
    private $lastValuePlaceholder;

    /** @var string */
    private $currValuePlaceholder;

    /** @var string */
    private $default;

    public function __construct(
        string $type,
        string $column,
        string $lastValuePlaceholder,
        string $currValuePlaceholder,
        string $default
    ) {
        $this->type = $type;
        $this->column = $column;
        $this->lastValuePlaceholder = $lastValuePlaceholder;
        $this->currValuePlaceholder = $currValuePlaceholder;
        $this->default = $default;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getPlaceholders(): IList
    {
        return ListCollection::ofT(
            'string',
            [$this->getLastValuePlaceholder(), $this->getCurrValuePlaceholder()]
        );
    }

    private function getLastValuePlaceholder(): string
    {
        return $this->lastValuePlaceholder;
    }

    private function getCurrValuePlaceholder(): string
    {
        return $this->currValuePlaceholder;
    }

    public function getValue(string $placeholder): string
    {
        // todo - parse value from file and if there is none, return default

        return $this->getDefault();
    }

    private function getDefault(): string
    {
        return $this->default;
    }
}
