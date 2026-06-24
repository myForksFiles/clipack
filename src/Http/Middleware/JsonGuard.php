<?php

namespace MyForksFiles\CliPack\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;

class JsonGuard
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $userAgent = $request->header('User-Agent');
        $referer = $request->header('Referer');

        // List of blocked user agents (example patterns)
        $blockedUserAgents = [
            '/curl/i',
            '/wget/i',
            '/bot/i',
            '/spider/i',
            '/crawl/i',
            '/scrapy/i',
        ];

        $appUrl = config('app.url');
        $appUrl = explode('://', (string) $appUrl);
        $appUrl = $appUrl[1] ?? '';
        if (stristr($appUrl, 'www.')) {
            $appUrl = str_replace('www.', '', $baseUrl);
        }
        if (stristr($appUrl, '/')) {
            $appUrl = explode('/', $appUrl);
            $appUrl = $appUrl[0] ?? '';
        }
        if (empty($appUrl)) {
            throw new Exception('APP_URL is empty');
        }

        config('APP_ALLOWED_HOSTS'); // @TODO: Define coma separated list in .env

        // List of allowed domains (adjust as needed)
        $allowedReferrers = [
            $appUrl,
            'www.'.$appUrl,
            'production.'.$appUrl,
            'www.production.'.$appUrl,
            'staging.'.$appUrl,
        ];

        foreach ($allowedReferrers as $allowedReferrer) {
            $allowedReferrers[] = 'http://'.$allowedReferrer;
            $allowedReferrers[] = 'https://'.$allowedReferrer;
        }

        // Check if the User-Agent matches any of the blocked patterns
        foreach ($blockedUserAgents as $blockedUserAgent) {
            if (preg_match($blockedUserAgent, $userAgent)) {
                return response()->json(
                    ['error' => 'Access forbidden: Your request looks like a web crawler or bot.'],
                    403
                );
            }
        }

        // Check if the request has a valid Referer header
        if ($referer && collect($allowedReferrers)->doesntContain(fn ($url) => stripos($referer, (string) $url) === 0)) {
            return response()->json(
                ['error' => 'Access forbidden: Invalid referer.'],
                403
            );
        }

        return $next($request);
    }
}
