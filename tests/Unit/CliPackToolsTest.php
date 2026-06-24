<?php

use MyForksFiles\CliPack\CliPackTools;

function cliPackToolsTestSubject(): object
{
    return new class
    {
        use CliPackTools;
    };
}

it('formats bytes as human readable file size', function (): void {
    $subject = cliPackToolsTestSubject();

    expect($subject::fileSize(0))->toBe('0,00 B')
        ->and($subject::fileSize(1024))->toBe('1,00 kB')
        ->and($subject::fileSize(1048576))->toBe('1,00 MB')
        ->and($subject::fileSize(1536, 1, '.'))->toBe('1.5 kB');
});

it('returns current date with default format', function (): void {
    $subject = cliPackToolsTestSubject();

    expect($subject::getDate())->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
});

it('returns current date with custom format', function (): void {
    $subject = cliPackToolsTestSubject();

    expect($subject::getDate('', 'Y'))->toMatch('/^\d{4}$/');
});

it('resolves default auth basic protection file path', function (): void {
    config()->set('packages.MyForksFiles.CliPack.app.fileAuthBasicProtection', null);

    $subject = cliPackToolsTestSubject();

    expect($subject::getFileAuthBasicProtection())->toBe(storage_path('auth_basic_protection'));
});

it('resolves configured auth basic protection file path', function (): void {
    config()->set('packages.MyForksFiles.CliPack.app.fileAuthBasicProtection', 'custom_auth_file');

    $subject = cliPackToolsTestSubject();

    expect($subject::getFileAuthBasicProtection())->toBe(storage_path('custom_auth_file'));
});
