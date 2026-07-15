<?php

use Illuminate\Console\Command;

it('blocks runphp command when disabled', function (): void {
    $file = storage_path('app/clipack-scripts/example.php');

    $this->artisan('mff:runphp', ['file' => $file])
        ->expectsOutput('This command is disabled. Set CLIPACK_RUN_PHP_ENABLED=true to enable it.')
        ->assertExitCode(Command::FAILURE);
});

it('requires force option when enabled', function (): void {
    config()->set('clipack.run_php.enabled', true);

    $dir = storage_path('app/clipack-scripts');
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $file = $dir.'/example.php';
    file_put_contents($file, '<?php echo "ok";');

    $this->artisan('mff:runphp', ['file' => $file])
        ->expectsOutput('Refusing to execute without --force.')
        ->assertExitCode(Command::FAILURE);
});

it('blocks files outside configured allowed path', function (): void {
    config()->set('clipack.run_php.enabled', true);
    config()->set('clipack.run_php.allowed_path', storage_path('app/clipack-scripts'));

    $dir = storage_path('app/not-allowed');
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $file = $dir.'/example.php';
    file_put_contents($file, '<?php echo "outside";');

    $this->artisan('mff:runphp', [
        'file' => $file,
        '--force' => true,
    ])->assertExitCode(Command::FAILURE);
});

it('executes allowed file when enabled and forced', function (): void {
    config()->set('clipack.run_php.enabled', true);

    $dir = storage_path('app/clipack-scripts');
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $file = $dir.'/example.php';
    file_put_contents($file, '<?php echo "allowed-script-output";');

    $this->artisan('mff:runphp', [
        'file' => $file,
        '--force' => true,
    ])->assertSuccessful();
});
