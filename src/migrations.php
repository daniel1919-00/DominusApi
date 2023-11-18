<?php

use Dominus\System\Migration;

require 'Dominus' . DIRECTORY_SEPARATOR . 'init.php';
require 'startup.php';

const MAX_RETRIES = 2;

$command = trim($argv[1] ?? '');
$moduleName = trim($argv[2] ?? '');
$migrationId = trim($argv[3] ?? '');
$appNamespace = env('APP_NAMESPACE');

function outputError(string $command, string $error, bool $outputHelp = false): void
{
    $msg = '';
    switch ($command)
    {
        case 'add':
            $msg .= 'Failed to generate migration file, <add> command failed: ' . $error;
            if($outputHelp)
            {
                $msg .= PHP_EOL . 'Usage: php migrations.php <add> <my_module>';
            }
            break;

        case 'down':
            $msg .= 'Failed downgrade database, <add> command failed: ' . $error;
            if($outputHelp)
            {
                $msg .= PHP_EOL . 'Usage: php migrations.php <down> <my_module> <my_migration_id>';
            }
            break;

        case 'up':
            $msg .= 'Failed to upgrade database, <add> command failed: ' . $error;
            if($outputHelp)
            {
                $msg .= PHP_EOL . 'Usage: php migrations.php <up> <my_module>'
                      . PHP_EOL . 'Usage: php migrations.php <up> <my_module> <my_migration_id>';
            }
            break;
    }

    echo $msg . PHP_EOL;
}

function getMigrationInstance(string $appNamespace, string $moduleName, string $migrationFilePath, string $migrationFilename): Migration
{
    $migrationClass = $appNamespace . "Modules\\$moduleName\\Migrations\\" . $migrationFilename;
    if(!class_exists($migrationClass))
    {
        require $migrationFilePath;
    }
    return new $migrationClass();
}

$migrationConfig = AppConfiguration::getMigrationsConfig();
$migrationConfig->init();

