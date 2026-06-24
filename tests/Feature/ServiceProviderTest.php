<?php

use Illuminate\Contracts\Console\Kernel;
use MyForksFiles\CliPack\CliPackServiceProvider;

it('loads package service provider', function (): void {
    expect(app()->getProvider(CliPackServiceProvider::class))->not->toBeNull();
});

it('merges package configuration', function (): void {
    expect(config('clipack.name'))->toBe('Laravel CLI Pack')
        ->and(config('clipack.version'))->toBe('1.0.0')
        ->and(config('clipack.auth_basic.user'))->toBe('user')
        ->and(config('clipack.auth_basic.password'))->toBe('secretPassword')
        ->and(config('clipack.run_php.enabled'))->toBeFalse();
});

it('registers package commands', function (): void {
    $commands = app(Kernel::class)->all();

    expect($commands)->toHaveKeys([
        'cleanup',
        'mff:db:dump',
        'dev:log',
        'mff:runphp',
        'mff:scheduled',
        'mff:auth:basic',
    ]);
});
