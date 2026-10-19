<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use Countable;
use InvalidArgumentException;
use JsonException;
use Omegaalfa\Collection\Contract\SequenceInterface;
use RuntimeException;

/**
 * DataFrame: abstração tabular (estilo pandas) sobre a arquitetura
 * de collections e file parsers desta biblioteca.
 *
 * Cada linha é um array associativo `array<string, mixed>` (coluna => valor).
 * Cadeias de transformação (select/where/withColumn/drop/rename) são lazy:
 * nada é avaliado até uma operação terminal (toArray/head/tail/agregações/
 * groupBy/export). Leituras de arquivo delegam para {@see LazyFileIterator}
 * e os parsers de `FileParser`, mantendo streaming com memória constante.
 *
 * @template TRow of array<string, mixed>
 */
final readonly class DataFrame implements Countable
{
    /**
     * @var LazySequence<TRow>
     */
    private LazySequence $rows;

    /**
     * Colunas conhecidas, ou null para inferir da primeira linha (fontes lazy)
     *
     * @var list<string>|null
     */
    private ?array $columns;

    /**
     * @param LazySequence<TRow> $rows
     * @param list<string>|null $columns
     */
    private function __construct(LazySequence $rows, ?array $columns = null)
    {
        $this->rows = $rows;
        $this->columns = $columns;
    }

    // ------------------------------------------------------------------
    // Factory constructors
    // ------------------------------------------------------------------

    /**
     * Cria um DataFrame a partir de uma lista de linhas associativas
     *
     * @template TNewRow of array<string, mixed>
     * @param array<array-key, TNewRow> $data
     * @return self<TNewRow>
     */
    public static function fromArray(array $data): self
    {
        $rows = [];
        $columns = [];

        foreach ($data as $row) {
            $normalized = self::normalizeRow(self::assertRow($row), null);
            $rows[] = $normalized;

            foreach (array_keys($normalized) as $key) {
                if (!in_array($key, $columns, true)) {
                    $columns[] = $key;
                }
            }
        }

        /** @var self<TNewRow> $frame */
        $frame = new self(LazySequence::from($rows), $columns);

        return $frame;
    }

    /**
     * Lê um CSV de forma lazy via {@see LazyFileIterator::csv()}.
     *
     * Opções: delimiter, enclosure, escape, hasHeaders (padrão true) e
     * columns (nomes para arquivos sem header / renomear na leitura).
     *
     * @param array{
     *     delimiter?: string,
     *     enclosure?: string,
     *     escape?: string,
     *     hasHeaders?: bool,
     *     columns?: list<string>
     * } $options
     * @return self<array<string, mixed>>
     */
    public static function fromCsv(string $filePath, array $options = []): self
    {
        $iterator = LazyFileIterator::csv(
            $filePath,
            $options['delimiter'] ?? ',',
            $options['enclosure'] ?? '"',
            $options['escape'] ?? '\\',
            $options['hasHeaders'] ?? true,
        );

        return self::fromFileIterator($iterator, $options['columns'] ?? null);
    }

    /**
     * Lê um arquivo JSON Lines de forma lazy via {@see LazyFileIterator::jsonLines()}
     *
     * @return self<array<string, mixed>>
     */
    public static function fromJsonLines(string $filePath): self
    {
        return self::fromFileIterator(LazyFileIterator::jsonLines($filePath), null);
    }

    /**
     * Cria um DataFrame a partir de uma SequenceInterface de linhas
     *
     * @template TNewRow of array<string, mixed>
     * @param SequenceInterface<TNewRow> $sequence
     * @return self<TNewRow>
     */
    public static function fromSequence(SequenceInterface $sequence): self
    {
        $rows = LazySequence::from($sequence)->map(
            static fn(mixed $row): array => self::normalizeRow(self::assertRow($row), null)
        );

        /** @var self<TNewRow> $frame */
        $frame = new self($rows, null);

        return $frame;
    }

    // ------------------------------------------------------------------
    // Selection & filtering
    // ------------------------------------------------------------------

    /**
     * Projeta as colunas informadas (ordem preservada; ausentes viram null)
     *
     * @param string ...$columns
     * @return self<array<string, mixed>>
     */
    public function select(string ...$columns): self
    {
        if ($columns === []) {
            throw new InvalidArgumentException('select() requires at least one column');
        }

        if ($this->columns !== null) {
            foreach ($columns as $column) {
                if (!in_array($column, $this->columns, true)) {
                    throw new InvalidArgumentException("Column '{$column}' not found");
                }
            }
        }

        $rows = $this->rows->map(static function (array $row) use ($columns): array {
            $selected = [];
            foreach ($columns as $column) {
                $selected[$column] = $row[$column] ?? null;
            }

            return $selected;
        });

        /** @var self<array<string, mixed>> $frame */
        $frame = new self($rows, array_values($columns));

        return $frame;
    }

    /**
     * Filtra linhas de forma lazy (a cadeia só avalia até o terminal)
     *
     * @param callable(TRow): bool $predicate
     * @return self<TRow>
     */
    public function where(callable $predicate): self
    {
        /** @var self<TRow> $frame */
        $frame = new self(
            $this->rows->filter(static fn(array $row): bool => $predicate($row)),
            $this->columns
        );

        return $frame;
    }

    /**
     * Primeiras N linhas (avalia no máximo N linhas da fonte)
     *
     * @return list<TRow>
     */
    public function head(int $n = 5): array
    {
        if ($n <= 0) {
            return [];
        }

        return $this->rows->take($n)->toArray();
    }

    /**
     * Últimas N linhas (memória adicional O(n), não O(total))
     *
     * @return list<TRow>
     */
    public function tail(int $n = 5): array
    {
        if ($n <= 0) {
            return [];
        }

        $tail = new \SplQueue();
        foreach ($this->rows as $row) {
            $tail->enqueue($row);
            if ($tail->count() > $n) {
                $tail->dequeue();
            }
        }

        return iterator_to_array($tail, false);
    }

    // ------------------------------------------------------------------
    // Column operations
    // ------------------------------------------------------------------

    /**
     * Adiciona ou substitui uma coluna (lazy; o callback recebe a linha)
     *
     * @param callable(TRow): mixed $callback
     * @return self<array<string, mixed>>
     */
    public function withColumn(string $name, callable $callback): self
    {
        if ($name === '') {
            throw new InvalidArgumentException('Column name must not be empty');
        }

        $rows = $this->rows->map(static function (array $row) use ($name, $callback): array {
            $row[$name] = $callback($row);

            return $row;
        });

        $columns = $this->columns;
        if ($columns !== null && !in_array($name, $columns, true)) {
            $columns[] = $name;
        }

        /** @var self<array<string, mixed>> $frame */
        $frame = new self($rows, $columns);

        return $frame;
    }

    /**
     * Remove colunas (lazy)
     *
     * @param string ...$columns
     * @return self<array<string, mixed>>
     */
    public function drop(string ...$columns): self
    {
        $rows = $this->rows->map(static function (array $row) use ($columns): array {
            foreach ($columns as $column) {
                unset($row[$column]);
            }

            return $row;
        });

        $known = $this->columns;
        if ($known !== null) {
            $known = array_values(array_diff($known, $columns));
        }

        return new self($rows, $known);
    }

    /**
     * Renomeia colunas (lazy); mapping no formato [antigo => novo]
     *
     * @param array<string, string> $mapping
     * @return self<array<string, mixed>>
     */
    public function rename(array $mapping): self
    {
        foreach ($mapping as $old => $new) {
            if ($new === '') {
                throw new InvalidArgumentException("New name for column '{$old}' must not be empty");
            }
        }

        $rows = $this->rows->map(static function (array $row) use ($mapping): array {
            $renamed = [];
            foreach ($row as $key => $value) {
                $renamed[$mapping[$key] ?? $key] = $value;
            }

            return $renamed;
        });

        $known = $this->columns;
        if ($known !== null) {
            $known = array_map(static fn(string $column): string => $mapping[$column] ?? $column, $known);
        }

        return new self($rows, $known);
    }

    /**
     * Lista de colunas (da primeira linha, para fontes lazy)
     *
     * @return list<string>
     */
    public function columns(): array
    {
        return $this->columns ?? $this->peekColumns() ?? [];
    }

    // ------------------------------------------------------------------
    // Group By & aggregations
    // ------------------------------------------------------------------

    /**
     * Agrupa linhas pelo valor de uma coluna (ordem de primeira aparição).
     * Chaves seguem as chaves nativas de array PHP (int|string).
     *
     * @return array<array-key, self<TRow>>
     */
    public function groupBy(string $column): array
    {
        $this->assertColumnExists($column);

        $groups = [];
        $order = [];

        foreach ($this->rows as $row) {
            $key = self::normalizeGroupKey($row[$column] ?? null);
            if (!array_key_exists($key, $groups)) {
                $groups[$key] = [];
                $order[] = $key;
            }
            $groups[$key][] = $row;
        }

        $result = [];
        foreach ($order as $key) {
            /** @var self<TRow> $frame */
            $frame = new self(LazySequence::from($groups[$key]), $this->columns);
            $result[$key] = $frame;
        }

        return $result;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->rows->count();
    }

    /**
     * Soma dos valores numéricos da coluna (não numéricos contam como 0)
     *
     * @return float|int
     */
    public function sum(string $column): float|int
    {
        $this->assertColumnExists($column);

        $sum = 0;
        foreach ($this->rows as $row) {
            $value = $row[$column] ?? null;
            if (is_numeric($value)) {
                $sum += $value;
            }
        }

        return $sum;
    }

    /**
     * Média dos valores numéricos da coluna
     *
     * @throws InvalidArgumentException Quando a coluna não tem valores numéricos
     */
    public function mean(string $column): float
    {
        $this->assertColumnExists($column);

        $sum = 0;
        $count = 0;
        foreach ($this->rows as $row) {
            $value = $row[$column] ?? null;
            if (is_numeric($value)) {
                $sum += $value;
                $count++;
            }
        }

        if ($count === 0) {
            throw new InvalidArgumentException("Column '{$column}' has no numeric values");
        }

        return $sum / $count;
    }

    /**
     * Menor valor da coluna (nulls ignorados; null se vazia)
     */
    public function min(string $column): mixed
    {
        $this->assertColumnExists($column);

        $min = null;
        foreach ($this->rows as $row) {
            $value = $row[$column] ?? null;
            if ($value === null) {
                continue;
            }
            if ($min === null || $value < $min) {
                $min = $value;
            }
        }

        return $min;
    }

    /**
     * Maior valor da coluna (nulls ignorados; null se vazia)
     */
    public function max(string $column): mixed
    {
        $this->assertColumnExists($column);

        $max = null;
        foreach ($this->rows as $row) {
            $value = $row[$column] ?? null;
            if ($value === null) {
                continue;
            }
            if ($max === null || $value > $max) {
                $max = $value;
            }
        }

        return $max;
    }

    /**
     * Calcula as principais estatísticas numéricas em uma única passagem.
     *
     * @return array{count: int, sum: int|float, mean: float|null, min: mixed, max: mixed}
     */
    public function describe(string $column): array
    {
        $this->assertColumnExists($column);

        $count = 0;
        $sum = 0;
        $min = null;
        $max = null;

        foreach ($this->rows as $row) {
            $value = $row[$column] ?? null;
            if (!is_numeric($value)) {
                continue;
            }

            $count++;
            $sum += $value;
            $min = $min === null || $value < $min ? $value : $min;
            $max = $max === null || $value > $max ? $value : $max;
        }

        return [
            'count' => $count,
            'sum' => $sum,
            'mean' => $count === 0 ? null : (float) ($sum / $count),
            'min' => $min,
            'max' => $max,
        ];
    }

    /**
     * Materializa as linhas uma vez e devolve um DataFrame reutilizável.
     * Útil quando vários terminais serão executados sobre uma fonte de arquivo.
     *
     * @return self<TRow>
     */
    public function cached(): self
    {
        return new self(LazySequence::from($this->rows->toArray()), $this->columns);
    }

    // ------------------------------------------------------------------
    // Exporting
    // ------------------------------------------------------------------

    /**
     * @return list<TRow>
     */
    public function toArray(): array
    {
        return $this->rows->toArray();
    }

    /**
     * Exporta para CSV (header = colunas conhecidas)
     *
     * @throws RuntimeException Quando o arquivo não pode ser aberto para escrita
     */
    public function toCsv(string $filePath): void
    {
        $handle = fopen($filePath, 'wb');
        if ($handle === false) {
            throw new RuntimeException("Cannot open file for writing: {$filePath}");
        }

        try {
            $columns = $this->columns();

            if ($columns !== []) {
                fputcsv($handle, $columns, escape: '\\');
            }

            foreach ($this->rows as $row) {
                $cells = [];
                foreach ($columns as $column) {
                    $cells[] = self::toCell($row[$column] ?? null);
                }
                fputcsv($handle, $cells);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @throws JsonException Quando alguma linha não é serializável
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * @param LazyFileIterator $iterator
     * @param list<string>|null $columnNames
     * @return self<array<string, mixed>>
     */
    private static function fromFileIterator(LazyFileIterator $iterator, ?array $columnNames): self
    {
        $rows = LazySequence::from($iterator)->map(
            static fn(mixed $row): array => self::normalizeRow(self::assertRow($row), $columnNames)
        );

        return new self($rows, $columnNames);
    }

    /**
     * Valida uma linha em runtime (entrada sem tipos garantidos)
     *
     * @param mixed $row
     * @return array<array-key, mixed>
     */
    private static function assertRow(mixed $row): array
    {
        if (!is_array($row)) {
            throw new InvalidArgumentException('Each row must be an array');
        }

        return $row;
    }

    /**
     * Normaliza uma linha para `array<string, mixed>`.
     * Com nomes informados, os valores (posicionais ou do header) são
     * combinados com eles na ordem das colunas — como o `names` do pandas.
     * Sem nomes, linhas posicionais recebem chaves '0', '1', ...
     *
     * @param array<array-key, mixed> $row
     * @param list<string>|null $columnNames
     * @return array<string, mixed>
     */
    private static function normalizeRow(array $row, ?array $columnNames): array
    {
        if ($columnNames !== null) {
            $values = array_values($row);
            $normalized = [];
            foreach ($columnNames as $index => $name) {
                $normalized[$name] = $values[$index] ?? null;
            }

            return $normalized;
        }

        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[(string)$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     * @return array-key
     */
    private static function normalizeGroupKey(mixed $value): int|string
    {
        return match (true) {
            is_int($value) => $value,
            is_string($value) => $value,
            is_bool($value) => $value ? '1' : '0',
            is_float($value) => (string)$value,
            is_null($value) => '',
            default => throw new InvalidArgumentException(
                'groupBy column values must be scalar or null, ' . get_debug_type($value) . ' given'
            ),
        };
    }

    private static function toCell(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? '1' : '0',
            is_scalar($value) => (string)$value,
            default => json_encode($value, JSON_THROW_ON_ERROR),
        };
    }

    /**
     * @return list<string>|null
     */
    private function peekColumns(): ?array
    {
        foreach ($this->rows as $row) {
            return array_keys($row);
        }

        return null;
    }

    private function assertColumnExists(string $column): void
    {
        $known = $this->columns ?? $this->peekColumns();

        if ($known === null) {
            return; // frame vazio sem schema explícito: nada a validar
        }

        if (!in_array($column, $known, true)) {
            throw new InvalidArgumentException("Column '{$column}' not found");
        }
    }
}
