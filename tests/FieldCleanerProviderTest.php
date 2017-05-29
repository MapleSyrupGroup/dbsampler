<?php

namespace Quidco\DbSampler\Tests;

use Quidco\DbSampler\FieldCleanerProvider;

class FieldCleanerProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldCleanerProvider
     */
    private $fieldCleaner;

    public function setUp()
    {
        $this->fieldCleaner = new FieldCleanerProvider();
    }

    public function randomDigitsProvider()
    {
        return [
            [5],
            [8],
            [32],
        ];
    }

    /**
     * @dataProvider randomDigitsProvider
     *
     * @param $numDigits
     */
    public function testRandomDigits($numDigits)
    {
        $randomDigitCleaner = $this->fieldCleaner->getCleanerByDescription('randomdigits:' . $numDigits);

        $this->assertNotNull($randomDigitCleaner);

        $cleaned = $randomDigitCleaner(null);
        $this->assertTrue(is_numeric($cleaned));
        $this->assertEquals($numDigits, strlen($cleaned));
    }

    public function testRandomDigitsDefault()
    {
        $randomDigitCleaner = $this->fieldCleaner->getCleanerByDescription('randomdigits');

        $this->assertNotNull($randomDigitCleaner);

        $cleaned = $randomDigitCleaner(null);
        $this->assertTrue(is_numeric($cleaned));
        $this->assertEquals(5, strlen($cleaned));
    }
}