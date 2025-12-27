<?php

namespace Omegaalfa\Collection\Tests;

use PHPUnit\Framework\TestCase;
use Omegaalfa\Collection\Collection;

class CollectionEnhancedTest extends TestCase
{
	public function testFirstReturnsFirstElement(): void
	{
		$collection = new Collection([1, 2, 3]);
		$this->assertEquals(1, $collection->first());
	}

	public function testFirstReturnsNullOnEmpty(): void
	{
		$collection = new Collection([]);
		$this->assertNull($collection->first());
	}

	public function testLastReturnsLastElement(): void
	{
		$collection = new Collection([1, 2, 3]);
		$this->assertEquals(3, $collection->last());
	}

	public function testIsEmpty(): void
	{
		$collection = new Collection([]);
		$this->assertTrue($collection->isEmpty());
		$this->assertFalse($collection->isNotEmpty());
	}

	public function testIsNotEmpty(): void
	{
		$collection = new Collection([1]);
		$this->assertFalse($collection->isEmpty());
		$this->assertTrue($collection->isNotEmpty());
	}

	public function testReduce(): void
	{
		$collection = new Collection([1, 2, 3, 4]);
		$result = $collection->reduce(fn($carry, $item) => $carry + $item, 0);
		$this->assertEquals(10, $result);
	}

	public function testPluck(): void
	{
		$collection = new Collection([
			['id' => 1, 'name' => 'John'],
			['id' => 2, 'name' => 'Jane'],
		]);
		$names = $collection->pluck('name');
		$this->assertEquals(['John', 'Jane'], $names->toArray());
	}

	public function testKeys(): void
	{
		$collection = new Collection(['a' => 1, 'b' => 2]);
		$keys = $collection->keys();
		$this->assertEquals(['a', 'b'], $keys->toArray());
	}

	public function testValues(): void
	{
		$collection = new Collection(['a' => 1, 'b' => 2]);
		$values = $collection->values();
		$this->assertEquals([1, 2], $values->toArray());
	}

	public function testUnique(): void
	{
		$collection = new Collection([1, 2, 2, 3, 3, 3]);
		$unique = $collection->unique();
		$this->assertEquals([1, 2, 3], array_values($unique->toArray()));
	}

	public function testReverse(): void
	{
		$collection = new Collection([1, 2, 3]);
		$reversed = $collection->reverse();
		$this->assertEquals([3, 2, 1], array_values($reversed->toArray()));
	}

	public function testChunk(): void
	{
		$collection = new Collection([1, 2, 3, 4, 5]);
		$chunks = $collection->chunk(2);
		$this->assertEquals(3, $chunks->count());
		$this->assertEquals([1, 2], array_values($chunks->first()->toArray()));
	}

	public function testSum(): void
	{
		$collection = new Collection([1, 2, 3, 4]);
		$this->assertEquals(10, $collection->sum());
	}

	public function testAvg(): void
	{
		$collection = new Collection([1, 2, 3, 4]);
		$this->assertEquals(2.5, $collection->avg());
	}

	public function testMin(): void
	{
		$collection = new Collection([3, 1, 4, 2]);
		$this->assertEquals(1, $collection->min());
	}

	public function testMax(): void
	{
		$collection = new Collection([3, 1, 4, 2]);
		$this->assertEquals(4, $collection->max());
	}

	public function testSort(): void
	{
		$collection = new Collection([3, 1, 4, 2]);
		$sorted = $collection->sort(fn($a, $b) => $a <=> $b);
		$this->assertEquals([1, 2, 3, 4], array_values($sorted->toArray()));
	}

	public function testSlice(): void
	{
		$collection = new Collection([1, 2, 3, 4, 5]);
		$sliced = $collection->slice(1, 3);
		$this->assertEquals([2, 3, 4], array_values($sliced->toArray()));
	}

	public function testTake(): void
	{
		$collection = new Collection([1, 2, 3, 4, 5]);
		$taken = $collection->take(3);
		$this->assertEquals([1, 2, 3], array_values($taken->toArray()));
	}