switch ($command)
{
    case '?':
    case 'help':
        echo 'Usage: php migrations.php <command> <module_name> <migration_id>' . PHP_EOL;
        echo "Example: php migrations.php <up> #will apply migrations that are down for ALL available modules under the namespace set in the .env file." . PHP_EOL;
        echo "Example: php migrations.php <up> <my_module>  #will apply migrations that are down for the 'my_module' module" . PHP_EOL;
        echo "Example: php migrations.php <up> <my_module> <my_migration_id>  # Will apply the migration id 'my_migration_id' for the module 'my_module'" . PHP_EOL;
        echo "Example: php migrations.php <down> <my_module> <my_migration_id>  # Will downgrade the module 'my_module' using the migration id 'my_migration_id'" . PHP_EOL;
        echo "Example: php migrations.php <add> <my_module>  # Will generate a new migration file for the module 'my_module' under the namespace set in the .env file" . PHP_EOL;
        break;

    case 'up':
    case 'update':
        if($migrationId != '')
        {
            if($moduleName == '')
            {
                outputError('up', 'Migration id was given without the module name!', true);
                exit;
            }

            $migrationFilePath = PATH_MODULES . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR . $migrationId . '.php';
            if(!is_file($migrationFilePath))
            {
                outputError('up', 'Migration not found: ' . $migrationFilePath);
                exit;
            }

            if($migrationConfig->isApplied($migrationId))
            {
                echo "Migration $migrationId is already applied!";
                exit;
            }

            try
            {
                getMigrationInstance(
                    $appNamespace,
                    $moduleName,
                    $migrationFilePath,
                    $migrationId
                )->up();

                $migrationConfig->databaseUpgraded($migrationId);
                echo "Successfully applied migration: $migrationId" . PHP_EOL;
            }
            catch (Exception $e)
            {
                outputError('up', $e->getMessage());
            }

            $migrationConfig->storeMigrations();
            exit;
        }

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

        $appliedMigrations = 0;
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
                if($migrationConfig->isApplied($migrationId))
                {
                    continue;
                }

                $migrationInstance = getMigrationInstance(
                    $appNamespace,
                    $module,
                    PATH_MODULES . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR . $migration,
                    $migrationId
                );

                try
                {
                    $migrationInstance->up();
                    $migrationConfig->databaseUpgraded($migrationId);
                    ++$appliedMigrations;
                }
                catch (Exception)
                {
                    $failedMigrations[$migrationId] = $migrationInstance;
                }
            }
        }

        if($failedMigrations)
        {
            $appliedAfterRetry = [];
            for($i = 1; $i <= MAX_RETRIES; ++$i)
            {
                foreach ($failedMigrations as $migrationId => $failedMigration)
                {
                    try
                    {
                        $failedMigration->up();
                        $migrationConfig->databaseUpgraded($migrationId);
                        unset($failedMigrations[$migrationId]);
                        $appliedAfterRetry[$migrationId] = $i;
                        ++$appliedMigrations;
                    }
                    catch (Exception $e)
                    {
                        if($i == MAX_RETRIES)
                        {
                            $failedMigrations[$migrationId] = $e->getMessage();
                        }
                    }
                }
            }

            if($appliedAfterRetry)
            {
                echo 'The following migrations failed to apply initially but succeeded after retrying:' . PHP_EOL;
                foreach ($appliedAfterRetry as $migrationId => $retries)
                {
                    echo "  ->$migrationId successfully applied after $retries retries" . PHP_EOL;
                }
            }

            if($failedMigrations)
            {
                echo PHP_EOL . 'The following migrations failed to apply(even after '.MAX_RETRIES.' retries):' . PHP_EOL;
                foreach ($failedMigrations as $migrationId => $error)
                {
                    echo "  ->$migrationId:$error" . PHP_EOL;
                }
            }
        }

        $migrationConfig->storeMigrations();
        if($appliedMigrations || $failedMigrations)
        {
            echo PHP_EOL . "Successful migrations: $appliedMigrations" . PHP_EOL . "Failed migrations: " . count($failedMigrations) . PHP_EOL;
        }
        else
        {
            echo "Database up to date." . PHP_EOL;
        }
        break;

    case 'downgrade':
    case 'down':
        if($moduleName == '')
        {
            outputError('down', 'Missing module name', true);
            exit;
        }

        if($migrationId == '')
        {
            outputError('down', 'Missing migration ID', true);
            exit;
        }

        if(!$migrationConfig->isApplied($migrationId))
        {
            outputError('down', 'Migration not applied! Nothing to downgrade.');
            exit;
        }

        $migrationFilePath = PATH_MODULES . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR . $migrationId . '.php';
        if(!is_file($migrationFilePath))
        {
            outputError('down', 'Migration not found: ' . $migrationFilePath);
            exit;
        }

        try
        {
            getMigrationInstance(
                $appNamespace,
                $moduleName,
                $migrationFilePath,
                $migrationId
            )->down();

            $migrationConfig->databaseDowngraded($migrationId);
            $migrationConfig->storeMigrations();
        }
        catch (Exception $e)
        {
            outputError('down', $e->getMessage());
            exit;
        }

        break;

    case 'generate':
    case 'add':
        if($moduleName == '')
        {
            outputError('add', 'Missing module name', true);
            exit;
        }

        $migrationsDir = PATH_ROOT . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Migrations';
        if(!file_exists($migrationsDir) && !mkdir(directory: $migrationsDir, recursive: true))
        {
            outputError('add', "Failed to create directory: $migrationsDir");
            exit;
        }

        $currentDateTime = date('Y-m-d H:i:s');
        $basename = $moduleName . time();
        $fileName = $basename;
        $duplicateFileIndex = 0;
        while(is_file($migrationsDir . DIRECTORY_SEPARATOR .$fileName . '.php'))
        {
            $fileName = $basename . (++$duplicateFileIndex);
        }

        file_put_contents($migrationsDir . DIRECTORY_SEPARATOR . $fileName . '.php', "<?php 
// Migration generated at $currentDateTime

namespace ".$appNamespace."Modules\\$moduleName\\Migrations;

use Dominus\\System\\Migration;
class $fileName extends Migration
{
    /**
    * Apply the migration
    * @return void
    */
    public function up(): void
    {
        // You can use the standard Dominus db connector using a connector config alias from the .env file
        // or use a custom one
        \$db = \\Dominus\\Services\\Database\\Database::getConnection('YOUR_CONNECTION_ALIAS');
    }
    
    /**
    * Revert the migration
    * @return void
    */
    public function down(): void
    {
        // You can use the standard Dominus db connector using a connector config alias from the .env file
        // or use a custom one
        \$db = \\Dominus\\Services\\Database\\Database::getConnection('YOUR_CONNECTION_ALIAS');
    }
}
        ");
        break;

    default:
        echo 'Dominus database migrations.' . PHP_EOL;
        echo 'Usage: php migrations.php <command> <module_name> <migration_id>' . PHP_EOL;
}