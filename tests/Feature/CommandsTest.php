<?php

use Illuminate\Contracts\Console\Kernel;

it('registers schedule list command', function (): void {
    $this->artisan('mff:scheduled')
        ->assertSuccessful();
});

it('registers cleanup command without executing it', function (): void {
    $commands = app(Kernel::class)->all();

    expect($commands)->toHaveKey('cleanup');
});
