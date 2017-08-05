<?php

namespace VysokeSkoly\SolrFeeder\Facade;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\LockHandler;
use VysokeSkoly\SolrFeeder\Service\DatabaseFactory;
use VysokeSkoly\SolrFeeder\Service\DatabaseModel;
use VysokeSkoly\SolrFeeder\Service\SolrFactory;
use VysokeSkoly\SolrFeeder\Service\SolrFeeder;
use VysokeSkoly\SolrFeeder\Service\XmlParser;

class FeedFacade
{
    /** @var XmlParser */
    private $xmlParser;

    /** @var DatabaseFactory */
    private $databaseFactory;

    /** @var DatabaseModel */
    private $model;

    /** @var SolrFactory */
    private $solrFactory;

    /** @var SolrFeeder */
    private $feeder;

    public function __construct(
        XmlParser $xmlParser,
        DatabaseFactory $databaseFactory,
        DatabaseModel $model,
        SolrFactory $solrFactory,
        SolrFeeder $feeder
    ) {
        $this->xmlParser = $xmlParser;
        $this->databaseFactory = $databaseFactory;
        $this->model = $model;
        $this->solrFactory = $solrFactory;
        $this->feeder = $feeder;
    }

    public function feedDataToSolr(string $configPath, SymfonyStyle $io): void
    {
        $config = $this->xmlParser->parseConfig($configPath);

        $lock = new LockHandler('solr-feeder:feed', $config->getLockFile());
        $lock->lock(true);

        $database = $this->databaseFactory->createConnection($config->getDatabase());
        $data = $this->model->getData($database, $config->getFeeding(), $io);

        $solr = $this->solrFactory->createConnection($config->getSolr());
        $this->feeder->feedSolr($solr, $data, $io);

        $lock->release();
    }
}
