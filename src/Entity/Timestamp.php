<?php

namespace VysokeSkoly\SolrFeeder\Entity;

use Assert\Assertion;
use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\ListCollection;

class Timestamp
{
    const TYPE_UPDATED = 'updated';
    const TYPE_DELETED = 'deleted';
    const TYPE_TIMESTAMP = 'timestamp';

    const TYPES = [
        self::TYPE_UPDATED,
        self::TYPE_DELETED,
        self::TYPE_TIMESTAMP,
    ];

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

    /** @var string */
    private $lastValue;

    /** @var string */
    private $currentValue;

    /** @var string */
    private $updated;

    public function __construct(
        string $type,
        string $column,
        string $lastValuePlaceholder,
        string $currValuePlaceholder,
        string $default
    ) {
        Assertion::inArray($type, self::TYPES);

        $this->type = $type;
        $this->column = $column;
        $this->lastValuePlaceholder = $lastValuePlaceholder;
        $this->currValuePlaceholder = $currValuePlaceholder;
        $this->default = $default;
    }

    public function setLastValue(string $lastValue)
    {
        $this->lastValue = $lastValue;
    }

    public function isGreaterThanCurrentValue(string $value)
    {
        return $value > $this->currentValue;
    }

    public function setCurrentValue(string $currentValue)
    {
        $this->currentValue = $currentValue;
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
        switch ($placeholder) {
            case $this->getLastValuePlaceholder():
                return $this->lastValue ?? $this->default;

            case $this->getCurrValuePlaceholder():
                return $this->currentValue ?? $this->default;
        }

        return $this->default;
    }

    public function update(string $value)
    {
        $this->updated = $value;
    }

    public function getCurrentUpdated(): string
    {
        return $this->updated ?? $this->default;
    }
}
