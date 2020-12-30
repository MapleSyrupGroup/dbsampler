<?php

namespace Quidco\DbSampler\Tests\Migrator;

use Psr\Log\LoggerInterface;
use Quidco\DbSampler\Collection\TableCollection;
use Quidco\DbSampler\Collection\ViewCollection;
use Quidco\DbSampler\Configuration\MigrationConfiguration;
use Quidco\DbSampler\Migrator\Migrator;
use Quidco\DbSampler\Sampler\MatchedRows;
use Quidco\DbSampler\Sampler\NewestById;
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

    public function testItCreatesTheCorrectSamplerConfig(): void
    {
        $this->markTestSkipped();
        $fruits = [
            "sampler" => "matched",
            "constraints" => [
                "name" => [
                    "apple",
                    "pear"
                ]
            ],
            "remember" => [
                "id" => "fruit_ids"
            ]
        ];

        $vegetables = [
            "sampler" => "NewestById",
            "idField" => "id",
            "quantity" => 2
        ];

        $config = MigrationConfiguration::fromJson(\json_encode([
            'name' => 'test-migration',
            "tables" => [
                "fruits" => $fruits,
                "vegetables" => $vegetables,
            ]
        ]));

        $tableCollection = TableCollection::fromConfig($config);

        $this->assertInstanceOf(MatchedRows::class, $tableCollection->getTables(new ReferenceStore())['fruits']);
        $this->assertInstanceOf(NewestById::class, $tableCollection->getTables(new ReferenceStore())['vegetables']);
    }
}
