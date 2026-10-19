<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use Closure;
use Iterator;
use Omegaalfa\Collection\FileParser\CsvParser;
use Omegaalfa\Collection\FileParser\JsonLinesParser;
use Omegaalfa\Collection\FileParser\ParserInterface;
use Omegaalfa\Collection\FileParser\PlainTextParser;
use Omegaalfa\Collection\FileParser\TsvParser;
use RuntimeException;
use SplFileObject;

/**
 * @implements Iterator<int, mixed>
 */
class LazyFileIterator implements Iterator
{
    /**
     * @var int
     */
    private int $pointer = 0;

    /**
     * @var string
     */
    private string $line = '';

    /**
     * Valor parseado da linha corrente (null = linha ainda não processada)
     */
    private mixed $currentParsed = null;

    private bool $hasCurrent = false;

    private bool $firstRead = true;

    /**
     * @var SplFileObject
     */
    private SplFileObject $file;

    /**
     * @var ParserInterface|Closure
     */
    private ParserInterface|Closure $parser;

    /**
     * @param string $filePath Path to file
     * @param ParserInterface|Closure|null $parser Custom parser (null = auto-detect)
     *
     * @throws RuntimeException If file doesn't exist or isn't readable
     */
    public function __construct(string $filePath, ParserInterface|Closure|null $parser = null)
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException("File not readable: {$filePath}");
        }

        $this->file = new SplFileObject($filePath);
        $this->parser = $parser ?? $this->autoDetectParser($filePath);
    }

    /**
     * Auto-detect parser based on file extension
     */
    private function autoDetectParser(string $filePath): ParserInterface
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'jsonl', 'ndjson' => new JsonLinesParser(),
            'csv' => new CsvParser(),
            'tsv' => new TsvParser(),
            'txt', 'log' => new PlainTextParser(),
            default => new JsonLinesParser(), // Default to JSON for backward compatibility
        };
    }

    /**
     * Factory: JSON Lines iterator
     *
     * @param string $filePath
     * @return self
     */
    public static function jsonLines(string $filePath): self
    {
        return new self($filePath, new JsonLinesParser());
    }

    /**
     * Factory: CSV iterator
     *
     * @param string $filePath
     * @param string $delimiter Field delimiter
     * @param string $enclosure Field enclosure
     * @param string $escape Escape character
     * @param bool $hasHeaders First line is header
     *
     * @return self
     *
     */
    public static function csv(
        string $filePath,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
        bool   $hasHeaders = true
    ): self
    {
        return new self($filePath, new CsvParser($delimiter, $enclosure, $escape, $hasHeaders));
    }

    /**
     * Factory: TSV iterator
     */
    public static function tsv(string $filePath, bool $hasHeaders = true): self
    {
        return new self($filePath, new TsvParser($hasHeaders));
    }

    /**
     * Factory: Plain text iterator
     */
    public static function text(string $filePath): self
    {
        return new self($filePath, new PlainTextParser());
    }

    /**
     * Factory: Custom parser
     */
    public static function custom(string $filePath, callable $parser): self
    {
        return new self($filePath, $parser instanceof Closure ? $parser : Closure::fromCallable($parser));
    }

    /**
     * @return mixed
     */
    public function current(): mixed
    {
        return $this->currentParsed;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->advanceToData();
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->hasCurrent;
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->pointer;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->pointer = 0;
        $this->firstRead = true;
        $this->hasCurrent = false;
        $this->currentParsed = null;
        $this->file->rewind();

        // Reset parser state if needed (e.g., CSV headers)
        if ($this->parser instanceof ParserInterface) {
            $this->parser->reset();
        }

        $this->advanceToData();
    }

    /**
     * Avança para a próxima linha com dados.
     *
     * Linhas em branco são ignoradas. Um parse que retorna null indica
     * "sem dados" (ex.: linha de header do CSV) e também é ignorado.
     * O valor "0" é um dado válido e nunca deve ser descartado.
     */
    private function advanceToData(): void
    {
        while (true) {
            // SplFileObject lança exceção se fgets() for chamado após o EOF;
            // no EOF ele retorna '' uma única vez, por isso os dois guardas
            if (!$this->file->valid()) {
                $this->endOfData();
                return;
            }

            $str = $this->file->fgets();

            // EOF: false (PHP 8.4) ou '' (PHP 8.5+); "0" é dado válido
            if (strlen((string) $str) === 0) {
                $this->endOfData();
                return;
            }

            if (!$this->firstRead) {
                $this->pointer++;
            }
            $this->firstRead = false;

            $line = rtrim((string) $str, "\r\n");
            if ($line === '') {
                continue;
            }

            $this->line = $line;
            $parsed = $this->parseLine();

            if ($parsed === null) {
                continue;
            }

            $this->currentParsed = $parsed;
            $this->hasCurrent = true;
            return;
        }
    }

    private function endOfData(): void
    {
        $this->line = '';
        $this->currentParsed = null;
        $this->hasCurrent = false;
    }

    private function parseLine(): mixed
    {
        try {
            if ($this->parser instanceof Closure) {
                return ($this->parser)($this->line, $this->pointer);
            }

            return $this->parser->parse($this->line, $this->pointer);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                sprintf(
                    "Parse error at line %d: %s. Content: %s",
                    $this->pointer,
                    $e->getMessage(),
                    substr($this->line, 0, 100)
                ),
                0,
                $e
            );
        }
    }
}
