<?php

namespace Quidco\DbSampler\Tests\Cleaner\FieldCleaner;

use Quidco\DbSampler\Cleaner\FieldCleaner\DateTime;
use PHPUnit\Framework\TestCase;

class DateTimeTest extends TestCase
{
    public function testNow(): void
    {
        $cleaner = new DateTime();

        $result = $cleaner->clean([]);

        $this->assertNotFalse(\DateTimeImmutable::createFromFormat(DateTime::DATE_FORMAT, $result));
    }

    public function testDeterministicDateTime(): void
    {
        $cleaner = new DateTime();

        $result = $cleaner->clean(['10 September 2000']);

        $dateTime = \DateTimeImmutable::createFromFormat(DateTime::DATE_FORMAT, $result);

        $this->assertSame('2000', $dateTime->format('Y'));
        $this->assertSame('09', $dateTime->format('m'));
        $this->assertSame('10', $dateTime->format('d'));
    }
}
