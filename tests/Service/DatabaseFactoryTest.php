<?php

namespace VysokeSkoly\SolrFeeder\Tests\Service;

use VysokeSkoly\SolrFeeder\Entity\FeedingBatch;
use VysokeSkoly\SolrFeeder\Service\DatabaseFactory;
use VysokeSkoly\SolrFeeder\Service\XmlParser;
use VysokeSkoly\SolrFeeder\Tests\AbstractTestCase;

class DatabaseFactoryTest extends AbstractTestCase
{
    /** @var DatabaseFactory */
    private $databaseFactory;

    /** @var XmlParser */
    private $configParser;

    public function setUp()
    {
        $this->configParser = new XmlParser();
        $this->databaseFactory = new DatabaseFactory();
    }

    public function testShouldCreateConnectionAndSelectData()
    {
        $this->databaseSafeTest(function () {
            $config = $this->configParser->parseConfig(__DIR__ . '/../Fixtures/mysql_test_config.xml');
            $connection = $this->databaseFactory->createConnection($config->getDatabase());

            foreach ($config->getFeeding()->getBatchMap() as $batch) {
                if ($batch->getType() === FeedingBatch::TYPE_ADD) {
                    $query = $connection->query($batch->getQuery());
                    $query->execute();

                    $result = $query->fetchAll();
                    $this->assertNotEmpty($result);
                }
            }

            $this->assertInstanceOf(\PDO::class, $connection);
        });
    }
}
