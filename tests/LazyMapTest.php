<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Tests;

use Omegaalfa\Collection\LazyMap;
use Omegaalfa\Collection\Map;
use PHPUnit\Framework\TestCase;

class LazyMapTest extends TestCase
{
	public function testEmptyMap(): void
	{
		$map = LazyMap::empty();
		$this->assertTrue($map->isEmpty());
		$this->assertSame(0, $map->count());
	}

	public function testFromArray(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => 2]);
		$this->assertSame(1, $map->get('a'));
		$this->assertSame(2, $map->get('b'));
	}

	public function testOf(): void
	{
		$map = LazyMap::of(['name', 'John'], ['age', 30]);
		$this->assertSame('John', $map->get('name'));
		$this->assertSame(30, $map->get('age'));
	}

	public function testLazyValueNotComputedUntilAccess(): void
	{
		$executed = false;
		$map = LazyMap::from([
			'expensive' => function() use (&$executed) {
				$executed = true;
				return 42;
			}
		]);

		// Not executed yet
		$this->assertFalse($executed);
		$this->assertSame(0, $map->getMaterializedCount());

		// Now it executes
		$value = $map->get('expensive');
		$this->assertTrue($executed);
		$this->assertSame(42, $value);
		$this->assertSame(1, $map->getMaterializedCount());
	}

	public function testLazyValueComputedOnlyOnce(): void
	{
		$callCount = 0;
		$map = LazyMap::from([
			'value' => function () use (&$callCount) {
				$callCount++;
				return 100;
			}
		]);

		// First access
		$value1 = $map->get('value');
		$this->assertSame(1, $callCount);
		$this->assertSame(100, $value1);

		// Second access - should use cached value
		$value2 = $map->get('value');
		$this->assertSame(1, $callCount); // Still 1!
		$this->assertSame(100, $value2);
	}

	public function testGetNonExistentKey(): void
	{
		$map = LazyMap::from(['a' => 1]);
		$this->assertNull($map->get('b'));
	}

	public function testGetOrDefault(): void
	{
		$map = LazyMap::from(['a' => 1]);
		$this->assertSame(1, $map->getOrDefault('a', 999));
		$this->assertSame(999, $map->getOrDefault('b', 999));
	}

	public function testHas(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => fn() => 2]);
		$this->assertTrue($map->has('a'));
		$this->assertTrue($map->has('b'));
		$this->assertFalse($map->has('c'));

		// Has doesn't materialize lazy values
		$this->assertSame(0, $map->getMaterializedCount());
	}

	public function testPut(): void
	{
		$map = LazyMap::from(['a' => 1]);
		$newMap = $map->put('b', 2);

		// Original unchanged
		$this->assertFalse($map->has('b'));

		// New map has both
		$this->assertSame(1, $newMap->get('a'));
		$this->assertSame(2, $newMap->get('b'));
	}

	public function testPutAll(): void
	{
		$map = LazyMap::from(['a' => 1]);
		$newMap = $map->putAll(['b' => 2, 'c' => 3]);

		$this->assertSame(1, $newMap->get('a'));
		$this->assertSame(2, $newMap->get('b'));
		$this->assertSame(3, $newMap->get('c'));
	}

	public function testRemove(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => 2]);
		$newMap = $map->remove('a');

		// Original unchanged
		$this->assertTrue($map->has('a'));

		// New map doesn't have 'a'
		$this->assertFalse($newMap->has('a'));
		$this->assertTrue($newMap->has('b'));
	}

	public function testMerge(): void
	{
		$map1 = LazyMap::from(['a' => 1, 'b' => 2]);
		$map2 = LazyMap::from(['b' => 20, 'c' => 30]);
		$merged = $map1->merge($map2);

		$this->assertSame(1, $merged->get('a'));
		$this->assertSame(20, $merged->get('b')); // map2 wins
		$this->assertSame(30, $merged->get('c'));
	}

	public function testMapValues(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => 2]);
		$doubled = $map->mapValues(fn($k, $v) => $v * 2);

		$this->assertSame(2, $doubled->get('a'));
		$this->assertSame(4, $doubled->get('b'));
	}

	public function testMapValuesPreservesLaziness(): void
	{
		$executed = false;
		$map = LazyMap::from([
			'lazy' => function() use (&$executed) {
				$executed = true;
				return 10;
			}
		]);

		$mapped = $map->mapValues(fn($k, $v) => $v * 2);

		// Still not executed
		$this->assertFalse($executed);

		// Now executes both closure and mapping
		$value = $mapped->get('lazy');
		$this->assertTrue($executed);
		$this->assertSame(20, $value);
	}

	public function testMapKeys(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => 2]);
		$uppercased = $map->mapKeys(fn($k, $v) => strtoupper($k));

		$this->assertSame(1, $uppercased->get('A'));
		$this->assertSame(2, $uppercased->get('B'));
	}

	public function testFilter(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
		$even = $map->filter(fn($k, $v) => $v % 2 === 0);

		$this->assertFalse($even->has('a'));
		$this->assertTrue($even->has('b'));
		$this->assertFalse($even->has('c'));
		$this->assertTrue($even->has('d'));
	}

	public function testFilterKeys(): void
	{
		$map = LazyMap::from([
			'a' => fn() => 1,
			'b' => fn() => 2,
			'c' => fn() => 3
		]);

		$filtered = $map->filterKeys(fn($k) => in_array($k, ['a', 'c']));

		// Keys filtered without materializing values!
		$this->assertSame(0, $map->getMaterializedCount());
		$this->assertTrue($filtered->has('a'));
		$this->assertFalse($filtered->has('b'));
		$this->assertTrue($filtered->has('c'));
	}

	public function testFilterValues(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$greaterThan1 = $map->filterValues(fn($v) => $v > 1);

		$this->assertFalse($greaterThan1->has('a'));
		$this->assertTrue($greaterThan1->has('b'));
		$this->assertTrue($greaterThan1->has('c'));
	}

	public function testReduce(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$sum = $map->reduce(fn($carry, $k, $v) => $carry + $v, 0);

		$this->assertSame(6, $sum);
	}

	public function testEach(): void
	{
		$collected = [];
		$map = LazyMap::from(['a' => 1, 'b' => 2]);
		$map->each(function ($k, $v) use (&$collected) {
			$collected[$k] = $v * 2;
		});

		$this->assertSame(['a' => 2, 'b' => 4], $collected);
	}

	public function testKeys(): void
	{
		$map = LazyMap::from([
			'a' => fn() => 1,
			'b' => fn() => 2,
			'c' => fn() => 3
		]);

		$keys = $map->keys();

		// Keys extracted without materializing!
		$this->assertSame(0, $map->getMaterializedCount());
		$this->assertSame(['a', 'b', 'c'], $keys->toArray());
	}

	public function testValues(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$values = $map->values();

		$this->assertSame([1, 2, 3], $values->toArray());
	}

	public function testSortValues(): void
	{
		$map = LazyMap::from(['a' => 3, 'b' => 1, 'c' => 2]);
		$sorted = $map->sortValues(fn($a, $b) => $a <=> $b);

		$this->assertSame(['b' => 1, 'c' => 2, 'a' => 3], $sorted->toArray());
	}

	public function testSortKeys(): void
	{
		$map = LazyMap::from(['c' => 1, 'a' => 2, 'b' => 3]);
		$sorted = $map->sortKeys(fn($a, $b) => $a <=> $b);

		$this->assertSame(['a' => 2, 'b' => 3, 'c' => 1], $sorted->toArray());
	}

	public function testCount(): void
	{
		$map = LazyMap::from([
			'a' => fn() => 1,
			'b' => fn() => 2,
			'c' => fn() => 3
		]);

		// Count without materializing!
		$this->assertSame(3, $map->count());
		$this->assertSame(0, $map->getMaterializedCount());
	}

	public function testIsEmpty(): void
	{
		$this->assertTrue(LazyMap::empty()->isEmpty());
		$this->assertFalse(LazyMap::from(['a' => 1])->isEmpty());
	}

	public function testToArray(): void
	{
		$map = LazyMap::from([
			'a' => fn() => 1,
			'b' => 2,
			'c' => fn() => 3
		]);

		$array = $map->toArray();

		$this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $array);
		$this->assertSame(2, $map->getMaterializedCount()); // a and c were lazy
	}

	public function testToEager(): void
	{
		$lazy = LazyMap::from(['a' => 1, 'b' => 2]);
		$eager = $lazy->toEager();

		$this->assertInstanceOf(Map::class, $eager);
		$this->assertSame(['a' => 1, 'b' => 2], $eager->toArray());
	}

	public function testIteration(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => 2, 'c' => 3]);
		$collected = [];

		foreach ($map as $key => $value) {
			$collected[$key] = $value;
		}

		$this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $collected);
	}

	public function testMaterializeAll(): void
	{
		$map = LazyMap::from([
			'a' => fn() => 1,
			'b' => fn() => 2,
			'c' => fn() => 3
		]);

		$this->assertSame(0, $map->getMaterializedCount());

		$map->materializeAll();

		$this->assertSame(3, $map->getMaterializedCount());
	}

	public function testIsMaterialized(): void
	{
		$map = LazyMap::from([
			'eager' => 1,
			'lazy' => fn() => 2
		]);

		$this->assertFalse($map->isMaterialized('eager'));
		$this->assertFalse($map->isMaterialized('lazy'));

		$map->get('lazy');

		$this->assertFalse($map->isMaterialized('eager')); // Was never lazy
		$this->assertTrue($map->isMaterialized('lazy'));
	}

	public function testLazyLoadingScenario(): void
	{
		// Simulate expensive database queries
		$queryCount = 0;

		$users = LazyMap::from([
			'john' => function() use (&$queryCount) {
				return ['id' => 1, 'name' => 'John', 'queries' => ++$queryCount];
			},
			'jane' => function() use (&$queryCount) {
				return ['id' => 2, 'name' => 'Jane', 'queries' => ++$queryCount];
			},
			'bob' => function() use (&$queryCount) {
				return ['id' => 3, 'name' => 'Bob', 'queries' => ++$queryCount];
			}
		]);

		// No queries yet
		$this->assertSame(0, $queryCount);

		// Only load john
		$john = $users->get('john');
		$this->assertSame(1, $queryCount); // Only 1 query!
		$this->assertSame('John', $john['name']);

		// Load jane
		$jane = $users->get('jane');
		$this->assertSame(2, $queryCount); // Now 2 queries

		// Bob never loaded = saved 1 query!
		$this->assertSame(2, $queryCount);
	}

	public function testPerformanceBenefit(): void
	{
		// Create map with 1000 expensive computations
		$items = [];
		for ($i = 0; $i < 1000; $i++) {
			$items["key$i"] = fn() => md5((string)$i); // Expensive
		}

		$start = microtime(true);
		$map = LazyMap::from($items);
		$creationTime = microtime(true) - $start;

		// Creation should be instant (no computation yet)
		$this->assertLessThan(0.01, $creationTime); // < 10ms

		// Access only 10 items
		$start = microtime(true);
		for ($i = 0; $i < 10; $i++) {
			$map->get("key$i");
		}
		$accessTime = microtime(true) - $start;

		// Only 10 materialized
		$this->assertSame(10, $map->getMaterializedCount());

		// Fast because only 10 computed
		$this->assertLessThan(0.05, $accessTime); // < 50ms
	}
}
