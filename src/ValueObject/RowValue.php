<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\ValueObject;

class RowValue
{
    /** @var array */
    private $row;
    /** @var string */
    private $column;

    public function __construct(array $row, string $column)
    {
        $this->row = $row;
        $this->column = $column;
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
