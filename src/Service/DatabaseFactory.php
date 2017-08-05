<?php

namespace VysokeSkoly\SolrFeeder\Service;

use VysokeSkoly\SolrFeeder\Entity\Database;

class DatabaseFactory
{
    public function createConnection(Database $databaseConfig): \PDO
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }
}
