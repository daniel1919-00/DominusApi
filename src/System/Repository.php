<?php
/**
 * @noinspection PhpUnused
 */

namespace Dominus\System;
use Exception;
use Dominus\Services\Database\Database;

abstract class Repository extends Injectable
{
    private Database $db;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public static function _getInjectionInstance(): static
    {
        return new static();
    }

    /**
     * Retrieve a connection to the default database
     * @throws Exception
     */
    protected final function getDb(): Database
    {
        return $this->db;
    }
}