<?php

namespace VysokeSkoly\SolrFeeder\Service;

use MF\Collection\Immutable\Generic\IList;
use VysokeSkoly\SolrFeeder\Entity\ColumnMapping;

class DataMapper
{
    /** @var Notifier */
    private $notifier;

    public function __construct(Notifier $notifier)
    {
        $this->notifier = $notifier;
    }

    public function mapRows(IList $rows, IList $columnMappings): IList
    {
        $isMapping = !$columnMappings->isEmpty();
        if ($isMapping) {
            $this->notifier->notifyRowsMapping($rows);
        }

        $rows = !$isMapping
            ? $rows
            : $rows->map(function (array $row) use ($columnMappings): array {
                $mappedRow = $row;

                $columnMappings
                    ->map(function (ColumnMapping $mapping) use ($row, &$mappedRow) {
                        $mappedColumn = $mapping->getColumn();
                        $value = $row[$mappedColumn];

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
                    ->each(function (ColumnMapping $mapping) use (&$mappedRow) {
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

    private function mapRow(?string $value, ?string $separator)
    {
        if (empty($separator)) {
            return $value;
        }

        return empty($value)
            ? []
            : explode($separator, $value);
    }
}
