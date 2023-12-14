# Database migrations

Database migrations are a way to keep database schemas in sync on all your environments/servers (local, production, stage, etc.). 

Migration files can also be stored in git, so you always have a sync between your code and databases.

All migration operations are done via the `migrations.php` script. You can check out the available arguments using this command: `php migrations.php help`.

## Configuring the database migration system

By default, data regarding applied migrations are stored in a plain text file, ideally you would want to store these in a database.

To implement your custom migration config, start by making a new class and implement the `Dominus\System\DominusConfiguration` interface.

``` php
<?php
use Dominus\Services\Database\Database;
use Dominus\System\Interfaces\MigrationsConfig;

class MyCustomMigrationsConfig implements MigrationsConfig
{
    private array $appliedMigrations = [];

    /**
     * In this example we will use a postgresql database
     * @throws Exception
     */
    public function init(): void
    {
        $db = Database::getConnection('MIGRATIONS');
        $migrationsTableExists = $db->query("SELECT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'db_migrations'
        )")->fetchColumn();

        if(!$migrationsTableExists)
        {
            $db->query('
                CREATE TABLE public.db_migrations (
                    migration_id text PRIMARY KEY
                )
            ');
        }
        else
        {
            $this->appliedMigrations = $db->query('SELECT migration_id FROM public.db_migrations')->fetchAllFromColumn();
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
        $db = Database::getConnection('MIGRATIONS');
        $db->query('TRUNCATE TABLE public.db_migrations');
        $db->query('INSERT INTO public.db_migrations (migration_id) VALUES ' . implode(',', array_map(static function (string $migrationId)
            {
                return "($migrationId)";
            }, $this->appliedMigrations)));
    }
}
```

After you have your configuration class, in your `startup.php` file, make a new static function `public static function getMigrationsConfig(): MigrationsConfig` that we will use to override the default migrations config and return our newly created config class.

``` php
<?php
class AppConfiguration extends DominusConfiguration
{
    ...
    
    /**
     * This function will return our custom configuration
     * @return DefaultMigrationsConfig
     */
    public static function getMigrationsConfig(): MigrationsConfig
    {
        return new MyCustomMigrationsConfig();
    }
}
```

## Creating a new migration file

You can create models using the Dominus CLI using the `generate migration` command. It will automatically use the namespace of the current Module and create an empty migration file.

You can also use the `migrations.php` file in the Dominus project root(next to `index.php`) like so: `php my_project/migrations.php add MyModuleDirName`.


``` php
<?php
class Test1700333087 extends Migration
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
    */
    public function up()
    {
        
    }
    
    /**
    * Revert the migration
    * @return void
    */
    public function down()
    {
        
    }
}
```

## Upgrading the database

You can upgrade all your modules(that have database migrations) by doing: `php migrations.php up`

If using containers, you can have this command placed in your start script so that your container database is always up-to-date with the code.

## Downgrading the database

You can downgrade by calling the `migrations.php` with the `down` argument and pass the migration ID (The filename without it's extension, e.g. Test1700333087.php -> Test1700333087). 