	public function testContains(): void
	{
		$collection = new Collection([1, 2, 3]);
		$this->assertTrue($collection->contains(2));
		$this->assertFalse($collection->contains(4));
	}

	public function testArrayAccess(): void
	{
		$collection = new Collection(['a' => 1, 'b' => 2]);
		
		$this->assertTrue(isset($collection['a']));
		$this->assertEquals(1, $collection['a']);
		
		$collection['c'] = 3;
		$this->assertEquals(3, $collection['c']);
		
		unset($collection['a']);
		$this->assertFalse(isset($collection['a']));
	}

	public function testMapPreservesKeys(): void
	{
		$collection = new Collection(['a' => 1, 'b' => 2]);
		$mapped = $collection->map(fn($item) => $item * 2);
		$this->assertEquals(['a' => 2, 'b' => 4], $mapped->toArray());
	}

	public function testFilterPreservesKeys(): void
	{
		$collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
		$filtered = $collection->filter(fn($item) => $item > 1);
		$this->assertArrayHasKey('b', $filtered->toArray());
		$this->assertArrayHasKey('c', $filtered->toArray());
	}

	public function testCountable(): void
	{
		$collection = new Collection([1, 2, 3]);
		$this->assertCount(3, $collection);
	}

	public function testCurrent(): void
	{
		$collection = new Collection([1, 2, 3]);
		$this->assertEquals(1, $collection->current());
	}

	public function testAddAndRemove(): void
	{
		$collection = new Collection([1, 2, 3]);
		$collection->add(4);
		$this->assertEquals(4, $collection->count());
		
		$collection->remove(2);
		$this->assertEquals(3, $collection->count());
		$this->assertFalse($collection->contains(2));
	}

	public function testSortKeys(): void
	{
		$collection = new Collection(['c' => 3, 'a' => 1, 'b' => 2]);
		$sorted = $collection->sortKeys();
		$this->assertEquals(['a', 'b', 'c'], array_keys($sorted->toArray()));
	}

	public function testOffsetSetWithNullKey(): void
	{
		$collection = new Collection([1, 2, 3]);
		$collection[] = 4;
		$this->assertEquals(4, $collection->count());
		$this->assertTrue($collection->contains(4));
	}

	public function testSetAttribute(): void
	{
		$collection = new Collection(['a' => 1, 'b' => 2]);
		$collection->setAttribute('c', 3);
		$this->assertEquals(3, $collection->getAttribute('c'));
	}

	public function testGetAttributeReturnsNull(): void
	{
		$collection = new Collection(['a' => 1]);
		$this->assertNull($collection->getAttribute('nonexistent'));
	}

	public function testOffsetUnsetWithNonExistentKey(): void
	{
		$collection = new Collection(['a' => 1]);
		unset($collection['nonexistent']);
		$this->assertEquals(1, $collection->count());
	}

	public function testArrayAccessWithIterator(): void
	{
		$generator = function () {
			yield 'a' => 1;
			yield 'b' => 2;
		};
		$collection = new Collection($generator());
		$this->assertTrue(isset($collection['a']));
	}

	public function testLazy(): void
	{
		$collection = Collection::lazy(function () {
			yield 1;
			yield 2;
			yield 3;
		});
		$this->assertEquals([1, 2, 3], $collection->toArray());
	}

	public function testLazyRange(): void
	{
		$collection = Collection::lazyRange(1, 5);
		$this->assertEquals([1, 2, 3, 4, 5], $collection->toArray());
	}

	public function testLazyRangeWithStep(): void
	{
		$collection = Collection::lazyRange(0, 10, 2);
		$this->assertEquals([0, 2, 4, 6, 8, 10], $collection->toArray());
	}

	public function testLazyRangeWithNegativeStep(): void
	{
		$collection = Collection::lazyRange(10, 0, -2);
		$this->assertEquals([10, 8, 6, 4, 2, 0], $collection->toArray());
	}

