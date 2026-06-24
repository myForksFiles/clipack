<?php

namespace MyForksFiles\CliPack\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class YoutubeChannelResolverService
{
    protected string $baseUrl = 'https://www.youtube.com';

    public function resolve(string $input): array
    {
        $input = trim($input);

        if ($input === '') {
            throw new RuntimeException('Pusty input.');
        }

        if ($this->isChannelId($input)) {
            return [
                'input' => $input,
                'type' => 'channel_id',
                'channel_id' => $input,
                'url' => $this->baseUrl.'/channel/'.$input,
                'canonical_url' => $this->baseUrl.'/channel/'.$input,
                'resolved_by' => 'direct',
                'title' => null,
            ];
        }

        if ($this->looksLikeUrl($input)) {
            return $this->resolveFromUrl($input);
        }

        if (Str::startsWith($input, '@')) {
            return $this->resolveFromHandle($input);
        }

        try {
            return $this->resolveFromHandle('@'.ltrim($input, '@'));
        } catch (\Throwable) {
        }

        return $this->resolveFromSearch($input);
    }

    public function resolveFromUrl(string $url): array
    {
        $url = trim($url);

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://'.ltrim($url, '/');
        }

        if (preg_match('~youtube\.com/channel/(UC[a-zA-Z0-9_-]{20,})~i', $url, $matches)) {
            return [
                'input' => $url,
                'type' => 'url_channel',
                'channel_id' => $matches[1],
                'url' => $this->baseUrl.'/channel/'.$matches[1],
                'canonical_url' => $this->baseUrl.'/channel/'.$matches[1],
                'resolved_by' => 'url_channel',
                'title' => null,
            ];
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? '';

        if (preg_match('~^/@([A-Za-z0-9._-]+)$~', $path, $matches)) {
            return $this->resolveFromHandle('@'.$matches[1]);
        }

        $html = $this->fetchHtml($url);
        $meta = $this->extractChannelMeta($html);

        if (! $meta['channel_id']) {
            $fallbackQuery = $this->extractFallbackQueryFromUrl($url);

            if ($fallbackQuery !== null) {
                $fallback = $this->resolveFromSearch($fallbackQuery);
                $fallback['input'] = $url;
                $fallback['type'] = 'url';
                $fallback['resolved_by'] = 'url_search_fallback';

                return $fallback;
            }

            throw new RuntimeException('Nie znaleziono channel ID dla URL.');
        }

        return [
            'input' => $url,
            'type' => 'url',
            'channel_id' => $meta['channel_id'],
            'url' => $this->baseUrl.'/channel/'.$meta['channel_id'],
            'canonical_url' => $meta['canonical_url'] ?: $this->baseUrl.'/channel/'.$meta['channel_id'],
            'resolved_by' => 'url_html',
            'title' => $meta['title'],
        ];
    }

    public function resolveFromHandle(string $handle): array
    {
        $handle = '@'.ltrim(trim($handle), '@');
        $url = $this->baseUrl.'/'.$handle;

        $html = $this->fetchHtml($url);
        $meta = $this->extractChannelMeta($html);

        if (! $meta['channel_id']) {
            $fallback = $this->resolveFromSearch($handle);
            $fallback['input'] = $handle;
            $fallback['type'] = 'handle';
            $fallback['resolved_by'] = 'handle_search_fallback';
            $fallback['canonical_url'] = $fallback['canonical_url'] ?: $url;

            return $fallback;
        }

        return [
            'input' => $handle,
            'type' => 'handle',
            'channel_id' => $meta['channel_id'],
            'url' => $this->baseUrl.'/channel/'.$meta['channel_id'],
            'canonical_url' => $meta['canonical_url'] ?: $this->baseUrl.'/'.$handle,
            'resolved_by' => 'handle_html',
            'title' => $meta['title'],
        ];
    }

    public function resolveFromSearch(string $query): array
    {
        $searchQueries = array_values(array_unique(array_filter([
            $query,
            ltrim($query, '@'),
            str_replace('@', '', $query),
        ])));

        foreach ($searchQueries as $searchQuery) {
            $searchUrl = $this->baseUrl.'/results?search_query='.urlencode($searchQuery);
            $html = $this->fetchHtml($searchUrl);

            $channelId = $this->extractFirstChannelIdFromSearch($html);

            if ($channelId) {
                return [
                    'input' => $query,
                    'type' => 'search',
                    'channel_id' => $channelId,
                    'url' => $this->baseUrl.'/channel/'.$channelId,
                    'canonical_url' => $this->baseUrl.'/channel/'.$channelId,
                    'resolved_by' => 'search_html',
                    'title' => null,
                ];
            }
        }

        throw new RuntimeException("Nie znaleziono channel ID dla frazy: {$query}");
    }

