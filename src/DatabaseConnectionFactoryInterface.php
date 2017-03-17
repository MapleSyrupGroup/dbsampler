<?php
namespace Quidco\DbSampler;

use Doctrine\DBAL\Connection;

interface DatabaseConnectionFactoryInterface
{
    const CONNECTION_SOURCE = 'source';
    const CONNECTION_DEST = 'dest';

    /**
     * Create a connection class for a given DB name. Other credentials (host, password etc) must already be known
     *
     * @param string $name Database name
     *
     * @return Connection
     */
    public function createSourceConnectionByDbName($name);

    /**
     * Create a connection class for a given DB name. Other credentials (host, password etc) must already be known
     *
     * @param string $name Database name
     *
     * @return Connection
     */
    public function createDestConnectionByDbName($name);
}
