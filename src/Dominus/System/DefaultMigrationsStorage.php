<?php

namespace Dominus\System;

use Dominus\System\Interfaces\MigrationsStorage;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_file;
use function json_decode;
use function json_encode;
use function serialize;
use function unserialize;
use const DIRECTORY_SEPARATOR;
use const PATH_ROOT;

class DefaultMigrationsStorage implements MigrationsStorage
{
    private array $appliedMigrations = [];

    public function init(): void
    {
        $storedMigrationsFile = PATH_ROOT . DIRECTORY_SEPARATOR . 'dominus_migrations.json';
        if(is_file($storedMigrationsFile))
        {
            $appliedMigrations = file_get_contents($storedMigrationsFile);
            if($appliedMigrations)
            {
                $this->appliedMigrations = json_decode($appliedMigrations);
            }
        }
    }

    public function isApplied(string $migrationId): bool
    {
        return in_array($migrationId, $this->appliedMigrations);
    }

    public function databaseUpgraded(string $migrationId): void
    {
        if(!in_array($migrationId, $this->appliedMigrations))
        {
            $this->appliedMigrations[] = $migrationId;
        }
    }

    public function databaseDowngraded(string $migrationId): void
    {
        foreach ($this->appliedMigrations as $idx => $migration)
        {
            if($migration == $migrationId)
            {
                unset($this->appliedMigrations[$idx]);
                break;
            }
        }
    }

    public function storeMigrations(): void
    {
        file_put_contents(PATH_ROOT . DIRECTORY_SEPARATOR . 'dominus_migrations.json', json_encode($this->appliedMigrations));
    }
}