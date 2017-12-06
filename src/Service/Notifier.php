<?php

namespace VysokeSkoly\SolrFeeder\Service;

use MF\Collection\IList;
use Solarium\QueryType\Update\Result;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Functional\with;

class Notifier
{
    /** @var SymfonyStyle|null */
    private $io;

    public function setIo(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    public function notifyRowsMapping(IList $rows)
    {
        with($this->io, function (SymfonyStyle $io) use ($rows) {
            $io->section('Mapping rows...');
            $io->progressStart($rows->count());
        });
    }

    public function notifyRowsMapped(IList $rows)
    {
        with($this->io, function (SymfonyStyle $io) use ($rows) {
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

    public function notifyFeeding()
    {
        with($this->io, function (SymfonyStyle $io) {
            $io->title('Solr feeding...');
        });
    }

    public function notifyPreparingAndSendingToSolr(string $type, IList $data)
    {
        with($this->io, function (SymfonyStyle $io) use ($type, $data) {
            $io->section(sprintf('Preparing batches and sending to solr <%s>', $type));
            $io->progressStart($data->count());
        });
    }

    public function notifyProgress()
    {
        with($this->io, function (SymfonyStyle $io) {
            try {
                $io->progressAdvance();
            } catch (\RuntimeException $e) {
                if ($io->isDebug()) {
                    $io->writeln('progress should advance, but ' . $e->getMessage());
                }
            }
        });
    }

    public function notifyUpdate(Result $result)
    {
        with($this->io, function (SymfonyStyle $io) use ($result) {
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

    public function notifyUpdateDone()
    {
        with($this->io, function (SymfonyStyle $io) {
            $this->finishProgress($io);
            $io->success('Sending batches is done.');
        });
    }

    public function notifyFetchData()
    {
        with($this->io, function (SymfonyStyle $io) {
            $io->section('Fetching data from database...');
        });
    }

    public function notifyFetchedData(IList $data)
    {
        with($this->io, function (SymfonyStyle $io) use ($data) {
            $io->success(sprintf('%d rows fetched.', $data->count()));
        });
    }

    public function notifyNote(string $note)
    {
        with($this->io, function (SymfonyStyle $io) use ($note) {
            $io->note($note);
        });
    }

    public function notifyStoreCurrentTimestamps(IList $data)
    {
        with($this->io, function (SymfonyStyle $io) use ($data) {
            $io->section('Storing current timestamps...');
            $io->progressStart($data->count());
        });
    }

    public function notifyCurrentTimestampsStored()
    {
        with($this->io, function (SymfonyStyle $io) {
            $this->finishProgress($io);
            $io->success('Storing is done.');
        });
    }
}
