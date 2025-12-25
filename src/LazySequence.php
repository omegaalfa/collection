<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use Countable;
use Generator;
use IteratorAggregate;
use Traversable;

/**
 * Lazy evaluated sequence - operations are deferred until materialization
 * Perfect for large collections, pipelines with take/first, and infinite sequences
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 */
class LazySequence implements IteratorAggregate, Countable
{
    /**
     * @var list<array{type: string, fn?: callable, arg?: mixed}>
     */
    private readonly array $operations;

    /**
     * @var iterable<T>|null
     */
    private readonly ?iterable $source;

    /**
     * @param iterable<T>|null $source
     * @param list<array{type: string, fn?: callable, arg?: mixed}> $operations
     */
    private function __construct(?iterable $source = null, array $operations = [])
    {
        $this->source = $source;
        $this->operations = $operations;
    }

    /**
     * Create empty lazy sequence
     *
     * @return self<never>
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Create lazy sequence from values
     *
     * @template TValue
     * @param TValue ...$values
     * @return self<TValue>
     */
    public static function of(mixed ...$values): self
    {
        return new self($values);
    }

    /**
     * Create lazy range sequence
     *
     * @param int $start
     * @param int $end
     * @param int $step
     * @return self<int>
     */
    public static function range(int $start, int $end, int $step = 1): self
    {
        return new self(null, [['type' => 'range', 'arg' => [$start, $end, $step]]]);
    }

    /**
     * Lazy map - transformation deferred until iteration
     *
     * @template TNew
     * @param callable(T, int): TNew $fn
     * @return self<TNew>
     */
    public function map(callable $fn): self
    {
        return new self($this->source, [...$this->operations, ['type' => 'map', 'fn' => $fn]]);
    }

    /**
     * Lazy filter - filtering deferred until iteration
     *
     * @param callable(T, int): bool $fn
     * @return self<T>
     */
    public function filter(callable $fn): self
    {
        return new self($this->source, [...$this->operations, ['type' => 'filter', 'fn' => $fn]]);
    }

    /**
     * Lazy flat map
     *
     * @template TNew
     * @param callable(T, int): iterable<TNew> $fn
     * @return self<TNew>
     */
    public function flatMap(callable $fn): self
    {
        return new self($this->source, [...$this->operations, ['type' => 'flatMap', 'fn' => $fn]]);
    }

    /**
     * Take first N elements (lazy - stops iteration early!)
     *
     * @param int $limit
     * @return self<T>
     */
    public function take(int $limit): self
    {
        return new self($this->source, [...$this->operations, ['type' => 'take', 'arg' => $limit]]);
    }

    /**
     * Skip first N elements
     *
     * @param int $count
     * @return self<T>
     */
    public function skip(int $count): self
    {
        return new self($this->source, [...$this->operations, ['type' => 'skip', 'arg' => $count]]);
    }

    /**
     * Slice sequence
     *
     * @param int $offset
     * @param int|null $length
     * @return self<T>
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new self($this->source, [...$this->operations, ['type' => 'slice', 'arg' => [$offset, $length]]]);
    }

    /**
     * Remove duplicates (maintains insertion order)
     *
     * @return self<T>
     */
    public function unique(): self
    {
        return new self($this->source, [...$this->operations, ['type' => 'unique']]);
    }

    /**
     * Execute side effect for each element
     *
     * @param callable(T, int): void $fn
     * @return self<T>
     */
    public function each(callable $fn): self
    {
        return new self($this->source, [...$this->operations, ['type' => 'each', 'fn' => $fn]]);
    }

    /**
     * Chunk into groups
     *
     * @param int $size
     * @return self<Sequence<T>>
     */
    public function chunk(int $size): self
    {
        return new self($this->source, [...$this->operations, ['type' => 'chunk', 'arg' => $size]]);
    }

