<?php

namespace Quidco\DbSampler\Tests\Collection;

use PHPUnit\Framework\TestCase;
use Quidco\DbSampler\Collection\ViewCollection;
use Quidco\DbSampler\Configuration\MigrationConfiguration;

class ViewCollectionTest extends TestCase
{
    public function testItReturnsTheListOfViews(): void
    {
        $views = [
            'fruit_basket',
            'hamper'
        ];

        $config = MigrationConfiguration::fromJson(\json_encode([
            'name' => 'test-migration',
            'views' => $views
        ]));

        $viewCollection = ViewCollection::fromConfig($config);

        $this->assertSame($views, $viewCollection->getViews());
    }

    public function testViewsAreOptional(): void
    {
        $fruits = [
            'sampler' => 'matched',
        ];

        $config = MigrationConfiguration::fromJson(\json_encode([
            'name' => 'test-migration',
            'tables' => [
                'fruits' => $fruits,
            ]
        ]));

        $viewCollection = ViewCollection::fromConfig($config);

        $this->assertSame([], $viewCollection->getViews());
    }
}
