<?php

namespace Quidco\DbSampler\Tests;

use Quidco\DbSampler\ReferenceStore;
use Quidco\DbSampler\Sampler\CopyAll;
use Quidco\DbSampler\Sampler\CopyEmpty;

class SamplerTest extends SqliteBasedTestCase
{
    public function testEmptySampler()
    {
        $sampler = new CopyEmpty();
        $this->assertSame([], $sampler->getRows());
    }

    public function testCopyAllSampler()
    {
        $sampler = new CopyAll();
        $sampler->setTableName('fruits');
        $sampler->setSourceConnection($this->sourceConnection);
        $this->assertSame(4, count($sampler->getRows()));
    }

    public function testCopyAllWithReferenceStore()
    {
        $sampler = new CopyAll();
        $sampler->setTableName('fruits');
        $sampler->setSourceConnection($this->sourceConnection);
        $sampler->setDestConnection($this->destConnection);
        $referenceStore = new ReferenceStore();
        $sampler->setReferenceStore($referenceStore);
        $sampler->loadConfig((object)['remember' => ['id' => 'fruit_ids']]);
        $sampler->execute();

        $this->assertEquals(4, count($referenceStore->getReferencesByName('fruit_ids')));
    }
}
