<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Contract;

use Countable;
use IteratorAggregate;

/**
 * Map: Key-value dictionary with string/int keys
 * 
 * Based on Larry Garfield's "Never Use Arrays" philosophy
 * @see https://presentations.garfieldtech.com/slides-never-use-arrays/
 * 
 * @template K of array-key
 * @template V
 * @extends IteratorAggregate<K, V>
 */
interface MapInterface extends IteratorAggregate, Countable
{
	/**
	 * Get value by key
	 * 
	 * @param K $key
	 * @return V
	 * @throws \OutOfBoundsException
	 */
	public function get(mixed $key): mixed;

	/**
	 * Get value by key or default
	 * 
	 * @param K $key
	 * @param V $default
	 * @return V
	 */
	public function getOrDefault(mixed $key, mixed $default): mixed;

	/**
	 * Check if key exists
	 * 
	 * @param K $key
	 * @return bool
	 */
	public function has(mixed $key): bool;

	/**
	 * Get all keys as sequence
	 * 
	 * @return SequenceInterface<K>
	 */
	public function keys(): SequenceInterface;

	/**
	 * Get all values as sequence
	 * 
	 * @return SequenceInterface<V>
	 */
	public function values(): SequenceInterface;

	/**
	 * Put key-value pair (returns new instance)
	 * 
	 * @param K $key
	 * @param V $value
	 * @return static
	 */
	public function put(mixed $key, mixed $value): static;

	/**
	 * Put multiple pairs (returns new instance)
	 * 
	 * @param iterable<K, V> $pairs
	 * @return static
	 */
	public function putAll(iterable $pairs): static;

	/**
	 * Remove key (returns new instance)
	 * 
	 * @param K $key
	 * @return static
	 */
	public function remove(mixed $key): static;

	/**
	 * Map values (returns new map)
	 * 
	 * @template U
	 * @param callable(K, V): U $mapper
	 * @return MapInterface<K, U>
	 */
	public function mapValues(callable $mapper): MapInterface;

	/**
	 * Filter entries (returns new instance)
	 * 
	 * @param callable(K, V): bool $predicate
	 * @return static
	 */
	public function filter(callable $predicate): static;

	/**
	 * Merge with another map (returns new instance)
	 * 
	 * @param MapInterface<K, V> $other
	 * @return static
	 */
	public function merge(MapInterface $other): static;

	/**
	 * Check if map is empty
	 * 
	 * @return bool
	 */
	public function isEmpty(): bool;

	/**
	 * Convert to array
	 * 
	 * @return array<K, V>
	 */
	public function toArray(): array;
}
