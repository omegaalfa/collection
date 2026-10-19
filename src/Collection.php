<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Generator;
use Iterator;
use IteratorAggregate;
use Omegaalfa\Collection\Traits\CollectionAggregatesTrait;
use Omegaalfa\Collection\Traits\CollectionArrayAccessTrait;
use Omegaalfa\Collection\Traits\CollectionTransformationsTrait;
use Omegaalfa\Collection\Traits\LazyOperationsTrait;
use Omegaalfa\Collection\Util\RewindableIterator;
use Omegaalfa\Collection\Util\RegenerableIterator;
use Traversable;

/**
 * Refatorado para melhor coesão e menor complexidade
 * 
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 */
class Collection implements IteratorAggregate, Countable, ArrayAccess
{
    use CollectionArrayAccessTrait;
    use CollectionAggregatesTrait;
    use CollectionTransformationsTrait;
    use LazyOperationsTrait;

    /**
     * @var Iterator<TKey, TValue>|array<TKey, TValue>
     */
    protected Iterator|array $collection;

    /**
     * @var int|null
     */
    private ?int $cachedCount = null;

    /**
     * @var array<TKey, TValue>|null
     */
    private ?array $cachedArray = null;

    /**
     * @var RewindableIterator<TKey, TValue>|null
     */
    private ?RewindableIterator $rewindable = null;

    /**
     * @param Iterator<TKey, TValue>|array<TKey, TValue> $collection
     */
    public function __construct(Iterator|array $collection = [])
    {
        $this->collection = $collection;
        $this->invalidateCache();
    }

    /**
     * @return void
     */
    private function invalidateCache(): void
    {
        $this->cachedCount = null;
        $this->cachedArray = null;
    }

    /**
     * Materializa o iterator interno em array (para operações mutantes).
     * Após a materialização a coleção permanece reiterável.
     *
     * @return void
     */
    private function realizeCollection(): void
    {
        if (is_array($this->collection)) {
            return;
        }

        if ($this->rewindable !== null) {
            $this->collection = iterator_to_array($this->rewindable, true);
        } elseif ($this->collection instanceof Generator) {
            $this->collection = iterator_to_array($this->collection, true);
        } else {
            $this->collection = iterator_to_array($this->getIterator(), true);
        }
        $this->rewindable = null;
    }

    /**
     * Cria uma coleção lazy a partir de um callback
     *
     * @template TNewKey of array-key
     * @template TNewValue
     * @param callable(): Generator<TNewKey, TNewValue> $callback
     * @return Collection<TNewKey, TNewValue>
     */
    public static function lazy(callable $callback): Collection
    {
        return new self(new RegenerableIterator($callback));
    }

    /**
     * Cria range lazy usando generator
     *
     * @param int $start
     * @param int $end
     * @param int $step
     * @return Collection<int, int>
     */
    public static function lazyRange(int $start, int $end, int $step = 1): Collection
    {
        if ($step === 0) {
            throw new \InvalidArgumentException('Step must not be zero');
        }

        $generator = function () use ($start, $end, $step): Generator {
            if ($step > 0) {
                for ($i = $start; $i <= $end; $i += $step) {
                    yield $i;
                }
            } else {
                for ($i = $start; $i >= $end; $i += $step) {
                    yield $i;
                }
            }
        };

        return new self(new RegenerableIterator($generator));
    }

    /**
     * @param Iterator<TKey, TValue>|array<TKey, TValue> $collection
     * @return void
     */
    public function addIterator(Iterator|array $collection = []): void
    {
        $this->collection = $collection;
        $this->rewindable = null;
        $this->invalidateCache();
    }

    /**
     * @return TValue|false
     */
    public function current(): mixed
    {
        if ($this->collection instanceof Iterator) {
            return $this->collection->current();
        }

        return current($this->collection);
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        if (is_array($this->collection)) {
            return new ArrayIterator($this->collection);
        }

        // Envolve o iterator para permitir reiteração (fontes one-shot,
        // como Generator, são memorizadas conforme são consumidas)
        if ($this->collection instanceof RegenerableIterator) {
            return $this->collection->newIterator();
        }

        if (!$this->collection instanceof Generator) {
            return $this->collection;
        }

        return $this->rewindable ??= new RewindableIterator($this->collection);
    }

    /**
     * Busca recursivamente o valor associado a uma chave.
     * Valores falsy (0, '', false, null) são retornados corretamente.
     *
     * @param array<array-key, mixed> $array
     * @param string $key
     * @return mixed
     */
    public function searchValueKey(array $array, string $key): mixed
    {
        [, $value] = $this->findValueKey($array, $key);

        return $value;
    }

    /**
     * @param array<array-key, mixed> $array
     * @param string $key
     * @return array{bool, mixed} [encontrado, valor]
     */
    private function findValueKey(array $array, string $key): array
    {
        if (array_key_exists($key, $array)) {
            return [true, $array[$key]];
        }

        foreach ($this->arrayToGenerator($array) as $value) {
            if (!is_array($value)) {
                continue;
            }

            [$found, $result] = $this->findValueKey($value, $key);
            if ($found) {
                return [$found, $result];
            }
        }

        return [false, null];
    }

    /**
     * @param array<TKey, TValue> $array
     * @return Generator<TKey, TValue>
     */
    public function arrayToGenerator(array $array): Generator
    {
        yield from $array;
    }

    /**
     * @param TValue $item
     * @return void
     */
    public function remove(mixed $item): void
    {
        $this->realizeCollection();

        $this->collection = array_filter($this->collection, static function ($currentItem) use ($item) {
            return $currentItem !== $item;
        });
        $this->invalidateCache();
    }

    /**
     * @return TValue|null
     */
    public function first(): mixed
    {
        foreach ($this->getIterator() as $item) {
            return $item;
        }

        return null;
    }

    /**
     * @return TValue|null
     */
    public function last(): mixed
    {
        $last = null;
        foreach ($this->getIterator() as $item) {
            $last = $item;
        }

        return $last;
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        if ($this->cachedCount !== null) {
            return $this->cachedCount;
        }

        if (is_array($this->collection)) {
            return $this->cachedCount = count($this->collection);
        }

        return $this->cachedCount = iterator_count($this->getIterator());
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        // Usa cache se disponível
        if ($this->cachedArray !== null) {
            return $this->cachedArray;
        }

        // Se já é array, retorna diretamente
        if (is_array($this->collection)) {
            return $this->cachedArray = $this->collection;
        }

        // Converte iterator para array e cacheia
        $this->realizeCollection();

        return $this->cachedArray = $this->collection;
    }

    /**
     * @param TValue $value
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        // Fast path: use native in_array for arrays
        if (is_array($this->collection)) {
            return in_array($value, $this->collection, true);
        }

        // Short-circuit para iterators (não materializa a coleção inteira)
        foreach ($this->getIterator() as $item) {
            if ($item === $value) {
                return true;
            }
        }

        return false;
    }
}
