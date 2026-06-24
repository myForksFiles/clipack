<?php

namespace MyForksFiles\CliPack\Services;

class TranscriptCleaner
{
    public static function clean(string $raw): string
    {
        $text = str_replace("\r\n", "\n", $raw);

        // Usuń nagłówek VTT.

        $text = preg_replace('/^WEBVTT.*?\n\n/s', '', $text) ?? $text;

        // Usuń timestampy VTT/SRT.

        $text = preg_replace(

            '/^\d{1,6}\s*$/m',

            '',

            $text

        ) ?? $text;

        $text = preg_replace(

            '/^\d{2}:\d{2}:\d{2}[,.]\d{3}\s+-->\s+\d{2}:\d{2}:\d{2}[,.]\d{3}.*$/m',

            '',

            $text

        ) ?? $text;

        $text = preg_replace(

            '/^\d{2}:\d{2}[,.]\d{3}\s+-->\s+\d{2}:\d{2}[,.]\d{3}.*$/m',

            '',

            $text

        ) ?? $text;

        // Usuń tagi typu <c>, <00:00:01.000>, itp.

        $text = preg_replace('/<[^>]+>/', '', $text) ?? $text;

        // Usuń HTML entities.

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Usuń duplikujące się linie z auto-caption.

        $lines = array_values(
            array_filter(
                array_map(

                    trim(...),

                    explode("\n", $text)

                )
            )
        );

        $result = [];

        $previous = null;

        foreach ($lines as $line) {
            if ($line === $previous) {
                continue;
            }

            $result[] = $line;

            $previous = $line;
        }

        $text = implode("\n", $result);

        // Lekkie sklejenie w akapity.

        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
