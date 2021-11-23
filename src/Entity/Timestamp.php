<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Entity;

use Assert\Assertion;
use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\ListCollection;

class Timestamp
{
    public const TYPE_UPDATED = 'updated';
    public const TYPE_DELETED = 'deleted';
    public const TYPE_TIMESTAMP = 'timestamp';

    public const TYPES = [
        self::TYPE_UPDATED,
        self::TYPE_DELETED,
        self::TYPE_TIMESTAMP,
    ];

    private string $type;

    private string $column;

    private string $lastValuePlaceholder;

    private string $currValuePlaceholder;

    private string $default;

    /** @var ?string */
    private $lastValue = null;

    /** @var ?string */
    private $currentValue = null;

    /** @var ?string */
    private $updated = null;

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

    public function setLastValue(string $lastValue): void
    {
        $this->lastValue = $lastValue;
    }

    public function isGreaterThanCurrentValue(string $value): bool
    {
        return $value > $this->currentValue;
    }

    public function setCurrentValue(string $currentValue): void
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
        return ListCollection::fromT(
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

    public function update(?string $value): void
    {
        $this->updated = $value;
    }

    public function getCurrentUpdated(): string
    {
        return $this->updated ?? $this->default;
    }
}
