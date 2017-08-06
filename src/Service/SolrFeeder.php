<?php

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use MF\Collection\Generic\IList;
use MF\Collection\Mutable\Generic\ListCollection;
use Solarium\Client;
use Solarium\QueryType\Update\Query\Document\DocumentInterface;
use Solarium\QueryType\Update\Result;
use Symfony\Component\Console\Style\SymfonyStyle;
use VysokeSkoly\SolrFeeder\Entity\FeedingBatch;
use function Functional\with;

class SolrFeeder
{
    /** @var SymfonyStyle|null */
    private $io;

    public function feedSolr(
        Client $solr,
        FeedingBatch $batch,
        IList $data,
        int $batchSize,
        SymfonyStyle $io = null
    ): void {
        $this->io = $io;
        $this->notifyFeeding();

        Assertion::inArray($batch->getType(), FeedingBatch::TYPES);

        switch ($batch->getType()) {
            case FeedingBatch::TYPE_ADD:
                $this->add($solr, $batch->getIdColumn(), $data, $batchSize);
                break;

            case FeedingBatch::TYPE_DELETE:
                $this->delete($solr, $batch->getIdColumn(), $data, $batchSize);
                break;
        }

        throw new \Exception(
            sprintf('Method %s for type "%s" is not implemented yet.', __METHOD__, $batch->getType())
        );
    }

    private function notifyFeeding()
    {
        with($this->io, function (SymfonyStyle $io) {
            $io->title('Solr feeding...');
        });
    }

    private function add(Client $solr, string $primaryKeyColumn, IList $data, int $batchSize): void
    {
        $this->notifyPrepare('add', $data);
        $update = $solr->createUpdate();

        $batches = new ListCollection(IList::class);
        $batch = new ListCollection(DocumentInterface::class);

        $data
            ->map(
                function (array $row) use ($update, $primaryKeyColumn) {
                    Assertion::keyExists($row, $primaryKeyColumn);
                    $document = $update->createDocument();

                    foreach ($row as $column => $value) {
                        $document->{$column} = $value;
                    }

                    return $document;
                },
                DocumentInterface::class
            )
            ->each(function (DocumentInterface $document) use ($batches, &$batch, $batchSize) {
                $batch->add($document);

                if ($batch->count() >= $batchSize) {
                    $batches->add($batch);
                    $batch = new ListCollection(DocumentInterface::class);
                }
                $this->notifyProgress();
            });
        $batches->add($batch);

        $this->notifyPreparedAndSending($batches);
        $batches->each(function (IList $batch) use ($solr, $update) {
            $update->addDocuments($batch->toArray());

            $update->addCommit();
            $result = $solr->update($update);

            $this->notifyProgress();
            $this->notifyUpdate($result);
        });

        $this->notifyUpdateDone();
    }

    private function delete(Client $solr, string $primaryKeyColumn, IList $data, int $batchSize)
    {
        $this->notifyPrepare('delete', $data);
        $update = $solr->createUpdate();

        $batches = new ListCollection(IList::class);
        $batch = new ListCollection('int');

        $data
            ->map(
                function (array $row) use ($primaryKeyColumn) {
                    Assertion::keyExists($row, $primaryKeyColumn);

                    return (int) $row[$primaryKeyColumn];
                },
                'int'
            )
            ->each(function (int $id) use ($batches, &$batch, $batchSize) {
                $batch->add($id);

                if ($batch->count() >= $batchSize) {
                    $batches->add($batch);
                    $batch = new ListCollection('int');
                }
                $this->notifyProgress();
            });
        $batches->add($batch);

        $this->notifyPreparedAndSending($batches);
        $batches->each(function (IList $batch) use ($solr, $update) {
            $update->addDeleteByIds($batch->toArray());

            $update->addCommit();
            $result = $solr->update($update);

            $this->notifyProgress();
            $this->notifyUpdate($result);
        });

        $this->notifyUpdateDone();
    }

    private function notifyPrepare(string $type, IList $data)
    {
        with($this->io, function (SymfonyStyle $io) use ($type, $data) {
            $io->section(sprintf('Prepare update batch <%s> for solr', $type));

            $io->writeln('Preparing documents for solr...');
            $io->progressStart($data->count());
        });
    }

    private function notifyProgress()
    {
        with($this->io, function (SymfonyStyle $io) {
            $io->progressAdvance();
        });
    }

    private function notifyPreparedAndSending(IList $batches)
    {
        with($this->io, function (SymfonyStyle $io) use ($batches) {
            $io->progressFinish();
            $io->writeln('Documents prepared.');

            $count = $batches->count();
            $io->section(sprintf('Sending %d batches to solr...', $count));
            $io->progressStart($count);
        });
    }

    private function notifyUpdate(Result $result)
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

    private function notifyUpdateDone()
    {
        with($this->io, function (SymfonyStyle $io) {
            $io->progressFinish();
            $io->success('Sending batches is done.');
        });
    }
}
