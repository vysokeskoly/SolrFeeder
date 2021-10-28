<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Entity;

use Assert\Assertion;
use MF\Collection\Immutable\Generic\IList;

class FeedingBatch
{
    public const SPACE_DOUBLE = '  ';
    public const SPACE_SINGLE = ' ';

    public const TYPE_ADD = 'add';
    public const TYPE_DELETE = 'delete';
    public const TYPES = [self::TYPE_ADD, self::TYPE_DELETE];

    /** @var string */
    private $type;
    /** @var string */
    private $idColumn;
    /** @var string */
    private $query;
    /** @var IList<ColumnMapping> */
    private $columnsMapping;

    public function __construct(string $type, string $idColumn, string $query, IList $columnsMapping)
    {
        Assertion::inArray($type, self::TYPES);

        $this->type = $type;
        $this->idColumn = $idColumn;
        $this->query = $this->normalizeQuery($query);
        $this->columnsMapping = $columnsMapping;
    }

    private function normalizeQuery(string $query): string
    {
        $oneLine = str_replace(["\n"], self::SPACE_SINGLE, $query);

        while (mb_strpos($oneLine, self::SPACE_DOUBLE) !== false) {
            $oneLine = str_replace(self::SPACE_DOUBLE, self::SPACE_SINGLE, $oneLine);
        }

        return trim($oneLine);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIdColumn(): string
    {
        return $this->idColumn;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return IList<ColumnMapping>
     */
    public function getColumnsMapping(): IList
    {
        return $this->columnsMapping;
    }
}
