<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Entity;

class Config
{
    private string $lockFile;

    private string $statusReportFile;

    private Database $database;

    private Timestamps $timestamps;

    private Feeding $feeding;

    private Solr $solr;

    public function __construct(
        string $lockFile,
        string $statusReportFile,
        Database $database,
        Timestamps $timestamps,
        Feeding $feeding,
        Solr $solr
    ) {
        $this->lockFile = $lockFile;
        $this->statusReportFile = $statusReportFile;
        $this->timestamps = $timestamps;
        $this->database = $database;
        $this->feeding = $feeding;
        $this->solr = $solr;
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
