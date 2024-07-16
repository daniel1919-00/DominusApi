<?php

namespace Dominus\System\Interfaces;

interface MigrationsStorage
{
    /**
     * Called to initialize the migration storage where the applied migrations will reside
     * By default they will be serialized in a json file (dominus_migrations.json).
     *
     * Also fetches the already applied migrations
     * @return void
     */
    public function init(): void;

    /**
     * Should return whether a given migration id is already applied
     * @param string $migrationId
     * @return bool
     */
    public function isApplied(string $migrationId): bool;

    /**
     * Called when the database has been successfully upgraded using the migration file
     * @param string $migrationId
     * @return void
     */
    public function databaseUpgraded(string $migrationId): void;

    /**
     * Called when the database has been successfully downgraded using the migration file
     * @param string $migrationId
     * @return void
     */
    public function databaseDowngraded(string $migrationId): void;

    /**
     * Called when the migration process is finished and is time to store the applied migrations
     * @return void
     */
    public function storeMigrations(): void;
}