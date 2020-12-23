<?php

namespace Quidco\DbSampler\Tests;

use Quidco\DbSampler\ReferenceStore;
use PHPUnit\Framework\TestCase;

class ReferenceStoreTest extends TestCase
{
    public function testBasicFunctions(): void
    {
        $store = new ReferenceStore();
        $primes = [1, 3, 5, 7];
        $store->setReferencesByName('primes', $primes);
        $this->assertEquals($primes, $store->getReferencesByName('primes'));
        $this->assertEquals([], $store->getReferencesByName('nosuch'));
    }
}
