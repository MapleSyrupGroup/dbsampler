<?php

namespace Quidco\DbSampler\Tests\Cleaner;

use Quidco\DbSampler\Cleaner\CleanerConfig;
use Quidco\DbSampler\Cleaner\FieldCleanerFactory;
use PHPUnit\Framework\TestCase;

class FieldCleanerFactoryTest extends TestCase
{
    public function testAnIncorrectCleanerThrowsAnException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configured cleaner is not valid!');
        FieldCleanerFactory::getCleaner(CleanerConfig::fromString('unknowncleaner'));
    }

    /**
     * @dataProvider builtInCleaners
     */
    public function testTheCorrectCleanerIsReturned(string $cleanerName): void
    {
        $cleaner = FieldCleanerFactory::getCleaner(CleanerConfig::fromString($cleanerName));

        $this->assertInstanceOf(FieldCleanerFactory::CLEANERS[$cleanerName], $cleaner);
    }

    public function builtInCleaners(): array
    {
        $cleaners = FieldCleanerFactory::CLEANERS;

        return array_reduce(\array_keys($cleaners), function ($initial, $item) {
            $initial[] = [$item];
            return $initial;
        }, []);
    }
}
