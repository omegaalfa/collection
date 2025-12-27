<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\FileParser;


class CsvParser implements ParserInterface
{
    /**
     * @var array|null
     */
    private ?array $headers = null;
    /**
     * @var bool
     */
    private bool $headersRead = false;

    /**
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param bool $hasHeaders
     */
    public function __construct(
        private string $delimiter = ',',
        private string $enclosure = '"',
        private string $escape = '\\',
        private bool   $hasHeaders = true
    )
    {
    }

    /**
     * @param string $line
     * @param int $lineNumber
     * @return mixed
     */
    public function parse(string $line, int $lineNumber): mixed
    {
        $data = str_getcsv($line, $this->delimiter, $this->enclosure, $this->escape);

        // First line is headers
        if ($this->hasHeaders && !$this->headersRead) {
            $this->headers = $data;
            $this->headersRead = true;
            return null; // Skip header line
        }

        // Return associative array if headers exist
        if ($this->headers !== null) {
            $result = [];
            foreach ($this->headers as $index => $header) {
                $result[$header] = $data[$index] ?? null;
            }
            return $result;
        }

        // Return indexed array
        return $data;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->headers = null;
        $this->headersRead = false;
    }
}