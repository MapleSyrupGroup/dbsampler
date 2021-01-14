<?php

namespace Quidco\DbSampler\Tests\DatabaseSchema;

use Quidco\DbSampler\DatabaseSchema\TablesList;
use PHPUnit\Framework\TestCase;
use Quidco\DbSampler\Configuration\MigrationConfiguration;
use Quidco\DbSampler\Sampler\Matched;
use Quidco\DbSampler\Sampler\NewestById;

class TablesListTest extends TestCase
{
    public function testItThrowsAnExceptionWithAnUnknownSampler(): void
    {
        $fruits = [
            'sampler' => 'invalidsampler',
        ];

        $config = MigrationConfiguration::fromJson(\json_encode([
            'name' => 'test-migration',
            'tables' => [
                'fruits' => $fruits
            ]
        ]));

        $tablesList = TablesList::fromConfig($config);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised sampler type \'invalidsampler\' required');
        $tablesList->getTables();
    }

    public function testItCreatesTheCorrectSamplerConfig(): void
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

        $tablesList = TablesList::fromConfig($config);

        $this->assertInstanceOf(Matched::class, $tablesList->getTables()['fruits']);
        $this->assertInstanceOf(NewestById::class, $tablesList->getTables()['vegetables']);
    }
}
