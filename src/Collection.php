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
	 * @var Iterator<TKey, TValue>|array<TKey, TValue>
	 */
	private Iterator|array $items;


	/**
	 * @param  Iterator<TKey, TValue>|array<TKey, TValue>  $items
	 */
	public function __construct(Iterator|array $items = [])
	{
		$this->items = $items;
	}


	/**
	 * @return Traversable<TKey, TValue>
	 */
	public function getIterator(): Traversable
	{
		if(!$this->items instanceof Traversable) {
			$this->items = $this->arrayToGenerator($this->items);
		}

		return $this->items;
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
		$newItems = [];
		foreach($this->getIterator() as $item) {
			$newItems[] = $callback($item);
		}

		return new self($newItems);
	}

	/**
	 * @param  callable(TValue): bool  $callback
	 *
	 * @return Collection<TKey, TValue>
	 */
	public function filter(callable $callback): Collection
	{
		$newItems = [];
		foreach($this->getIterator() as $item) {
			if($callback($item)) {
				$newItems[] = $item;
			}
		}

		return new self($newItems);
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
