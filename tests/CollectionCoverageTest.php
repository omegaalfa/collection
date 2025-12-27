<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Tests;

use Omegaalfa\Collection\Collection;
use Omegaalfa\Collection\LazyMap;
use Omegaalfa\Collection\Map;
use Omegaalfa\Collection\Sequence;
use PHPUnit\Framework\TestCase;

class CollectionCoverageTest extends TestCase
{
	// Testes para aumentar cobertura do CollectionArrayAccessTrait
	public function testOffsetExistsWithIterator(): void
	{
		$generator = function () {
			yield 'a' => 1;
			yield 'b' => 2;
		};
		$collection = new Collection($generator());
		// offsetExists with iterator converts to array, so test once
		$this->assertTrue(isset($collection['a']));
	}

	public function testGetAttributeWithIterator(): void
	{
		$generator = function () {
			yield 'a' => 1;
		};
		$collection = new Collection($generator());
		// getAttribute retorna null para iteradores que não são arrays
		$this->assertNull($collection->getAttribute('a'));
	}

	public function testAddWithIterator(): void
	{
		$generator = function () {
			yield 1;
			yield 2;
		};
		$collection = new Collection($generator());
		$collection->add(3);
		// Após add, o iterator deve ter sido convertido para array
		$this->assertEquals([1, 2], $collection->toArray());
	}

	public function testSetAttributeWithIterator(): void
	{
		$generator = function () {
			yield 'a' => 1;
		};
		$collection = new Collection($generator());
		// setAttribute não faz nada em iteradores
		$collection->setAttribute('b', 2);
		$this->assertEquals(['a' => 1], $collection->toArray());
	}

	// Testes para aumentar cobertura do CollectionTransformationsTrait
	public function testReverseWithArray(): void
	{
		$collection = new Collection([1, 2, 3]);
		$reversed = $collection->reverse();
		$this->assertEquals([2 => 3, 1 => 2, 0 => 1], $reversed->toArray());
	}

	public function testReverseWithIterator(): void
	{
		$generator = function () {
			yield 0 => 1;
			yield 1 => 2;
			yield 2 => 3;
		};
		$collection = new Collection($generator());
		$reversed = $collection->reverse();
		$this->assertEquals([2 => 3, 1 => 2, 0 => 1], $reversed->toArray());
	}

	public function testSliceWithLength(): void
	{
		$collection = new Collection([1, 2, 3, 4, 5]);
		$sliced = $collection->slice(1, 2);
		$this->assertEquals([1 => 2, 2 => 3], $sliced->toArray());
	}

	public function testSliceWithNegativeOffset(): void
	{
		$collection = new Collection([1, 2, 3, 4, 5]);
		$sliced = $collection->slice(-2);
		$this->assertEquals([3 => 4, 4 => 5], $sliced->toArray());
	}

	public function testPluckWithArrayValues(): void
	{
		$collection = new Collection([
			['name' => 'John', 'age' => 30],
			['name' => 'Jane', 'age' => 25]
		]);
		$names = $collection->pluck('name');
		$this->assertEquals(['John', 'Jane'], $names->toArray());
	}

	public function testPluckWithObjectProperties(): void
	{
		$obj1 = new \stdClass();
		$obj1->name = 'John';
		$obj2 = new \stdClass();
		$obj2->name = 'Jane';

		$collection = new Collection([$obj1, $obj2]);
		$names = $collection->pluck('name');
		$this->assertEquals(['John', 'Jane'], $names->toArray());
	}

	// Testes para aumentar cobertura do CollectionAggregatesTrait
	public function testAvgReturnsNullForEmpty(): void
	{
		$collection = new Collection([]);
		$this->assertNull($collection->avg());
	}

	public function testSumWithIterator(): void
	{
		$generator = function () {
			yield 1;
			yield 2;
			yield 3;
		};
		$collection = new Collection($generator());
		$this->assertEquals(6, $collection->sum());
	}

	public function testSumWithNonNumericValues(): void
	{
		$generator = function () {
			yield 1;
			yield 'not a number';
			yield 3;
		};
		$collection = new Collection($generator());
		$this->assertEquals(4, $collection->sum());
	}

	public function testMinReturnsNullForEmpty(): void
	{
		$collection = new Collection([]);
		$this->assertNull($collection->min());
	}

	public function testMaxReturnsNullForEmpty(): void
	{
		$collection = new Collection([]);
		$this->assertNull($collection->max());
	}

