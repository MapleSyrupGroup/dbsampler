<?php
namespace Quidco\DbSampler;

use Doctrine\DBAL\Connection;

interface DatabaseConnectionFactoryInterface
{
    /**
     * Create a connection class for a given DB name. Other credentials (host, password etc) must already be known
     *
     * @param string $name Database name
     *
     * @return Connection
     */
    public function createConnectionByDbName($name);
}
