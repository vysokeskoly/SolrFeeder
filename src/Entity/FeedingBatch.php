<?php

namespace VysokeSkoly\SolrFeeder\Entity;

use MF\Collection\Immutable\Generic\IList;

class FeedingBatch
{
    const SPACE_DOUBLE = '  ';
    const SPACE_SINGLE = ' ';
    /** @var string */
    private $type;

    /** @var string */
    private $idColumn;

    /** @var string */
    private $query;

    /** @var IList<ColumnMapping> */
    private $columnsMapping;

    public function __construct($type, $idColumn, $query, IList $columnsMapping = null)
    {
        $this->type = $type;
        $this->idColumn = $idColumn;
        $this->query = $this->normalizeQuery($query);
        $this->columnsMapping = $columnsMapping;
    }

    private function normalizeQuery(string $query): string
    {
        $oneLine = str_replace(["\n"], self::SPACE_SINGLE, $query);

        while (strpos($oneLine, self::SPACE_DOUBLE) !== false) {
            $oneLine = str_replace(self::SPACE_DOUBLE, self::SPACE_SINGLE, $oneLine);
        }

        return trim($oneLine);
    }
}
