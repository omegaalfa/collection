<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Util;

use Iterator;

/** @template TKey of array-key @template TValue @implements Iterator<TKey, TValue> */
final class RegenerableIterator implements Iterator
{
    /** @var Iterator<TKey, TValue>|null */
    private ?Iterator $source = null;

    /** @param callable(): Iterator<TKey, TValue> $factory */
    public function __construct(private readonly mixed $factory) { }

    /**
     * Cria uma nova passagem independente sobre a mesma factory.
     *
     * @return self<TKey, TValue>
     */
    public function newIterator(): self
    {
        return new self($this->factory);
    }

    public function rewind(): void
    {
        $source = ($this->factory)();
        if (!$source instanceof Iterator) {
            throw new \UnexpectedValueException('Regenerable iterator factory must return an Iterator');
        }
        $this->source = $source;
        $this->source->rewind();
    }

    public function valid(): bool { return $this->source?->valid() ?? false; }
    public function key(): mixed { return $this->source?->key(); }
    public function current(): mixed { return $this->source?->current(); }
    public function next(): void { $this->source?->next(); }
}
