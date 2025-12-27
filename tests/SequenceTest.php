<?php

namespace Omegaalfa\Collection\Tests;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Omegaalfa\Collection\Sequence;

class SequenceTest extends TestCase
{
	public function testEmpty(): void
	{
		$seq = Sequence::empty();
		$this->assertTrue($seq->isEmpty());
		$this->assertEquals(0, $seq->count());
	}

	public function testOf(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$this->assertEquals([1, 2, 3], $seq->toArray());
	}

	public function testFrom(): void
	{
		$seq = Sequence::from([1, 2, 3]);
		$this->assertEquals([1, 2, 3], $seq->toArray());
	}

	public function testRange(): void
	{
		$seq = Sequence::range(1, 5);
		$this->assertEquals([1, 2, 3, 4, 5], $seq->toArray());
	}

	public function testAt(): void
	{
		$seq = Sequence::of(10, 20, 30);
		$this->assertEquals(10, $seq->at(0));
		$this->assertEquals(20, $seq->at(1));
		$this->assertEquals(30, $seq->at(2));
	}

	public function testAtOutOfBounds(): void
	{
		$this->expectException(OutOfBoundsException::class);
		$seq = Sequence::of(1, 2, 3);
		$seq->at(10);
	}

	public function testFirst(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$this->assertEquals(1, $seq->first());

		$empty = Sequence::empty();
		$this->assertNull($empty->first());
	}

	public function testLast(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$this->assertEquals(3, $seq->last());

		$empty = Sequence::empty();
		$this->assertNull($empty->last());
	}

	public function testContains(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$this->assertTrue($seq->contains(2));
		$this->assertFalse($seq->contains(10));
	}

	public function testIndexOf(): void
	{
		$seq = Sequence::of('a', 'b', 'c');
		$this->assertEquals(1, $seq->indexOf('b'));
		$this->assertNull($seq->indexOf('z'));
	}

	public function testAppend(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$newSeq = $seq->append(4);

		$this->assertEquals([1, 2, 3], $seq->toArray());
		$this->assertEquals([1, 2, 3, 4], $newSeq->toArray());
	}

	public function testPrepend(): void
	{
		$seq = Sequence::of(2, 3);
		$newSeq = $seq->prepend(1);

		$this->assertEquals([2, 3], $seq->toArray());
		$this->assertEquals([1, 2, 3], $newSeq->toArray());
	}

	public function testInsert(): void
	{
		$seq = Sequence::of(1, 3);
		$newSeq = $seq->insert(1, 2);

		$this->assertEquals([1, 3], $seq->toArray());
		$this->assertEquals([1, 2, 3], $newSeq->toArray());
	}

	public function testRemove(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$newSeq = $seq->remove(1);

		$this->assertEquals([1, 2, 3], $seq->toArray());
		$this->assertEquals([1, 3], $newSeq->toArray());
	}

	public function testSlice(): void
	{
		$seq = Sequence::of(1, 2, 3, 4, 5);
		$sliced = $seq->slice(1, 3);

		$this->assertEquals([2, 3, 4], $sliced->toArray());
	}

	public function testReverse(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$reversed = $seq->reverse();

		$this->assertEquals([3, 2, 1], $reversed->toArray());
	}

	public function testSort(): void
	{
		$seq = Sequence::of(3, 1, 4, 2);
		$sorted = $seq->sort(fn($a, $b) => $a <=> $b);

		$this->assertEquals([1, 2, 3, 4], $sorted->toArray());
	}

	public function testMap(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$mapped = $seq->map(fn($x) => $x * 2);

		$this->assertEquals([2, 4, 6], $mapped->toArray());
	}

	public function testFilter(): void
	{
		$seq = Sequence::of(1, 2, 3, 4, 5);
		$filtered = $seq->filter(fn($x) => $x % 2 === 0);

		$this->assertEquals([2, 4], $filtered->toArray());
	}

	public function testReduce(): void
	{
		$seq = Sequence::of(1, 2, 3, 4);
		$sum = $seq->reduce(fn($acc, $x) => $acc + $x, 0);

		$this->assertEquals(10, $sum);
	}

	public function testFlatMap(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$flattened = $seq->flatMap(fn($x) => [$x, $x * 10]);

		$this->assertEquals([1, 10, 2, 20, 3, 30], $flattened->toArray());
	}

	public function testTake(): void
	{
		$seq = Sequence::of(1, 2, 3, 4, 5);
		$taken = $seq->take(3);

		$this->assertEquals([1, 2, 3], $taken->toArray());
	}

	public function testSkip(): void
	{
		$seq = Sequence::of(1, 2, 3, 4, 5);
		$skipped = $seq->skip(2);

		$this->assertEquals([3, 4, 5], $skipped->toArray());
	}

	public function testUnique(): void
	{
		$seq = Sequence::of(1, 2, 2, 3, 3, 3);
		$unique = $seq->unique();

		$this->assertEquals([1, 2, 3], $unique->toArray());
	}

	public function testChunk(): void
	{
		$seq = Sequence::of(1, 2, 3, 4, 5);
		$chunks = $seq->chunk(2);

		$this->assertEquals(3, $chunks->count());
		$this->assertEquals([1, 2], $chunks->at(0)->toArray());
		$this->assertEquals([3, 4], $chunks->at(1)->toArray());
		$this->assertEquals([5], $chunks->at(2)->toArray());
	}

	public function testSum(): void
	{
		$seq = Sequence::of(1, 2, 3, 4);
		$this->assertEquals(10, $seq->sum());
	}

	public function testAvg(): void
	{
		$seq = Sequence::of(1, 2, 3, 4);
		$this->assertEquals(2.5, $seq->avg());
	}

	public function testMin(): void
	{
		$seq = Sequence::of(3, 1, 4, 2);
		$this->assertEquals(1, $seq->min());
	}

	public function testMax(): void
	{
		$seq = Sequence::of(3, 1, 4, 2);
		$this->assertEquals(4, $seq->max());
	}

	public function testIteration(): void
	{
		$seq = Sequence::of(1, 2, 3);
		$result = [];

		foreach ($seq as $item) {
			$result[] = $item;
		}

		$this->assertEquals([1, 2, 3], $result);
	}

	public function testImmutability(): void
	{
		$original = Sequence::of(1, 2, 3);
		$modified = $original->append(4);

		$this->assertEquals([1, 2, 3], $original->toArray());
		$this->assertEquals([1, 2, 3, 4], $modified->toArray());
	}

	public function testJoin(): void
	{
		$seq = Sequence::of('a', 'b', 'c');
		$this->assertEquals('a, b, c', $seq->join(', '));
	}

	public function testToMap(): void
	{
		$seq = Sequence::of('apple', 'banana', 'cherry');
		$map = $seq->toMap(fn($fruit, $index) => [$index, strtoupper($fruit)]);

		$this->assertEquals('APPLE', $map->get(0));
		$this->assertEquals('BANANA', $map->get(1));
		$this->assertEquals('CHERRY', $map->get(2));
	}
}
