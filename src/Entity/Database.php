<?php

namespace VysokeSkoly\SolrFeeder\Entity;

class Database
{
    /** @var string */
    private $driver;

    /** @var string */
    private $dsn;

    /** @var string */
    private $user;

    /** @var string */
    private $password;

    public function __construct(string $driver, string $dsn, string $user, string $password)
    {
        $this->driver = $driver;
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
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
