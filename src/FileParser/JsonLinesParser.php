<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\FileParser;

use JsonException;

class JsonLinesParser implements ParserInterface
{
    /**
     * @throws JsonException
     */
    public function parse(string $line, int $lineNumber): mixed
    {
        return json_decode($line, false, 512, JSON_THROW_ON_ERROR);
    }

    public function reset(): void
    {
        // No state to reset
    }
}