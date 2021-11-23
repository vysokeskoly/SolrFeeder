<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use MF\Collection\Generic\IList;
use MF\Collection\Mutable\Generic\ListCollection;
use Solarium\Client;
use Solarium\Core\Query\DocumentInterface;
use Solarium\QueryType\Update\Query\Query;
use VysokeSkoly\SolrFeeder\Entity\FeedingBatch;
use VysokeSkoly\SolrFeeder\Entity\Timestamp;
use VysokeSkoly\SolrFeeder\Entity\Timestamps;
use VysokeSkoly\SolrFeeder\ValueObject\PrimaryKey;

class SolrFeeder
{
    private Notifier $notifier;

    private TimestampUpdater $timestampUpdater;

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
        /** @var PrimaryKey|null $primaryKey */
        $primaryKey = null;

        $data->each(function (array $row) use (
            $solr,
            $timestamps,
            &$update,
            $primaryKeyColumn,
            &$batch,
            $batchSize,
            &$primaryKey
        ): void {
            Assertion::keyExists($row, $primaryKeyColumn);
            $primaryKey = new PrimaryKey($row, $primaryKeyColumn);
            $document = $update->createDocument();

            foreach ($row as $column => $value) {
                $document->{$column} = $value;
            }

            $batch->add($document);

            if ($batch->count() >= $batchSize) {
                $update->addDocuments($batch->toArray());
                $this->sendAddToSolr($solr, $timestamps, $update, Timestamp::TYPE_UPDATED, $primaryKey);

                $update = $solr->createUpdate();
                $batch = new ListCollection(DocumentInterface::class);
            }

            $this->notifier->notifyProgress();
        });

        $update->addDocuments($batch->toArray());
        $this->sendAddToSolr($solr, $timestamps, $update, Timestamp::TYPE_UPDATED, $primaryKey);

        $this->notifier->notifyUpdateDone();
    }

    private function sendAddToSolr(
        Client $solr,
        Timestamps $timestamps,
        Query $update,
        string $type,
        ?PrimaryKey $primaryKey
    ): void {
        if ($primaryKey === null) {
            return;
        }

        $update->addCommit();
        $result = $solr->update($update);

        $this->timestampUpdater->updateCurrentTimestamps($timestamps, $type, $primaryKey);
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
        /** @var PrimaryKey|null $primaryKey */
        $primaryKey = null;

        $data->each(function (array $row) use (
            $batchSize,
            $solr,
            $timestamps,
            &$update,
            &$batch,
            $primaryKeyColumn,
            &$primaryKey
        ): void {
            $primaryKey = new PrimaryKey($row, $primaryKeyColumn);

            $batch->add($primaryKey->getIntValue());

            if ($batch->count() >= $batchSize) {
                $update->addDeleteByIds($batch->toArray());
                $this->sendAddToSolr($solr, $timestamps, $update, Timestamp::TYPE_DELETED, $primaryKey);

                $batch = new ListCollection('int');
            }
            $this->notifier->notifyProgress();
        });

        $update->addDeleteByIds($batch->toArray());
        $this->sendAddToSolr($solr, $timestamps, $update, Timestamp::TYPE_DELETED, $primaryKey);

        $this->notifier->notifyUpdateDone();
    }
}
