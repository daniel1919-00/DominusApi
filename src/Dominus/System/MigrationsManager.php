<?php

namespace Dominus\System;

use AppConfiguration;
use Dominus\Helpers\Terminal\Color;
use Dominus\Helpers\Terminal\TerminalHelpers;
use Exception;
use function class_exists;
use function closedir;
use function is_dir;
use function is_file;
use function opendir;
use function readdir;
use function scandir;
use function str_ireplace;
use const DIRECTORY_SEPARATOR;
use const PATH_MODULES;
use const PHP_EOL;
use const SCANDIR_SORT_ASCENDING;

class MigrationsManager
{
    private DefaultMigrationsStorage|Interfaces\MigrationsStorage $migrationsStorage;

    /**
     * @param array{'appNamespace': string} $config
     */
    public function __construct(private readonly array $config)
    {
        $this->migrationsStorage = AppConfiguration::getMigrationsStorage();
        $this->migrationsStorage->init();
    }

    /**
     * @param bool $upgrade
     * @param string $moduleName
     * @param string $migrationId
     * @param bool $outputMessages
     * @param bool $force Applies migration regardless if already applied or not
     * @return void
     * @throws Exception
     */
    public function applyMigration(bool $upgrade, string $moduleName, string $migrationId, bool $outputMessages = true, bool $force = false): void
    {
        $operationType = $upgrade ? '[UPGRADE]' : '[DOWNGRADE]';
        if($moduleName == '')
        {
            throw new Exception("[$moduleName] $operationType Migration id was given without the module name!");
        }

        $migrationFilePath = $this->getMigrationFilePath($moduleName, $migrationId);
        if(!is_file($migrationFilePath))
        {
            throw new Exception("[$moduleName] $operationType Migration not found: $migrationFilePath");
        }

        $migrationInstance = $this->getMigrationInstance(
            $moduleName,
            $migrationFilePath,
            $migrationId
        );

        if($upgrade)
        {
            if(!$force && $this->migrationsStorage->isApplied($migrationId))
            {
                if($outputMessages)
                {
                    echo TerminalHelpers::colorString(Color::YELLOW, "[$moduleName] $operationType Migration $migrationId is already applied!" . PHP_EOL);
                }
                return;
            }

            $dependencies = $migrationInstance->getDependencies();
            if($dependencies)
            {
                echo TerminalHelpers::colorString(Color::BLUE, "[$moduleName] $operationType Applying migrations for dependent modules first." . PHP_EOL);
                foreach ($dependencies as $dependency)
                {
                    $this->updateModuleMigrations($dependency);
                }
            }

            $migrationInstance->up();
            $this->migrationsStorage->databaseUpgraded($migrationId);
        }
        else
        {
            if(!$force && !$this->migrationsStorage->isApplied($migrationId))
            {
                if($outputMessages)
                {
                    echo TerminalHelpers::colorString(Color::YELLOW, "[$moduleName] $operationType Migration $migrationId is not applied!" . PHP_EOL);
                }
                return;
            }

            $migrationInstance->down();
            $this->migrationsStorage->databaseDowngraded($migrationId);
        }

        if($outputMessages)
        {
            echo TerminalHelpers::colorString(Color::GREEN, "[$moduleName] $operationType Successfully applied migration: $migrationId" . PHP_EOL);
        }
    }

    public function updateModuleMigrations(string $moduleName): void
    {
        $modules = [];
        if($moduleName == '')
        {
            $modulesDir = opendir(PATH_MODULES);
            while(false !== ($module = readdir($modulesDir)))
            {
                if($module[0] == '.')
                {
                    continue;
                }

                $modules[] = $module;
            }
            closedir($modulesDir);
        }
        else
        {
            $modules[] = $moduleName;
        }

        $failedMigrations = [];
        foreach ($modules as $module)
        {
            $migrationsDir = PATH_MODULES . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Migrations';
            if(!is_dir($migrationsDir))
            {
                continue;
            }

            $migrations = scandir($migrationsDir, SCANDIR_SORT_ASCENDING);
            foreach ($migrations as $migration)
            {
                if($migration[0] == '.')
                {
                    continue;
                }

                $migrationId = str_ireplace('.php', '', $migration);

                try
                {
                    $this->applyMigration(true, $module, $migrationId);
                }
                catch (Exception $e)
                {
                    try
                    {
                        $this->applyMigration(false, $module, $migrationId, false, true);
                    }
                    catch (Exception) {}
                    $failedMigrations[$migrationId] = $e->getMessage();
                }
            }
        }

        if($failedMigrations)
        {
            echo TerminalHelpers::colorString(Color::RED, PHP_EOL . "[$moduleName] The following migrations failed to apply:" . PHP_EOL);
            foreach ($failedMigrations as $migrationId => $error)
            {
                echo TerminalHelpers::colorString(Color::RED,"  ->$migrationId: $error" . PHP_EOL);
            }
        }

        $this->migrationsStorage->storeMigrations();
    }

    private function getMigrationFilePath(string $module, string $migrationId): string
    {
        return PATH_MODULES . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR . $migrationId . '.php';
    }

    private function getMigrationInstance(string $moduleName, string $migrationFilePath, string $migrationFilename): Migration
    {
        $migrationClass = $this->config['appNamespace'] . "Modules\\$moduleName\\Migrations\\" . $migrationFilename;
        if(!class_exists($migrationClass))
        {
            require $migrationFilePath;
        }
        return new $migrationClass();
    }
}