<?php

namespace Dominus\System;

use Dominus\System\Interfaces\MigrationsConfig;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_file;
use function serialize;
use const DIRECTORY_SEPARATOR;
use const PATH_ROOT;

class DefaultMigrationsConfig implements MigrationsConfig
{
    private array $appliedMigrations = [];

    public function init(): void
    {
        $storedMigrationsFile = PATH_ROOT . DIRECTORY_SEPARATOR . 'dominus_migrations.txt';
        $this->appliedMigrations = is_file($storedMigrationsFile) ? unserialize(file_get_contents($storedMigrationsFile)) : [];
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
        file_put_contents(PATH_ROOT . DIRECTORY_SEPARATOR . 'dominus_migrations.txt', serialize($this->appliedMigrations));
    }
}