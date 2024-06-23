<?php

namespace Omegaalfa\Collection\Tests;

use PHPUnit\Framework\TestCase;
use Omegaalfa\Collection\Collection;


class CollectionTest extends TestCase
{
	public function testCount()
	{
		$collection = new Collection([1, 2, 3]);
		$this->assertEquals(3, $collection->count());
	}

	public function testMap()
	{
		$collection = new Collection([1, 2, 3]);
		$newCollection = $collection->map(fn($item) => $item * 2);
		$this->assertEquals([2, 4, 6], $newCollection->toArray());
	}

	public function testFilter()
	{
		$collection = new Collection([1, 2, 3, 4]);
		$newCollection = $collection->filter(fn($item) => $item % 2 == 0);
		$this->assertEquals([2, 4], $newCollection->toArray());
	}

	public function testEach()
	{
		$collection = new Collection([1, 2, 3]);
		$values = [];
		$collection->each(function($item) use (&$values) {
			$values[] = $item * 2;
		});
		$this->assertEquals([2, 4, 6], $values);
	}

	public function testSearchValueKey()
	{
		$array = [
			'first' => 'value1',
			'nested' => [
				'second' => 'value2',
			],
		];
		$collection = new Collection();
		$this->assertEquals('value2', $collection->searchValueKey($array, 'second'));
	}

	public function testToArray()
	{
		$collection = new Collection([1, 2, 3]);
		$this->assertEquals([1, 2, 3], $collection->toArray());
	}
}
