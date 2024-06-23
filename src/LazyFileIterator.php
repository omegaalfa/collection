<?php

declare(strict_types = 1);

namespace Omegaalfa\Collection;

use Iterator;
use JsonException;
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
	private string $line;

	/**
	 * @var SplFileObject
	 */
	private SplFileObject $file;

	public function __construct(string $filePath)
	{
		$this->file = new SplFileObject($filePath);
	}

	/**
	 * @return mixed
	 * @throws JsonException
	 */
	public function current(): mixed
	{
		return json_decode($this->line, false, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return void
	 */
	public function next(): void
	{
		if($str = $this->file->fgets()) {
			$this->line = $str;
			$this->pointer++;
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
		return !empty($this->line) && $this->file->valid();
	}

	/**
	 * @return void
	 */
	public function rewind(): void
	{
		if($str = $this->file->fgets()) {
			$this->pointer = 0;
			$this->file->seek(0);
			$this->line = $str;
		}
	}
}
