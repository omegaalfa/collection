<?php

declare(strict_types = 1);

namespace Omegaalfa\Collection;

use Generator;
use Iterator;
use IteratorAggregate;
use Traversable;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class Collection implements IteratorAggregate
{

	/**
	 * @var Iterator<TKey, TValue>|ArrayIterator|array<TKey, TValue>
	 */
	protected Iterator|array|ArrayIterator $collection;


	/**
	 * @param  Iterator<TKey, TValue>|array<TKey, TValue>  $collection
	 */
	public function __construct(Iterator|array $collection = [])
	{
		$this->collection = $collection;
	}


	/**
	 * @param  Iterator<TKey, TValue>|array<TKey, TValue>  $collection
	 *
	 * @return void
	 */
	public function addIterator(Iterator|array $collection = []): void
	{
		$this->collection = $collection;
	}


	/**
	 * @return void
	 */
	public function current(): void
	{
		if($this->collection instanceof Iterator) {
			$this->collection->current();
		}
	}

	/**
	 * @param  mixed  $key
	 * @param  mixed  $value
	 *
	 * @return void
	 */
	public function setAttribute(mixed $key, mixed $value): void
	{
		if(is_array($this->collection)) {
			$this->collection[$key] = $value;
		}
	}

	/**
	 * @param  mixed  $key
	 *
	 * @return mixed
	 */
	public function getAttribute(mixed $key): mixed
	{
		if(is_array($this->collection)) {
			return $this->collection[$key];
		}

		return null;
	}

	/**
	 * @return Traversable<TKey, TValue>
	 */
	public function getIterator(): Traversable
	{
		if(!$this->collection instanceof Traversable) {
			$this->collection = $this->arrayToGenerator($this->collection);
		}

		return $this->collection;
	}

	/**
	 * @template TNewValue
	 *
	 * @param  callable(TValue): TNewValue  $callback
	 *
	 * @return Collection<TKey, TNewValue>
	 */
	public function map(callable $callback): Collection
	{
		$newcollection = [];
		foreach($this->getIterator() as $item) {
			$newcollection[] = $callback($item);
		}

		return new self($newcollection);
	}

	/**
	 * @param  callable(TValue): bool  $callback
	 *
	 * @return Collection<TKey, TValue>
	 */
	public function filter(callable $callback): Collection
	{
		$newcollection = [];
		foreach($this->getIterator() as $item) {
			if($callback($item)) {
				$newcollection[] = $item;
			}
		}

		return new self($newcollection);
	}


	/**
	 * @template TNewValue
	 *
	 * @param  callable(TValue): TNewValue  $callback
	 *
	 * @return Collection<TKey, TNewValue>
	 */
	public function each(callable $callback): Collection
	{
		foreach($this->getIterator() as $item) {
			$callback($item);
		}

		return $this;
	}

	/**
	 * @return int
	 */
	public function count(): int
	{
		return iterator_count($this->getIterator());
	}


	/**
	 * @param  list<mixed>  $array
	 * @param  string       $key
	 *
	 * @return mixed
	 */
	public function searchValueKey(array $array, string $key): mixed
	{
		if(array_key_exists($key, $array)) {
			return $array[$key];
		}

		foreach($this->arrayToGenerator($array) as $value) {
			if(is_array($value) && $result = $this->searchValueKey($value, $key)) {
				return $result;
			}
		}

		return null;
	}


	/**
	 * @param  mixed  $item
	 *
	 * @return void
	 */
	public function add(mixed $item): void
	{
		if($this->collection instanceof Iterator) {
			$this->collection = iterator_to_array($this->collection, false);
		}

		$this->collection[] = $item;
	}

	/**
	 * @param  mixed  $item
	 *
	 * @return void
	 */
	public function remove(mixed $item): void
	{
		if($this->collection instanceof Iterator) {
			$this->collection = iterator_to_array($this->collection, false);
		}

		$this->collection = array_filter($this->collection, static function($currentItem) use ($item) {
			return $currentItem !== $item;
		});
	}


	/**
	 * @param  list<mixed>  $array
	 *
	 * @return Generator
	 */
	public function arrayToGenerator(array $array): Generator
	{
		yield from $array;
	}

	/**
	 * @return  list<mixed>
	 */
	public function toArray(): array
	{
		return iterator_to_array($this, false);
	}
}
