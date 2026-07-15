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

it('registers package commands and aliases', function (): void {
    $commands = app(Kernel::class)->all();

    expect($commands)->toHaveKeys([
        'mff:clear',
        'clean',
        'cleanup',
        'mff:cleanup',
        'mff:clear:all',
        'mff:clean:up',
        'mff:cache:clear',
        'mff:cached',
        'mff:dev:clear',
        'dev:clear',
        'mff:clean:files',
        'mff:files:clear',
        'mff:logs:clear',
        'mff:apache:logs',
        'mff:logs:rotate',
        'knx:qs:logs:rotate',
        'mff:db:dump',
        'mff:db:import',
        'mff:dev:log',
        'dev:log',
        'mff:runphp',
        'mff:schedule:list',
        'mff:scheduled',
        'mff:auth:basic',
        'mff:disk:free',
        'mff:space',
        'mff:disk:check',
        'knx:qs:disk',
        'mff:create:user',
        'mff:lang:export',
        'mff:crontab:backup',
        'mff:schema:check',
        'dev:check:schema',
        'mff:security:audit',
        'security:audit',
        'mff:security:check',
        'mmf:security:check',
        'mff:video:download:x',
        'video:download:x',
        'mff:video:download:yt',
        'video:download:yt',
        'mff:youtube:transcript',
        'youtube:transcript',
        'mff:article:from-transcript',
        'article:from-transcript',
        'mff:youtube:channel-id',
        'youtube:channel-id',
    ]);
});
