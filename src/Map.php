<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use ArrayIterator;
use Omegaalfa\Collection\Contract\MapInterface;
use Omegaalfa\Collection\Contract\SequenceInterface;
use OutOfBoundsException;
use Traversable;

/**
 * Map: Immutable key-value dictionary
 * 
 * Based on Larry Garfield's "Never Use Arrays" philosophy
 * @see https://presentations.garfieldtech.com/slides-never-use-arrays/
 * 
 * @template K of array-key
 * @template V
 * @implements MapInterface<K, V>
 */
final class Map implements MapInterface
{
	/**
	 * @var array<K, V>
	 */
	private readonly array $items;

	/**
	 * @param array<K, V> $items
	 */
	private function __construct(array $items)
	{
		$this->items = $items;
	}

	/**
	 * Create empty map
	 * 
	 * @return self<K, V>
	 */
	public static function empty(): self
	{
		return new self([]);
	}

	/**
	 * Create from array or iterable
	 * 
	 * @template K2 of array-key
	 * @template V2
	 * @param iterable<K2, V2> $items
	 * @return self<K2, V2>
	 */
	public static function from(iterable $items): self
	{
		if ($items instanceof self) {
			return $items;
		}

		return new self(is_array($items) ? $items : iterator_to_array($items, true));
	}

	/**
	 * Create from key-value pairs
	 * 
	 * @template K2 of array-key
	 * @template V2
	 * @param K2 $k1
	 * @param V2 $v1
	 * @param mixed ...$rest
	 * @return self<K2, V2>
	 */
	public static function of(mixed $k1, mixed $v1, mixed ...$rest): self
	{
		$items = [$k1 => $v1];

		for ($i = 0; $i < count($rest); $i += 2) {
			if (!isset($rest[$i + 1])) {
				throw new \InvalidArgumentException('Map::of requires key-value pairs');
			}
			$items[$rest[$i]] = $rest[$i + 1];
		}

		return new self($items);
	}

	/**
	 * @inheritDoc
	 */
	public function get(mixed $key): mixed
	{
		if (!array_key_exists($key, $this->items)) {
			throw new OutOfBoundsException("Key '{$key}' not found");
		}

		return $this->items[$key];
	}

	/**
	 * @inheritDoc
	 */
	public function getOrDefault(mixed $key, mixed $default): mixed
	{
		return $this->items[$key] ?? $default;
	}

	/**
	 * @inheritDoc
	 */
	public function has(mixed $key): bool
	{
		return array_key_exists($key, $this->items);
	}

	/**
	 * @inheritDoc
	 */
	public function keys(): SequenceInterface
	{
		return Sequence::from(array_keys($this->items));
	}

	/**
	 * @inheritDoc
	 */
	public function values(): SequenceInterface
	{
		return Sequence::from(array_values($this->items));
	}

	/**
	 * @inheritDoc
	 */
	public function put(mixed $key, mixed $value): static
	{
		$items = $this->items;
		$items[$key] = $value;

		return new self($items);
	}

	/**
	 * @inheritDoc
	 */
	public function putAll(iterable $pairs): static
	{
		$items = $this->items;

		foreach ($pairs as $key => $value) {
			$items[$key] = $value;
		}

		return new self($items);
	}

	/**
	 * @inheritDoc
	 */
	public function remove(mixed $key): static
	{
		if (!array_key_exists($key, $this->items)) {
			return $this;
		}

		$items = $this->items;
		unset($items[$key]);

		return new self($items);
	}

	/**
	 * Map over entries
	 * 
	 * @template K2 of array-key
	 * @template V2
	 * @param callable(K, V): array{K2, V2} $mapper
	 * @return self<K2, V2>
	 */
	public function map(callable $mapper): self
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			[$newKey, $newValue] = $mapper($key, $value);
			$result[$newKey] = $newValue;
		}

		return new self($result);
	}

	/**
	 * @inheritDoc
	 */
	public function mapValues(callable $mapper): MapInterface
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			$result[$key] = $mapper($key, $value);
		}

		return new self($result);
	}

	/**
	 * Map keys
	 * 
	 * @template K2 of array-key
	 * @param callable(K): K2 $mapper
	 * @return self<K2, V>
	 */
	public function mapKeys(callable $mapper): self
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			$result[$mapper($key)] = $value;
		}

		return new self($result);
	}

	/**
	 * @inheritDoc
	 */
	public function filter(callable $predicate): static
	{
		$result = [];
		foreach ($this->items as $key => $value) {
			if ($predicate($key, $value)) {
				$result[$key] = $value;
			}
		}

		return new self($result);
	}

	/**
	 * Filter by keys
	 * 
	 * @param callable(K): bool $predicate
	 * @return static
	 */
	public function filterKeys(callable $predicate): static
	{
		return $this->filter(fn($key, $value) => $predicate($key));
	}

	/**
	 * Filter by values
	 * 
	 * @param callable(V): bool $predicate
	 * @return static
	 */
	public function filterValues(callable $predicate): static
	{
		return $this->filter(fn($key, $value) => $predicate($value));
	}

	/**
	 * @inheritDoc
	 */
	public function merge(MapInterface $other): static
	{
		return new self([...$this->items, ...$other->toArray()]);
	}

	/**
	 * Apply function to each entry
	 * 
	 * @param callable(K, V): void $action
	 * @return $this
	 */
	public function each(callable $action): self
	{
		foreach ($this->items as $key => $value) {
			$action($key, $value);
		}

		return $this;
	}

	/**
	 * Reduce to single value
	 * 
	 * @template R
	 * @param callable(R, K, V): R $reducer
	 * @param R $initial
	 * @return R
	 */
	public function reduce(callable $reducer, mixed $initial): mixed
	{
		$carry = $initial;
		foreach ($this->items as $key => $value) {
			$carry = $reducer($carry, $key, $value);
		}

		return $carry;
	}

	/**
	 * Sort by values
	 * 
	 * @param callable(V, V): int $comparator
	 * @return static
	 */
	public function sortValues(callable $comparator): static
	{
		$items = $this->items;
		uasort($items, $comparator);

		return new self($items);
	}

	/**
	 * Sort by keys
	 * 
	 * @return static
	 */
	public function sortKeys(): static
	{
		$items = $this->items;
		ksort($items);

		return new self($items);
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
	 * Convert to Sequence of pairs
	 * 
	 * @return Sequence<array{K, V}>
	 */
	public function toSequence(): Sequence
	{
		$pairs = [];
		foreach ($this->items as $key => $value) {
			$pairs[] = [$key, $value];
		}

		return Sequence::from($pairs);
	}

	/**
	 * Convert to lazy map for deferred value computation
	 * Useful for expensive computations or database lazy loading
	 * 
	 * @return LazyMap<K, V>
	 */
	public function toLazy(): LazyMap
	{
		return LazyMap::from($this->items);
	}

	/**
	 * @inheritDoc
	 */
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->items);
	}
}
