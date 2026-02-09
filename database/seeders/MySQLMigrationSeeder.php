<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MySQLMigrationSeeder extends Seeder
{
    /**
     * Migrate SQLite data to MySQL
     */
    public function run(): void
    {
        $sqlitePath = database_path('recipesarticles.sqlite');

        if (!file_exists($sqlitePath)) {
            $this->command->warn('SQLite database not found. Skipping migration.');
            return;
        }

        $this->command->info('Starting SQLite to MySQL migration...');

        // Configure SQLite connection
        config([
            'database.connections.sqlite_old' => [
                'driver' => 'sqlite',
                'database' => $sqlitePath,
            ]
        ]);

        $tables = ['tenants', 'domains', 'users', 'categories', 'recipes', 'articles'];

        foreach ($tables as $table) {
            try {
                // Get data from SQLite
                $data = DB::connection('sqlite_old')->table($table)->get();

                if ($data->isEmpty()) {
                    $this->command->info("Table {$table}: No data to migrate");
                    continue;
                }

                // Insert into MySQL
                foreach ($data->chunk(100) as $chunk) {
                    DB::table($table)->insert($chunk->toArray());
                }

                $count = $data->count();
                $this->command->info("Migrated {$count} records from {$table}");

            } catch (\Exception $e) {
                $this->command->error("Error migrating {$table}: " . $e->getMessage());
            }
        }

        // Purge old connection
        DB::purge('sqlite_old');

        $this->command->info('✅ Migration complete!');
    }
}
