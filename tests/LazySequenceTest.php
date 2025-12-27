<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Tests;

use Omegaalfa\Collection\LazySequence;
use Omegaalfa\Collection\Sequence;
use PHPUnit\Framework\TestCase;

class LazySequenceTest extends TestCase
{
	public function testEmptySequence(): void
	{
		$seq = LazySequence::empty();
		$this->assertTrue($seq->isEmpty());
		$this->assertSame(0, $seq->count());
	}

	public function testOfCreatesSequence(): void
	{
		$seq = LazySequence::of(1, 2, 3);
		$this->assertSame([1, 2, 3], $seq->toArray());
	}

	public function testFromIterable(): void
	{
		$seq = LazySequence::from([1, 2, 3, 4, 5]);
		$this->assertSame([1, 2, 3, 4, 5], $seq->toArray());
	}

	public function testRange(): void
	{
		$seq = LazySequence::range(1, 5);
		$this->assertSame([1, 2, 3, 4, 5], $seq->toArray());
	}

	public function testRangeWithStep(): void
	{
		$seq = LazySequence::range(0, 10, 2);
		$this->assertSame([0, 2, 4, 6, 8, 10], $seq->toArray());
	}

	public function testLazyMapDoesNotExecuteImmediately(): void
	{
		$executed = false;
		$seq = LazySequence::of(1, 2, 3)->map(function ($x) use (&$executed) {
			$executed = true;
			return $x * 2;
		});

		// Not executed yet
		$this->assertFalse($executed);

		// Now it executes
		$seq->toArray();
		$this->assertTrue($executed);
	}

	public function testLazyMapTransformation(): void
	{
		$seq = LazySequence::of(1, 2, 3, 4)->map(fn($x) => $x * 2);
		$this->assertSame([2, 4, 6, 8], $seq->toArray());
	}

	public function testLazyFilter(): void
	{
		$seq = LazySequence::of(1, 2, 3, 4, 5, 6)->filter(fn($x) => $x % 2 === 0);
		$this->assertSame([2, 4, 6], $seq->toArray());
	}

	public function testLazyPipeline(): void
	{
		$seq = LazySequence::range(1, 10)
			->filter(fn($x) => $x % 2 === 0)
			->map(fn($x) => $x * $x);

		$this->assertSame([4, 16, 36, 64, 100], $seq->toArray());
	}

	public function testTakeStopsEarlyExecution(): void
	{
		$callCount = 0;
		$seq = LazySequence::range(1, 1000000)
			->map(function ($x) use (&$callCount) {
				$callCount++;
				return $x * 2;
			})
			->take(5);

		$result = $seq->toArray();

		// Only executed 5 times, not 1 million!
		$this->assertSame(5, $callCount);
		$this->assertSame([2, 4, 6, 8, 10], $result);
	}

	public function testSkip(): void
	{
		$seq = LazySequence::of(1, 2, 3, 4, 5)->skip(2);
		$this->assertSame([3, 4, 5], $seq->toArray());
	}

	public function testSlice(): void
	{
		$seq = LazySequence::range(0, 10)->slice(2, 5);
		$this->assertSame([2, 3, 4, 5, 6], $seq->toArray());
	}

	public function testUnique(): void
	{
		$seq = LazySequence::of(1, 2, 2, 3, 3, 3, 4)->unique();
		$this->assertSame([1, 2, 3, 4], $seq->toArray());
	}

	public function testFlatMap(): void
	{
		$seq = LazySequence::of(1, 2, 3)->flatMap(fn($x) => [$x, $x * 10]);
		$this->assertSame([1, 10, 2, 20, 3, 30], $seq->toArray());
	}

	public function testEach(): void
	{
		$collected = [];
		LazySequence::of(1, 2, 3)
			->each(function ($x) use (&$collected) {
				$collected[] = $x * 2;
			})
			->toArray();

		$this->assertSame([2, 4, 6], $collected);
	}

	public function testChunk(): void
	{
		$seq = LazySequence::range(1, 9)->chunk(3);
		$chunks = $seq->toArray();

		$this->assertCount(3, $chunks);
		$this->assertSame([1, 2, 3], $chunks[0]->toArray());
		$this->assertSame([4, 5, 6], $chunks[1]->toArray());
		$this->assertSame([7, 8, 9], $chunks[2]->toArray());
	}

	public function testReduce(): void
	{
		$sum = LazySequence::of(1, 2, 3, 4, 5)->reduce(fn($carry, $x) => $carry + $x, 0);
		$this->assertSame(15, $sum);
	}

	public function testFirst(): void
	{
		$first = LazySequence::of(10, 20, 30)->first();
		$this->assertSame(10, $first);
	}

	public function testFirstOnEmpty(): void
	{
		$first = LazySequence::empty()->first();
		$this->assertNull($first);
	}

	public function testFirstStopsEarly(): void
	{
		$callCount = 0;
		$first = LazySequence::range(1, 1000000)
			->map(function ($x) use (&$callCount) {
				$callCount++;
				return $x;
			})
			->first();

		// Only called once!
		$this->assertSame(1, $callCount);
		$this->assertSame(1, $first);
	}

