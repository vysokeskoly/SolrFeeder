<?php

namespace VysokeSkoly\SolrFeeder\Tests\Service;

use VysokeSkoly\SolrFeeder\Entity\Config;
use VysokeSkoly\SolrFeeder\Service\DatabaseFactory;
use VysokeSkoly\SolrFeeder\Service\DatabaseModel;
use VysokeSkoly\SolrFeeder\Service\DataMapper;
use VysokeSkoly\SolrFeeder\Service\XmlParser;
use VysokeSkoly\SolrFeeder\Tests\AbstractTestCase;
use VysokeSkoly\SolrFeeder\Utils\StringHelper;

class DatabaseModelTest extends AbstractTestCase
{
    /** @var DatabaseModel */
    private $model;

    /** @var Config */
    private $config;

    /** @var DatabaseFactory */
    private $databaseFactory;

    public function setUp()
    {
        $this->model = new DatabaseModel(new DataMapper(), new StringHelper());

        $this->config = (new XmlParser())->parseConfig(__DIR__ . '/../Fixtures/mysql_test_map_config.xml');
        $this->databaseFactory = new DatabaseFactory();
    }

    public function testShouldFetchDataAndMapThem()
    {
        $this->databaseSafeTest(function () {
            $connection = $this->databaseFactory->createConnection($this->config->getDatabase());

            $expectedData = [
                'add' => [
                    [
                        'study_id' => '1',
                        'study_name' => ['kybernetika'],
                        'study_keyword' => ['kybernetika', 'IT'],
                        'deleted' => null,
                        'study_name_str' => ['kybernetika'],
                    ],
                    [
                        'study_id' => '2',
                        'study_name' => ['ekonomika'],
                        'study_keyword' => ['ekonomika', 'ekonomie'],
                        'deleted' => null,
                        'study_name_str' => ['ekonomika'],
                    ],
                    [
                        'study_id' => '3',
                        'study_name' => ['zdravka'],
                        'study_keyword' => ['zdravka', 'zdravotnictví', 'medicína'],
                        'deleted' => null,
                        'study_name_str' => ['zdravka'],
                    ],
                ],
                'delete' => [],
            ];

            $this->prepareDatabase($connection);

            foreach ($this->config->getFeeding()->getBatchMap() as $batch) {
                $data = $this->model->getData(
                    $connection,
                    $this->config->getTimestamps(),
                    $batch
                );

                $this->assertEquals($expectedData[$batch->getType()], $data->toArray());
            }
        });
    }

    private function prepareDatabase(\PDO $connection)
    {
        $connection->query('TRUNCATE TABLE study')->execute();
        $connection->query(
            "INSERT INTO study (study_name, study_keyword, updated) VALUES
              ('kybernetika', 'kybernetika|IT', '2017-08-06 12:22:45'),
              ('ekonomika', 'ekonomika|ekonomie', '2017-08-06 12:22:45'),
              ('zdravka', 'zdravka|zdravotnictví|medicína', '2017-08-06 12:22:45')"
        )->execute();
    }
}
