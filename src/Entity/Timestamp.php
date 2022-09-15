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

    private ?string $lastValue = null;
    private ?string $currentValue = null;
    private ?string $updated = null;

    public function __construct(
        private readonly string $type,
        private readonly string $column,
        private readonly string $lastValuePlaceholder,
        private readonly string $currValuePlaceholder,
        private readonly string $default,
    ) {
        Assertion::inArray($type, self::TYPES);
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

    /** @phpstan-return IList<string> */
    public function getPlaceholders(): IList
    {
        return ListCollection::from([
            $this->getLastValuePlaceholder(),
            $this->getCurrValuePlaceholder(),
        ]);
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
        return match ($placeholder) {
            $this->getLastValuePlaceholder() => $this->lastValue ?? $this->default,
            $this->getCurrValuePlaceholder() => $this->currentValue ?? $this->default,
            default => $this->default
        };
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
