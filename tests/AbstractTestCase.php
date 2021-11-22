<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

abstract class AbstractTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @before
     */
    public function init(): void
    {
        $dir = __DIR__ . '/../var';
        if (file_exists($dir)) {
            (new Process(['rm', '-rf', $dir]))->run();
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
}
