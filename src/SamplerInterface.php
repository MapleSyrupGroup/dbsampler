<?php
namespace Quidco\DbSampler;

use Doctrine\DBAL\Connection;

interface SamplerInterface
{
    /**
     * Return a unique name for this sampler for informational purposes
     *
     * @return string
     * @inheritdoc
     */
    public function getName();

    /**
     * Accept configuration as provided in a .db.json file
     *
     * @param \stdClass $config Configuration stanza, decoded to object
     *
     * @return void
     * @inheritdoc
     */
    public function loadConfig($config);

    /**
     * Specify which table this sampler operates on
     *
     * @param string $table Name of the table
     *
     * @return void
     * @inheritdoc
     */
    public function setTableName($table);

    /**
     * Return all rows that this sampler would copy
     *
     * @return array[]
     * @inheritdoc
     */
    public function getRows();

    /**
     * Copy sampled data from source to destination DB according to internal logic
     *
     * @return void
     * @inheritdoc
     */
    public function execute();

    /**
     * Set the DBAL connection to sample data from
     *
     * @param Connection $sourceConnection DBAL connection
     *
     * @return void
     * @inheritdoc
     */
    public function setSourceConnection(Connection $sourceConnection);

    /**
     * Set the DBAL connection to store data to
     *
     * @param Connection $destConnection DBAL connection
     *
     * @return void
     * @inheritdoc
     */
    public function setDestConnection(Connection $destConnection);

    /**
     * Pass in a ReferenceStore to act as common memory
     *
     * @param ReferenceStore $referenceStore Storage
     *
     * @return mixed
     */
    public function setReferenceStore(ReferenceStore $referenceStore);
}
