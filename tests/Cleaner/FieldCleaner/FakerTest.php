<?php

namespace Quidco\DbSampler\Tests\Cleaner\FieldCleaner;

use Faker\Factory;
use Quidco\DbSampler\Cleaner\FieldCleaner\Faker;
use PHPUnit\Framework\TestCase;

class FakerTest extends TestCase
{
    public function testAnExceptionIsThrownWhenUsingInvalidParameter(): void
    {
        $cleaner = new Faker(Factory::create());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Faker does not support 'badtype'");

        $cleaner->clean(['badtype']);
    }

    public function testACompanyNameIsReturned(): void
    {
        $cleaner = new Faker(Factory::create());

        $result = $cleaner->clean(['company']);

        $this->assertRegExp("/^[a-zA-Z\ \.\,\-]+$/", $result);
    }
}
