<?php
namespace Quidco\DbSampler\Sampler;

use Quidco\DbSampler\BaseSampler;

class NewestById extends BaseSampler
{

    /**
     * @var string
     */
    protected $idField;

    /**
     * @var int
     */
    protected $quantity;

    /**
     * Return a unique name for this sampler for informational purposes
     *
     * @return string
     * @inheritdoc
     */
    public function getName()
    {
        return 'NewestById';
    }

    /**
     * Accept configuration as provided in a .db.json file
     *
     * @param \stdClass $config Configuration stanza, decoded to object
     *
     * @return void
     * @inheritdoc
     */
    public function loadConfig($config)
    {
        $this->quantity = (int)$this->demandParameterValue($config, 'quantity');
        $this->idField = $this->demandParameterValue($config, 'idField');
    }

    /**
     * Return all rows that this sampler would copy
     *
     * @return array[]
     * @inheritdoc
     */
    public function getRows()
    {
        $query = $this->sourceConnection->createQueryBuilder()->select('*')->from($this->tableName)
            ->addOrderBy($this->idField, 'DESC')
            ->setMaxResults($this->quantity)
            ->execute();

        return $query->fetchAll();
    }
}
