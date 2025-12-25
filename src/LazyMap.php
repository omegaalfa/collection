<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use Countable;
use IteratorAggregate;
use Traversable;
use ArrayIterator;

/**
 * Lazy evaluated map - values are only computed when accessed
 * Perfect for expensive computations, database lazy loading, and large datasets
 * 
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class LazyMap implements IteratorAggregate, Countable
{
	/**
	 * @var array<TKey, TValue|\Closure(): TValue>
	 */
	private readonly array $items;

	/**
	 * @var array<TKey, TValue> Materialized values cache
	 */
	private array $materialized = [];

	/**
	 * @param array<TKey, TValue|\Closure(): TValue> $items
	 */
	private function __construct(array $items = [])
	{
		$this->items = $items;
	}

	/**
	 * Create empty lazy map
	 * 
	 * @return self<never, never>
	 */
	public static function empty(): self
	{
		return new self([]);
	}

	/**
	 * Create lazy map from array
	 * Values can be actual values or closures that compute them lazily
	 * 
	 * @template K of array-key
	 * @template V
	 * @param array<K, V|\Closure(): V> $items
	 * @return self<K, V>
	 */
	public static function from(array $items): self
	{
		return new self($items);
	}

	/**
	 * Create lazy map from key-value pairs
	 * 
	 * @template K of array-key
	 * @template V
	 * @param array{K, V} ...$pairs
	 * @return self<K, V>
	 */
	public static function of(array ...$pairs): self
	{
		$items = [];
		foreach ($pairs as [$key, $value]) {
			$items[$key] = $value;
		}
		return new self($items);
	}

	/**
	 * Get value by key (lazy - only computes if closure!)
	 * 
	 * @param TKey $key
	 * @return TValue|null
	 */
	public function get(mixed $key): mixed
	{
		if (!array_key_exists($key, $this->items)) {
			return null;
		}

		// Return cached if already materialized
		if (array_key_exists($key, $this->materialized)) {
			return $this->materialized[$key];
		}

		$value = $this->items[$key];

		// Execute closure if lazy value
		if ($value instanceof \Closure) {
			$value = $value();
			$this->materialized[$key] = $value;
		}

		return $value;
	}

	/**
	 * Get value or default
	 * 
	 * @param TKey $key
	 * @param TValue $default
	 * @return TValue
	 */
	public function getOrDefault(mixed $key, mixed $default): mixed
	{
		return $this->has($key) ? $this->get($key) : $default;
	}

	/**
	 * Check if key exists
	 * 
	 * @param TKey $key
	 * @return bool
	 */
	public function has(mixed $key): bool
	{
		return array_key_exists($key, $this->items);
	}

	/**
	 * Put key-value pair (returns new instance - immutable!)
	 * 
	 * @param TKey $key
	 * @param TValue|\Closure(): TValue $value
	 * @return self<TKey, TValue>
	 */
	public function put(mixed $key, mixed $value): self
	{
		return new self([...$this->items, $key => $value]);
	}

	/**
	 * Put multiple key-value pairs
	 * 
	 * @param array<TKey, TValue|\Closure(): TValue> $items
	 * @return self<TKey, TValue>
	 */
	public function putAll(array $items): self
	{
		return new self([...$this->items, ...$items]);
	}

	/**
	 * Remove key
	 * 
	 * @param TKey $key
	 * @return self<TKey, TValue>
	 */
	public function remove(mixed $key): self
	{
		$items = $this->items;
		unset($items[$key]);
		return new self($items);
	}

	/**
	 * Merge with another map (other map wins on conflicts)
	 * 
	 * @param self<TKey, TValue> $other
	 * @return self<TKey, TValue>
	 */
	public function merge(self $other): self
	{
		return new self([...$this->items, ...$other->items]);
	}

	/**
	 * Map both keys and values
	 * 
	 * @template TNewKey of array-key
	 * @template TNewValue
	 * @param callable(TKey, TValue): array{TNewKey, TNewValue} $fn
	 * @return self<TNewKey, TNewValue>
	 */
	public function map(callable $fn): self
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			$materializedValue = $this->get($key);
			[$newKey, $newValue] = $fn($key, $materializedValue);
			$result[$newKey] = $newValue;
		}
		return new self($result);
	}

	/**
	 * Map values only (lazy - preserves closures!)
	 * 
	 * @template TNewValue
	 * @param callable(TKey, TValue): TNewValue $fn
	 * @return self<TKey, TNewValue>
	 */
	public function mapValues(callable $fn): self
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			// Wrap transformation in closure if value is already lazy
			if ($value instanceof \Closure) {
				$result[$key] = fn() => $fn($key, $value());
			} else {
				$result[$key] = $fn($key, $value);
			}
		}
		return new self($result);
	}

	/**
	 * Map keys only
	 * 
	 * @template TNewKey of array-key
	 * @param callable(TKey, TValue): TNewKey $fn
	 * @return self<TNewKey, TValue>
	 */
	public function mapKeys(callable $fn): self
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			$materializedValue = $this->get($key);
			$newKey = $fn($key, $materializedValue);
			$result[$newKey] = $value;
		}
		return new self($result);
	}

	/**
	 * Filter entries (materializes all values!)
	 * 
	 * @param callable(TKey, TValue): bool $fn
	 * @return self<TKey, TValue>
	 */
	public function filter(callable $fn): self
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			$materializedValue = $this->get($key);
			if ($fn($key, $materializedValue)) {
				$result[$key] = $value;
			}
		}
		return new self($result);
	}

	/**
	 * Filter by keys only (no materialization!)
	 * 
	 * @param callable(TKey): bool $fn
	 * @return self<TKey, TValue>
	 */
	public function filterKeys(callable $fn): self
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			if ($fn($key)) {
				$result[$key] = $value;
			}
		}
		return new self($result);
	}

	/**
	 * Filter by values (materializes all!)
	 * 
	 * @param callable(TValue): bool $fn
	 * @return self<TKey, TValue>
	 */
	public function filterValues(callable $fn): self
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			$materializedValue = $this->get($key);
			if ($fn($materializedValue)) {
				$result[$key] = $value;
			}
		}
		return new self($result);
	}

	/**
	 * Reduce to single value (materializes all!)
	 * 
	 * @template TReduce
	 * @param callable(TReduce, TKey, TValue): TReduce $fn
	 * @param TReduce $initial
	 * @return TReduce
	 */
	public function reduce(callable $fn, mixed $initial = null): mixed
	{
		$carry = $initial;
		foreach ($this->items as $key => $value) {
			$materializedValue = $this->get($key);
			$carry = $fn($carry, $key, $materializedValue);
		}
		return $carry;
	}

	/**
	 * Execute side effect for each entry (materializes all!)
	 * 
	 * @param callable(TKey, TValue): void $fn
	 * @return self<TKey, TValue>
	 */
	public function each(callable $fn): self
	{
		foreach ($this->items as $key => $value) {
			$materializedValue = $this->get($key);
			$fn($key, $materializedValue);
		}
		return $this;
	}

	/**
	 * Get all keys (no materialization!)
	 * 
	 * @return Sequence<TKey>
	 */
	public function keys(): Sequence
	{
		return Sequence::from(array_keys($this->items));
	}

	/**
	 * Get all values (materializes all!)
	 * 
	 * @return Sequence<TValue>
	 */
	public function values(): Sequence
	{
		$values = [];
		foreach ($this->items as $key => $value) {
			$values[] = $this->get($key);
		}
		return Sequence::from($values);
	}

	/**
	 * Sort by values (materializes all!)
	 * 
	 * @param callable(TValue, TValue): int $fn
	 * @return self<TKey, TValue>
	 */
	public function sortValues(callable $fn): self
	{
		$materialized = $this->toArray();
		uasort($materialized, $fn);
		return new self($materialized);
	}

	/**
	 * Sort by keys
	 * 
	 * @param callable(TKey, TKey): int $fn
	 * @return self<TKey, TValue>
	 */
	public function sortKeys(callable $fn): self
	{
		$items = $this->items;
		uksort($items, $fn);
		return new self($items);
	}

	/**
	 * Count entries (no materialization!)
	 * 
	 * @return int
	 */
	public function count(): int
	{
		return count($this->items);
	}

	/**
	 * Check if empty (no materialization!)
	 * 
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	/**
	 * Check if not empty (no materialization!)
	 * 
	 * @return bool
	 */
	public function isNotEmpty(): bool
	{
		return !$this->isEmpty();
	}

	/**
	 * Convert to array (materializes all!)
	 * 
	 * @return array<TKey, TValue>
	 */
	public function toArray(): array
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			$result[$key] = $this->get($key);
		}
		return $result;
	}

	/**
	 * Convert to eager Map (materializes all!)
	 * 
	 * @return Map<TKey, TValue>
	 */
	public function toEager(): Map
	{
		return Map::from($this->toArray());
	}

	/**
	 * Convert values to sequence (materializes all!)
	 * 
	 * @return LazySequence<TValue>
	 */
	public function toSequence(): LazySequence
	{
		$values = [];
		foreach ($this->items as $key => $value) {
			$values[] = $this->get($key);
		}
		return LazySequence::from($values);
	}

	/**
	 * Get iterator (materializes on iteration!)
	 * 
	 * @return Traversable<TKey, TValue>
	 */
	public function getIterator(): Traversable
	{
		foreach ($this->items as $key => $value) {
			yield $key => $this->get($key);
		}
	}

	/**
	 * Force materialization of all lazy values
	 * Useful for warming up cache
	 * 
	 * @return self<TKey, TValue>
	 */
	public function materializeAll(): self
	{
		foreach ($this->items as $key => $value) {
			$this->get($key); // Forces materialization
		}
		return $this;
	}

	/**
	 * Get count of materialized values (for debugging)
	 * 
	 * @return int
	 */
	public function getMaterializedCount(): int
	{
		return count($this->materialized);
	}

	/**
	 * Check if key is materialized (for debugging)
	 * 
	 * @param TKey $key
	 * @return bool
	 */
	public function isMaterialized(mixed $key): bool
	{
		return array_key_exists($key, $this->materialized);
	}
}
