<?php

namespace MyForksFiles\CliPack\Services;

use Illuminate\Support\Facades\Request;

class SecurityAuditService
{
    public function run(): array
    {
        $documentRoot = $this->normalizePath(Request::server('DOCUMENT_ROOT') ?? '');
        $scriptFile = $this->normalizePath(base_path('artisan'));
        $basePath = $this->normalizePath(base_path());
        $publicPath = $this->normalizePath(public_path());
        $storagePath = $this->normalizePath(storage_path());
        $bootstrapCachePath = $this->normalizePath(base_path('bootstrap/cache'));
        $tempDir = $this->normalizePath(sys_get_temp_dir());
        $cwd = $this->normalizePath(getcwd() ?: '');

        $openBasedir = $this->iniValueSafe('open_basedir');
        $disableFunctionsRaw = $this->iniValueSafe('disable_functions');
        $displayErrors = $this->iniValueSafe('display_errors');
        $logErrors = $this->iniValueSafe('log_errors');
        $allowUrlInclude = $this->iniValueSafe('allow_url_include');
        $exposePhp = $this->iniValueSafe('expose_php');
        $sessionUseStrictMode = $this->iniValueSafe('session.use_strict_mode');
        $sessionCookieHttpOnly = $this->iniValueSafe('session.cookie_httponly');
        $sessionCookieSecure = $this->iniValueSafe('session.cookie_secure');

        $https = (! empty(Request::server('HTTPS')) && Request::server('HTTPS') !== 'off')
            || ((Request::server('HTTP_X_FORWARDED_PROTO') ?? null) === 'https');

        $paths = array_values(array_filter(array_map(
            $this->safeStatPath(...),
            array_unique(array_filter([
                $documentRoot,
                $scriptFile,
                $basePath,
                $publicPath,
                $storagePath,
                $bootstrapCachePath,
                $tempDir,
                $cwd,
            ]))
        )));

        $outsideDocroot = $documentRoot !== ''
            ? $this->evaluateOutsideDocrootAccess($documentRoot)
            : [];

        $dangerousFunctions = $this->dangerousFunctionsReport($disableFunctionsRaw);

        $sensitiveFiles = $documentRoot !== ''
            ? $this->scanSensitiveNames($documentRoot)
            : [];

        $uploadRisks = $documentRoot !== ''
            ? $this->checkUploadExecutionRisk($documentRoot)
            : [];

        $headers = [
            'x-frame-options' => false,
            'x-content-type-options' => false,
            'content-security-policy' => false,
            'strict-transport-security' => false,
            'referrer-policy' => false,
        ];

        $versionProfile = $this->phpVersionSecurityProfile();

        $dangerousAvailable = collect($dangerousFunctions)->contains(fn ($row) => $row['available'] === true);

        $canInspectOutsideDocroot = collect($outsideDocroot)
            ->contains(fn ($row) => $row['outside_docroot'] && ($row['exists'] || $row['is_readable'] || $row['is_writable']));
        $scriptDirWritable = is_writable($publicPath);

        $summaryChecks = [
            'HTTPS enabled' => $https,
            'open_basedir configured' => ! in_array($openBasedir, ['[empty]', '[not available]'], true),
            'display_errors disabled' => in_array(strtolower($displayErrors), ['0', 'off', '[empty]'], true),
            'log_errors enabled' => in_array(strtolower($logErrors), ['1', 'on'], true),
            'allow_url_include disabled' => in_array(strtolower($allowUrlInclude), ['0', 'off', '[empty]'], true),
            'expose_php disabled' => in_array(strtolower($exposePhp), ['0', 'off', '[empty]'], true),
            'session.use_strict_mode enabled' => in_array(strtolower($sessionUseStrictMode), ['1', 'on'], true),
            'session.cookie_httponly enabled' => in_array(strtolower($sessionCookieHttpOnly), ['1', 'on'], true),
            'session.cookie_secure enabled when HTTPS' => ! $https || in_array(strtolower($sessionCookieSecure), ['1', 'on'], true),
            'PHP can inspect above DOCUMENT_ROOT' => ! $canInspectOutsideDocroot,
            'Dangerous PHP functions unavailable' => ! $dangerousAvailable,
            'No sensitive files found inside DOCUMENT_ROOT' => count($sensitiveFiles) === 0,
            'Public path is writable' => ! $scriptDirWritable,
            'Supported PHP branch' => $versionProfile['is_supported_branch'],
            'Latest known PHP patch installed' => ($versionProfile['is_latest_known_patch'] !== false),
        ];

        $riskScore = collect($summaryChecks)->filter(fn ($v) => $v === false)->count();

        return [
            'hostname' => gethostname() ?: (Request::server('SERVER_NAME') ?? 'unknown'),
            'php_version' => PHP_VERSION,
            'php_branch' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
            'risk_score' => $riskScore,
            'summary_checks' => $summaryChecks,
            'version_profile' => $versionProfile,
            'paths' => $paths,
            'outside_docroot' => $outsideDocroot,
            'dangerous_functions' => $dangerousFunctions,
            'sensitive_files' => $sensitiveFiles,
            'upload_risks' => $uploadRisks,
            'headers' => $headers,
            'meta' => [
                'server_software' => Request::server('SERVER_SOFTWARE') ?? null,
                'request_uri' => Request::server('REQUEST_URI') ?? null,
                'laravel' => app()->version(),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
                'base_path' => $basePath,
                'public_path' => $publicPath,
            ],
            'audited_at' => now(),
        ];
    }

