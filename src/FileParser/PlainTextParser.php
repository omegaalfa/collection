<?php

declare(strict_types=1);


namespace Omegaalfa\Collection\FileParser;

class PlainTextParser implements ParserInterface
{
    public function parse(string $line, int $lineNumber): string
    {
        return $line;
    }

    public function reset(): void
    {
        // No state to reset
    }

}