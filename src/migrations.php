<?php

use Dominus\System\MigrationsManager;

require 'Dominus' . DIRECTORY_SEPARATOR . 'init.php';
require 'startup.php';

$command = trim($argv[1] ?? '');
$moduleName = trim($argv[2] ?? '');
$migrationId = trim($argv[3] ?? '');
$appNamespace = env('APP_NAMESPACE');
$migrationManager = new MigrationsManager([
    'appNamespace' => $appNamespace
]);

function outputError(string $command, string $error, bool $outputHelp = false): void
{
    $msg = '';
    switch ($command)
    {
        case 'add':
            $msg .= 'Failed to generate migration file, <add> command failed: ' . $error;
            if ($outputHelp)
            {
                $msg .= PHP_EOL . 'Usage: php migrations.php <add> <my_module>';
            }
            break;

        case 'down':
            $msg .= 'Failed downgrade database, <add> command failed: ' . $error;
            if ($outputHelp)
            {
                $msg .= PHP_EOL . 'Usage: php migrations.php <down> <my_module> <my_migration_id>';
            }
            break;

        case 'up':
            $msg .= 'Failed to upgrade database, <add> command failed: ' . $error;
            if ($outputHelp)
            {
                $msg .= PHP_EOL . 'Usage: php migrations.php <up> <my_module>'
                    . PHP_EOL . 'Usage: php migrations.php <up> <my_module> <my_migration_id>';
            }
            break;
    }

    echo $msg . PHP_EOL;
}

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
        try
        {
            if($migrationId != '')
            {
                $migrationId = str_ireplace('.php', '', $migrationId);
                if ($moduleName == '')
                {
                    throw new Exception('Migration id was given without the module name!');
                }

                $migrationManager->applyMigration(true, $migrationId, $moduleName);
            }
            else
            {
                $migrationManager->updateModuleMigrations($moduleName);
            }
        }
        catch (Exception $e)
        {
            outputError('up', $e->getMessage(), true);
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

        $migrationId = str_ireplace('.php', '', $migrationId);

        try
        {
            $migrationManager->applyMigration(false, $migrationId, $moduleName);
        }
        catch (Exception $e)
        {
            outputError('up', $e->getMessage(), true);
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
     * A list of Modules on which this migration depends on. Example return ['MyModule'];
     * An empty array should be returned if this migration has no dependencies;
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [];
    }
    
    /**
    * Apply the migration
    * @return void
    * @throws Exception Should be thrown on error
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
    * @throws Exception Should be thrown on error
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