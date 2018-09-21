<?php

namespace Quidco\DbSampler\Tests;

use Quidco\DbSampler\ReferenceStore;
use Quidco\DbSampler\Sampler\CopyAll;
use Quidco\DbSampler\Sampler\CopyEmpty;
use Quidco\DbSampler\Sampler\Matched;

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
        $this->assertCount(4, $sampler->getRows());
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

        $this->assertCount(4, $referenceStore->getReferencesByName('fruit_ids'));
    }

    public function testMatchedWithWhereClause()
    {
        $sampler = new Matched();
        $sampler->setTableName('fruit_x_basket');
        $sampler->setSourceConnection($this->sourceConnection);
        $sampler->setDestConnection($this->destConnection);

        $config = [
            'constraints' => ['fruit_id' => 1],
            'where' => ['basket_id > 1']
        ];
        $sampler->loadConfig((object)$config);
        $sampler->execute();

        $this->assertCount(2, $sampler->getRows());
    }
}
