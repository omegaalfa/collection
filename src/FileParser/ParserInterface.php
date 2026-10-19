<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\FileParser;

interface ParserInterface
{
    /**
     * Parse a line from the file
     *
     * Retornar null indica "sem dados para esta linha" e faz o iterador
     * pulá-la (ex.: linha de header do CSV). Linhas com dados falsy como
     * "0" ou "" devem retornar o valor parseado, nunca null.
     *
     * @param string $line The line content
     * @param int $lineNumber Line number (0-based)
     * @return mixed Parsed data, or null to skip the line
     */
    public function parse(string $line, int $lineNumber): mixed;

    /**
     * Reset parser state (called on rewind)
     *
     * @return void
     */
    public function reset(): void;
}
