<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Traits;

use Generator;

/**
 * Trait para operações de transformação (map, filter, chunk, reverse, sort)
 * 
 * @template TKey of array-key
 * @template TValue
 */
trait CollectionTransformationsTrait
{
    /**
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $callback
     * @return self<TKey, TNewValue>
     */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->toArray()));
    }

    /**
     * @param callable(TValue, TKey): bool $callback
     * @return self<TKey, TValue>
     */
    public function filter(callable $callback): self
    {
        return new self(array_filter($this->toArray(), $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @param callable(TValue, TKey): void $callback
     * @return $this
     */
    public function each(callable $callback): static
    {
        foreach ($this->getIterator() as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /**
     * @return self<TKey, TValue>
     */
    public function reverse(): self
    {
        if (is_array($this->collection)) {
            return new self(array_reverse($this->collection, true));
        }

        return new self(array_reverse($this->toArray(), true));
    }

    /**
     * @param int $size
     * @return self<int, self<TKey, TValue>>
     */
    public function chunk(int $size): self
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('Chunk size must be greater than 0');
        }

        $chunks = [];
        $chunk = [];
        $count = 0;

        foreach ($this->getIterator() as $key => $item) {
            $chunk[$key] = $item;
            $count++;

            if ($count === $size) {
                $chunks[] = new self($chunk);
                $chunk = [];
                $count = 0;
            }
        }

        if (!empty($chunk)) {
            $chunks[] = new self($chunk);
        }

        return new self($chunks);
    }

    /**
     * @param callable(TValue, TValue): int $callback
     * @return self<TKey, TValue>
     */
    public function sort(callable $callback): self
    {
        $array = $this->toArray();
        uasort($array, $callback);

        return new self($array);
    }

    /**
     * @return self<TKey, TValue>
     */
    public function sortKeys(): self
    {
        $array = $this->toArray();
        ksort($array);

        return new self($array);
    }

    /**
     * @param int $limit
     * @return self<TKey, TValue>
     */
    public function take(int $limit): self
    {
        if ($limit < 0) {
            return $this->slice($limit);
        }

        return $this->slice(0, $limit);
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return self<TKey, TValue>
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new self(array_slice($this->toArray(), $offset, $length, true));
    }

    /**
     * @return self<TKey, TValue>
     */
    public function unique(): self
    {
        return new self(array_unique($this->toArray(), SORT_REGULAR));
    }

    /**
     * @template TReduce
     * @param callable(TReduce, TValue, TKey): TReduce $callback
     * @param TReduce $initial
     * @return TReduce
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($this->getIterator() as $key => $item) {
            $carry = $callback($carry, $item, $key);
        }

        return $carry;
    }

    /**
     * @param string|int $key
     * @return self<TKey, mixed>
     */
    public function pluck(string|int $key): self
    {
        $values = [];

        foreach ($this->getIterator() as $item) {
            if (is_array($item) && array_key_exists($key, $item)) {
                $values[] = $item[$key];
            } elseif (is_object($item) && property_exists($item, $key)) {
                $values[] = $item->$key;
            }
        }

        return new self($values);
    }

    /**
     * @return self<int, TKey>
     */
    public function keys(): self
    {
        return new self(array_keys($this->toArray()));
    }

    /**
     * @return self<int, TValue>
     */
    public function values(): self
    {
        $values = [];

        foreach ($this->getIterator() as $item) {
            $values[] = $item;
        }

        return new self($values);
    }
}
