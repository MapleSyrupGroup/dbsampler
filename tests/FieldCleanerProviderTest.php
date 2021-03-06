<?php

namespace Quidco\DbSampler\Tests;

use Quidco\DbSampler\FieldCleanerProvider;
use PHPUnit\Framework\TestCase;

class FieldCleanerProviderTest extends TestCase
{
    /**
     * @var FieldCleanerProvider
     */
    private $fieldCleaner;

    public function setUp(): void
    {
        $this->fieldCleaner = new FieldCleanerProvider();
    }

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
        $randomDigitCleaner = $this->fieldCleaner->getCleanerByDescription('randomdigits:' . $numDigits);

        $this->assertNotNull($randomDigitCleaner);

        $cleaned = $randomDigitCleaner(null);
        $this->assertTrue(is_numeric($cleaned));
        $this->assertEquals($numDigits, strlen($cleaned));
    }

    public function testRandomDigitsDefault(): void
    {
        $randomDigitCleaner = $this->fieldCleaner->getCleanerByDescription('randomdigits');

        $this->assertNotNull($randomDigitCleaner);

        $cleaned = $randomDigitCleaner(null);
        $this->assertTrue(is_numeric($cleaned));
        $this->assertEquals(5, strlen($cleaned));
    }
}
