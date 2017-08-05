<?php

namespace VysokeSkoly\SolrFeeder\Service;

use MF\Collection\Immutable\Generic\IList;
use Symfony\Component\Console\Style\SymfonyStyle;
use VysokeSkoly\SolrFeeder\Entity\ColumnMapping;

class DataMapper
{
    const DEFAULT_SEPARATOR = ' ';

    /** @var SymfonyStyle|null */
    private $io;

    public function setIo(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public function mapRows(IList $rows, IList $columnMappings): IList
    {
        $isMapping = !$columnMappings->isEmpty();
        if ($this->io && $isMapping) {
            $this->io->section('Mapping rows...');
            $this->io->progressStart($rows->count());
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

                if ($this->io) {
                    $this->io->progressAdvance();
                }

                return $mappedRow;
            });

        if ($this->io && $isMapping) {
            $this->io->progressFinish();
            $this->io->success(sprintf('%d rows mapped.', $rows->count()));
        }

        return $rows;
    }

    private function mapRow(?string $value, ?string $separator)
    {
        return empty($value)
            ? []
            : explode($separator ?? self::DEFAULT_SEPARATOR, $value);
    }
}
