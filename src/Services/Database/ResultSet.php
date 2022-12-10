<?php
/**
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnused
 */

namespace Dominus\Services\Database;

use PDO;
use PDOStatement;

class ResultSet
{
    private string $query;
    private array $queryParameters;
    private PDO|null $pdo;
    private PDOStatement|null $statement;
    private bool $hasError;

    public function __construct(PDO|null $pdo, PDOStatement|null $statement, string $executedQuery, array $queryParameters = [])
    {
        $this->pdo = $pdo;
        $this->statement = $statement;
        $this->hasError = !$statement;
        $this->query = $executedQuery;
        $this->queryParameters = $queryParameters;
    }

    /**
     * True if there were problems executing the query
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->hasError;
    }

    /**
     * Re-executes query with the passed expression as the select statement
     * @param string $expression
     * @param bool $removeGroupByClause
     * @return int Number of rows
     */
    public function count(string $expression = 'count(*)', bool $removeGroupByClause = false): int
    {
        $dbLink = $this->pdo;
        if (!$dbLink || !$this->statement)
        {
            return 0;
        }

        $query = $this->query;
        $queryLen = strlen($query);
        $openParentheses = 0;
        $currentWord = '';
        $currentFromIndex = 0;
        $selectStartIndex = null;

        for ($charIndex = 0; $charIndex < $queryLen; ++$charIndex)
        {
            $char = $query[$charIndex];

            if ($char == '(')
            {
                ++$openParentheses;
                continue;
            }
            else if ($char == ')')
            {
                --$openParentheses;
                continue;
            }
            else if ($openParentheses)
            {
                continue;
            }
            else if ($char == ' ' || $char == "\n" || $char == "\r" || $char == "\t")
            {
                if (strcasecmp($currentWord, 'from') === 0)
                {
                    $currentFromIndex = $charIndex - 5; // NOTE: index should go back 5 characters ('from' word length + current character)
                    break;
                }
                else if ($selectStartIndex === null && strcasecmp($currentWord, 'select') === 0)
                {
                    $selectStartIndex = $charIndex - 7;
                    continue;
                }

                $currentWord = '';
                continue;
            }

            $currentWord .= $char;
        }

        $subStrLength = false;

        if ($removeGroupByClause)
        {
            $subStrLength = strripos($query, 'group by', $currentFromIndex);
        }

        $queryAuxStmts = ($selectStartIndex > 0 ? substr($query, 0, $selectStartIndex) : '') . " select $expression ";

        if ($subStrLength !== false)
        {
            $query = $queryAuxStmts . substr($query, $currentFromIndex, $subStrLength - $currentFromIndex);
        }
        else
        {
            $query = $queryAuxStmts . substr($query, $currentFromIndex);
        }


        $queryParams = $this->queryParameters ?: null;
        if ($queryParams)
        {
            foreach ($queryParams as $param => $val)
            {
                if (!str_contains($query, $param))
                {
                    unset($queryParams[$param]);
                }
            }
        }
        $stmt = $dbLink->prepare($query);
        $stmt->execute($queryParams);
        return (int)$stmt->fetchColumn();
    }

    /**
     * @param int|string $columnIndexOrName Column alias or the 0-indexed number of the column you wish to retrieve from the row.
     * @return array Returns all values from a single column from the next row of a result set or FALSE if there are no more rows.
     */
    public function fetchAllFromColumn(int|string $columnIndexOrName = 0): array
    {
        $statement = $this->statement;
        if (!$statement)
        {
            return [];
        }

        $results = [];
        if (is_numeric($columnIndexOrName))
        {
            while ($result = $statement->fetchColumn($columnIndexOrName))
            {
                $results[] = $result;
            }
        }
        else
        {
            $statement->setFetchMode(PDO::FETCH_BOTH);
            while ($result = $statement->fetch())
            {
                $results[] = $result[$columnIndexOrName];
            }
        }

        return $results;
    }

    /**
     * @param int|string $columnIndexOrName Column alias or the 0-indexed number of the column you wish to retrieve from the row.
     * @return mixed Returns a single column from the next row of a result set or FALSE if there are no more rows.
     */
    public function fetchColumn(int|string $columnIndexOrName = 0): mixed
    {
        if (is_numeric($columnIndexOrName))
        {
            if(!$this->statement)
            {
                return false;
            }

            return $this->statement->fetchColumn($columnIndexOrName);
        }
        else
        {
            $result = $this->fetch();

            if(!$result)
            {
                return false;
            }

            return $result->$columnIndexOrName;
        }
    }

    /**
     * @return mixed Returns null on failure.
     */
    public function fetch(): mixed
    {
        $fetch = $this->statement?->fetch();

        return $fetch ?: null;
    }

    /**
     * @return object[]
     */
    public function fetchAll(): array
    {
        $statement = $this->statement;
        if (!$statement)
        {
            return [];
        }
        $results = $statement->fetchAll();
        return $results ?: [];
    }

    public function __destruct()
    {
        $this->pdo = null;
        $this->statement = null;
    }
}