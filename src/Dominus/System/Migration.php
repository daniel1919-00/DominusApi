<?php

namespace Dominus\System;

use Dominus\Services\Database\Database;
use Exception;
use PDO;
use SplFileObject;

abstract class Migration
{
    /**
     * A list of Modules on which this migration depends on. Example return ['MyModule'];
     * An empty array should be returned if this migration has no dependencies;
     * @return string[]
     */
    abstract public function getDependencies(): array;

    /**
     * @throws Exception Should be thrown on error
     */
    abstract public function up();
    /**
     * @throws Exception Should be thrown on error
     */
    abstract public function down();

    /**
     * Parses a .sql file and executes all semicolon separated queries found.
     * Note: This function should not be used for more complicated files, for example if a function or procedure is declared in the file that contains multiple semicolons.
     * @throws Exception
     */
    public function executeSqlFile(PDO|Database $database, string $filePath): void
    {
        if(!is_file($filePath))
        {
            throw new Exception("Failed to access sql file: $filePath");
        }

        $file = new SplFileObject($filePath);

        $query = '';
        $unbalancedChar = '';

        while (false !== ($char = $file->fgetc()))
        {
            switch ($char)
            {
                case "'":
                case '"':
                    if($unbalancedChar !== '')
                    {
                        if($unbalancedChar === $char)
                        {
                            $unbalancedChar = '';
                        }
                    }
                    else
                    {
                        $unbalancedChar = $char;
                    }
                    break;

                case ';':
                    if($unbalancedChar === '')
                    {
                        $database->query($query);
                        $query = '';
                        $char = '';
                    }
                    break;
            }
            $query .= $char;
        }

        unset($file);
    }
}