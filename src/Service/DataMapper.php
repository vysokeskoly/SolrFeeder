<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use MF\Collection\Immutable\Generic\IList;
use VysokeSkoly\SolrFeeder\Entity\ColumnMapping;
use VysokeSkoly\SolrFeeder\ValueObject\RowValue;

/**
 * @phpstan-type Row array
 * @phpstan-type MappedRow array
 */
class DataMapper
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    /**
     * @phpstan-param IList<Row> $rows
     * @phpstan-param IList<ColumnMapping> $columnMappings
     * @phpstan-return IList<MappedRow>
     */
    public function mapRows(IList $rows, IList $columnMappings): IList
    {
        $isMapping = !$columnMappings->isEmpty();
        if ($isMapping) {
            $this->notifier->notifyRowsMapping($rows);
        }

        /** @phpstan-var IList<array> $rows */
        $rows = !$isMapping
            ? $rows
            : $rows->map(function (array $row) use ($columnMappings): array {
                $mappedRow = $row;

                $columnMappings
                    ->map(function (ColumnMapping $mapping) use ($row, &$mappedRow) {
                        $mappedColumn = $mapping->getColumn();
                        $value = (new RowValue($row, $mappedColumn))->getStringValue();

                        $mappedRow[$mapping->getDestination()] = $this->mapRow($value, $mapping->getSeparator());

                        return $mapping;
                    })
                    ->filter(function (ColumnMapping $current) use ($columnMappings): bool {
                        /** @var ColumnMapping $mapping */
                        foreach ($columnMappings as $mapping) {
                            if ($mapping->getDestination() === $current->getColumn()) {
                                return false;
                            }
                        }

                        return true;
                    })
                    ->each(function (ColumnMapping $mapping) use (&$mappedRow): void {
                        unset($mappedRow[$mapping->getColumn()]);
                    });

                unset($mappedRow['_ignored']);

                $this->notifier->notifyProgress();

                return $mappedRow;
            });

        if ($isMapping) {
            $this->notifier->notifyRowsMapped($rows);
        }

        return $rows;
    }

    private function mapRow(?string $value, ?string $separator): string|array|null
    {
        if (empty($separator)) {
            return $value;
        }

        return empty($value)
            ? []
            : explode($separator, $value);
    }
}
