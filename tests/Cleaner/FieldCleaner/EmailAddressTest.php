<?php

namespace Quidco\DbSampler\Tests\Cleaner\FieldCleaner;

use Faker\Factory;
use Quidco\DbSampler\Cleaner\FieldCleaner\EmailAddress;
use PHPUnit\Framework\TestCase;

class EmailAddressTest extends TestCase
{
    public function testItReturnsAnEmailAddress(): void
    {
        $cleaner = new EmailAddress(Factory::create());

        $this->assertRegExp("/.+?\@.+?$/", $cleaner->clean([]));
    }
}
