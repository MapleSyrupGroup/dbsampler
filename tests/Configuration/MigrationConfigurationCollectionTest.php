<?php

namespace Quidco\DbSampler\Tests\Configuration;

use Quidco\DbSampler\Configuration\MigrationConfiguration;
use PHPUnit\Framework\TestCase;

class MigrationConfigurationTest extends TestCase
{
    public function testANonValidJsonThrowsAnException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Migration JSON config was not valid');
        MigrationConfiguration::fromJson('hello!');
    }

    public function testANameFieldMustBeSupplied(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Migration file has no name field');
        MigrationConfiguration::fromJson('{}');
    }

    public function testTheNameIsReturned(): void
    {
        $migrationSetName = 'test-migration';

        $config = MigrationConfiguration::fromJson(\json_encode([
            'name' => $migrationSetName,
        ]));

        $this->assertSame('test-migration', $config->getName());
    }

    public function testTheDatabaseNamesAreReturned(): void
    {
        $sourceDb = 'source-db';
        $destinationDb = 'destination-db';

        $config = MigrationConfiguration::fromJson(\json_encode([
            'name' => 'test-migration',
            'sourceDb' => $sourceDb,
            'destDb' => $destinationDb,
        ]));

        $this->assertSame($sourceDb, $config->getSourceDbName());
        $this->assertSame($destinationDb, $config->getDestinationDbName());
    }

    public function testTheTableConfigIsReturned(): void
    {
        $fruits = [
            'sampler' => 'matched',
            'constraints' => [
                'name' => [
                    'apple',
                    'pear'
                ]
            ],
            'remember' => [
                'id' => 'fruit_ids'
            ]
        ];

        $vegetables = [
            'sampler' => 'NewestById',
            'idField' => 'id',
            'quantity' => 2
        ];

        $config = MigrationConfiguration::fromJson(\json_encode([
            'name' => 'test-migration',
            'tables' => [
                'fruits' => $fruits,
                'vegetables' => $vegetables,
            ]
        ]));

        $this->assertEquals(
            [
                'fruits' => $fruits,
                'vegetables' => $vegetables
            ],
            \json_decode(\json_encode($config->getTables()), true)
        );
    }

    public function testTheViewConfigIsReturned(): void
    {
        $config = MigrationConfiguration::fromJson(\json_encode([
            'name' => 'test-migration',
            'views' => [
                'basket_contents'
            ]
        ]));

        $this->assertEquals(
            ['basket_contents'],
            $config->getViews()
        );
    }
}
