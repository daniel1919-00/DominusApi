<?php
namespace Dominus\Services\Database;

use Exception;
use PDO;
use PDOStatement;
use Dominus\System\Models\LogType;
use function _log;
use function array_map;
use function count;
use function gettype;
use function implode;
use function is_array;
use function is_callable;
use function is_int;
use function is_null;
use function is_object;
use function is_string;
use function json_encode;
use function preg_quote;
use function preg_replace;
use function str_replace;

class PreparedStatement
{
    private array $queryParameters = [];
    private string $modelClass = '';
    private ?int $queryOffset = null;
    private ?int $queryLimit = null;
    private ?string $queryOrderBy = null;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $query
    ){}

    public function setOffset(int $offset): PreparedStatement
    {
        $this->queryOffset = $offset;
        return $this;
    }

    public function setLimit(?int $limit): PreparedStatement
    {
        if($limit)
        {
            $this->queryLimit = $limit;
        }

        return $this;
    }

    /**
     * Warning: Data passed here is unsafe/unescaped (sql injection risk), use with caution.
     * @param string $orderBy
     * @return static
     */
    public function setOrderBy(string $orderBy): PreparedStatement
    {
        if($orderBy)
        {
            $this->queryOrderBy = $orderBy;
        }
        return $this;
    }

    /**
     * @param string $class Used to auto-map the result set using the given data model class
     * @return $this
     */
    public function setDataModel(string $class): PreparedStatement
    {
        $this->modelClass = $class;
        return $this;
    }

    /**
     * @param array $parameters Query parameters
     * @return $this
     */
    public function bindParameters(array $parameters): PreparedStatement
    {
        foreach ($parameters as $parameter => $value)
        {
            $this->bindParameter($parameter, $value);
        }
        return $this;
    }

    /**
     * @param string $parameter
     * @param string|int|float|array|null|callable|object $value
     * The value of the bound parameter can be a function that accepts 2 arguments(the currently executed query and the bound parameter name) and returns an array with the altered query and the bound param value: [query, boundParamValue].
     * This is useful, for example in postgresql if you need to bind a php array to a postgresql array column, you would do ARRAY[:your_bind]::type instead of just :your_bind. The bound value is still processed automatically (arrays are imploded into comma-separated values).
     * @return $this
     */
    public function bindParameter(string $parameter, null|string|int|float|array|callable|object $value): PreparedStatement
    {
        $this->queryParameters[$parameter] = $value;
        return $this;
    }

    /**
     * @param bool $replaceBindingsWithValues Parameters won't be replaced with values if set to false
     * @return $this
     */
    public function outputQuery(bool $replaceBindingsWithValues = true): PreparedStatement
    {
        echo $this->getDebugQueryWithBindings($replaceBindingsWithValues);
        return $this;
    }

    /**
     * @return ResultSet
     */
    public function execute(): ResultSet
    {
        list($query, $queryParams) = $this->processQueryAndParams($this->query, $this->queryParameters);

        try
        {
            if($statement = $this->pdo->prepare($query . ($this->queryOrderBy ? " ORDER BY $this->queryOrderBy" : '') . ($this->queryOffset ? " OFFSET $this->queryOffset" : '') . ($this->queryLimit ? " LIMIT $this->queryLimit" : '')))
            {
                self::bindPreparedStatementParams($statement, $queryParams)->execute();
            }
        }
        catch (Exception $e)
        {
            _log("\n SQL ERROR: {$e->getMessage()} \n QUERY: {$this->getDebugQueryWithBindings()}", LogType::ERROR);
            $statement = null;
        }

        return new ResultSet(
            pdo: $this->pdo,
            statement: $statement,
            query: $query,
            queryParameters: $queryParams,
            dataModelClassName: $this->modelClass);
    }

    /**
     * Counts the total rows using the current statement and the given count expression
     * @param string $expression
     * @param bool $removeGroupByClause
     * @return int
     */
    public function count(string $expression = 'count(*)', bool $removeGroupByClause = false): int
    {
        if(!$this->pdo)
        {
            return 0;
        }
        list($query, $queryParams) = $this->processQueryAndParams($this->query, $this->queryParameters);
        return Database::countRows($this->pdo, $query, $queryParams, $expression, $removeGroupByClause);
    }

    public static function bindPreparedStatementParams(PDOStatement $statement, array $params): PDOStatement
    {
        foreach ($params as $param => $value)
        {
            $statement->bindValue($param, $value, match (gettype($value))
            {
                'integer', 'double' => PDO::PARAM_INT,
                'NULL' => PDO::PARAM_NULL,
                'boolean' => PDO::PARAM_BOOL,
                default => PDO::PARAM_STR,
            });
        }

        return $statement;
    }

    private function processQueryAndParams(string $query, array $queryParameters): array
    {
        $queryParams = [];

        foreach ($queryParameters as $param => $value)
        {
            if ($value)
            {
                if (is_callable($value))
                {
                    list($query, $value) = $value($query, $param);
                }
                else if (is_object($value))
                {
                    $value = json_encode($value);
                }
            }

            if (is_array($value))
            {
                $valueCount = count($value);
                if ($valueCount === 0)
                {
                    $queryParams[$param] = null;
                }
                else if ($valueCount === 1)
                {
                    $queryParams[$param] = $value[0];
                }
                else
                {
                    $list = [];
                    foreach ($value as $index => $item)
                    {
                        $key = $param . $index;
                        $queryParams[$key] = $item;
                        $list[] = $key;
                    }

                    $listStr = implode(',', $list);
                    $query = str_replace($param, $listStr, $query);

                    if ($this->queryOrderBy)
                    {
                        $this->queryOrderBy = str_replace($param, $listStr, $this->queryOrderBy);
                    }
                }
            }
            else
            {
                $queryParams[$param] = $value;
            }
        }

        return [$query, $queryParams];
    }

    /**
     * @return string Query with parameters
     */
    private function getDebugQueryWithBindings(bool $replaceBindingsWithValues = true): string
    {
        $query = $this->query
            . ($this->queryOrderBy ? " ORDER BY $this->queryOrderBy" : '')
            . ($this->queryOffset ? " OFFSET $this->queryOffset" : '')
            . ($this->queryLimit ? " LIMIT $this->queryLimit" : '')
            . "\n";

        if ($replaceBindingsWithValues)
        {
            foreach ($this->queryParameters as $parameter => $value)
            {
                if ($value)
                {
                    if (is_callable($value))
                    {
                        list($query, $value) = $value($query, $parameter);
                    }
                    else if (is_object($value))
                    {
                        $value = json_encode($value);
                    }
                }

                $pattern = '/' . preg_quote($parameter, '/') . '\b/';

                if (is_null($value))
                {
                    $query = preg_replace($pattern, 'NULL', $query);
                }
                else if (is_int($value))
                {
                    $query = preg_replace($pattern, $value, $query);
                }
                else if (is_array($value))
                {
                    $arrayToString = implode(',', array_map(function ($item)
                    {
                        return is_string($item) ? "'$item'" : $item;
                    }, $value));
                    $query = preg_replace($pattern, $arrayToString, $query);
                }
                else
                {
                    $query = preg_replace($pattern, "'$value'", $query);
                }
            }
        }

        return $query;
    }
}