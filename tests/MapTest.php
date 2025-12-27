<?php

namespace Omegaalfa\Collection\Tests;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Omegaalfa\Collection\Map;
use Omegaalfa\Collection\Sequence;

class MapTest extends TestCase
{
	public function testEmpty(): void
	{
		$map = Map::empty();
		$this->assertTrue($map->isEmpty());
		$this->assertEquals(0, $map->count());
	}

	public function testFrom(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2]);
		$this->assertEquals(['a' => 1, 'b' => 2], $map->toArray());
	}

	public function testOf(): void
	{
		$map = Map::of('a', 1, 'b', 2, 'c', 3);
		$this->assertEquals(1, $map->get('a'));
		$this->assertEquals(2, $map->get('b'));
		$this->assertEquals(3, $map->get('c'));
	}

	public function testGet(): void
	{
		$map = Map::from(['name' => 'John', 'age' => 30]);
		$this->assertEquals('John', $map->get('name'));
		$this->assertEquals(30, $map->get('age'));
	}

	public function testGetNotFound(): void
	{
		$this->expectException(OutOfBoundsException::class);
		$map = Map::from(['a' => 1]);
		$map->get('z');
	}

	public function testGetOrDefault(): void
	{
		$map = Map::from(['a' => 1]);
		$this->assertEquals(1, $map->getOrDefault('a', 999));
		$this->assertEquals(999, $map->getOrDefault('z', 999));
	}

	public function testHas(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2]);
		$this->assertTrue($map->has('a'));
		$this->assertFalse($map->has('z'));
	}

	public function testKeys(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$keys = $map->keys();

		$this->assertInstanceOf(Sequence::class, $keys);
		$this->assertEquals(['a', 'b', 'c'], $keys->toArray());
	}

	public function testValues(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$values = $map->values();

		$this->assertInstanceOf(Sequence::class, $values);
		$this->assertEquals([1, 2, 3], $values->toArray());
	}

	public function testPut(): void
	{
		$map = Map::from(['a' => 1]);
		$newMap = $map->put('b', 2);

		$this->assertEquals(['a' => 1], $map->toArray());
		$this->assertEquals(['a' => 1, 'b' => 2], $newMap->toArray());
	}

	public function testPutOverwrite(): void
	{
		$map = Map::from(['a' => 1]);
		$newMap = $map->put('a', 100);

		$this->assertEquals(1, $map->get('a'));
		$this->assertEquals(100, $newMap->get('a'));
	}

	public function testPutAll(): void
	{
		$map = Map::from(['a' => 1]);
		$newMap = $map->putAll(['b' => 2, 'c' => 3]);

		$this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $newMap->toArray());
	}

	public function testRemove(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$newMap = $map->remove('b');

		$this->assertTrue($map->has('b'));
		$this->assertFalse($newMap->has('b'));
		$this->assertEquals(['a' => 1, 'c' => 3], $newMap->toArray());
	}

	public function testMap(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2]);
		$newMap = $map->map(fn($key, $value) => [strtoupper($key), $value * 10]);

		$this->assertEquals(['A' => 10, 'B' => 20], $newMap->toArray());
	}

	public function testMapValues(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2]);
		$newMap = $map->mapValues(fn($key, $value) => $value * 2);

		$this->assertEquals(['a' => 2, 'b' => 4], $newMap->toArray());
	}

	public function testMapKeys(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2]);
		$newMap = $map->mapKeys(fn($key) => strtoupper($key));

		$this->assertEquals(['A' => 1, 'B' => 2], $newMap->toArray());
	}

	public function testFilter(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
		$filtered = $map->filter(fn($key, $value) => $value % 2 === 0);

		$this->assertEquals(['b' => 2, 'd' => 4], $filtered->toArray());
	}

	public function testFilterKeys(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$filtered = $map->filterKeys(fn($key) => $key !== 'b');

		$this->assertEquals(['a' => 1, 'c' => 3], $filtered->toArray());
	}

	public function testFilterValues(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$filtered = $map->filterValues(fn($value) => $value > 1);

		$this->assertEquals(['b' => 2, 'c' => 3], $filtered->toArray());
	}

	public function testMerge(): void
	{
		$map1 = Map::from(['a' => 1, 'b' => 2]);
		$map2 = Map::from(['c' => 3, 'd' => 4]);
		$merged = $map1->merge($map2);

		$this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $merged->toArray());
	}

	public function testMergeOverwrite(): void
	{
		$map1 = Map::from(['a' => 1, 'b' => 2]);
		$map2 = Map::from(['b' => 200, 'c' => 3]);
		$merged = $map1->merge($map2);

		$this->assertEquals(200, $merged->get('b'));
	}

	public function testReduce(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$sum = $map->reduce(fn($carry, $key, $value) => $carry + $value, 0);

		$this->assertEquals(6, $sum);
	}

	public function testSortValues(): void
	{
		$map = Map::from(['a' => 3, 'b' => 1, 'c' => 2]);
		$sorted = $map->sortValues(fn($a, $b) => $a <=> $b);

		$this->assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $sorted->toArray());
	}

	public function testSortKeys(): void
	{
		$map = Map::from(['c' => 3, 'a' => 1, 'b' => 2]);
		$sorted = $map->sortKeys();

		$this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $sorted->toArray());
	}

	public function testIteration(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$result = [];

		foreach ($map as $key => $value) {
			$result[$key] = $value;
		}

		$this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $result);
	}

	public function testImmutability(): void
	{
		$original = Map::from(['a' => 1]);
		$modified = $original->put('b', 2);

		$this->assertFalse($original->has('b'));
		$this->assertTrue($modified->has('b'));
	}

	public function testToSequence(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2]);
		$seq = $map->toSequence();

		$this->assertInstanceOf(Sequence::class, $seq);
		$this->assertEquals([['a', 1], ['b', 2]], $seq->toArray());
	}

	public function testEach(): void
	{
		$map = Map::from(['a' => 1, 'b' => 2]);
		$result = [];

		$map->each(function($key, $value) use (&$result) {
			$result[$key] = $value * 2;
		});

		$this->assertEquals(['a' => 2, 'b' => 4], $result);
	}
}
