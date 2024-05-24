<?php
namespace Dominus\System;
use Dominus\System\Interfaces\Injectable\Factory;
use Dominus\System\Interfaces\Injectable\Injectable;
use Exception;
use Dominus\Services\Database\Database;

abstract class Repository implements Injectable, Factory
{
    private ?Database $db;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->db = null;
    }

    public static function _getInjectionInstance(): static
    {
        return new static();
    }

    /**
     * Turns off autocommit mode.
     * While autocommit mode is turned off, changes made to the database via the Database object instance are not committed until you end the transaction by calling Database::commit(). Calling Database::rollBack() will roll back all changes to the database and return the connection to autocommit mode.
     * Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language (DDL) statement such as DROP TABLE or CREATE TABLE is issued within a transaction. The implicit COMMIT will prevent you from rolling back any other changes within the transaction boundary.
     * @return bool
     *
     * @throws Exception
     */
    public function beginTransaction(): bool
    {
        return $this->db && $this->db->beginTransaction();
    }

    /**
     * Commits a pending transaction
     * @return bool
     */
    public function commit(): bool
    {
        try
        {
            return $this->db && $this->db->commit();
        }
        catch (Exception)
        {
            return false;
        }
    }

    /**
     * Rolls back a transaction
     * @return bool
     */
    public function rollback(): bool
    {
        try
        {
            return $this->db && $this->db->rollBack();
        }
        catch (Exception)
        {
            return false;
        }
    }

    /**
     * Retrieve a connection to the default database
     * @throws Exception
     */
    protected final function getDb(): Database
    {
        if($this->db === null)
        {
            $this->db = Database::getConnection();
        }
        return $this->db;
    }
}