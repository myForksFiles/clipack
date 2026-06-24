<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CheckAppSchema extends Command
{
    protected $signature = 'dev:check:schema {--fail-on-warning : Return non-zero exit code on warnings}';

    protected $description = 'Checks migrations status, DB connectivity, tables for models, timestamps and soft deletes columns';

    protected array $errors = [];

    protected array $warnings = [];

    public function handle(): int
    {
        $this->info('Checking application schema...');

        $this->checkDatabaseConnection();
        $this->checkPendingMigrations();
        $this->checkModelsAgainstDatabase();

        $this->newLine();
        $this->line(str_repeat('-', 60));

        if ($this->errors) {
            $this->error('Errors:');
            foreach ($this->errors as $error) {
                $this->line(" - {$error}");
            }
        }

        if ($this->warnings) {
            $this->warn('Warnings:');
            foreach ($this->warnings as $warning) {
                $this->line(" - {$warning}");
            }
        }

        if (! $this->errors && ! $this->warnings) {
            $this->info('OK: migrations, models and database look consistent.');

            return self::SUCCESS;
        }

        if ($this->errors) {
            return self::FAILURE;
        }

        return $this->option('fail-on-warning') ? self::FAILURE : self::SUCCESS;
    }

    protected function checkDatabaseConnection(): void
    {
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $this->info("DB connection OK: {$dbName}");
        } catch (\Throwable $e) {
            $this->errors[] = 'Cannot connect to database: '.$e->getMessage();
        }
    }

    protected function checkPendingMigrations(): void
    {
        try {
            $migrationPath = database_path('migrations');
            $files = collect(File::files($migrationPath))
                ->map(fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME))
                ->sort()
                ->values();

            if (! Schema::hasTable('migrations')) {
                $this->warnings[] = 'Table "migrations" does not exist. Database probably not migrated yet.';

                return;
            }

            $ran = collect(DB::table('migrations')->pluck('migration'))
                ->sort()
                ->values();

            $pending = $files->diff($ran)->values();

            if ($pending->isEmpty()) {
                $this->info('No pending migrations.');
            } else {
                $this->warnings[] = 'Pending migrations: '.$pending->implode(', ');
            }
        } catch (\Throwable $e) {
            $this->errors[] = 'Failed while checking migrations: '.$e->getMessage();
        }
    }

    protected function checkModelsAgainstDatabase(): void
    {
        $models = $this->discoverModels();

        if (empty($models)) {
            $this->warnings[] = 'No models found in app/Models.';

            return;
        }

        foreach ($models as $modelClass) {
            try {
                /** @var Model $model */
                $model = new $modelClass;

                $table = $model->getTable();

                if (! Schema::hasTable($table)) {
                    $this->warnings[] = "{$modelClass}: table '{$table}' does not exist.";

                    continue;
                }

                $columns = Schema::getColumnListing($table);

                if ($model->usesTimestamps()) {
                    if (! in_array('created_at', $columns, true)) {
                        $this->warnings[] = "{$modelClass}: missing created_at in table '{$table}'.";
                    }

                    if (! in_array('updated_at', $columns, true)) {
                        $this->warnings[] = "{$modelClass}: missing updated_at in table '{$table}'.";
                    }
                }

                if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
                    if (! in_array('deleted_at', $columns, true)) {
                        $this->warnings[] = "{$modelClass}: uses SoftDeletes but table '{$table}' has no deleted_at column.";
                    }
                }
            } catch (\Throwable $e) {
                $this->warnings[] = "{$modelClass}: could not be checked ({$e->getMessage()}).";
            }
        }

        $this->info('Models checked against database.');
    }

    protected function discoverModels(): array
    {
        $modelsPath = app_path('Models');

        if (! is_dir($modelsPath)) {
            return [];
        }

        $classes = [];

        foreach (File::allFiles($modelsPath) as $file) {
            $relativePath = $file->getRelativePathname();
            $class = 'App\\Models\\'.str_replace(
                ['/', '.php'],
                ['\\', ''],
                $relativePath
            );

            if (! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, Model::class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }
}
