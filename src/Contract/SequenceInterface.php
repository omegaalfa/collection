<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Contract;

use Countable;
use IteratorAggregate;

/**
 * Sequence: Ordered list of values with integer indices
 * 
 * Based on Larry Garfield's "Never Use Arrays" philosophy
 * @see https://presentations.garfieldtech.com/slides-never-use-arrays/
 * 
 * @template T
 * @extends IteratorAggregate<int, T>
 */
interface SequenceInterface extends IteratorAggregate, Countable
{
	/**
	 * Access element at index
	 * 
	 * @param int $index
	 * @return T
	 * @throws \OutOfBoundsException
	 */
	public function at(int $index): mixed;

	/**
	 * Get first element
	 * 
	 * @return T|null
	 */
	public function first(): mixed;

	/**
	 * Get last element
	 * 
	 * @return T|null
	 */
	public function last(): mixed;

	/**
	 * Check if sequence contains value
	 * 
	 * @param T $value
	 * @return bool
	 */
	public function contains(mixed $value): bool;

	/**
	 * Find index of value
	 * 
	 * @param T $value
	 * @return int|null
	 */
	public function indexOf(mixed $value): ?int;

	/**
	 * Append value to end (returns new instance)
	 * 
	 * @param T $value
	 * @return static
	 */
	public function append(mixed $value): static;

	/**
	 * Prepend value to beginning (returns new instance)
	 * 
	 * @param T $value
	 * @return static
	 */
	public function prepend(mixed $value): static;

	/**
	 * Insert value at index (returns new instance)
	 * 
	 * @param int $index
	 * @param T $value
	 * @return static
	 * @throws \OutOfBoundsException
	 */
	public function insert(int $index, mixed $value): static;

	/**
	 * Remove element at index (returns new instance)
	 * 
	 * @param int $index
	 * @return static
	 * @throws \OutOfBoundsException
	 */
	public function remove(int $index): static;

	/**
	 * Extract slice (returns new instance)
	 * 
	 * @param int $offset
	 * @param int|null $length
	 * @return static
	 */
	public function slice(int $offset, ?int $length = null): static;

	/**
	 * Reverse order (returns new instance)
	 * 
	 * @return static
	 */
	public function reverse(): static;

	/**
	 * Sort with comparator (returns new instance)
	 * 
	 * @param callable(T, T): int $comparator
	 * @return static
	 */
	public function sort(callable $comparator): static;

	/**
	 * Map values (returns new sequence)
	 * 
	 * @template U
	 * @param callable(T, int): U $mapper
	 * @return SequenceInterface<U>
	 */
	public function map(callable $mapper): SequenceInterface;

	/**
	 * Filter values (returns new instance)
	 * 
	 * @param callable(T, int): bool $predicate
	 * @return static
	 */
	public function filter(callable $predicate): static;

	/**
	 * Reduce to single value
	 * 
	 * @template R
	 * @param callable(R, T, int): R $reducer
	 * @param R $initial
	 * @return R
	 */
	public function reduce(callable $reducer, mixed $initial): mixed;

	/**
	 * Check if sequence is empty
	 * 
	 * @return bool
	 */
	public function isEmpty(): bool;

	/**
	 * Convert to array
	 * 
	 * @return list<T>
	 */
	public function toArray(): array;
}
