<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use Iterator;
use JsonException;
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
     * @param string $filePath
     *
     * @throws RuntimeException
     */
    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException("File not readable: {$filePath}");
        }

        $this->file = new SplFileObject($filePath);
        $this->line = $this->file->fgets() ?: '';
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

        return json_decode($this->line, false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return void
     */
    public function next(): void
    {
        if ($str = $this->file->fgets()) {
            $this->line = $str;
            $this->pointer++;
        } else {
            $this->line = '';
        }
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->pointer;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return !empty($this->line) && !$this->file->eof();
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->pointer = 0;
        $this->file->rewind();
        $this->line = $this->file->fgets() ?: '';
    }
}
