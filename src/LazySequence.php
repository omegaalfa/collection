<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use Countable;
use Generator;
use IteratorAggregate;
use Omegaalfa\Collection\Util\RewindableIterator;
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
        /** @var self<TNew> */
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
        /** @var self<TNew> */
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
        /** @var self<Sequence<T>> */
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
     * Build and execute lazy pipeline.
     *
     * Cada operação vira um estágio de Generator encadeado: `take` interrompe
     * o estágio dele (e, portanto, o consumo da fonte), `filter`/`flatMap`
     * avançam os índices corretamente e valores `null` nunca são descartados.
     *
     * @return Generator<int, T>
     */
    private function buildPipeline(): Generator
    {
        $stream = $this->getSource();

        foreach ($this->operations as $op) {
            $stream = $this->applyOperation($op, $stream);
        }

        yield from $stream;
    }

    /**
     * @param array{type: string, fn?: callable, arg?: mixed} $op
     * @param Generator<int, mixed> $input
     * @return Generator<int, mixed>
     */
    private function applyOperation(array $op, Generator $input): Generator
    {
        return match ($op['type']) {
            'range' => $input, // tratado em getSource()
            'map' => $this->stageMap($input, $op['fn']),
            'filter' => $this->stageFilter($input, $op['fn']),
            'flatMap' => $this->stageFlatMap($input, $op['fn']),
            'take' => $this->stageTake($input, $op['arg']),
            'skip' => $this->stageSkip($input, $op['arg']),
            'slice' => $this->stageSlice($input, $op['arg'][0], $op['arg'][1]),
            'unique' => $this->stageUnique($input),
            'each' => $this->stageEach($input, $op['fn']),
            'chunk' => $this->stageChunk($input, $op['arg']),
            default => throw new \InvalidArgumentException("Unknown operation: {$op['type']}"),
        };
    }

    /**
     * @param Generator<int, mixed> $input
     * @param callable $fn
     * @return Generator<int, mixed>
     */
    private function stageMap(Generator $input, callable $fn): Generator
    {
        $index = 0;
        foreach ($input as $value) {
            yield $fn($value, $index++);
        }
    }

    /**
     * @param Generator<int, mixed> $input
     * @param callable $fn
     * @return Generator<int, mixed>
     */
    private function stageFilter(Generator $input, callable $fn): Generator
    {
        $index = 0;
        foreach ($input as $value) {
            if ($fn($value, $index++)) {
                yield $value;
            }
        }
    }

    /**
     * @param Generator<int, mixed> $input
     * @param callable $fn
     * @return Generator<int, mixed>
     */
    private function stageFlatMap(Generator $input, callable $fn): Generator
    {
        $index = 0;
        foreach ($input as $value) {
            foreach ($fn($value, $index++) as $flatValue) {
                yield $flatValue;
            }
        }
    }

    /**
     * @param Generator<int, mixed> $input
     * @param int $limit
     * @return Generator<int, mixed>
     */
    private function stageTake(Generator $input, int $limit): Generator
    {
        if ($limit <= 0) {
            return;
        }

        $count = 0;
        foreach ($input as $value) {
            yield $value;
            if (++$count >= $limit) {
                return;
            }
        }
    }

    /**
     * @param Generator<int, mixed> $input
     * @param int $count
     * @return Generator<int, mixed>
     */
    private function stageSkip(Generator $input, int $count): Generator
    {
        $skipped = 0;
        foreach ($input as $value) {
            if ($skipped < $count) {
                $skipped++;
                continue;
            }
            yield $value;
        }
    }

    /**
     * @param Generator<int, mixed> $input
     * @param int $offset
     * @param int|null $length
     * @return Generator<int, mixed>
     */
    private function stageSlice(Generator $input, int $offset, ?int $length): Generator
    {
        if ($offset < 0 || ($length !== null && $length < 0)) {
            // Semantics of array_slice para valores negativos exigem a sequência completa
            $items = iterator_to_array($input, false);

            return (static function () use ($items, $offset, $length): Generator {
                yield from array_slice($items, $offset, $length);
            })();
        }

        return (static function () use ($input, $offset, $length): Generator {
            $index = 0;
            $yielded = 0;
            foreach ($input as $value) {
                if ($index++ < $offset) {
                    continue;
                }
                if ($length !== null && $yielded >= $length) {
                    return;
                }
                $yielded++;
                yield $value;
            }
        })();
    }

    /**
     * @param Generator<int, mixed> $input
     * @return Generator<int, mixed>
     */
    private function stageUnique(Generator $input): Generator
    {
        $seen = [];
        foreach ($input as $value) {
            $hash = self::valueHash($value);
            if (isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;
            yield $value;
        }
    }

    /**
     * @param Generator<int, mixed> $input
     * @param callable $fn
     * @return Generator<int, mixed>
     */
    private function stageEach(Generator $input, callable $fn): Generator
    {
        $index = 0;
        foreach ($input as $value) {
            $fn($value, $index++);
            yield $value;
        }
    }

    /**
     * @param Generator<int, mixed> $input
     * @param int $size
     * @return Generator<int, Sequence<mixed>>
     */
    private function stageChunk(Generator $input, int $size): Generator
    {
        $chunk = [];
        foreach ($input as $value) {
            $chunk[] = $value;
            if (count($chunk) === $size) {
                yield Sequence::from($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            yield Sequence::from($chunk);
        }
    }

    /**
     * Hash estável para unique(), seguro para Closures/resources.
     *
     * @param mixed $value
     * @return string
     */
    private static function valueHash(mixed $value): string
    {
        try {
            return serialize($value);
        } catch (\Throwable) {
            return match (true) {
                is_object($value) => 'obj:' . get_class($value) . ':' . spl_object_id($value),
                is_array($value) => 'arr:' . implode('|', array_map(self::valueHash(...), array_values($value))),
                is_resource($value) => 'res:' . (int) $value,
                default => get_debug_type($value) . ':' . var_export($value, true),
            };
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
                if ($step === 0) {
                    throw new \InvalidArgumentException('Step must not be zero');
                }
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
        if ($iterable instanceof Generator) {
            /** @var iterable<TValue> $reiterable */
            $reiterable = new RewindableIterator($iterable);
            return new self($reiterable);
        }

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
