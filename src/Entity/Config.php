<?php

namespace VysokeSkoly\SolrFeeder\Entity;

class Config
{
    /** @var string */
    private $lockFile;

    /** @var string */
    private $statusReportFile;

    /** @var Database */
    private $database;

    /** @var Timestamps */
    private $timestamps;

    /** @var Feeding */
    private $feeding;

    /** @var Solr */
    private $solr;

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


    public function getDatabase(): Database
    {
        return $this->database;
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
