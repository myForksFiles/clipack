<?php

namespace App\Console\Commands;

use Google\Client as GoogleClient;
use Google\Service\YouTube;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class YoutubeFindChannelIdCommand extends Command
{
    protected $signature = 'youtube:channel-id
                            {value? : Handle, URL albo fraza}
                            {--handle= : YouTube handle, np. @GoogleDevelopers}
                            {--url= : URL kanału YouTube}
                            {--query= : Fraza do wyszukania kanału}';

    protected $description = 'Znajduje YouTube channel ID po handle, URL lub frazie';

    public function handle(): int
    {
        $apiKey = config('services.youtube.api_key');

        if (empty($apiKey)) {
            $this->error('Brak YOUTUBE_API_KEY w .env');

            return self::FAILURE;
        }

        $youtube = $this->makeYoutubeService($apiKey);

        $value = $this->argument('value');
        $handle = $this->option('handle');
        $url = $this->option('url');
        $query = $this->option('query');

        if ($value && ! $handle && ! $url && ! $query) {
            if (Str::startsWith($value, ['http://', 'https://'])) {
                $url = $value;
            } elseif (Str::startsWith($value, '@')) {
                $handle = $value;
            } else {
                $query = $value;
            }
        }

        try {
            if ($handle) {
                return $this->findByHandle($youtube, $handle);
            }

            if ($url) {
                return $this->findByUrl($youtube, $url);
            }

            if ($query) {
                return $this->findByQuery($youtube, $query);
            }

            $this->warn('Podaj handle, URL albo query.');
            $this->line('Przykłady:');
            $this->line('php artisan youtube:channel-id --handle=@GoogleDevelopers');
            $this->line('php artisan youtube:channel-id --url=https://www.youtube.com/@GoogleDevelopers');
            $this->line('php artisan youtube:channel-id --query="Google Developers"');
            $this->line('php artisan youtube:channel-id @GoogleDevelopers');

            return self::INVALID;
        } catch (\Throwable $e) {
            $this->error('Błąd: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function makeYoutubeService(string $apiKey): YouTube
    {
        $client = new GoogleClient;
        $client->setDeveloperKey($apiKey);

        return new YouTube($client);
    }

    protected function findByHandle(YouTube $youtube, string $handle): int
    {
        $handle = ltrim(trim($handle), '@');

        $response = $youtube->search->listSearch('snippet', [
            'q' => '@'.$handle,
            'type' => 'channel',
            'maxResults' => 10,
        ]);

        if (empty($response->getItems())) {
            $this->warn("Nie znaleziono kanału dla handle: @$handle");

            return self::FAILURE;
        }

        $matchedItem = null;

        foreach ($response->getItems() as $item) {
            $title = $item->getSnippet()->getChannelTitle() ?? '';
            $description = $item->getSnippet()->getDescription() ?? '';

            if (
                Str::contains(Str::lower($title), Str::lower($handle)) ||
                Str::contains(Str::lower($description), '@'.Str::lower($handle))
            ) {
                $matchedItem = $item;
                break;
            }
        }

        $matchedItem ??= $response->getItems()[0];

        $channelId = $matchedItem->getSnippet()->getChannelId();
        $channelTitle = $matchedItem->getSnippet()->getChannelTitle();

        $this->info('Znaleziono kanał:');
        $this->line('Title: '.$channelTitle);
        $this->line('Channel ID: '.$channelId);

        return self::SUCCESS;
    }

    protected function findByUrl(YouTube $youtube, string $url): int
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';

        // /channel/UCxxxx
        if (preg_match('#/channel/([A-Za-z0-9_-]+)#', $path, $matches)) {
            $channelId = $matches[1];

            $channelResponse = $youtube->channels->listChannels('snippet', [
                'id' => $channelId,
                'maxResults' => 1,
            ]);

            if (! empty($channelResponse->getItems())) {
                $channel = $channelResponse->getItems()[0];
                $this->info('Znaleziono kanał:');
                $this->line('Title: '.$channel->getSnippet()->getTitle());
                $this->line('Channel ID: '.$channel->getId());

                return self::SUCCESS;
            }

            $this->warn("Nie znaleziono kanału dla ID z URL: {$channelId}");

            return self::FAILURE;
        }

        // /@handle
        if (preg_match('#/@([A-Za-z0-9._-]+)#', $path, $matches)) {
            return $this->findByHandle($youtube, '@'.$matches[1]);
        }

        // /c/nazwa albo /user/nazwa
        if (preg_match('#/(c|user)/([^/]+)#', $path, $matches)) {
            return $this->findByQuery($youtube, urldecode($matches[2]));
        }

        $this->warn('Nie udało się rozpoznać formatu URL.');

        return self::FAILURE;
    }

    protected function findByQuery(YouTube $youtube, string $query): int
    {
        $response = $youtube->search->listSearch('snippet', [
            'q' => $query,
            'type' => 'channel',
            'maxResults' => 10,
        ]);

        if (empty($response->getItems())) {
            $this->warn("Nie znaleziono kanału dla frazy: {$query}");

            return self::FAILURE;
        }

        $rows = [];

        foreach ($response->getItems() as $item) {
            $rows[] = [
                'title' => $item->getSnippet()->getChannelTitle(),
                'channel_id' => $item->getSnippet()->getChannelId(),
                'published_at' => $item->getSnippet()->getPublishedAt(),
            ];
        }

        $this->table(
            ['Title', 'Channel ID', 'Published At'],
            array_map(fn ($row) => [
                $row['title'],
                $row['channel_id'],
                $row['published_at'],
            ], $rows)
        );

        $this->info('Pierwszy wynik: '.$rows[0]['channel_id']);

        return self::SUCCESS;
    }
}
