<?php

namespace MyForksFiles\CliPack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthBasic
{
    /**
     * @var array<string, string>
     */
    protected array $credentials = [
        'user' => 'user',
        'pass' => 'secretPassword',
    ];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! App::isProduction()) {
            return $next($request);
        }

        $this->credentials = $this->getCredentials();

        $user = (string) $request->getUser();
        $pass = (string) $request->getPassword();

        if ($user === '' || $pass === '') {
            Log::warning('Basic auth login required.', [
                'ip' => $request->ip(),
            ]);

            return $this->authResponse('Login required.');
        }

        if (! $this->basicValidation($user, $pass)) {
            Log::warning('Invalid basic auth credentials.', [
                'ip' => $request->ip(),
                'user' => $user,
            ]);

            return $this->authResponse('Invalid credentials.');
        }

        return $next($request);
    }

    /**
     * @return array{user: string, pass: string}
     */
    protected function getCredentials(): array
    {
        return [
            'user' => (string) config('clipack.auth_basic.user', $this->credentials['user']),
            'pass' => (string) config('clipack.auth_basic.password', $this->credentials['pass']),
        ];
    }

    private function authResponse(string $msg): Response
    {
        return response(
            'Whoops, looks like something went wrong: '.$msg,
            401,
            ['WWW-Authenticate' => 'Basic']
        );
    }

    private function basicValidation(string $user, string $pass): bool
    {
        return hash_equals($this->credentials['user'], $user)
            && hash_equals($this->credentials['pass'], $pass);
    }
}
