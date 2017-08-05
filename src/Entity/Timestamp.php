<?php

namespace VysokeSkoly\SolrFeeder\Entity;

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
}