	// Testes para aumentar cobertura do LazyOperationsTrait
	public function testLazyPipelineWithCallableReturningFalse(): void
	{
		$collection = new Collection([1, 2, 3, 4]);
		$result = $collection->lazyPipeline([
			fn($x) => $x % 2 === 0 ? $x : false
		]);
		$this->assertEquals([1 => 2, 3 => 4], $result->toArray());
	}

	public function testLazyPipelineWithCallableReturningNull(): void
	{
		$collection = new Collection([1, 2, 3]);
		$result = $collection->lazyPipeline([
			fn($x) => null
		]);
		$this->assertEquals([1, 2, 3], $result->toArray());
	}

	public function testLazyPipelineWithCallableReturningTrue(): void
	{
		$collection = new Collection([1, 2, 3]);
		$result = $collection->lazyPipeline([
			fn($x) => true
		]);
		$this->assertEquals([1, 2, 3], $result->toArray());
	}

	// Testes para Collection
	public function testLastReturnsNullForEmpty(): void
	{
		$collection = new Collection([]);
		$this->assertNull($collection->last());
	}

	public function testContainsWithArray(): void
	{
		$collection = new Collection([1, 2, 3]);
		$this->assertTrue($collection->contains(2));
		$this->assertFalse($collection->contains(4));
	}

	public function testContainsWithIterator(): void
	{
		$generator = function () {
			yield 1;
			yield 2;
			yield 3;
		};
		$collection = new Collection($generator());
		$this->assertTrue($collection->contains(2));
		$this->assertFalse($collection->contains(4));
	}

	public function testCountWithCachedValue(): void
	{
		$collection = new Collection([1, 2, 3]);
		$count1 = $collection->count();
		$count2 = $collection->count(); // Should use cached value
		$this->assertEquals($count1, $count2);
		$this->assertEquals(3, $count2);
	}

	public function testToArrayWithCachedValue(): void
	{
		$collection = new Collection([1, 2, 3]);
		$array1 = $collection->toArray();
		$array2 = $collection->toArray(); // Should use cached value
		$this->assertEquals($array1, $array2);
	}

	public function testMaterializeWithCachedArray(): void
	{
		$collection = new Collection([1, 2, 3]);
		$collection->toArray(); // Cache the array
		$materialized = $collection->materialize();
		$this->assertFalse($materialized->isLazy());
		$this->assertEquals([1, 2, 3], $materialized->toArray());
	}

	// Testes adicionais para LazyMap
	public function testLazyMapPut(): void
	{
		$map = LazyMap::from(['a' => 1]);
		$newMap = $map->put('b', 2);
		$this->assertEquals(1, $newMap->get('a'));
		$this->assertEquals(2, $newMap->get('b'));
	}

	public function testLazyMapHas(): void
	{
		$map = LazyMap::from(['a' => 1]);
		$this->assertTrue($map->has('a'));
		$this->assertFalse($map->has('b'));
	}

	public function testLazyMapRemove(): void
	{
		$map = LazyMap::from(['a' => 1, 'b' => 2]);
		$newMap = $map->remove('a');
		$this->assertFalse($newMap->has('a'));
		$this->assertTrue($newMap->has('b'));
	}

	public function testLazyMapGetOrDefault(): void
	{
		$map = LazyMap::from(['a' => 1]);
		$this->assertEquals(1, $map->getOrDefault('a', 99));
		$this->assertEquals(99, $map->getOrDefault('b', 99));
	}

	// Testes adicionais para Map
	public function testMapPut(): void
	{
		$map = Map::from(['a' => 1]);
		$newMap = $map->put('b', 2);
		$this->assertEquals(2, $newMap->get('b'));
	}

	public function testMapHas(): void
	{
		$map = Map::from(['a' => 1]);
		$this->assertTrue($map->has('a'));
		$this->assertFalse($map->has('b'));
	}

	// Testes adicionais para Sequence
	public function testSequenceAppend(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$newSeq = $seq->append(4);
		$this->assertEquals([1, 2, 3, 4], $newSeq->toArray());
	}

	public function testSequencePrepend(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$newSeq = $seq->prepend(0);
		$this->assertEquals([0, 1, 2, 3], $newSeq->toArray());
	}

	public function testSequenceContains(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$this->assertTrue($seq->contains(2));
		$this->assertFalse($seq->contains(4));
	}

	public function testSequenceIndexOf(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$this->assertEquals(1, $seq->indexOf(2));
		$this->assertNull($seq->indexOf(4));
	}

	public function testSequenceReverse(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$reversed = $seq->reverse();
		$this->assertEquals([3, 2, 1], $reversed->toArray());
	}
}
