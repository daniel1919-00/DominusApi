<?php
/**
 * @noinspection PhpUnused
 */

namespace Dominus\System;
use Exception;
use Dominus\Services\Database\Database;

abstract class Repository extends Injectable
{
    private ?Database $db;

    public static function _getInjectionInstance(): static
    {
        return new static();
    }

    /**
     * Retrieve a connection to the default database
     * @throws Exception
     */
    protected function getDb(): Database
    {
        if(!$this->db)
        {
            $this->db = Database::getConnection();
        }
        return $this->db;
    }
}