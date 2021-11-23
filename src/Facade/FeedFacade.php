<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Facade;

use VysokeSkoly\SolrFeeder\Service\DatabaseFactory;
use VysokeSkoly\SolrFeeder\Service\DatabaseModel;
use VysokeSkoly\SolrFeeder\Service\Log;
use VysokeSkoly\SolrFeeder\Service\SolrFactory;
use VysokeSkoly\SolrFeeder\Service\SolrFeeder;
use VysokeSkoly\SolrFeeder\Service\XmlParser;
use VysokeSkoly\SolrFeeder\Utils\LockHandler;

class FeedFacade
{
    private XmlParser $xmlParser;

    private DatabaseFactory $databaseFactory;

    private DatabaseModel $model;

    private SolrFactory $solrFactory;

    private SolrFeeder $feeder;

    private Log $log;

    public function __construct(
        XmlParser $xmlParser,
        DatabaseFactory $databaseFactory,
        DatabaseModel $model,
        SolrFactory $solrFactory,
        SolrFeeder $feeder,
        Log $log
    ) {
        $this->xmlParser = $xmlParser;
        $this->databaseFactory = $databaseFactory;
        $this->model = $model;
        $this->solrFactory = $solrFactory;
        $this->feeder = $feeder;
        $this->log = $log;
    }

    public function feedDataToSolr(string $configPath): int
    {
        $config = $this->xmlParser->parseConfig($configPath);

        $lock = new LockHandler('solr-feeder:feed', $config->getLockFile());
        $lock->lock(true);

        $status = 0;

        try {
            $database = $this->databaseFactory->createConnection($config->getDatabase());
            $solrConfig = $config->getSolr();
            $solr = $this->solrFactory->createConnection($solrConfig);

            $timestamps = $config->getTimestamps();
            $batchSize = $solrConfig->getBatchSize();
            foreach ($config->getFeeding()->getBatchMap() as $batch) {
                $data = $this->model->getData($database, $timestamps, $batch);
                $this->feeder->feedSolr($solr, $batch, $data, $batchSize, $timestamps);
            }

            $this->log->saveStatusReport($config->getStatusReportFile(), $status);

            $lock->release();

            return $status;
        } catch (\Throwable $e) {
            $status = 1;

            $this->log->saveStatusReport($config->getStatusReportFile(), $status, $e->getMessage());
            $lock->release();

            throw $e;
        }
    }
}
