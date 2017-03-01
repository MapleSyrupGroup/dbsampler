<?php

namespace Quidco\DbSampler\Tests;

use Quidco\DbSampler\ReferenceStore;

class ReferenceStoreTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicFunctions()
    {
        $store = new ReferenceStore();
        $primes = [1, 3, 5, 7];
        $store->setReferencesByName('primes', $primes);
        $this->assertEquals($primes, $store->getReferencesByName('primes'));
        $this->assertEquals([], $store->getReferencesByName('nosuch'));
    }
}
