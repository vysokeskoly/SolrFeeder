<?php

namespace VysokeSkoly\SolrFeeder\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected function databaseSafeTest(callable $test): void
    {
        try {
            $test();
        } catch (\PDOException $e) {
            $this->markTestSkipped(sprintf('Database factory test skipped due: %s', $e->getMessage()));
        }
    }

    public function tearDown()
    {
        m::close();
    }
}