    /**
     * Get first element (lazy - stops after finding one!)
     *
     * @return T|null
     */
    public function first(): mixed
    {
        foreach ($this->getIterator() as $value) {
            return $value;
        }
        return null;
    }

    /**
     * Get lazy iterator - this is where magic happens!
     *
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return $this->buildPipeline();
    }

    /**
     * Build and execute lazy pipeline
     *
     * @return Generator<int, T>
     */
    private function buildPipeline(): Generator
    {
        // Get source generator
        $generator = $this->getSource();

        // Apply operations lazily
        $index = 0;
        $skipped = 0;
        $taken = 0;
        $seen = [];
        $takeLimit = null;
        $skipCount = 0;
        $sliceOffset = null;
        $sliceLength = null;
        $chunkSize = null;
        $currentChunk = [];

        foreach ($generator as $value) {
            // Apply transformations
            foreach ($this->operations as $op) {
                if ($value === null && $op['type'] !== 'skip' && $op['type'] !== 'take') {
                    continue 2; // Skip to next source item
                }

                switch ($op['type']) {
                    case 'map':
                        $value = $op['fn']($value, $index);
                        break;

                    case 'filter':
                        if (!$op['fn']($value, $index)) {
                            continue 3; // Skip to next source item
                        }
                        break;

                    case 'flatMap':
                        foreach ($op['fn']($value, $index) as $flatValue) {
                            yield $flatValue;
                        }
                        continue 3;

                    case 'skip':
                        $skipCount = $op['arg'];
                        if ($skipped < $skipCount) {
                            $skipped++;
                            continue 3;
                        }
                        break;

                    case 'take':
                        $takeLimit = $op['arg'];
                        if ($taken >= $takeLimit) {
                            return; // Stop iteration
                        }
                        break;

                    case 'slice':
                        [$sliceOffset, $sliceLength] = $op['arg'];
                        if ($index < $sliceOffset) {
                            $index++;
                            continue 3;
                        }
                        if ($sliceLength !== null && $index >= $sliceOffset + $sliceLength) {
                            return;
                        }
                        break;

                    case 'unique':
                        $hash = serialize($value);
                        if (in_array($hash, $seen, true)) {
                            continue 3;
                        }
                        $seen[] = $hash;
                        break;

                    case 'each':
                        $op['fn']($value, $index);
                        break;

                    case 'chunk':
                        $chunkSize = $op['arg'];
                        $currentChunk[] = $value;
                        if (count($currentChunk) === $chunkSize) {
                            yield Sequence::from($currentChunk);
                            $currentChunk = [];
                        }
                        continue 3;
                }
            }

            // Yield transformed value
            yield $value;
            $index++;
            $taken++;

            // Check take limit
            if ($takeLimit !== null && $taken >= $takeLimit) {
                return;
            }
        }

        // Yield remaining chunk
        if ($chunkSize !== null && !empty($currentChunk)) {
            yield Sequence::from($currentChunk);
        }
    }

    /**
     * Get source generator
     *
     * @return Generator<int, T>
     */
    private function getSource(): Generator
    {
        // Handle range specially
        foreach ($this->operations as $op) {
            if ($op['type'] === 'range') {
                [$start, $end, $step] = $op['arg'];
                if ($step > 0) {
                    for ($i = $start; $i <= $end; $i += $step) {
                        yield $i;
                    }
                } else {
                    for ($i = $start; $i >= $end; $i += $step) {
                        yield $i;
                    }
                }
                return;
            }
        }

        // Use provided source
        if ($this->source !== null) {
            $index = 0;
            foreach ($this->source as $value) {
                yield $index++ => $value;
            }
        }
    }

    /**
     * Create lazy sequence from iterable
     *
     * @template TValue
     * @param iterable<TValue> $iterable
     * @return self<TValue>
     */
    public static function from(iterable $iterable): self
    {
        return new self($iterable);
    }

    /**
     * Get last element (forces full evaluation)
     *
     * @return T|null
     */
    public function last(): mixed
    {
        $last = null;
        foreach ($this->getIterator() as $value) {
            $last = $value;
        }
        return $last;
    }