	public function testIsLazy(): void
	{
		$lazyCollection = Collection::lazy(function () {
			yield 1;
		});
		$this->assertTrue($lazyCollection->isLazy());

		$arrayCollection = new Collection([1, 2, 3]);
		$this->assertFalse($arrayCollection->isLazy());
	}

	public function testMaterialize(): void
	{
		$lazyCollection = Collection::lazy(function () {
			yield 1;
			yield 2;
		});
		$materialized = $lazyCollection->materialize();
		$this->assertFalse($materialized->isLazy());
		$this->assertEquals([1, 2], $materialized->toArray());
	}

	public function testLazyMap(): void
	{
		$collection = new Collection([1, 2, 3]);
		$lazy = $collection->lazyMap(fn($x) => $x * 2);
		$this->assertTrue($lazy->isLazy());
		$this->assertEquals([2, 4, 6], $lazy->toArray());
	}

	public function testLazyFilter(): void
	{
		$collection = new Collection([1, 2, 3, 4]);
		$lazy = $collection->lazyFilter(fn($x) => $x > 2);
		$this->assertTrue($lazy->isLazy());
		$this->assertEquals([2 => 3, 3 => 4], $lazy->toArray());
	}

	public function testLazyChunk(): void
	{
		$collection = new Collection([1, 2, 3, 4, 5]);
		$chunks = $collection->lazyChunk(2);
		$result = [];
		foreach ($chunks as $chunk) {
			$result[] = $chunk->toArray();
		}
		$this->assertEquals([[0 => 1, 1 => 2], [2 => 3, 3 => 4], [4 => 5]], $result);
	}

	public function testLazyChunkThrowsExceptionForInvalidSize(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$collection = new Collection([1, 2, 3]);
		$collection->lazyChunk(0);
	}

	public function testLazyTake(): void
	{
		$collection = new Collection([1, 2, 3, 4, 5]);
		$lazy = $collection->lazyTake(3);
		$this->assertEquals([1, 2, 3], $lazy->toArray());
	}

	public function testLazyTakeWithNegativeLimit(): void
	{
		$collection = new Collection([1, 2, 3, 4, 5]);
		$lazy = $collection->lazyTake(-2);
		$this->assertCount(2, $lazy);
	}

	public function testLazyPipeline(): void
	{
		$collection = new Collection([1, 2, 3, 4, 5]);
		$result = $collection->lazyPipeline([
			['map', fn($x) => $x * 2],
			['filter', fn($x) => $x > 4],
			['take', 2]
		]);
		$this->assertEquals([2 => 6, 3 => 8], $result->toArray());
	}

	public function testLazyPipelineWithSkip(): void
	{
		$collection = new Collection([1, 2, 3, 4, 5]);
		$result = $collection->lazyPipeline([
			['skip', 2]
		]);
		$this->assertEquals([2 => 3, 3 => 4, 4 => 5], $result->toArray());
	}

	public function testLazyPipelineWithCallable(): void
	{
		$collection = new Collection([1, 2, 3, 4]);
		$result = $collection->lazyPipeline([
			fn($x) => $x * 2
		]);
		$this->assertEquals([2, 4, 6, 8], $result->toArray());
	}

	public function testLazyPipelineThrowsExceptionForInvalidOperation(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$collection = new Collection([1, 2, 3]);
		$collection->lazyPipeline([
			['invalid', fn($x) => $x]
		]);
	}

	public function testLazyPipelineThrowsExceptionForInvalidParameter(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$collection = new Collection([1, 2, 3]);
		$collection->lazyPipeline([
			['map', 'not_callable']
		]);
	}

	public function testLazyPipelineThrowsExceptionForInvalidTakeParameter(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$collection = new Collection([1, 2, 3]);
		$collection->lazyPipeline([
			['take', 'not_an_int']
		]);
	}

	public function testLazyPipelineThrowsExceptionForInvalidFormat(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$collection = new Collection([1, 2, 3]);
		$collection->lazyPipeline([
			'invalid_format'
		]);
	}
}
