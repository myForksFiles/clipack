<?php

use MyForksFiles\CliPack\Services\SecurityAuditService;

function callSecurityAuditPrivateMethod(string $method, mixed ...$arguments): mixed
{
    $service = new SecurityAuditService;
    $reflection = new ReflectionMethod($service, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke($service, ...$arguments);
}

it('normalizes empty disabled functions list', function (): void {
    expect(callSecurityAuditPrivateMethod('normalizeDisabledFunctions', ''))->toBe([])
        ->and(callSecurityAuditPrivateMethod('normalizeDisabledFunctions', '[empty]'))->toBe([])
        ->and(callSecurityAuditPrivateMethod('normalizeDisabledFunctions', '[not available]'))->toBe([]);
});

it('normalizes comma separated disabled functions list', function (): void {
    expect(callSecurityAuditPrivateMethod('normalizeDisabledFunctions', 'exec, shell_exec, system'))
        ->toBe(['exec', 'shell_exec', 'system']);
});

it('detects prefix paths safely', function (): void {
    expect(callSecurityAuditPrivateMethod('pathStartsWith', '/var/www/html/public', '/var/www/html'))->toBeTrue()
        ->and(callSecurityAuditPrivateMethod('pathStartsWith', '/var/www/html', '/var/www/html'))->toBeTrue()
        ->and(callSecurityAuditPrivateMethod('pathStartsWith', '/var/www-other/html', '/var/www/html'))->toBeFalse();
});

it('returns parent path levels', function (): void {
    $levels = callSecurityAuditPrivateMethod('parentLevels', '/var/www/html/public', 3);

    expect($levels)->toBe([
        '/var/www/html',
        '/var/www',
        '/var',
    ]);
});

it('reports dangerous functions availability structure', function (): void {
    $report = callSecurityAuditPrivateMethod('dangerousFunctionsReport', 'exec,shell_exec');

    expect($report)->toBeArray()
        ->and($report[0])->toHaveKeys(['name', 'exists', 'disabled', 'available']);
});
