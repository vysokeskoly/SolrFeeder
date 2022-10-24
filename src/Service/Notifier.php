<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use function Functional\with;
use MF\Collection\Immutable\Generic\IList;
use Solarium\QueryType\Update\Result;
use Symfony\Component\Console\Style\SymfonyStyle;

class Notifier
{
    private ?SymfonyStyle $io = null;

    public function setIo(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    /** @phpstan-param IList<array> $rows */
    public function notifyRowsMapping(IList $rows): void
    {
        with($this->io, function (SymfonyStyle $io) use ($rows): void {
            $io->section('Mapping rows...');
            $io->progressStart(count($rows));
        });
    }

    /** @phpstan-param IList<array> $rows */
    public function notifyRowsMapped(IList $rows): void
    {
        with($this->io, function (SymfonyStyle $io) use ($rows): void {
            $this->finishProgress($io);
            $io->success(sprintf('%d rows mapped.', count($rows)));
        });
    }

    private function finishProgress(SymfonyStyle $io): void
    {
        try {
            $io->progressFinish();
        } catch (\RuntimeException $e) {
            if ($io->isDebug()) {
                $io->writeln('progress should finish, but ' . $e->getMessage());
            }
        }
    }

    public function notifyFeeding(): void
    {
        with($this->io, function (SymfonyStyle $io): void {
            $io->title('Solr feeding...');
        });
    }

    /** @phpstan-param IList<array> $data */
    public function notifyPreparingAndSendingToSolr(string $type, IList $data): void
    {
        with($this->io, function (SymfonyStyle $io) use ($type, $data): void {
            $io->section(sprintf('Preparing batches and sending to solr <%s>', $type));
            $io->progressStart(count($data));
        });
    }

    public function notifyProgress(): void
    {
        with($this->io, function (SymfonyStyle $io): void {
            try {
                $io->progressAdvance();
            } catch (\RuntimeException $e) {
                if ($io->isDebug()) {
                    $io->writeln('progress should advance, but ' . $e->getMessage());
                }
            }
        });
    }

    public function notifyUpdate(Result $result): void
    {
        with($this->io, function (SymfonyStyle $io) use ($result): void {
            if ($io->isVerbose()) {
                $io->writeln(
                    sprintf(
                        'Update query executed with status "%s". [in %s s]',
                        $result->getStatus(),
                        $result->getQueryTime(),
                    ),
                );
            }
        });
    }

    public function notifyUpdateDone(): void
    {
        with($this->io, function (SymfonyStyle $io): void {
            $this->finishProgress($io);
            $io->success('Sending batches is done.');
        });
    }

    public function notifyFetchData(): void
    {
        with($this->io, function (SymfonyStyle $io): void {
            $io->section('Fetching data from database...');
        });
    }

    /** @phpstan-param IList<array> $data */
    public function notifyFetchedData(IList $data): void
    {
        with($this->io, function (SymfonyStyle $io) use ($data): void {
            $io->success(sprintf('%d rows fetched.', count($data)));
        });
    }

    public function notifyNote(string $note): void
    {
        with($this->io, function (SymfonyStyle $io) use ($note): void {
            $io->note($note);
        });
    }

    /** @phpstan-param IList<array> $data */
    public function notifyStoreCurrentTimestamps(IList $data): void
    {
        with($this->io, function (SymfonyStyle $io) use ($data): void {
            $io->section('Storing current timestamps...');
            $io->progressStart(count($data));
        });
    }

    public function notifyCurrentTimestampsStored(): void
    {
        with($this->io, function (SymfonyStyle $io): void {
            $this->finishProgress($io);
            $io->success('Storing is done.');
        });
    }
}
