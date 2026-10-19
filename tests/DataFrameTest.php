<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Tests;

use InvalidArgumentException;
use Omegaalfa\Collection\Collection;
use Omegaalfa\Collection\DataFrame;
use Omegaalfa\Collection\Sequence;
use PHPUnit\Framework\TestCase;

class DataFrameTest extends TestCase
{
    private string $tmpFile = '';

    protected function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'df');
    }

    protected function tearDown(): void
    {
        if ($this->tmpFile !== '' && file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleRows(): array
    {
        return [
            ['name' => 'alice', 'dept' => 'eng', 'salary' => 100],
            ['name' => 'bob', 'dept' => 'eng', 'salary' => 200],
            ['name' => 'carol', 'dept' => 'hr', 'salary' => 300],
        ];
    }

    // ------------------------------------------------------------------
    // Factory constructors
    // ------------------------------------------------------------------

    public function testFromArray(): void
    {
        $df = DataFrame::fromArray($this->sampleRows());

        $this->assertSame(3, $df->count());
        $this->assertSame(['name', 'dept', 'salary'], $df->columns());
        $this->assertSame($this->sampleRows(), $df->toArray());
    }

    public function testFromArrayCollectsColumnsFromAllRows(): void
    {
        $df = DataFrame::fromArray([
            ['a' => 1],
            ['b' => 2],
        ]);

        $this->assertSame(['a', 'b'], $df->columns());
    }

    public function testFromArrayRejectsNonArrayRows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DataFrame::fromArray(['not-a-row']);
    }

    public function testFromCsvWithHeaders(): void
    {
        file_put_contents($this->tmpFile, "id,name\n1,alice\n2,bob\n");

        $df = DataFrame::fromCsv($this->tmpFile);

        $this->assertSame(['id', 'name'], $df->columns());
        $this->assertSame([
            ['id' => '1', 'name' => 'alice'],
            ['id' => '2', 'name' => 'bob'],
        ], $df->toArray());
    }

    public function testFromCsvWithoutHeadersAndColumnNames(): void
    {
        file_put_contents($this->tmpFile, "1;alice\n2;bob\n");

        $df = DataFrame::fromCsv($this->tmpFile, [
            'delimiter' => ';',
            'hasHeaders' => false,
            'columns' => ['id', 'name'],
        ]);

        $this->assertSame(['id', 'name'], $df->columns());
        $this->assertSame([
            ['id' => '1', 'name' => 'alice'],
            ['id' => '2', 'name' => 'bob'],
        ], $df->toArray());
    }

    public function testFromCsvColumnNamesOverrideHeaders(): void
    {
        file_put_contents($this->tmpFile, "id,name\n1,alice\n");

        $df = DataFrame::fromCsv($this->tmpFile, ['columns' => ['identifier', 'label']]);

        $this->assertSame(['identifier', 'label'], $df->columns());
        $this->assertSame([['identifier' => '1', 'label' => 'alice']], $df->toArray());
    }

    public function testFromJsonLines(): void
    {
        file_put_contents(
            $this->tmpFile,
            "{\"id\": 1, \"tags\": [\"a\", \"b\"]}\n{\"id\": 2, \"tags\": []}\n"
        );

        $df = DataFrame::fromJsonLines($this->tmpFile);

        $this->assertSame([
            ['id' => 1, 'tags' => ['a', 'b']],
            ['id' => 2, 'tags' => []],
        ], $df->toArray());
    }

    public function testFromSequence(): void
    {
        $df = DataFrame::fromSequence(Sequence::of(
            ['a' => 1],
            ['a' => 2],
        ));

        $this->assertSame(['a'], $df->columns());
        $this->assertSame([['a' => 1], ['a' => 2]], $df->toArray());
    }

    // ------------------------------------------------------------------
    // Selection & filtering
    // ------------------------------------------------------------------

    public function testSelect(): void
    {
        $df = DataFrame::fromArray($this->sampleRows())->select('salary', 'name');

        $this->assertSame(['salary', 'name'], $df->columns());
        $this->assertSame([
            ['salary' => 100, 'name' => 'alice'],
            ['salary' => 200, 'name' => 'bob'],
            ['salary' => 300, 'name' => 'carol'],
        ], $df->toArray());
    }

    public function testSelectFillsMissingColumnWithNullOnLazySources(): void
    {
        // Schema desconhecido (fonte lazy): colunas ausentes viram null
        file_put_contents($this->tmpFile, "a\n1\n");

        $df = DataFrame::fromCsv($this->tmpFile)->select('a', 'b');

        $this->assertSame([['a' => '1', 'b' => null]], $df->toArray());
    }

    public function testSelectRejectsUnknownColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'nope' not found");

        DataFrame::fromArray($this->sampleRows())->select('nope');
    }

    public function testSelectRequiresAtLeastOneColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DataFrame::fromArray($this->sampleRows())->select();
    }

    public function testWhere(): void
    {
        $df = DataFrame::fromArray($this->sampleRows())
            ->where(fn(array $row): bool => $row['dept'] === 'eng');

        $this->assertCount(2, $df->toArray());
        $this->assertSame('alice', $df->toArray()[0]['name']);
    }

    public function testHeadAndTail(): void
    {
        $df = DataFrame::fromArray($this->sampleRows());

        $this->assertCount(2, $df->head(2));
        $this->assertSame('alice', $df->head(2)[0]['name']);
        $this->assertCount(2, $df->tail(2));
        $this->assertSame('carol', $df->tail(2)[1]['name']);
        $this->assertCount(3, $df->head(), 'head() padrão devolve até 5 linhas (menos se houver menos)');
        $this->assertSame([], $df->head(0));
        $this->assertSame([], $df->tail(-1));
    }

    // ------------------------------------------------------------------
    // Column operations
    // ------------------------------------------------------------------

    public function testWithColumnAddsAndOverwrites(): void
    {
        $df = DataFrame::fromArray($this->sampleRows())
            ->withColumn('bonus', fn(array $row): int => $row['salary'] * 2)
            ->withColumn('salary', fn(array $row): int => $row['salary'] + 1);

        $this->assertContains('bonus', $df->columns());
        $this->assertSame(200, $df->toArray()[0]['bonus']);
        $this->assertSame(101, $df->toArray()[0]['salary']);
        $this->assertSame(
            ['name', 'dept', 'salary', 'bonus'],
            $df->columns(),
            'coluna nova vai ao fim; substituída mantém posição'
        );
    }

    public function testDrop(): void
    {
        $df = DataFrame::fromArray($this->sampleRows())->drop('dept', 'salary');

        $this->assertSame(['name'], $df->columns());
        $this->assertSame([['name' => 'alice']], [$df->toArray()[0]]);
    }

    public function testRename(): void
    {
        $df = DataFrame::fromArray($this->sampleRows())->rename(['salary' => 'pay']);

        $this->assertSame(['name', 'dept', 'pay'], $df->columns());
        $this->assertSame(100, $df->toArray()[0]['pay']);
        $this->assertArrayNotHasKey('salary', $df->toArray()[0]);
    }

    public function testRenameRejectsEmptyNewName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DataFrame::fromArray($this->sampleRows())->rename(['salary' => '']);
    }

    // ------------------------------------------------------------------
    // Group By & aggregations
    // ------------------------------------------------------------------

    public function testGroupBy(): void
    {
        $groups = DataFrame::fromArray($this->sampleRows())->groupBy('dept');

        $this->assertSame(['eng', 'hr'], array_keys($groups));
        $this->assertCount(2, $groups['eng']);
        $this->assertCount(1, $groups['hr']);
        $this->assertSame('carol', $groups['hr']->toArray()[0]['name']);
    }

    public function testGroupByRejectsNonScalarKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DataFrame::fromArray([['g' => ['nested']]])->groupBy('g');
    }

    public function testAggregations(): void
    {
        $df = DataFrame::fromArray($this->sampleRows());

        $this->assertSame(600, $df->sum('salary'));
        $this->assertSame(200.0, $df->mean('salary'));
        $this->assertSame(100, $df->min('salary'));
        $this->assertSame(300, $df->max('salary'));
        $this->assertSame('alice', $df->min('name'));
        $this->assertSame('carol', $df->max('name'));
    }

    public function testSumIgnoresNonNumericValues(): void
    {
        $df = DataFrame::fromArray([
            ['v' => 1],
            ['v' => 'x'],
            ['v' => 2.5],
        ]);

        $this->assertSame(3.5, $df->sum('v'));
        $this->assertSame(1.75, $df->mean('v'));
    }

    public function testMeanThrowsWithoutNumericValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DataFrame::fromArray([['v' => 'x']])->mean('v');
    }

    public function testAggregationsRejectUnknownColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DataFrame::fromArray($this->sampleRows())->sum('nope');
    }

    public function testMinMaxIgnoreNullValues(): void
    {
        $df = DataFrame::fromArray([['v' => null], ['v' => 5], ['v' => null]]);

        $this->assertSame(5, $df->min('v'));
        $this->assertSame(5, $df->max('v'));
    }

    // ------------------------------------------------------------------
    // Exporting
    // ------------------------------------------------------------------

    public function testToCsvRoundtrip(): void
    {
        $rows = $this->sampleRows();
        DataFrame::fromArray($rows)->toCsv($this->tmpFile);

        $df = DataFrame::fromCsv($this->tmpFile);

        $this->assertSame(['name', 'dept', 'salary'], $df->columns());
        $this->assertSame([
            ['name' => 'alice', 'dept' => 'eng', 'salary' => '100'],
            ['name' => 'bob', 'dept' => 'eng', 'salary' => '200'],
            ['name' => 'carol', 'dept' => 'hr', 'salary' => '300'],
        ], $df->toArray(), 'CSV devolve strings — tipagem original não sobrevive ao formato');
    }

    public function testToJson(): void
    {
        $json = DataFrame::fromArray([['a' => 1, 'b' => null]])->toJson();

        $this->assertSame('[{"a":1,"b":null}]', $json);
    }

    // ------------------------------------------------------------------
    // Lazy chaining & streaming
    // ------------------------------------------------------------------

    public function testLazyChainingShortCircuits(): void
    {
        $lines = ["id,name\n"];
        for ($i = 1; $i <= 1000; $i++) {
            $lines[] = "{$i},name{$i}\n";
        }
        file_put_contents($this->tmpFile, implode('', $lines));

        $evaluated = 0;
        $result = DataFrame::fromCsv($this->tmpFile)
            ->where(function (array $row) use (&$evaluated): bool {
                $evaluated++;

                return (int) $row['id'] >= 10;
            })
            ->select('id', 'name')
            ->head(3);

        $this->assertCount(3, $result);
        $this->assertSame('10', $result[0]['id']);
        $this->assertLessThanOrEqual(
            15,
            $evaluated,
            'A cadeia lazy deve parar após encontrar 3 matches (linhas 10-12), não varrer 1000'
        );
    }

    public function testStreamingHeadKeepsMemoryBounded(): void
    {
        $lines = ["id,value\n"];
        for ($i = 1; $i <= 20000; $i++) {
            $lines[] = "{$i},{$i}\n";
        }
        file_put_contents($this->tmpFile, implode('', $lines));

        gc_collect_cycles();
        $before = memory_get_usage();

        $result = DataFrame::fromCsv($this->tmpFile)
            ->where(fn(array $row): bool => true)
            ->select('id')
            ->head(5);

        $delta = memory_get_usage() - $before;

        $this->assertCount(5, $result);
        $this->assertLessThan(
            2 * 1024 * 1024,
            $delta,
            'head(5) não pode materializar as 20k linhas do arquivo'
        );
    }

    public function testChainedTransformationsStayLazyUntilTerminal(): void
    {
        file_put_contents($this->tmpFile, "id,name\n1,alice\n");

        // Nenhum acesso terminal antes disto: apenas constrói a cadeia
        $df = DataFrame::fromCsv($this->tmpFile)
            ->where(fn(array $row): bool => true)
            ->select('name')
            ->withColumn('upper', fn(array $row): string => strtoupper($row['name']))
            ->rename(['upper' => 'shout'])
            ->drop('name');

        $this->assertSame(['shout'], $df->columns());
        $this->assertSame([['shout' => 'ALICE']], $df->toArray());
    }

    public function testEmptyFrame(): void
    {
        $df = DataFrame::fromArray([]);

        $this->assertSame(0, $df->count());
        $this->assertSame([], $df->columns());
        $this->assertSame([], $df->toArray());
        $this->assertSame([], $df->head());
    }

    public function testEmptyLazyFrameAggregatesGracefully(): void
    {
        file_put_contents($this->tmpFile, "v\n"); // só header, sem linhas

        $df = DataFrame::fromCsv($this->tmpFile);

        $this->assertSame(0, $df->count());
        $this->assertSame(0, $df->sum('v'));
        $this->assertNull($df->min('v'));
    }

    public function testIntegratesWithExistingCollectionTypes(): void
    {
        $rows = (new Collection($this->sampleRows()))->toArray();
        $df = DataFrame::fromArray($rows);

        $this->assertSame($this->sampleRows(), $df->toArray());
    }

    public function testDescribeUsesOnePassAndCachedFrameCanBeReused(): void
    {
        $df = DataFrame::fromArray([
            ['value' => 2],
            ['value' => 4],
            ['value' => null],
        ]);

        $this->assertSame([
            'count' => 2,
            'sum' => 6,
            'mean' => 3.0,
            'min' => 2,
            'max' => 4,
        ], $df->describe('value'));

        $cached = $df->cached();
        $this->assertSame(3, $cached->count());
        $this->assertSame(6, $cached->sum('value'));
        $this->assertSame(3.0, $cached->mean('value'));
    }
}
