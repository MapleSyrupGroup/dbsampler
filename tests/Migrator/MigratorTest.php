<?php

namespace Quidco\DbSampler\Tests\Migrator;

use Psr\Log\LoggerInterface;
use Quidco\DbSampler\Collection\TableCollection;
use Quidco\DbSampler\Collection\ViewCollection;
use Quidco\DbSampler\Configuration\MigrationConfiguration;
use Quidco\DbSampler\Migrator\Migrator;
use Quidco\DbSampler\Tests\SqliteBasedTestCase;
use Quidco\DbSampler\ReferenceStore;

class MigratorTest extends SqliteBasedTestCase
{
    public function testItThrowsAnExceptionWithAnUnknownSampler(): void
    {
        $fruits = [
            "sampler" => "invalidsampler",
        ];

        $config = MigrationConfiguration::fromJson(\json_encode([
            'name' => 'test-migration',
            "tables" => [
                "fruits" => $fruits
            ]
        ]));

        $tableCollection = TableCollection::fromConfig($config);
        $viewCollection = ViewCollection::fromConfig($config);

        $logger = $this->createMock(LoggerInterface::class);


        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised sampler type \'invalidsampler\' required');
        $migrator = new Migrator($this->sourceConnection, $this->destConnection, $logger);
        $migrator->execute('test-migration', $tableCollection, $viewCollection);
    }
}
