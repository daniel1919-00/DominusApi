<?php
/**
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnused
 */

namespace Dominus\Services\Database;

use Exception;
use PDO;
use PDOException;
use PDOStatement;
use Dominus\System\Injectable;
use Dominus\System\Models\LogType;

final class Database extends Injectable
{
    private PDO | null $link;

    /**
     * @throws Exception
     */
    public static function _getInjectionInstance(): Database
    {
        return self::getConnection();
    }

    /**
     * Get a new database connection
     * @throws Exception
     */
    public static function getConnection(string $connectionAlias = 'DEFAULT'): Database
    {
        $connectionString = env('DB_' . $connectionAlias . '_DSN');
        $connectionUsername = env('DB_' . $connectionAlias . '_USERNAME');
        $connectionPassword = env('DB_' . $connectionAlias . '_PASSWORD');

        if(empty($connectionString))
        {
            throw new Exception("Connection alias: $connectionAlias not found!");
        }
        return new Database($connectionString, $connectionUsername, $connectionPassword);
    }

    /**
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $PDOOptions
     */
    public function __construct(string $dsn, string $username, string $password, array $PDOOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ])
    {
        try
        {
            $this->link = new PDO($dsn, $username, $password, $PDOOptions);
        }
        catch (Exception $e)
        {
            _log('Failed to connect to db using: ' . $dsn.': ' . $e->getMessage(), LogType::ERROR);
            $this->link = null;
        }
    }

    /**
     * @return bool Checks whether a db connection is active
     */
    public function isConnected(): bool
    {
        return $this->link !== null;
    }

    /**
     * Turns off autocommit mode.
     * While autocommit mode is turned off, changes made to the database via the Database object instance are not committed until you end the transaction by calling Database::commit(). Calling Database::rollBack() will roll back all changes to the database and return the connection to autocommit mode.
     * Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language (DDL) statement such as DROP TABLE or CREATE TABLE is issued within a transaction. The implicit COMMIT will prevent you from rolling back any other changes within the transaction boundary.
     * @return bool
     *
     * @throws PDOException
     */
    public function beginTransaction(): bool
    {
        $link = $this->link;
        if($link->beginTransaction())
        {
            return true;
        }

        return false;
    }

    /**
     * Commits a transaction
     * @return bool
     *
     * @throws PDOException
     */
    public function commit(): bool
    {
        return $this->link->commit();
    }

    /**
     * Prepares the query for execution and returns a statement.
     * @param string $query
     * @return PreparedStatement The prepared statement
     */
    public function prepare(string $query): PreparedStatement
    {
        return new PreparedStatement($this->link, trim($query));
    }

    /**
     * Rolls back a transaction
     * @return bool
     *
     * @throws PDOException
     */
    public function rollback(): bool
    {
        return $this->link->rollBack();
    }

    /**
     * Executes unprepared statements
     * Warning: Use without user input! No escapes are applied whatsoever
     * @param string $query
     * @param string $dataModelClassName
     * @return PDOStatement|null
     */
    public function executeRaw(string $query, string $dataModelClassName = ''): PDOStatement|null
    {
        $stmt = $this->link?->query($query);
        if($stmt && $dataModelClassName)
        {
            $stmt->setFetchMode(PDO::FETCH_CLASS, $dataModelClassName, []);
        }
        return $stmt;
    }

    public function __destruct()
    {
        $this->link = null;
    }
}