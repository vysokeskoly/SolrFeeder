<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Mutable\Generic\ListCollection;
use Solarium\Client;
use Solarium\Core\Query\DocumentInterface;
use Solarium\QueryType\Update\Query\Query;
use VysokeSkoly\SolrFeeder\Entity\FeedingBatch;
use VysokeSkoly\SolrFeeder\Entity\Timestamp;
use VysokeSkoly\SolrFeeder\Entity\Timestamps;
use VysokeSkoly\SolrFeeder\ValueObject\PrimaryKey;

/** @phpstan-import-type MappedRow from DataMapper */
class SolrFeeder
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly TimestampUpdater $timestampUpdater,
    ) {
    }

    /** @phpstan-param IList<MappedRow> $data */
    public function feedSolr(
        Client $solr,
        FeedingBatch $batch,
        IList $data,
        int $batchSize,
        Timestamps $timestamps,
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

    /** @phpstan-param IList<MappedRow> $data */
    private function add(
        Client $solr,
        string $primaryKeyColumn,
        IList $data,
        int $batchSize,
        Timestamps $timestamps,
    ): void {
        $this->notifier->notifyPreparingAndSendingToSolr('add', $data);

        $update = $solr->createUpdate();
        /** @var IList<DocumentInterface> $batch */
        $batch = new ListCollection();
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

                /** @var IList<DocumentInterface> $batch */
                $batch = new ListCollection();
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
        ?PrimaryKey $primaryKey,
    ): void {
        if ($primaryKey === null) {
            return;
        }

        $update->addCommit();
        $result = $solr->update($update);

        $this->timestampUpdater->updateCurrentTimestamps($timestamps, $type, $primaryKey);
        $this->notifier->notifyUpdate($result);
    }

    /** @phpstan-param IList<MappedRow> $data */
    private function delete(
        Client $solr,
        string $primaryKeyColumn,
        IList $data,
        int $batchSize,
        Timestamps $timestamps,
    ): void {
        $this->notifier->notifyPreparingAndSendingToSolr('delete', $data);

        $update = $solr->createUpdate();
        /** @var IList<int> $batch */
        $batch = new ListCollection();
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

                /** @var IList<int> $batch */
                $batch = new ListCollection();
            }
            $this->notifier->notifyProgress();
        });

        $update->addDeleteByIds($batch->toArray());
        $this->sendAddToSolr($solr, $timestamps, $update, Timestamp::TYPE_DELETED, $primaryKey);

        $this->notifier->notifyUpdateDone();
    }
}
