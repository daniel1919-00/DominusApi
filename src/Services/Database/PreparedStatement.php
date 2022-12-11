<?php
/**
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnused
 */

namespace Dominus\Services\Database;

use Exception;
use PDO;
use PDOStatement;
use Dominus\System\Models\LogType;

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

    public function bindParameter(string $parameter, null|string|int|float|array $value): PreparedStatement
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
        echo $this->getQueryWithBindings($replaceBindingsWithValues);
        return $this;
    }

    /**
     * @return ResultSet
     */
    public function execute(): ResultSet
    {
        $queryParams = [];

        try
        {
            $query = $this->query;
            foreach ($this->queryParameters as $param => $value)
            {
                if(is_array($value))
                {
                    if(!$value)
                    {
                        $queryParams[$param] = null;
                    }
                    else if(count($value) == 1)
                    {
                        $queryParams[$param] = $value[0];
                    }
                    else
                    {
                        $list = '';
                        foreach ($value as $index => $item)
                        {
                            $key = $param . $index;
                            $queryParams[$key] = $item;
                            $list .= $key . ',';
                        }

                        $list = rtrim($list, ',');
                        $query = str_replace($param, $list, $query);

                        if($this->queryOrderBy)
                        {
                            $this->queryOrderBy = str_replace($param, $list, $this->queryOrderBy);
                        }
                    }
                }
                else
                {
                    $queryParams[$param] = $value;
                }
            }

            if($statement = $this->pdo->prepare($query . ($this->queryOrderBy ? " ORDER BY $this->queryOrderBy" : '') . ($this->queryOffset ? " OFFSET $this->queryOffset" : '') . ($this->queryLimit ? " LIMIT $this->queryLimit" : '')))
            {
                if($this->modelClass)
                {
                    $statement->setFetchMode(PDO::FETCH_CLASS, $this->modelClass, []);
                }

                self::bindPreparedStatementParams($statement, $queryParams);
                $statement->execute();
            }
        }
        catch (Exception $e)
        {
            _log("\n SQL ERROR: {$e->getMessage()} \n QUERY: {$this->getQueryWithBindings()}", LogType::ERROR);
            $statement = null;
        }

        return new ResultSet(
            pdo: $this->pdo,
            statement: $statement,
            query: $query,
            queryParameters: $queryParams);
    }

    public static function bindPreparedStatementParams(PDOStatement $statement, array $params): void
    {
        foreach ($params as $param => $value)
        {
            $type = match (gettype($value))
            {
                'integer', 'double' => PDO::PARAM_INT,
                'NULL' => PDO::PARAM_NULL,
                'boolean' => PDO::PARAM_BOOL,
                default => PDO::PARAM_STR,
            };
            $statement->bindValue($param, $value, $type);
        }
    }

    /**
     * @return string Query with parameters
     */
    private function getQueryWithBindings(bool $replaceBindingsWithValues = true): string
    {
        $query = $this->query . ($this->queryOrderBy ? " ORDER BY $this->queryOrderBy" : '') . ($this->queryOffset ? " OFFSET $this->queryOffset" : '') . ($this->queryLimit ? " LIMIT $this->queryLimit" : '') . "\n";
        if($replaceBindingsWithValues)
        {
            foreach ($this->queryParameters as $parameter => $value)
            {
                if(is_null($value))
                {
                    $query = str_replace($parameter, 'NULL', $query);
                }
                else if (is_int($value))
                {
                    $query = str_replace($parameter, $value, $query);
                }
                else if (is_array($value))
                {
                    $arrayToString = '';
                    foreach ($value as $item)
                    {
                        $arrayToString .= (is_string($item) ? "'$item'" : $item) . ',';
                    }
                    $query = str_replace($parameter, rtrim($arrayToString, ','), $query);
                }
                else
                {
                    $query = str_replace($parameter, "'$value'", $query);
                }
            }
        }

        return $query;
    }
}