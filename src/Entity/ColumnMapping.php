<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Entity;

class ColumnMapping
{
    public function __construct(
        private readonly string $column,
        private readonly string $destination,
        private readonly ?string $separator = null,
    ) {
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getSeparator(): ?string
    {
        return $this->separator;
    }
}
