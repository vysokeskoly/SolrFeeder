<?php

namespace VysokeSkoly\SolrFeeder\Entity;

use VysokeSkoly\SolrFeeder\Constant\Functions as f;
use VysokeSkoly\SolrFeeder\Utils\Curry;
use function Functional\compose;

class Database
{
    const DRIVER_PGSQL = 'pgsql';
    const DRIVER_MYSQL = 'mysql';

    const DRIVERS = [
        'org.postgresql.Driver' => self::DRIVER_PGSQL,
        'org.mysql.Driver' => self::DRIVER_MYSQL,
    ];

    const DSN_TEMPLATE = '%s:host=%s;port=%d;dbname=%s;';

    /** @var string */
    private $driver;

    /** @var string */
    private $dsn;

    /** @var string */
    private $user;

    /** @var string */
    private $password;

    public function __construct(string $driver, string $connection, string $user, string $password)
    {
        $this->driver = self::DRIVERS[$driver] ?? $driver;
        $this->dsn = $this->parseDsn($connection);
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param string $connection 'jdbc:DRIVER://HOST:PORT/DB_NAME'
     * @return string
     */
    private function parseDsn(string $connection): string
    {
        $driver = $this->driver;
        $splitDriverAndRest = Curry::explode()('//');
        $splitHostAndDbName = Curry::explode()('/');
        $splitHostAndPort = Curry::explode()(':');

        list($hostPort, $dbName) = compose($splitDriverAndRest, f::LAST, $splitHostAndDbName)($connection);
        list($host, $port) = $splitHostAndPort($hostPort);

        return sprintf(self::DSN_TEMPLATE, $driver, $host, $port, $dbName);
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
