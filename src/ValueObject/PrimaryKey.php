<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\ValueObject;

use Assert\Assertion;

class PrimaryKey
{
    private array $row;

    private string $primaryKeyColumn;

    public function __construct(array $row, string $primaryKeyColumn)
    {
        $this->row = $row;
        $this->primaryKeyColumn = $primaryKeyColumn;
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
