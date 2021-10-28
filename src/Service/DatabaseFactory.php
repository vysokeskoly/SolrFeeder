<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use VysokeSkoly\SolrFeeder\Entity\Database;

class DatabaseFactory
{
    public function createConnection(Database $databaseConfig): \PDO
    {
        Assertion::inArray($databaseConfig->getDriver(), \PDO::getAvailableDrivers());

        return new \PDO($databaseConfig->getDsn(), $databaseConfig->getUser(), $databaseConfig->getPassword());
    }
}
