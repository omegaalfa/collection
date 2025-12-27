<?php


declare(strict_types=1);

namespace Omegaalfa\Collection\FileParser;

class TsvParser extends CsvParser
{

    public function __construct(bool $hasHeaders = true)
    {
        parent::__construct(
            delimiter: "\t",
            enclosure: '"',
            escape: '\\',
            hasHeaders: $hasHeaders
        );
    }

}