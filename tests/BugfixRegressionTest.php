<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Tests;

use Omegaalfa\Collection\Collection;
use Omegaalfa\Collection\LazyFileIterator;
use Omegaalfa\Collection\LazyMap;
use Omegaalfa\Collection\LazySequence;
use Omegaalfa\Collection\Map;
use Omegaalfa\Collection\Sequence;
use PHPUnit\Framework\TestCase;

/**
 * Testes de regressão para os bugs identificados na auditoria de src/
 */
class BugfixRegressionTest extends TestCase
{
    private string $tmpFile = '';

    protected function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'coll');
    }

    protected function tearDown(): void
    {
        if ($this->tmpFile !== '' && file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    // ------------------------------------------------------------------
    // LazyFileIterator: perda de dados
    // ------------------------------------------------------------------

    public function testFileLineWithZeroIsValidData(): void
    {
        file_put_contents($this->tmpFile, "0\n1\n2\n");

        $lines = iterator_to_array(new LazyFileIterator($this->tmpFile, new \Omegaalfa\Collection\FileParser\PlainTextParser()), false);

        $this->assertSame(['0', '1', '2'], $lines);
    }

    public function testCsvHeaderLineIsSkippedAndNotYielded(): void
    {
        file_put_contents($this->tmpFile, "id,name\n1,alice\n2,bob\n");

        $rows = iterator_to_array(LazyFileIterator::csv($this->tmpFile), false);

        $this->assertCount(2, $rows);
        $this->assertSame(['id' => '1', 'name' => 'alice'], $rows[0]);
        $this->assertSame(['id' => '2', 'name' => 'bob'], $rows[1]);
    }

    // ------------------------------------------------------------------
    // Collection: mutação sobre Iterator
    // ------------------------------------------------------------------

    public function testAddOnIteratorAppendsItem(): void
    {
        $collection = new Collection(new \ArrayIterator([1, 2]));
        $collection->add(3);

        $this->assertSame([1, 2, 3], array_values($collection->toArray()));
    }

    public function testOffsetGetOnIteratorReturnsValue(): void
    {
        $collection = new Collection(new \ArrayIterator(['k' => 'v']));

        $this->assertTrue(isset($collection['k']));
        $this->assertSame('v', $collection['k']);
    }

    public function testSetAttributeOnIteratorWritesValue(): void
    {
        $collection = new Collection(new \ArrayIterator(['a' => 1]));
        $collection->setAttribute('b', 2);

        $this->assertSame(['a' => 1, 'b' => 2], $collection->toArray());
    }

    // ------------------------------------------------------------------
    // Collection: valores falsy e reiteração de Generator
    // ------------------------------------------------------------------

    public function testSearchValueKeyFindsFalsyValues(): void
    {
        $collection = new Collection();

        $this->assertSame(0, $collection->searchValueKey(['a' => ['b' => 0]], 'b'));
        $this->assertSame('', $collection->searchValueKey(['a' => ['b' => '']], 'b'));
        $this->assertFalse($collection->searchValueKey(['a' => ['b' => false]], 'b'));
        $this->assertNull($collection->searchValueKey(['a' => ['b' => null]], 'b'));
    }

    public function testGeneratorSurvivesCountAndToArray(): void
    {
        $collection = new Collection((function () {
            yield 1;
            yield 2;
            yield 3;
        })());

        $this->assertSame(3, $collection->count());
        $this->assertSame([1, 2, 3], $collection->toArray());

        $seen = [];
        foreach ($collection as $value) {
            $seen[] = $value;
        }
        $this->assertSame([1, 2, 3], $seen);
    }

    public function testGeneratorSurvivesFirstThenIteration(): void
    {
        $collection = new Collection((function () {
            yield 1;
            yield 2;
            yield 3;
        })());

        $this->assertSame(1, $collection->first());

        $seen = [];
        foreach ($collection as $value) {
            $seen[] = $value;
        }
        $this->assertSame([1, 2, 3], $seen);
    }

    public function testLastThenFirstOnGenerator(): void
    {
        $collection = new Collection((function () {
            yield 1;
            yield 2;
            yield 3;
        })());

        $this->assertSame(3, $collection->last());
        $this->assertSame(1, $collection->first());
    }

    public function testLazyRangeRejectsZeroStep(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Collection::lazyRange(5, 1, 0);
    }

    public function testMapReceivesKeyAsSecondArgument(): void
    {
        $result = (new Collection(['a' => 1]))->map(fn($value, $key) => $key . '=' . $value);

        $this->assertSame(['a' => 'a=1'], $result->toArray());
    }

    public function testPluckSkipsInaccessibleProperties(): void
    {
        $object = new class {
            private string $hidden = 'x';
            public string $shown = 'y';
        };

        $values = (new Collection([$object]))->pluck('hidden')->toArray();

        $this->assertSame([], $values);
        $this->assertSame(['y'], (new Collection([$object]))->pluck('shown')->toArray());
    }

    // ------------------------------------------------------------------
    // LazySequence: null, índices, take/slice/unique
    // ------------------------------------------------------------------

    public function testLazySequencePreservesNullValuesWithOperations(): void
    {
        $this->assertSame(
            [1, null, 2],
            LazySequence::of(1, null, 2)->map(fn($x) => $x)->toArray()
        );

        $this->assertSame(
            [1, null, 2],
            LazySequence::of(1, null, 2)->filter(fn() => true)->toArray()
        );
    }

    public function testFlatMapAdvancesIndexAndRespectsTake(): void
    {
        $this->assertSame(
            [10, 0, 20, 1],
            LazySequence::of(10, 20)->flatMap(fn($value, $index) => [$value, $index])->toArray()
        );

        $this->assertSame(
            [1, 2, 3],
            LazySequence::of(1, 2, 3, 4)->flatMap(fn($value) => [$value])->take(3)->toArray()
        );
    }

    public function testTakeBeforeFilterDoesNotConsumeWholeSource(): void
    {
        $consumed = 0;
        $source = (function () use (&$consumed) {
            for ($i = 1; $i <= 100; $i++) {
                $consumed++;
                yield $i;
            }
        })();

        $result = LazySequence::from($source)->take(2)->filter(fn() => false)->toArray();

        $this->assertSame([], $result);
        $this->assertSame(2, $consumed, 'take(2) deve interromper o consumo da fonte');
    }

    public function testSliceWithNegativeOffsetMatchesSequence(): void
    {
        $this->assertSame([4, 5], LazySequence::of(1, 2, 3, 4, 5)->slice(-2, 2)->toArray());
        $this->assertSame([4, 5], Sequence::of(1, 2, 3, 4, 5)->slice(-2, 2)->toArray());
    }

    public function testUniqueAcceptsClosures(): void
    {
        $a = fn() => 1;
        $b = fn() => 2;

        $result = LazySequence::of($a, $a, $b)->unique()->toArray();

        $this->assertCount(2, $result);
    }

    // ------------------------------------------------------------------
    // Sequence: elementos null
    // ------------------------------------------------------------------

    public function testSequenceAtAndRemoveWithNullElements(): void
    {
        $sequence = Sequence::of(null, 1);

        $this->assertNull($sequence->at(0));
        $this->assertSame(1, $sequence->remove(0)->at(0));
    }

    // ------------------------------------------------------------------
    // Map / LazyMap: chaves inteiras e construções
    // ------------------------------------------------------------------

    public function testMapFromAnyIterable(): void
    {
        $this->assertSame(['a' => 1], Map::from((function () {
            yield 'a' => 1;
        })())->toArray());

        $this->assertSame(['a' => 1], Map::from(new \ArrayIterator(['a' => 1]))->toArray());
    }

    public function testMapOfAcceptsNullValues(): void
    {
        $map = Map::of('a', 1, 'b', null);

        $this->assertSame(['a' => 1, 'b' => null], $map->toArray());
    }

    public function testMapMergePreservesIntegerKeys(): void
    {
        $merged = Map::from([5 => 'a'])->merge(Map::from([10 => 'b']));

        $this->assertSame([5 => 'a', 10 => 'b'], $merged->toArray());
    }

    public function testLazyMapPutPreservesIntegerKeys(): void
    {
        $map = LazyMap::from([5 => 'a']);

        $this->assertSame([5 => 'a', 10 => 'b'], $map->put(10, 'b')->toArray());
        $this->assertSame([5 => 'a', 10 => 'b'], $map->putAll([10 => 'b'])->toArray());
        $this->assertSame([5 => 'a', 10 => 'b'], $map->merge(LazyMap::from([10 => 'b']))->toArray());
    }
}
