<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use function Functional\with;
use MF\Collection\IList;
use Solarium\QueryType\Update\Result;
use Symfony\Component\Console\Style\SymfonyStyle;

class Notifier
{
    /** @var SymfonyStyle|null */
    private $io;

    public function setIo(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public function notifyRowsMapping(IList $rows): void
    {
        with($this->io, function (SymfonyStyle $io) use ($rows): void {
            $io->section('Mapping rows...');
            $io->progressStart($rows->count());
        });
    }

    public function notifyRowsMapped(IList $rows): void
    {
        with($this->io, function (SymfonyStyle $io) use ($rows): void {
            $this->finishProgress($io);
            $io->success(sprintf('%d rows mapped.', $rows->count()));
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

    public function notifyPreparingAndSendingToSolr(string $type, IList $data): void
    {
        with($this->io, function (SymfonyStyle $io) use ($type, $data): void {
            $io->section(sprintf('Preparing batches and sending to solr <%s>', $type));
            $io->progressStart($data->count());
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
                        $result->getQueryTime()
                    )
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

    public function notifyFetchedData(IList $data): void
    {
        with($this->io, function (SymfonyStyle $io) use ($data): void {
            $io->success(sprintf('%d rows fetched.', $data->count()));
        });
    }

    public function notifyNote(string $note): void
    {
        with($this->io, function (SymfonyStyle $io) use ($note): void {
            $io->note($note);
        });
    }

    public function notifyStoreCurrentTimestamps(IList $data): void
    {
        with($this->io, function (SymfonyStyle $io) use ($data): void {
            $io->section('Storing current timestamps...');
            $io->progressStart($data->count());
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
