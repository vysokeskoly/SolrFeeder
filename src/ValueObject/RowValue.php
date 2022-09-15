<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\ValueObject;

class RowValue
{
    public function __construct(private readonly array $row, private readonly string $column)
    {
    }

    public function getStringValue(): ?string
    {
        return array_key_exists($this->column, $this->row)
            ? (string) $this->row[$this->column]
            : null;
    }

    public function getIntValue(): ?int
    {
        return ($value = $this->getStringValue()) === null
            ? null
            : (int) $value;
    }
}
