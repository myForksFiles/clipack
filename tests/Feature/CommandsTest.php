<?php

use Illuminate\Contracts\Console\Kernel;

it('registers schedule list command', function (): void {
    $this->artisan('mff:schedule:list')
        ->assertSuccessful();
});

it('keeps legacy schedule list alias', function (): void {
    $this->artisan('mff:scheduled')
        ->assertSuccessful();
});

it('registers unified clear commands without executing them', function (): void {
    $commands = app(Kernel::class)->all();

    expect($commands)->toHaveKey('mff:clear')
        ->and($commands)->toHaveKey('cleanup')
        ->and($commands)->toHaveKey('mff:clean:files')
        ->and($commands)->toHaveKey('mff:files:clear');
});

it('reports disk free space for root path', function (): void {
    $this->artisan('mff:disk:free', ['--path' => '/', '--limit' => 100])
        ->assertSuccessful();
});

it('shows auth basic status', function (): void {
    $this->artisan('mff:auth:basic', ['action' => 'status'])
        ->assertSuccessful();
});
