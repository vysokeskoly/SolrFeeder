<?php

namespace VysokeSkoly\SolrFeeder\Tests\Service;

use VysokeSkoly\SolrFeeder\Entity\Config;
use VysokeSkoly\SolrFeeder\Service\DatabaseFactory;
use VysokeSkoly\SolrFeeder\Service\DatabaseModel;
use VysokeSkoly\SolrFeeder\Service\DataMapper;
use VysokeSkoly\SolrFeeder\Service\Notifier;
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
        $notifier = new Notifier();
        $this->databaseFactory = new DatabaseFactory();

        $this->model = new DatabaseModel(new DataMapper($notifier), new StringHelper(), $notifier);
    }

    public function testShouldFetchDataAndMapThem()
    {
        $this->config = (new XmlParser())->parseConfig(__DIR__ . '/../Fixtures/mysql_test_map_config.xml');

        $expectedData = [
            'add' => [
                [
                    'study_id' => '1',
                    'study_name' => 'kybernetika',
                    'study_keyword' => ['kybernetika', 'IT'],
                    'deleted' => null,
                    'study_name_str' => 'kybernetika',
                ],
                [
                    'study_id' => '2',
                    'study_name' => 'ekonomika',
                    'study_keyword' => ['ekonomika', 'ekonomie'],
                    'deleted' => null,
                    'study_name_str' => 'ekonomika',
                ],
                [
                    'study_id' => '3',
                    'study_name' => 'zdravka',
                    'study_keyword' => ['zdravka', 'zdravotnictví', 'medicína'],
                    'deleted' => null,
                    'study_name_str' => 'zdravka',
                ],
            ],
            'delete' => [],
        ];

        $this->databaseSafeTest(function () use ($expectedData) {
            $connection = $this->databaseFactory->createConnection($this->config->getDatabase());
            $this->prepareDatabaseForMap($connection);

            foreach ($this->config->getFeeding()->getBatchMap() as $batch) {
                $data = $this->model->getData($connection, $this->config->getTimestamps(), $batch);

                $this->assertEquals($expectedData[$batch->getType()], $data->toArray());
            }
        });
    }

    private function prepareDatabaseForMap(\PDO $connection)
    {
        $connection->query('TRUNCATE TABLE study')->execute();
        $connection->query(
            "INSERT INTO study (study_name, study_keyword, updated) VALUES
              ('kybernetika', 'kybernetika|IT', '2017-08-06 12:22:45'),
              ('ekonomika', 'ekonomika|ekonomie', '2017-08-06 12:22:45'),
              ('zdravka', 'zdravka|zdravotnictví|medicína', '2017-08-06 12:22:45')"
        )->execute();
    }

    public function testShouldFetchDataByLastTimestampsAndMapThem()
    {
        $this->config = (new XmlParser())
            ->parseConfig(__DIR__ . '/../Fixtures/mysql_test_map_config_with_timestamps.xml');

        $expectedData = [
            'add' => [
                [
                    'study_id' => '3',
                    'study_name' => 'zdravka',
                    'study_keyword' => ['zdravka', 'zdravotnictví', 'medicína'],
                    'deleted' => null,
                    'study_name_str' => 'zdravka',
                ],
                [
                    'study_id' => '2',
                    'study_name' => 'ekonomika',
                    'study_keyword' => ['ekonomika', 'ekonomie'],
                    'deleted' => null,
                    'study_name_str' => 'ekonomika',
                ],
            ],
            'delete' => [],
        ];

        $expectedCurrentUpdated = '2018-08-06 12:22:45';
        $expectedCurrentDeleted = '1970-01-01 00:00:00';

        $this->databaseSafeTest(function () use ($expectedCurrentUpdated, $expectedCurrentDeleted, $expectedData) {
            $connection = $this->databaseFactory->createConnection($this->config->getDatabase());
            $this->prepareDatabaseForTimestamps($connection);

            foreach ($this->config->getFeeding()->getBatchMap() as $batch) {
                $data = $this->model->getData($connection, $this->config->getTimestamps(), $batch);

                $this->assertEquals($expectedData[$batch->getType()], $data->toArray());
            }

            $timestampMap = $this->config->getTimestamps()->getTimestampMap();
            $this->assertSame($expectedCurrentUpdated, $timestampMap->get('updated')->getValue('%%CURRENT_UPDATED%%'));
            $this->assertSame($expectedCurrentDeleted, $timestampMap->get('deleted')->getValue('%%CURRENT_DELETED%%'));
        });
    }

    private function prepareDatabaseForTimestamps(\PDO $connection)
    {
        $connection->query('TRUNCATE TABLE study')->execute();
        $connection->query(
            "INSERT INTO study (study_name, study_keyword, updated) VALUES
              ('kybernetika', 'kybernetika|IT', '2016-08-06 12:22:45'),
              ('ekonomika', 'ekonomika|ekonomie', '2018-08-06 12:22:45'),
              ('zdravka', 'zdravka|zdravotnictví|medicína', '2018-08-06 00:22:45')"
        )->execute();
    }

    private function fillDatabase(\PDO $connection)
    {
        $connection->query('TRUNCATE TABLE study')->execute();

        foreach (range(1, 150) as $i) {
            $values = [];
            foreach (range(1, 35) as $j) {
                $values = array_merge($values, [
                    "('$i _ $j _ kybernetika', 'kybernetika|IT', '2016-08-06 12:22:45')",
                    "('$i _ $j _ ekonomika', 'ekonomika|ekonomie', '2018-08-06 12:22:45')",
                    "('$i _ $j _ zdravka', 'zdravka|zdravotnictví|medicína', '2018-08-06 00:22:45')",
                ]);
            }
            $query = "INSERT INTO study (study_name, study_keyword, updated) VALUES " . implode(',', $values);

            $connection->query($query)->execute();
        }
    }
}
