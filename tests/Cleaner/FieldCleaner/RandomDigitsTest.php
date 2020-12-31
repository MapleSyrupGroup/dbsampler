<?php

namespace Quidco\DbSampler\Tests\Cleaner\FieldCleaner;

use Quidco\DbSampler\Cleaner\FieldCleaner\RandomDigits;
use PHPUnit\Framework\TestCase;

class RandomDigitsTest extends TestCase
{
    public function randomDigitsProvider(): array
    {
        return [
            [5],
            [8],
            [32],
        ];
    }

    /**
     * @dataProvider randomDigitsProvider
     */
    public function testRandomDigits(int $numDigits): void
    {
        $cleaner = new RandomDigits();

        $cleaned = $cleaner->clean([$numDigits]);

        $this->assertTrue(is_numeric($cleaned));
        $this->assertEquals($numDigits, strlen($cleaned));
    }

    public function testRandomDigitsDefault(): void
    {
        $cleaner = new RandomDigits();

        $cleaned = $cleaner->clean([]);

        $this->assertTrue(is_numeric($cleaned));
        $this->assertEquals(5, strlen($cleaned));
    }
}
