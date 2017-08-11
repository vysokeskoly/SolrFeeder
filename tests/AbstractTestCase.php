<?php

namespace VysokeSkoly\SolrFeeder\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @before
     */
    public function init()
    {
        $dir = __DIR__ . '/../var';
        if (file_exists($dir)) {
            (new Process('rm -rf ' . $dir))->run();
        }
    }

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
