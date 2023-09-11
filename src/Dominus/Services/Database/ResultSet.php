<?php
namespace Dominus\Services\Database;

use Dominus\System\Exceptions\AutoMapPropertyInvalidValue;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use PDO;
use PDOStatement;
use ReflectionClass;
use ReflectionException;

class ResultSet
{
    private bool $hasError;

    public function __construct(
        private readonly PDO               $pdo,
        private readonly PDOStatement|null $statement,
        private readonly string            $query,
        private readonly array             $queryParameters = [],
        private readonly string            $dataModelClassName = '')
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
     * Re-executes query with the passed expression as the select statement e.g. SELECT $expression FROM ...
     * @param string $expression
     * @param bool $removeGroupByClause
     * @return int
     */
    public function count(string $expression = 'count(*)', bool $removeGroupByClause = false): int
    {
        if (!$this->statement)
        {
            return 0;
        }

        return Database::countRows($this->pdo, $this->query, $this->queryParameters, $expression, $removeGroupByClause);
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
     * @throws AutoMapPropertyInvalidValue
     * @throws AutoMapPropertyMismatchException
     * @throws ReflectionException
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
     * @throws AutoMapPropertyInvalidValue Thrown only if using data models
     * @throws AutoMapPropertyMismatchException Thrown only if using data models
     * @throws ReflectionException Thrown only if using data models
     */
    public function fetch(): mixed
    {
        if(!$this->statement)
        {
            return false;
        }

        if($this->dataModelClassName)
        {
            $result = $this->statement->fetch();
            if($result)
            {
                $refClass = new ReflectionClass($this->dataModelClassName);
                $result = autoMap(source: $result, destination: $refClass->newInstanceWithoutConstructor(), autoValidate: false);
                if(method_exists($result, '__construct'))
                {
                    $result->__construct();
                }
            }

            return $result;
        }

        return $this->statement->fetch();
    }

    /**
     * @return object[]
     * @throws AutoMapPropertyInvalidValue
     * @throws AutoMapPropertyMismatchException
     * @throws ReflectionException
     */
    public function fetchAll(): array
    {
        $results = [];
        $statement = $this->statement;
        if ($statement)
        {
            while($result = $this->fetch())
            {
                $results[] = $result;
            }
        }
        return $results;
    }
}