    private function iniValueSafe(string $key): string
    {
        $value = ini_get($key);

        if ($value === false) {
            return '[not available]';
        }

        return $value === '' ? '[empty]' : (string) $value;
    }

    private function normalizePath(?string $path): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        $real = @realpath($path);

        return $real !== false ? $real : $path;
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return str_starts_with($haystack, $needle);
    }

    private function pathStartsWith(string $path, string $prefix): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $prefix = rtrim(str_replace('\\', '/', $prefix), '/');

        if ($path === $prefix) {
            return true;
        }

        return $this->startsWith($path.'/', $prefix.'/');
    }

    private function parentLevels(string $path, int $levels = 5): array
    {
        $result = [];
        $current = rtrim($path, DIRECTORY_SEPARATOR);

        for ($i = 0; $i < $levels; $i++) {
            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }
            $result[] = $parent;
            $current = $parent;
        }

        return $result;
    }

    private function safeStatPath(string $path): array
    {
        $realpath = @realpath($path);

        return [
            'path' => $path,
            'realpath' => $realpath !== false ? $realpath : '[unresolved]',
            'exists' => @file_exists($path),
            'is_dir' => @is_dir($path),
            'is_file' => @is_file($path),
            'is_readable' => @is_readable($path),
            'is_writable' => @is_writable($path),
            'is_link' => @is_link($path),
        ];
    }

    private function evaluateOutsideDocrootAccess(string $documentRoot): array
    {
        $rows = [];

        foreach ($this->parentLevels($documentRoot, 6) as $parent) {
            $stat = $this->safeStatPath($parent);
            $resolved = $stat['realpath'] === '[unresolved]' ? $stat['path'] : $stat['realpath'];

            $rows[] = [
                'path' => $stat['path'],
                'realpath' => $stat['realpath'],
                'outside_docroot' => ! $this->pathStartsWith($resolved, $documentRoot),
                'exists' => $stat['exists'],
                'is_readable' => $stat['is_readable'],
                'is_writable' => $stat['is_writable'],
                'is_link' => $stat['is_link'],
            ];
        }

        return $rows;
    }

    private function normalizeDisabledFunctions(string $value): array
    {
        if (trim($value) === '' || str_starts_with($value, '[')) {
            return [];
        }

        return array_values(array_filter(array_map(trim(...), explode(',', $value))));
    }

    private function dangerousFunctionsReport(string $disableFunctionsRaw): array
    {
        $disabled = $this->normalizeDisabledFunctions($disableFunctionsRaw);
        $functions = ['exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen', 'pcntl_exec', 'dl', 'putenv'];

        return collect($functions)->map(function (string $fn) use ($disabled) {
            $exists = function_exists($fn);
            $isDisabled = in_array($fn, $disabled, true);

            return [
                'name' => $fn,
                'exists' => $exists,
                'disabled' => $isDisabled,
                'available' => $exists && ! $isDisabled,
            ];
        })->all();
    }

    private function scanSensitiveNames(string $documentRoot): array
    {
        $candidates = [
            '.env',
            '.git',
            '.git/config',
            '.htpasswd',
            'composer.json',
            'composer.lock',
            'phpunit.xml',
            'error_log',
            'debug.log',
            'backup.zip',
            'backup.tar.gz',
            'dump.sql',
            'database.sql',
        ];

        $results = [];

        foreach ($candidates as $candidate) {
            $full = rtrim($documentRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            $stat = $this->safeStatPath($full);

            if ($stat['exists']) {
                $results[] = $stat;
            }
        }

        return $results;
    }

    private function checkUploadExecutionRisk(string $documentRoot): array
    {
        $candidates = ['uploads', 'upload', 'storage', 'public/uploads', 'files', 'tmp', 'media'];
        $rows = [];

        foreach ($candidates as $candidate) {
            $full = rtrim($documentRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $candidate);

            if (is_dir($full)) {
                $rows[] = [
                    'path' => $full,
                    'readable' => is_readable($full),
                    'writable' => is_writable($full),
                    'directory_listing_possible' => $this->likelyDirectoryListingEnabled($full),
                    'php_handler_hints_present' => file_exists($full.'/.htaccess') ||
                        file_exists($full.'/web.config') ||
                        file_exists($full.'/index.php'),
                ];
            }
        }

        return $rows;
    }

    private function likelyDirectoryListingEnabled(string $dir): bool
    {
        if (! is_dir($dir) || ! is_readable($dir)) {
            return false;
        }

        foreach (['index.php', 'index.html', 'index.htm'] as $index) {
            if (file_exists($dir.DIRECTORY_SEPARATOR.$index)) {
                return false;
            }
        }

        return true;
    }

    private function phpVersionSecurityProfile(): array
    {
        $version = PHP_VERSION;
        $branch = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        $today = now()->toDateString();

        $supportedBranches = [
            '8.2' => [
                'active_support_until' => '2024-12-31',
                'security_support_until' => '2026-12-31',
                'latest_known' => '8.2.30',
            ],
            '8.3' => [
                'active_support_until' => '2025-12-31',
                'security_support_until' => '2027-12-31',
                'latest_known' => '8.3.30',
            ],
            '8.4' => [
                'active_support_until' => '2026-12-31',
                'security_support_until' => '2028-12-31',
                'latest_known' => '8.4.18',
            ],
            '8.5' => [
                'active_support_until' => '2027-12-31',
                'security_support_until' => '2029-12-31',
                'latest_known' => '8.5.2',
            ],
        ];

        $result = [
            'version' => $version,
            'branch' => $branch,
            'is_supported_branch' => false,
            'support_phase' => 'unsupported',
            'active_support_until' => null,
            'security_support_until' => null,
            'latest_known_patch' => null,
            'is_latest_known_patch' => null,
            'patch_warning' => null,
            'compatibility_notes' => [],
        ];

        foreach ($supportedBranches as $supportedBranch => $meta) {
            if ($supportedBranch !== $branch) {
                continue;
            }

            $result['is_supported_branch'] = true;
            $result['active_support_until'] = $meta['active_support_until'];
            $result['security_support_until'] = $meta['security_support_until'];
            $result['latest_known_patch'] = $meta['latest_known'];

            if ($today <= $meta['active_support_until']) {
                $result['support_phase'] = 'active-support';
            } elseif ($today <= $meta['security_support_until']) {
                $result['support_phase'] = 'security-support';
            }

            $result['is_latest_known_patch'] = version_compare($version, $meta['latest_known'], '>=');

            if (! $result['is_latest_known_patch']) {
                $result['patch_warning'] = 'Running below the latest known patch release for this branch.';
            }

            break;
        }

        if (version_compare($version, '8.2.0', '>=')) {
            $result['compatibility_notes'][] = 'PHP 8.2+: review dynamic properties deprecations in legacy code.';
        }
        if (version_compare($version, '8.3.0', '>=')) {
            $result['compatibility_notes'][] = 'PHP 8.3: test backward incompatible changes before rollout.';
        }
        if (version_compare($version, '8.4.0', '>=')) {
            $result['compatibility_notes'][] = 'PHP 8.4: review deprecated features and removed extensions.';
        }
        if (version_compare($version, '8.5.0', '>=')) {
            $result['compatibility_notes'][] = 'PHP 8.5: re-test older assumptions against new language features.';
        }

        return $result;
    }
}
