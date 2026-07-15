<?php

use Illuminate\Support\Facades\File;

it('checks disk space with mff:disk:check', function (): void {
    $this->artisan('mff:disk:check', ['--path' => '/', '--limit' => 100])
        ->assertSuccessful();
});

it('keeps legacy knx disk alias', function (): void {
    $this->artisan('knx:qs:disk', ['--path' => '/', '--limit' => 100])
        ->assertSuccessful();
});

it('rotates logs into archive directory', function (): void {
    $source = storage_path('app/test-import-logs');
    $archive = storage_path('app/test-import-logs-archive');

    File::deleteDirectory($source);
    File::deleteDirectory($archive);
    File::ensureDirectoryExists($source);
    File::put($source.'/app.log', "line\n");

    $this->artisan('mff:logs:rotate', [
        '--path' => $source,
        '--archive' => $archive,
        '--days' => 60,
    ])->assertSuccessful();

    expect(File::files($source))->toBeEmpty()
        ->and(File::files($archive))->not->toBeEmpty();

    File::deleteDirectory($source);
    File::deleteDirectory($archive);
});

it('exports language files to csv', function (): void {
    $langPath = storage_path('app/test-lang');
    $output = storage_path('app/test-lang-export.csv');

    File::deleteDirectory($langPath);
    File::delete($output);
    File::ensureDirectoryExists($langPath.'/en');
    File::put($langPath.'/en/messages.php', "<?php\n\nreturn ['hello' => 'Hello', 'nested' => ['world' => 'World']];\n");

    $this->artisan('mff:lang:export', [
        '--path' => $langPath,
        '--output' => $output,
    ])->assertSuccessful();

    expect(File::exists($output))->toBeTrue();
    $csv = File::get($output);
    expect($csv)->toContain('hello')
        ->and($csv)->toContain('Hello')
        ->and($csv)->toContain('nested.world');

    File::deleteDirectory($langPath);
    File::delete($output);
});

it('fails create user when model class is missing', function (): void {
    $this->artisan('mff:create:user', [
        '--email' => 'demo@example.test',
        '--name' => 'Demo',
        '--password' => 'secret',
        '--model' => 'App\\Models\\DoesNotExistUser',
    ])->assertFailed();
});

it('runs laravel clear via artisan apis', function (): void {
    $this->artisan('mff:clear', ['--no-flush' => true])
        ->assertSuccessful();
});

it('keeps legacy cleanup alias for laravel clear', function (): void {
    $this->artisan('cleanup')
        ->assertSuccessful();
});

it('wipes storage cache files and truncates laravel log', function (): void {
    $cacheFile = storage_path('framework/cache/data/test-cache-file');
    File::ensureDirectoryExists(dirname($cacheFile));
    File::put($cacheFile, 'cached');
    File::ensureDirectoryExists(storage_path('logs'));
    File::put(storage_path('logs/laravel.log'), "old log\n");

    $this->artisan('mff:clean:files', ['--report' => true])
        ->assertSuccessful();

    expect(File::exists($cacheFile))->toBeFalse()
        ->and(File::get(storage_path('logs/laravel.log')))->toBe('');
});
