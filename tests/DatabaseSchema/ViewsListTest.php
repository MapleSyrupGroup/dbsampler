<?php

namespace Quidco\DbSampler\Tests\DatabaseSchema;

use PHPUnit\Framework\TestCase;
use Quidco\DbSampler\DatabaseSchema\ViewsList;
use Quidco\DbSampler\Configuration\MigrationConfiguration;

class ViewsListTest extends TestCase
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

        $viewsList = ViewsList::fromConfig($config);

        $this->assertSame($views, $viewsList->getViews());
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

        $viewsList = ViewsList::fromConfig($config);

        $this->assertSame([], $viewsList->getViews());
    }
}
