<?php
namespace Dominus\Services\Database;

use Dominus\System\Interfaces\Injectable\Factory;
use Dominus\System\Interfaces\Injectable\Injectable;
use Exception;
use PDO;
use PDOException;
use Dominus\System\Models\LogType;
use function env;
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
     * Get a new database connection from the configurations defined in the .env file -> DATABASE CONNECTIONS section
     * @param string $connectionAlias Configuration alias
     * @return Database
     * @throws Exception
     */
    public static function getConnection(string $connectionAlias = 'DEFAULT'): Database
    {
        $connectionString = env('DB_' . $connectionAlias . '_DSN');

        if(empty($connectionString))
        {
            throw new Exception("Connection alias: $connectionAlias not found in the .env file -> DATABASE CONNECTIONS section!");
        }

        return new Database(
            $connectionString,
            env('DB_' . $connectionAlias . '_USERNAME'),
            env('DB_' . $connectionAlias . '_PASSWORD'));
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
        if(!$stmt)
        {
            return 0;
        }

        return PreparedStatement::bindPreparedStatementParams($stmt, $queryParams)->execute() ? (int)$stmt->fetchColumn() : 0;
    }

    /**
     * @param string $dsn
     * @param null|string $username
     * @param null|string $password
     * @param null|array $PDOOptions
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $PDOOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ])
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
     * Rolls back a transaction
     * @return bool
     * @throws PDOException if there is no active transaction.
     */
    public function rollback(): bool
    {
        return $this->pdo && $this->pdo->rollBack();
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
     * Executes unprepared statements
     * @param string $query
     * @param string $dataModelClassName
     * @return ResultSet|null
     */
    public function query(string $query, string $dataModelClassName = ''): ?ResultSet
    {
        $stmt = $this->pdo?->query($query);

        if(!$stmt)
        {
            return null;
        }

        return new ResultSet(
            $this->pdo,
            $stmt,
            $query,
            [],
            $dataModelClassName
        );
    }
}