    /**
     * Check if contains value (lazy - stops when found!)
     *
     * @param T $value
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        foreach ($this->getIterator() as $item) {
            if ($item === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if any element matches predicate (lazy - short circuits!)
     *
     * @param callable(T, int): bool $fn
     * @return bool
     */
    public function any(callable $fn): bool
    {
        $index = 0;
        foreach ($this->getIterator() as $value) {
            if ($fn($value, $index++)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if all elements match predicate (lazy - short circuits!)
     *
     * @param callable(T, int): bool $fn
     * @return bool
     */
    public function all(callable $fn): bool
    {
        $index = 0;
        foreach ($this->getIterator() as $value) {
            if (!$fn($value, $index++)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get element at index (requires full iteration up to index)
     *
     * @param int $index
     * @return T|null
     */
    public function at(int $index): mixed
    {
        $current = 0;
        foreach ($this->getIterator() as $value) {
            if ($current === $index) {
                return $value;
            }
            $current++;
        }
        return null;
    }

    /**
     * Sum numeric values (forces evaluation)
     *
     * @return int|float
     */
    public function sum(): int|float
    {
        return $this->reduce(static fn($carry, $item) => $carry + (is_numeric($item) ? $item : 0), 0);
    }

    /**
     * Reduce to single value (forces evaluation!)
     *
     * @template TReduce
     * @param callable(TReduce, T, int): TReduce $fn
     * @param TReduce $initial
     * @return TReduce
     */
    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        $carry = $initial;
        $index = 0;
        foreach ($this->getIterator() as $value) {
            $carry = $fn($carry, $value, $index++);
        }
        return $carry;
    }

    /**
     * Average of numeric values (forces evaluation)
     *
     * @return float|null
     */
    public function avg(): ?float
    {
        $sum = 0;
        $count = 0;
        foreach ($this->getIterator() as $value) {
            if (is_numeric($value)) {
                $sum += $value;
                $count++;
            }
        }
        return $count > 0 ? $sum / $count : null;
    }

    /**
     * Minimum value (forces evaluation)
     *
     * @return T|null
     */
    public function min(): mixed
    {
        $min = null;
        foreach ($this->getIterator() as $value) {
            if ($min === null || $value < $min) {
                $min = $value;
            }
        }
        return $min;
    }

    /**
     * Maximum value (forces evaluation)
     *
     * @return T|null
     */
    public function max(): mixed
    {
        $max = null;
        foreach ($this->getIterator() as $value) {
            if ($max === null || $value > $max) {
                $max = $value;
            }
        }
        return $max;
    }

    /**
     * Count elements (forces evaluation!)
     *
     * @return int
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->getIterator() as $_) {
            $count++;
        }
        return $count;
    }

    /**
     * Check if empty (lazy - only checks first element!)
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        foreach ($this->getIterator() as $_) {
            return false;
        }
        return true;
    }

    /**
     * Join elements with separator (forces evaluation)
     *
     * @param string $separator
     * @return string
     */
    public function join(string $separator = ''): string
    {
        return implode($separator, $this->toArray());
    }

    /**
     * Convert to array (forces evaluation!)
     *
     * @return list<T>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }

    /**
     * Convert to eager Sequence (forces evaluation!)
     *
     * @return Sequence<T>
     */
    public function toEager(): Sequence
    {
        return Sequence::from($this->toArray());
    }

    /**
     * Convert to Map using key extractor
     *
     * @template TKey of array-key
     * @param callable(T, int): TKey $keyFn
     * @return LazyMap<TKey, T>
     */
    public function toMap(callable $keyFn): LazyMap
    {
        $map = [];
        $index = 0;
        foreach ($this->getIterator() as $value) {
            $key = $keyFn($value, $index++);
            $map[$key] = $value;
        }
        return LazyMap::from($map);
    }
}
