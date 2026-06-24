<?php

use MyForksFiles\CliPack\Services\TranscriptCleaner;

it('removes vtt headers timestamps entities and duplicate lines', function (): void {
    $raw = "WEBVTT\n\n00:00:01.000 --> 00:00:03.000\nHello &amp; welcome\nHello &amp; welcome\n\n00:00:04.000 --> 00:00:06.000\nNext line";

    $clean = TranscriptCleaner::clean($raw);

    expect($clean)->toBe("Hello & welcome\nNext line");
});

it('removes srt counters and timestamps', function (): void {
    $raw = "1\n00:00:01,000 --> 00:00:03,000\nFirst line\n\n2\n00:00:04,000 --> 00:00:06,000\nSecond line";

    $clean = TranscriptCleaner::clean($raw);

    expect($clean)->toBe("First line\nSecond line");
});

it('trims empty lines', function (): void {
    $clean = TranscriptCleaner::clean("\n\n  Alpha  \n\n  Beta  \n\n");

    expect($clean)->toBe("Alpha\nBeta");
});