	public function testLast(): void
	{
		$last = LazySequence::of(10, 20, 30)->last();
		$this->assertSame(30, $last);
	}

	public function testContainsTrue(): void
	{
		$contains = LazySequence::of(1, 2, 3, 4, 5)->contains(3);
		$this->assertTrue($contains);
	}

	public function testContainsFalse(): void
	{
		$contains = LazySequence::of(1, 2, 3)->contains(10);
		$this->assertFalse($contains);
	}

	public function testContainsStopsEarly(): void
	{
		$callCount = 0;
		$contains = LazySequence::range(1, 1000000)
			->map(function ($x) use (&$callCount) {
				$callCount++;
				return $x;
			})
			->contains(50);

		// Only iterated until found!
		$this->assertSame(50, $callCount);
		$this->assertTrue($contains);
	}

	public function testAny(): void
	{
		$hasEven = LazySequence::of(1, 3, 5, 6, 7)->any(fn($x) => $x % 2 === 0);
		$this->assertTrue($hasEven);
	}

	public function testAnyShortCircuits(): void
	{
		$callCount = 0;
		$hasLarge = LazySequence::range(1, 1000000)
			->map(function ($x) use (&$callCount) {
				$callCount++;
				return $x;
			})
			->any(fn($x) => $x > 100);

		// Stopped at 101
		$this->assertSame(101, $callCount);
		$this->assertTrue($hasLarge);
	}

	public function testAll(): void
	{
		$allEven = LazySequence::of(2, 4, 6, 8)->all(fn($x) => $x % 2 === 0);
		$this->assertTrue($allEven);

		$allOdd = LazySequence::of(1, 2, 3)->all(fn($x) => $x % 2 === 1);
		$this->assertFalse($allOdd);
	}

	public function testAt(): void
	{
		$value = LazySequence::of(10, 20, 30, 40)->at(2);
		$this->assertSame(30, $value);
	}

	public function testAtOutOfBounds(): void
	{
		$value = LazySequence::of(1, 2, 3)->at(10);
		$this->assertNull($value);
	}

	public function testSum(): void
	{
		$sum = LazySequence::of(1, 2, 3, 4, 5)->sum();
		$this->assertSame(15, $sum);
	}

	public function testAvg(): void
	{
		$avg = LazySequence::of(10, 20, 30)->avg();
		$this->assertSame(20.0, $avg);
	}

	public function testMin(): void
	{
		$min = LazySequence::of(5, 2, 8, 1, 9)->min();
		$this->assertSame(1, $min);
	}

	public function testMax(): void
	{
		$max = LazySequence::of(5, 2, 8, 1, 9)->max();
		$this->assertSame(9, $max);
	}

	public function testCount(): void
	{
		$count = LazySequence::of(1, 2, 3, 4, 5)->count();
		$this->assertSame(5, $count);
	}

	public function testIsEmpty(): void
	{
		$this->assertTrue(LazySequence::empty()->isEmpty());
		$this->assertFalse(LazySequence::of(1)->isEmpty());
	}

	public function testIsEmptyStopsEarly(): void
	{
		$callCount = 0;
		$isEmpty = LazySequence::range(1, 1000000)
			->map(function ($x) use (&$callCount) {
				$callCount++;
				return $x;
			})
			->isEmpty();

		// Only checked first element!
		$this->assertSame(1, $callCount);
		$this->assertFalse($isEmpty);
	}

	public function testJoin(): void
	{
		$joined = LazySequence::of('a', 'b', 'c')->join(', ');
		$this->assertSame('a, b, c', $joined);
	}

	public function testToEager(): void
	{
		$lazy = LazySequence::of(1, 2, 3);
		$eager = $lazy->toEager();

		$this->assertInstanceOf(Sequence::class, $eager);
		$this->assertSame([1, 2, 3], $eager->toArray());
	}

	public function testIteration(): void
	{
		$values = [];
		foreach (LazySequence::of(1, 2, 3) as $value) {
			$values[] = $value;
		}

		$this->assertSame([1, 2, 3], $values);
	}

	public function testComplexPipeline(): void
	{
		$result = LazySequence::range(1, 100)
			->filter(fn($x) => $x % 3 === 0 || $x % 5 === 0)
			->map(fn($x) => $x * $x)
			->filter(fn($x) => $x < 1000)
			->take(10)
			->toArray();

		$this->assertSame([9, 25, 36, 81, 100, 144, 225, 324, 400, 441], $result);
	}

	public function testPerformanceBenefit(): void
	{
		// This would be slow with eager evaluation (1M iterations)
		// But lazy stops at 100
		$start = microtime(true);

		$result = LazySequence::range(1, 1000000)
			->map(fn($x) => $x * 2)
			->filter(fn($x) => $x > 100)
			->take(100)
			->toArray();

		$duration = microtime(true) - $start;

		$this->assertCount(100, $result);
		$this->assertLessThan(0.1, $duration); // Should be very fast (< 100ms)
	}
}