    protected function fetchHtml(string $url): string
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9,pl;q=0.8',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ])
            ->withOptions([
                'allow_redirects' => true,
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Błąd HTTP {$response->status()} dla URL: {$url}");
        }

        $html = $response->body();

        if ($html === '') {
            throw new RuntimeException("Pusta odpowiedź HTML dla URL: {$url}");
        }

        return $html;
    }

    protected function extractChannelMeta(string $html): array
    {
        $channelId = null;
        $canonicalUrl = null;
        $title = null;

        $idPatterns = [
            '/https:\\\\/\\\\/www\\\\.youtube\\\\.com\\\\/feeds\\\\/videos\\\\.xml\\\\?channel_id=(UC[a-zA-Z0-9_-]{20,})/',
            '/"externalId":"(UC[a-zA-Z0-9_-]{20,})"/',
            '/"channelId":"(UC[a-zA-Z0-9_-]{20,})"/',
            '/"browseId":"(UC[a-zA-Z0-9_-]{20,})"/',
            '/"browseEndpoint":\\{"browseId":"(UC[a-zA-Z0-9_-]{20,})"/',
            '/"navigationEndpoint":\\{"browseEndpoint":\\{"browseId":"(UC[a-zA-Z0-9_-]{20,})"/',
            '/channel_id=(UC[a-zA-Z0-9_-]{20,})/',
            '/https:\\/\\/www\\.youtube\\.com\\/channel\\/(UC[a-zA-Z0-9_-]{20,})/',
            '/<link[^>]+rel="canonical"[^>]+href="https:\\/\\/www\\.youtube\\.com\\/channel\\/(UC[a-zA-Z0-9_-]{20,})"/i',
        ];

        foreach ($idPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $channelId = $matches[1];
                break;
            }
        }

        $canonicalPatterns = [
            '/<link[^>]+rel="canonical"[^>]+href="([^"]+)"/i',
            '/"canonicalBaseUrl":"([^"]+)"/',
            '/"vanityChannelUrl":"([^"]+)"/',
        ];

        foreach ($canonicalPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $canonicalUrl = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);

                if (Str::startsWith($canonicalUrl, '/')) {
                    $canonicalUrl = rtrim($this->baseUrl, '/').$canonicalUrl;
                }

                $canonicalUrl = str_replace('\\/', '/', $canonicalUrl);
                break;
            }
        }

        $titlePatterns = [
            '/<meta property="og:title" content="([^"]+)"/i',
            '/<title>(.*?)<\/title>/is',
            '/"title":"([^"]+)"/',
        ];

        foreach ($titlePatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $title = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5));
                $title = preg_replace('/\s+-\s+YouTube$/i', '', $title);
                break;
            }
        }

        return [
            'channel_id' => $channelId,
            'canonical_url' => $canonicalUrl,
            'title' => $title,
        ];
    }

    protected function extractFirstChannelIdFromSearch(string $html): ?string
    {
        $patterns = [
            '/"channelId":"(UC[a-zA-Z0-9_-]{20,})"/',
            '/"browseId":"(UC[a-zA-Z0-9_-]{20,})"/',
            '/"browseEndpoint":\\{"browseId":"(UC[a-zA-Z0-9_-]{20,})"/',
            '/"navigationEndpoint":\\{"browseEndpoint":\\{"browseId":"(UC[a-zA-Z0-9_-]{20,})"/',
            '/\/channel\/(UC[a-zA-Z0-9_-]{20,})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $matches = [] ? '' : $html, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    protected function extractFallbackQueryFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        $path = trim((string) ($parts['path'] ?? ''), '/');

        if ($path === '') {
            return null;
        }

        if (preg_match('~^@([A-Za-z0-9._-]+)$~', $path, $matches)) {
            return '@'.$matches[1];
        }

        if (preg_match('~^(user|c)/([^/]+)$~', $path, $matches)) {
            return urldecode($matches[2]);
        }

        return urldecode($path);
    }

    protected function looksLikeUrl(string $value): bool
    {
        return Str::startsWith($value, [
            'http://',
            'https://',
            'www.youtube.com',
            'youtube.com',
            'm.youtube.com',
        ]);
    }

    protected function isChannelId(string $value): bool
    {
        return (bool) preg_match('/^UC[a-zA-Z0-9_-]{20,}$/', $value);
    }
}
