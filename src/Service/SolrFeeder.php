<?php

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use MF\Collection\Generic\IList;
use MF\Collection\Mutable\Generic\ListCollection;
use Solarium\Client;
use Solarium\QueryType\Update\Query\Document\DocumentInterface;
use Solarium\QueryType\Update\Query\Query;
use VysokeSkoly\SolrFeeder\Entity\FeedingBatch;
use VysokeSkoly\SolrFeeder\Entity\Timestamp;
use VysokeSkoly\SolrFeeder\Entity\Timestamps;

class SolrFeeder
{
    /** @var Notifier */
    private $notifier;

    /** @var TimestampUpdater */
    private $timestampUpdater;

    public function __construct(Notifier $notifier, TimestampUpdater $timestampUpdater)
    {
        $this->notifier = $notifier;
        $this->timestampUpdater = $timestampUpdater;
    }

    public function feedSolr(
        Client $solr,
        FeedingBatch $batch,
        IList $data,
        int $batchSize,
        Timestamps $timestamps
    ): void {
        $this->notifier->notifyFeeding();

        $type = $batch->getType();
        Assertion::inArray($type, FeedingBatch::TYPES);

        switch ($type) {
            case FeedingBatch::TYPE_ADD:
                $this->add($solr, $batch->getIdColumn(), $data, $batchSize, $timestamps);
                break;

            case FeedingBatch::TYPE_DELETE:
                $this->delete($solr, $batch->getIdColumn(), $data, $batchSize, $timestamps);
                break;
        }

        $timestamps->saveValuesToFile();
    }

    private function add(
        Client $solr,
        string $primaryKeyColumn,
        IList $data,
        int $batchSize,
        Timestamps $timestamps
    ): void {
        $this->notifier->notifyPreparingAndSendingToSolr('add', $data);

        $update = $solr->createUpdate();
        $batch = new ListCollection(DocumentInterface::class);
        $primaryKeyValue = null;

        $data->each(function (array $row) use (
            $solr,
            $timestamps,
            &$update,
            $primaryKeyColumn,
            &$batch,
            $batchSize,
            &$primaryKeyValue
        ) {
            Assertion::keyExists($row, $primaryKeyColumn);
            $primaryKeyValue = $row[$primaryKeyColumn];
            $document = $update->createDocument();

            foreach ($row as $column => $value) {
                $document->{$column} = $value;
            }

            $batch->add($document);

            if ($batch->count() >= $batchSize) {
                $update->addDocuments($batch->toArray());
                $this->sendAddToSolr($solr, $timestamps, $update, Timestamp::TYPE_UPDATED, $primaryKeyValue);

                $update = $solr->createUpdate();
                $batch = new ListCollection(DocumentInterface::class);
            }

            $this->notifier->notifyProgress();
        });

        $update->addDocuments($batch->toArray());
        $this->sendAddToSolr($solr, $timestamps, $update, Timestamp::TYPE_UPDATED, $primaryKeyValue);

        $this->notifier->notifyUpdateDone();
    }

    private function sendAddToSolr(
        Client $solr,
        Timestamps $timestamps,
        Query $update,
        string $type,
        ?string $primaryKeyValue
    ) {
        if (empty($primaryKeyValue)) {
            return;
        }

        $update->addCommit();
        $result = $solr->update($update);

        $this->timestampUpdater->updateCurrentTimestamps($timestamps, $type, $primaryKeyValue);
        $this->notifier->notifyUpdate($result);
    }

    private function delete(
        Client $solr,
        string $primaryKeyColumn,
        IList $data,
        int $batchSize,
        Timestamps $timestamps
    ): void {
        $this->notifier->notifyPreparingAndSendingToSolr('delete', $data);

        $update = $solr->createUpdate();
        $batch = new ListCollection('int');
        $primaryKeyValue = null;

        $data->each(function (array $row) use (
            $batchSize,
            $solr,
            $timestamps,
            &$update,
            &$batch,
            $primaryKeyColumn,
            &$primaryKeyValue
        ) {
            Assertion::keyExists($row, $primaryKeyColumn);
            $primaryKeyValue = $row[$primaryKeyColumn];

            $batch->add((int) $primaryKeyValue);

            if ($batch->count() >= $batchSize) {
                $update->addDeleteByIds($batch->toArray());
                $this->sendAddToSolr($solr, $timestamps, $update, Timestamp::TYPE_DELETED, $primaryKeyValue);

                $batch = new ListCollection('int');
            }
            $this->notifier->notifyProgress();
        });

        $update->addDeleteByIds($batch->toArray());
        $this->sendAddToSolr($solr, $timestamps, $update, Timestamp::TYPE_DELETED, $primaryKeyValue);

        $this->notifier->notifyUpdateDone();
    }
}
