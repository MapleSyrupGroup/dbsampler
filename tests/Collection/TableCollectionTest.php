<?php

namespace Quidco\DbSampler\Tests\Collection;

use Quidco\DbSampler\Collection\TableCollection;
use PHPUnit\Framework\TestCase;
use Quidco\DbSampler\Configuration\MigrationConfiguration;
use Quidco\DbSampler\Sampler\Matched;
use Quidco\DbSampler\Sampler\NewestById;
use Quidco\DbSampler\ReferenceStore;

class TableCollectionTest extends TestCase
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised sampler type \'invalidsampler\' required');
        $tableCollection->getTables(new ReferenceStore());
    }

    public function testItCreatesTheCorrectSamplerConfig(): void
    {
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

        $this->assertInstanceOf(Matched::class, $tableCollection->getTables(new ReferenceStore())['fruits']);
        $this->assertInstanceOf(NewestById::class, $tableCollection->getTables(new ReferenceStore())['vegetables']);
    }
}
