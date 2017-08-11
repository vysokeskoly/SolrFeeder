<?php

namespace VysokeSkoly\SolrFeeder\Entity;

class ColumnMapping
{
    /** @var string */
    private $column;

    /** @var string */
    private $destination;

    /** @var ?string */
    private $separator;

    public function __construct(string $column, string $destination, string $separator = null)
    {
        $this->column = $column;
        $this->destination = $destination;
        $this->separator = $separator;
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
