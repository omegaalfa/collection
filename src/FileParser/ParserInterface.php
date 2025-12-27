<?php

namespace Omegaalfa\Collection\FileParser;

interface ParserInterface
{
    /**
     * Parse a line from the file
     *
     * @param string $line The line content
     * @param int $lineNumber Line number (0-based)
     * @return mixed Parsed data
     */
    public function parse(string $line, int $lineNumber): mixed;

    /**
     * Reset parser state (called on rewind)
     *
     * @return void
     */
    public function reset(): void;
}