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

    private readonly string $query;

    /** @phpstan-param IList<ColumnMapping> $columnsMapping */
    public function __construct(
        private readonly string $type,
        private readonly string $idColumn,
        string $query,
        private readonly IList $columnsMapping,
    ) {
        Assertion::inArray($type, self::TYPES);

        $this->query = $this->normalizeQuery($query);
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

    /** @phpstan-return IList<ColumnMapping> */
    public function getColumnsMapping(): IList
    {
        return $this->columnsMapping;
    }
}
