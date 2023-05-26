<?php
/**
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnused
 */

namespace Dominus\Services\Database;

use Dominus\System\Interfaces\Injectable\Factory;
use Dominus\System\Interfaces\Injectable\Injectable;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
use Dominus\System\Models\LogType;
use function str_contains;
use function strcasecmp;
use function strlen;
use function strripos;
use function substr;

/**
 * Injectable wrapper for the php PDO library
 */
class Database implements Injectable, Factory
{
    private ?PDO $pdo;

    /**
     * @throws Exception
     */
    public static function _getInjectionInstance(): Database
    {
        return self::getConnection();
    }

    /**
     * Get a new database connection from the .env definitions
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
     * Calculates the total rows using the given count expression
     * @param PDO $pdo
     * @param string $query
     * @param array $queryParams
     * @param string $expression
     * @param bool $removeGroupByClause
     * @return int
     */
    public static function countRows(PDO $pdo, string $query, array $queryParams, string $expression = 'count(*)', bool $removeGroupByClause = false): int
    {
        $queryLen = strlen($query);
        $unbalancedSpecialChars = 0;
        $keyWord = '';
        $keyWordFromIndex = 0;
        $keyWordStartIndex = null;

        for ($charIndex = 0; $charIndex < $queryLen; ++$charIndex)
        {
            $char = $query[$charIndex];

            if ($char == '(')
            {
                ++$unbalancedSpecialChars;
                continue;
            }
            else if ($char == ')')
            {
                --$unbalancedSpecialChars;
                continue;
            }
            else if ($unbalancedSpecialChars)
            {
                continue;
            }
            else if ($char == ' ' || $char == "\n" || $char == "\r" || $char == "\t")
            {
                if (strcasecmp($keyWord, 'from') === 0)
                {
                    $keyWordFromIndex = $charIndex - 5;
                    break;
                }
                else if ($keyWordStartIndex === null && strcasecmp($keyWord, 'select') === 0)
                {
                    $keyWordStartIndex = $charIndex - 7;
                    continue;
                }

                $keyWord = '';
                continue;
            }

            $keyWord .= $char;
        }

        $groupByClauseOffset = null;
        if ($removeGroupByClause)
        {
            $groupByPos = strripos($query, 'group by', $keyWordFromIndex);
            $groupByClauseOffset = $groupByPos !== false ? $groupByPos - $keyWordFromIndex : null;
        }

        $query = ($keyWordStartIndex > 0 ? substr($query, 0, $keyWordStartIndex) . ' ' : '')
            . "select $expression "
            . substr($query, $keyWordFromIndex, $groupByClauseOffset);

        foreach ($queryParams as $param => $val)
        {
            if (!str_contains($query, $param))
            {
                unset($queryParams[$param]);
            }
        }

        $stmt = $pdo->prepare($query);
        return $stmt->execute($queryParams) ? (int)$stmt->fetchColumn() : 0;
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
            $this->pdo = new PDO($dsn, $username, $password, $PDOOptions);
        }
        catch (Exception $e)
        {
            _log('Failed to connect to db using: ' . $dsn.': ' . $e->getMessage(), LogType::ERROR);
            $this->pdo = null;
        }
    }

    /**
     * @return bool Checks whether a db connection is active
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * @return PDO|null The underlying PDO instance or null if none active
     */
    public function getPDO(): ?PDO
    {
        return $this->pdo;
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
        return $this->pdo && $this->pdo->beginTransaction();
    }

    /**
     * Commits a pending transaction
     * @return bool
     * @throws PDOException if not transactions found
     */
    public function commit(): bool
    {
        return $this->pdo && $this->pdo->commit();
    }

    /**
     * Prepares the query for execution and returns a prepared statement.
     * @param string $query
     * @return PreparedStatement|null The prepared statement or null if there is no database connection
     */
    public function prepare(string $query): ?PreparedStatement
    {
        return $this->pdo ? new PreparedStatement($this->pdo, trim($query)) : null;
    }

    /**
     * Rolls back a transaction
     * @return bool
     * @throws PDOException if there is no active transaction.
     */
    public function rollback(): bool
    {
        return $this->pdo && $this->pdo->rollBack();
    }

    /**
     * Executes unprepared statements
     * @param string $query
     * @param string $dataModelClassName
     * @return PDOStatement|null
     */
    public function executeRaw(string $query, string $dataModelClassName = ''): ?PDOStatement
    {
        $stmt = $this->pdo?->query($query);
        if($stmt && $dataModelClassName)
        {
            $stmt->setFetchMode(PDO::FETCH_CLASS, $dataModelClassName, []);
        }
        return $stmt;
    }
}