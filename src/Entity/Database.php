<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Entity;

use function Functional\compose;
use VysokeSkoly\SolrFeeder\Constant\Functions as f;
use VysokeSkoly\SolrFeeder\Utils\Curry;

class Database
{
    public const DRIVER_PGSQL = 'pgsql';
    public const DRIVER_MYSQL = 'mysql';

    public const DRIVERS = [
        'org.postgresql.Driver' => self::DRIVER_PGSQL,
        'org.mysql.Driver' => self::DRIVER_MYSQL,
    ];

    public const DSN_TEMPLATE = '%s:host=%s;port=%d;dbname=%s;';

    private readonly string $driver;
    private readonly string $dsn;

    public function __construct(
        string $driver,
        string $connection,
        private readonly string $user,
        private readonly string $password,
    ) {
        $this->driver = self::DRIVERS[$driver] ?? $driver;
        $this->dsn = $this->parseDsn($connection);
    }

    /**
     * @param string $connection 'jdbc:DRIVER://HOST:PORT/DB_NAME'
     */
    private function parseDsn(string $connection): string
    {
        $splitBy = Curry::explode();
        $splitDriverAndRest = $splitBy('//');
        $splitHostAndDbName = $splitBy('/');
        $splitHostAndPort = $splitBy(':');

        [$hostPort, $dbName] = compose($splitDriverAndRest, f::LAST, $splitHostAndDbName)($connection);
        [$host, $port] = $splitHostAndPort($hostPort);

        return sprintf(self::DSN_TEMPLATE, $this->driver, $host, $port, $dbName);
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
