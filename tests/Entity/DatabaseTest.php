<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Tests\Entity;

use PHPUnit\Framework\TestCase;
use VysokeSkoly\SolrFeeder\Entity\Database;

class DatabaseTest extends TestCase
{
    public function testShouldCreateCorrectPgsqlDsn(): void
    {
        $database = new Database(
            'org.postgresql.Driver',
            'postgresql://dbvysokeskoly:5432/vysokeskoly',
            'vysokeskoly',
            'vysokeskoly'
        );

        $expectedDsn = sprintf('%s:host=%s;port=%d;dbname=%s;', 'pgsql', 'dbvysokeskoly', 5432, 'vysokeskoly');

        $this->assertSame($expectedDsn, $database->getDsn());
    }

    public function testShouldCreateCorrectMysqlDsn(): void
    {
        $database = new Database(
            'org.mysql.Driver',
            'mysql://dbvysokeskoly:3306/vysokeskoly',
            'vysokeskoly',
            'vysokeskoly'
        );

        $expectedDsn = sprintf('%s:host=%s;port=%d;dbname=%s;', 'mysql', 'dbvysokeskoly', 3306, 'vysokeskoly');

        $this->assertSame($expectedDsn, $database->getDsn());
    }
}
