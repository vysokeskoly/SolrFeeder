<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\ValueObject;

use Assert\Assertion;

class PrimaryKey
{
    public function __construct(private readonly array $row, private readonly string $primaryKeyColumn)
    {
        Assertion::keyExists($this->row, $this->primaryKeyColumn);
    }

    public function getValue(): string
    {
        return (string) $this->row[$this->primaryKeyColumn];
    }

    public function getIntValue(): int
    {
        return (int) $this->getValue();
    }
}
