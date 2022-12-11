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
    private bool $hasError;

    public function __construct(
        private readonly PDO               $pdo,
        private readonly PDOStatement|null $statement,
        private readonly string            $query,
        private readonly array             $queryParameters = [])
    {
        $this->hasError = !$statement;
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
     * Re-executes query with the passed expression as the select statement i.e. SELECT $expression FROM ...
     * @param string $expression
     * @param bool $removeGroupByClause
     * @return int Number of rows
     */
    public function count(string $expression = 'count(*)', bool $removeGroupByClause = false): int
    {
        if (!$this->statement)
        {
            return 0;
        }

        $query = $this->query;
        $queryLen = strlen($query);
        $openParentheses = 0;
        $keyWord = '';
        $keyWordFromIndex = 0;
        $keyWordStartIndex = null;

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

        $subStrLength = false;

        if ($removeGroupByClause)
        {
            $subStrLength = strripos($query, 'group by', $keyWordFromIndex);
        }

        $queryAuxStmts = ($keyWordStartIndex > 0 ? substr($query, 0, $keyWordStartIndex) : '') . " select $expression ";

        if ($subStrLength !== false)
        {
            $query = $queryAuxStmts . substr($query, $keyWordFromIndex, $subStrLength - $keyWordFromIndex);
        }
        else
        {
            $query = $queryAuxStmts . substr($query, $keyWordFromIndex);
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
        $stmt = $this->pdo->prepare($query);
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
     * @return mixed Returns false on failure.
     */
    public function fetch(): mixed
    {
        return $this->statement ? $this->statement->fetch() : false;
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
}