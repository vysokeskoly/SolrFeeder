<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Entity;

class Config
{
    public function __construct(
        private readonly string $lockFile,
        private readonly string $statusReportFile,
        private readonly Database $database,
        private readonly Timestamps $timestamps,
        private readonly Feeding $feeding,
        private readonly Solr $solr,
    ) {
    }

    public function getLockFile(): string
    {
        return $this->lockFile;
    }

    public function getStatusReportFile(): string
    {
        return $this->statusReportFile;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getTimestamps(): Timestamps
    {
        return $this->timestamps;
    }

    public function getFeeding(): Feeding
    {
        return $this->feeding;
    }

    public function getSolr(): Solr
    {
        return $this->solr;
    }
}
