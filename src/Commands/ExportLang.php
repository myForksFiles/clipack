<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class ExportLang extends Command
{
    protected $signature = 'mff:lang:export
                            {--path= : Language files directory (default: lang/ or resources/lang)}
                            {--output= : Output CSV path (default: storage/app/lang-export.csv)}
                            {--namespace= : Optional package namespace directory under lang path}';

    protected $description = 'Export language translation keys to a CSV file';

    public function handle(): int
    {
        $langPath = $this->resolveLangPath();
        $output = (string) ($this->option('output') ?: storage_path('app/lang-export.csv'));

        if ($langPath === null) {
            return self::FAILURE;
        }

        try {
            $rows = $this->collectTranslations($langPath);
        } catch (Throwable $e) {
            $this->error('Failed while reading language files: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($rows === []) {
            $this->warn('No translation keys found in: '.$langPath);

            return self::SUCCESS;
        }

        File::ensureDirectoryExists(dirname($output));

        $handle = fopen($output, 'wb');
        if ($handle === false) {
            $this->error('Unable to open output file: '.$output);

            return self::FAILURE;
        }

        fputcsv($handle, ['locale', 'file', 'key', 'value']);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->info('Exported '.count($rows).' translation rows.');
        $this->line('Source: '.$langPath);
        $this->line('Output: '.$output);

        return self::SUCCESS;
    }

    private function resolveLangPath(): ?string
    {
        $configured = $this->option('path');

        if (is_string($configured) && $configured !== '') {
            if (! is_dir($configured)) {
                $this->error('Language path does not exist: '.$configured);

                return null;
            }

            return $configured;
        }

        $namespace = $this->option('namespace');
        $candidates = [
            lang_path(is_string($namespace) && $namespace !== '' ? $namespace : ''),
            resource_path('lang'.(is_string($namespace) && $namespace !== '' ? '/'.$namespace : '')),
            base_path('lang'),
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        $this->error('No language directory found. Pass --path= to a lang directory.');

        return null;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    private function collectTranslations(string $langPath): array
    {
        $rows = [];

        foreach (File::directories($langPath) as $localeDir) {
            $locale = basename((string) $localeDir);

            foreach (File::allFiles($localeDir) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $relative = $file->getRelativePathname();
                $group = str_replace(['/', '\\', '.php'], ['.', '.', ''], $relative);
                /** @var mixed $translations */
                $translations = include $file->getPathname();

                if (! is_array($translations)) {
                    continue;
                }

                foreach ($this->flattenTranslations($translations) as $key => $value) {
                    if (is_scalar($value) || $value === null) {
                        $exportedValue = (string) $value;
                    } else {
                        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
                        $exportedValue = $encoded === false ? '' : $encoded;
                    }

                    $rows[] = [
                        $locale,
                        $group,
                        $key,
                        $exportedValue,
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * @param  array<string|int, mixed>  $translations
     * @return array<string, mixed>
     */
    private function flattenTranslations(array $translations, string $prefix = ''): array
    {
        $flat = [];

        foreach ($translations as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                /** @var array<string|int, mixed> $value */
                $flat += $this->flattenTranslations($value, $fullKey);

                continue;
            }

            $flat[$fullKey] = $value;
        }

        return $flat;
    }
}
