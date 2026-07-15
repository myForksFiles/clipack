<?php

use Illuminate\Http\Request;
use MyForksFiles\CliPack\Http\Middleware\AuthBasic;

it('passes through outside production', function (): void {
    $request = Request::create('/');
    $middleware = new AuthBasic;

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('ok');
});

it('requires credentials in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $request = Request::create('/');
    $middleware = new AuthBasic;

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(401);
    expect($response->headers->get('WWW-Authenticate'))->toBe('Basic');
});

it('rejects invalid credentials in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    config()->set('clipack.auth_basic.user', 'admin');
    config()->set('clipack.auth_basic.password', 'secret');

    $request = Request::create('/', server: [
        'PHP_AUTH_USER' => 'admin',
        'PHP_AUTH_PW' => 'wrong',
    ]);

    $middleware = new AuthBasic;

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(401);
});

it('accepts valid credentials in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    config()->set('clipack.auth_basic.user', 'admin');
    config()->set('clipack.auth_basic.password', 'secret');

    $request = Request::create('/', server: [
        'PHP_AUTH_USER' => 'admin',
        'PHP_AUTH_PW' => 'secret',
    ]);

    $middleware = new AuthBasic;

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('ok');
});
