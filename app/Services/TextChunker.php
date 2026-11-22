<?php

namespace App\Services;

class TextChunker
{
    /**
     * Split extracted text into overlapping chunks for embedding and retrieval.
     *
     * @return list<string>
     */
    public static function chunk(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        if ($chunkSize < 1 || $overlap < 0 || $overlap >= $chunkSize) {
            throw new \InvalidArgumentException('Chunk size must be positive and overlap must be smaller than the chunk size.');
        }

        $text = trim($text);
        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $chunk = trim(mb_substr($text, $start, $chunkSize));

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            $start += $chunkSize - $overlap;
        }

        return $chunks;
    }
}
