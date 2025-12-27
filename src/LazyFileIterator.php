<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use Closure;
use Iterator;
use JsonException;
use Omegaalfa\Collection\FileParser\CsvParser;
use Omegaalfa\Collection\FileParser\JsonLinesParser;
use Omegaalfa\Collection\FileParser\ParserInterface;
use Omegaalfa\Collection\FileParser\PlainTextParser;
use Omegaalfa\Collection\FileParser\TsvParser;
use RuntimeException;
use SplFileObject;

/**
 * @implements Iterator<mixed>
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
     * @throws JsonException
     */
    public function current(): mixed
    {
        if (empty($this->line)) {
            return null;
        }

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

    /**
     * @return void
     */
    public function next(): void
    {
        do {
            if ($this->file->valid() && $str = $this->file->fgets()) {
                $this->line = trim($str);
                $this->pointer++;
                continue;
            }

            $this->line = '';
            break;
        } while (empty($this->line) && !$this->file->eof());
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return !empty($this->line);
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
        $this->file->rewind();

        // Reset parser state if needed (e.g., CSV headers)
        if ($this->parser instanceof ParserInterface) {
            $this->parser->reset();
        }

        // Read first non-empty line
        do {
            $str = $this->file->fgets();

            if ($str === false) {
                $this->line = '';
                break;
            }

            $this->line = trim($str);

        } while (empty($this->line) && !$this->file->eof());
    }
}
