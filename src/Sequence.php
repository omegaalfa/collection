<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use ArrayIterator;
use Omegaalfa\Collection\Contract\SequenceInterface;
use OutOfBoundsException;
use Traversable;

/**
 * Sequence: Immutable ordered list of values
 *
 * Based on Larry Garfield's "Never Use Arrays" philosophy
 * @see https://presentations.garfieldtech.com/slides-never-use-arrays/
 *
 * @template T
 * @implements SequenceInterface<T>
 */
final readonly class Sequence implements SequenceInterface
{
    /**
     * @var list<T>
     */
    private array $items;

    /**
     * @param list<T> $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * Create empty sequence
     *
     * @return self<T>
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Create from values
     *
     * @template U
     * @param U ...$values
     * @return self<U>
     */
    public static function of(mixed ...$values): self
    {
        return new self($values);
    }

    /**
     * Create range
     *
     * @param int $start
     * @param int $end
     * @param int $step
     * @return self<int>
     */
    public static function range(int $start, int $end, int $step = 1): self
    {
        return new self(range($start, $end, $step));
    }

    /**
     * @inheritDoc
     */
    public function at(int $index): mixed
    {
        if (!isset($this->items[$index])) {
            throw new OutOfBoundsException("Index {$index} out of bounds");
        }

        return $this->items[$index];
    }

    /**
     * @inheritDoc
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function last(): mixed
    {
        $count = count($this->items);
        return $count > 0 ? $this->items[$count - 1] : null;
    }

    /**
     * @inheritDoc
     */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    /**
     * @inheritDoc
     */
    public function indexOf(mixed $value): ?int
    {
        $index = array_search($value, $this->items, true);
        return $index !== false ? $index : null;
    }

    /**
     * @inheritDoc
     */
    public function append(mixed $value): static
    {
        return new self([...$this->items, $value]);
    }

    /**
     * @inheritDoc
     */
    public function prepend(mixed $value): static
    {
        return new self([$value, ...$this->items]);
    }

    /**
     * @inheritDoc
     */
    public function insert(int $index, mixed $value): static
    {
        if ($index < 0 || $index > count($this->items)) {
            throw new OutOfBoundsException("Index {$index} out of bounds");
        }

        $items = $this->items;
        array_splice($items, $index, 0, [$value]);

        return new self($items);
    }

    /**
     * @inheritDoc
     */
    public function remove(int $index): static
    {
        if (!isset($this->items[$index])) {
            throw new OutOfBoundsException("Index {$index} out of bounds");
        }

        $items = $this->items;
        array_splice($items, $index, 1);

        return new self($items);
    }

    /**
     * @inheritDoc
     */
    public function reverse(): static
    {
        return new self(array_reverse($this->items));
    }

    /**
     * @inheritDoc
     */
    public function sort(callable $comparator): static
    {
        $items = $this->items;
        usort($items, $comparator);

        return new self($items);
    }

    /**
     * @inheritDoc
     */
    public function map(callable $mapper): SequenceInterface
    {
        $result = [];
        foreach ($this->items as $index => $item) {
            $result[] = $mapper($item, $index);
        }

        return new self($result);
    }

    /**
     * @inheritDoc
     */
    public function filter(callable $predicate): static
    {
        $result = [];
        foreach ($this->items as $index => $item) {
            if ($predicate($item, $index)) {
                $result[] = $item;
            }
        }

        return new self($result);
    }

    /**
     * Flat map
     *
     * @template U
     * @param callable(T, int): iterable<U> $mapper
     * @return self<U>
     */
    public function flatMap(callable $mapper): self
    {
        $result = [];
        foreach ($this->items as $index => $item) {
            foreach ($mapper($item, $index) as $mapped) {
                $result[] = $mapped;
            }
        }

        return new self($result);
    }

    /**
     * @inheritDoc
     */
    public function reduce(callable $reducer, mixed $initial): mixed
    {
        $carry = $initial;
        foreach ($this->items as $index => $item) {
            $carry = $reducer($carry, $item, $index);
        }

        return $carry;
    }

    /**
     * Apply function to each element
     *
     * @param callable(T, int): void $action
     * @return $this
     */
    public function each(callable $action): self
    {
        foreach ($this->items as $index => $item) {
            $action($item, $index);
        }

        return $this;
    }

    /**
     * Take first N elements
     *
     * @param int $n
     * @return static
     */
    public function take(int $n): static
    {
        return $this->slice(0, $n);
    }

    /**
     * @inheritDoc
     */
    public function slice(int $offset, ?int $length = null): static
    {
        return new self(array_slice($this->items, $offset, $length));
    }

    /**
     * Skip first N elements
     *
     * @param int $n
     * @return static
     */
    public function skip(int $n): static
    {
        return $this->slice($n);
    }

    /**
     * Get unique values
     *
     * @return static
     */
    public function unique(): static
    {
        return new self(array_values(array_unique($this->items, SORT_REGULAR)));
    }

    /**
     * Chunk into groups
     *
     * @param int $size
     * @return self<self<T>>
     */
    public function chunk(int $size): self
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('Chunk size must be positive');
        }

        $chunks = array_chunk($this->items, $size);
        return new self(array_map(fn($chunk) => new self($chunk), $chunks));
    }

    /**
     * Average of numeric values
     *
     * @return float|null
     */
    public function avg(): ?float
    {
        $count = count($this->items);
        return $count > 0 ? $this->sum() / $count : null;
    }

    /**
     * Sum numeric values
     *
     * @return int|float
     */
    public function sum(): int|float
    {
        return array_sum($this->items);
    }

    /**
     * Minimum value
     *
     * @return T|null
     */
    public function min(): mixed
    {
        return count($this->items) > 0 ? min($this->items) : null;
    }

    /**
     * Maximum value
     *
     * @return T|null
     */
    public function max(): mixed
    {
        return count($this->items) > 0 ? max($this->items) : null;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Convert to Map
     *
     * @template K of array-key
     * @template V
     * @param callable(T, int): array{K, V} $mapper
     * @return Map<K, V>
     */
    public function toMap(callable $mapper): Map
    {
        $result = [];
        foreach ($this->items as $index => $item) {
            [$key, $value] = $mapper($item, $index);
            $result[$key] = $value;
        }

        return Map::from($result);
    }

    /**
     * Create from iterable
     *
     * @template U
     * @param iterable<U> $items
     * @return self<U>
     */
    public static function from(iterable $items): self
    {
        if ($items instanceof self) {
            return $items;
        }

        return new self(is_array($items) ? array_values($items) : iterator_to_array($items, false));
    }

    /**
     * Convert to lazy sequence for deferred evaluation
     * Useful for large datasets or expensive transformations
     *
     * @return LazySequence<T>
     */
    public function toLazy(): LazySequence
    {
        return LazySequence::from($this->items);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Join elements into string
     *
     * @param string $separator
     * @return string
     */
    public function join(string $separator = ''): string
    {
        return implode($separator, $this->items);
    }
}
