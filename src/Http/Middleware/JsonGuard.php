<?php

namespace MyForksFiles\CliPack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonGuard
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = (string) $request->header('User-Agent', '');
        $referer = (string) $request->header('Referer', '');

        $blockedUserAgents = [
            '/curl/i',
            '/wget/i',
            '/bot/i',
            '/spider/i',
            '/crawl/i',
            '/scrapy/i',
        ];

        foreach ($blockedUserAgents as $blockedUserAgent) {
            if ($userAgent !== '' && preg_match($blockedUserAgent, $userAgent) === 1) {
                return response()->json(
                    ['error' => 'Access forbidden: Your request looks like a web crawler or bot.'],
                    403
                );
            }
        }

        $allowedHosts = $this->allowedHosts();

        if ($referer !== '' && ! $this->isAllowedReferer($referer, $allowedHosts)) {
            return response()->json(
                ['error' => 'Access forbidden: Invalid referer.'],
                403
            );
        }

        return $next($request);
    }

    /**
     * @return array<int, string>
     */
    private function allowedHosts(): array
    {
        $configured = config('clipack.allowed_hosts', []);
        $hosts = is_array($configured) ? $configured : [];

        $appUrl = (string) config('app.url', '');
        $host = parse_url($appUrl, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            $hosts[] = $host;
            $hosts[] = 'www.'.$host;
        }

        return array_values(array_unique(array_filter($hosts, static fn ($value) => is_string($value) && $value !== '')));
    }

    /**
     * @param  array<int, string>  $allowedHosts
     */
    private function isAllowedReferer(string $referer, array $allowedHosts): bool
    {
        $refererHost = parse_url($referer, PHP_URL_HOST);

        if (! is_string($refererHost) || $refererHost === '') {
            return false;
        }

        foreach ($allowedHosts as $allowedHost) {
            if (strcasecmp($refererHost, $allowedHost) === 0) {
                return true;
            }
        }

        return false;
    }
}
