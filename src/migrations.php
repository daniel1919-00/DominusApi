<?php
require 'Dominus' . DIRECTORY_SEPARATOR . 'init.php';

$command = trim($argv[1] ?? '');
$moduleName = trim($argv[2] ?? '');
$migrationId = trim($argv[3] ?? '');

$appNamespaced = env('APP_NAMESPACE');

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
        if($moduleName == '')
        {
            outputError('up', 'missing module name', true);
            exit;
        }

        break;

    case 'downgrade':
    case 'down':
        if($moduleName == '')
        {
            outputError('down', 'missing module name', true);
            exit;
        }
        break;

    case 'generate':
    case 'add':
        if($moduleName == '')
        {
            outputError('add', 'missing module name', true);
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
        while(is_file($fileName . '.php'))
        {
            $fileName = $basename . (++$duplicateFileIndex);
        }

        $EOL = PHP_EOL;
        file_put_contents($migrationsDir . DIRECTORY_SEPARATOR . $fileName . '.php', "<?php 
// Migration generated at $currentDateTime

use Dominus\System\Migration;
class $fileName extends Migration
{
    /**
    * Apply the migration
    * @return void
    */
    public function up()
    {
        // You can use the standard Dominus db connector using a connector config alias from the .env file
        // or use a custom one
        \$db = \\Dominus\\Services\\Database\\Database::getConnection('YOUR_CONNECTION_ALIAS');
    }
    
    /**
    * Revert the migration
    * @return void
    */
    public function down()
    {
        
    }
}
        ");
        break;

    default:
        echo 'Dominus database migrations.' . PHP_EOL;
        echo 'Usage: php migrations.php <command> <module_name> <migration_id>' . PHP_EOL;
}