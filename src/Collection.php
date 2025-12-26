<?php

declare(strict_types=1);

namespace Omegaalfa\Collection;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Generator;
use Iterator;
use IteratorAggregate;
use Traversable;
use Omegaalfa\Collection\Util\LazyProxyObject;
use Closure;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 */
class Collection implements IteratorAggregate, Countable, ArrayAccess
{

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
     * @var array<string, Closure>
     */
    private array $lazyOperations = [];


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
     * @param Iterator<TKey, TValue>|array<TKey, TValue> $collection
     *
     * @return void
     */
    public function addIterator(Iterator|array $collection = []): void
    {
        $this->collection = $collection;
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
     * @template TNewValue
     *
     * @param callable(TValue, TKey): TNewValue $callback
     *
     * @return Collection<TKey, TNewValue>
     */
    public function map(callable $callback): Collection
    {
        $newcollection = [];
        foreach ($this->getIterator() as $key => $item) {
            $newcollection[$key] = $callback($item, $key);
        }

        return new self($newcollection);
    }

    /**
     * Lazy map - adia execução até materialização
     * 
     * @template TNewValue
     *
     * @param callable(TValue, TKey): TNewValue $callback
     *
     * @return Collection<TKey, TNewValue>
     */
    public function lazyMap(callable $callback): Collection
    {
        $generator = function() use ($callback): Generator {
            foreach ($this->getIterator() as $key => $item) {
                yield $key => $callback($item, $key);
            }
        };

        return new self($generator());
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        if (!$this->collection instanceof Traversable) {
            return new ArrayIterator($this->collection);
        }

        return $this->collection;
    }

    /**
     * Cria uma coleção lazy a partir de um callback
     * 
     * @template TNewKey of array-key
     * @template TNewValue
     *
     * @param callable(): Generator<TNewKey, TNewValue> $callback
     *
     * @return Collection<TNewKey, TNewValue>
     */
    public static function lazy(callable $callback): Collection
    {
        return new self($callback());
    }

    /**
     * Cria range lazy usando generator
     * 
     * @param int $start
     * @param int $end
     * @param int $step
     *
     * @return Collection<int, int>
     */
    public static function lazyRange(int $start, int $end, int $step = 1): Collection
    {
        $generator = static function() use ($start, $end, $step): Generator {
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

        return new self($generator());
    }

    /**
     * @param callable(TValue, TKey): bool $callback
     *
     * @return Collection<TKey, TValue>
     */
    public function filter(callable $callback): Collection
    {
        $newcollection = [];
        foreach ($this->getIterator() as $key => $item) {
            if ($callback($item, $key)) {
                $newcollection[$key] = $item;
            }
        }

        return new self($newcollection);
    }

    /**
     * Lazy filter - adia execução até materialização
     * 
     * @param callable(TValue, TKey): bool $callback
     *
     * @return Collection<TKey, TValue>
     */
    public function lazyFilter(callable $callback): Collection
    {
        $generator = function() use ($callback): Generator {
            foreach ($this->getIterator() as $key => $item) {
                if ($callback($item, $key)) {
                    yield $key => $item;
                }
            }
        };

        return new self($generator());
    }

    /**
     * @param callable(TValue, TKey): void $callback
     *
     * @return $this
     */
    public function each(callable $callback): static
    {
        foreach ($this->getIterator() as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /**
     * Pipeline de operações lazy - combina múltiplas transformações em uma única passagem
     * 
     * @param array<callable> $operations Array de callbacks para aplicar
     *
     * @return Collection<TKey, TValue>
     */
    public function lazyPipeline(array $operations): Collection
    {
        $generator = function() use ($operations): Generator {
            foreach ($this->getIterator() as $key => $item) {
                $value = $item;
                $shouldYield = true;

                foreach ($operations as $operation) {
                    $result = $operation($value, $key);
                    
                    // Se retornar false, pula este item (filter)
                    if ($result === false) {
                        $shouldYield = false;
                        break;
                    }
                    
                    // Se retornar um valor, usa como novo valor (map)
                    if ($result !== null && $result !== true) {
                        $value = $result;
                    }
                }

                if ($shouldYield) {
                    yield $key => $value;
                }
            }
        };

        return new self($generator());
    }

    /**
     * @param list<mixed> $array
     * @param string $key
     *
     * @return mixed
     */
    public function searchValueKey(array $array, string $key): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach ($this->arrayToGenerator($array) as $value) {
            if (is_array($value) && $result = $this->searchValueKey($value, $key)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @param array<TKey, TValue> $array
     *
     * @return Generator<TKey, TValue>
     */
    public function arrayToGenerator(array $array): Generator
    {
        yield from $array;
    }

    /**
     * @param TValue $item
     *
     * @return void
     */
    public function remove(mixed $item): void
    {
        if ($this->collection instanceof Iterator) {
            $this->collection = iterator_to_array($this->collection, true);
        }

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
     * @param string|int $key
     *
     * @return Collection<int, mixed>
     */
    public function pluck(string|int $key): Collection
    {
        $result = [];
        foreach ($this->getIterator() as $item) {
            if (is_array($item) && array_key_exists($key, $item)) {
                $result[] = $item[$key];
            } elseif (is_object($item) && property_exists($item, $key)) {
                $result[] = $item->{$key};
            }
        }

        return new self($result);
    }

    /**
     * @return Collection<int, TKey>
     */
    public function keys(): Collection
    {
        $keys = [];
        foreach ($this->getIterator() as $key => $item) {
            $keys[] = $key;
        }

        return new self($keys);
    }

    /**
     * @return Collection<int, TValue>
     */
    public function values(): Collection
    {
        $values = [];
        foreach ($this->getIterator() as $item) {
            $values[] = $item;
        }

        return new self($values);
    }

    /**
     * @return Collection<TKey, TValue>
     */
    public function unique(): Collection
    {
        if (is_array($this->collection)) {
            return new self(array_unique($this->collection, SORT_REGULAR));
        }

        $seen = [];
        $result = [];
        foreach ($this->getIterator() as $key => $item) {
            $hash = serialize($item);
            if (!in_array($hash, $seen, true)) {
                $seen[] = $hash;
                $result[$key] = $item;
            }
        }

        return new self($result);
    }

    /**
     * @return Collection<TKey, TValue>
     */
    public function reverse(): Collection
    {
        if (is_array($this->collection)) {
            return new self(array_reverse($this->collection, true));
        }

        return new self(array_reverse(iterator_to_array($this->getIterator(), true), true));
    }

    /**
     * @param int $size
     *
     * @return Collection<int, Collection<TKey, TValue>>
     */
    public function chunk(int $size): Collection
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('Chunk size must be greater than 0');
        }

        $chunks = [];
        $chunk = [];
        $count = 0;

        foreach ($this->getIterator() as $key => $item) {
            $chunk[$key] = $item;
            $count++;

            if ($count === $size) {
                $chunks[] = new self($chunk);
                $chunk = [];
                $count = 0;
            }
        }

        if (!empty($chunk)) {
            $chunks[] = new self($chunk);
        }

        return new self($chunks);
    }

    /**
     * Lazy chunk - cria chunks sob demanda
     * 
     * @param int $size
     *
     * @return Collection<int, Collection<TKey, TValue>>
     */
    public function lazyChunk(int $size): Collection
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('Chunk size must be greater than 0');
        }

        $generator = function() use ($size): Generator {
            $chunk = [];
            $count = 0;

            foreach ($this->getIterator() as $key => $item) {
                $chunk[$key] = $item;
                $count++;

                if ($count === $size) {
                    yield new self($chunk);
                    $chunk = [];
                    $count = 0;
                }
            }

            if (!empty($chunk)) {
                yield new self($chunk);
            }
        };

        return new self($generator());
    }

    /**
     * Cria uma coleção de objetos lazy usando LazyProxyObject
     * Os objetos só são instanciados quando acessados
     * 
     * @template TObject of object
     * 
     * @param array<TKey, Closure(): TObject> $factories Array de factories para criar objetos
     *
     * @return Collection<TKey, TObject>
     */
    public function lazyObjects(array $factories): Collection
    {
        $lazyObjects = [];

        foreach ($factories as $key => $factory) {
            // Garante que é Closure
            if (!$factory instanceof Closure) {
                $factory = Closure::fromCallable($factory);
            }

            // Determina a classe do objeto a partir do primeiro objeto criado
            // Nota: Em produção, você pode querer passar a classe explicitamente
            $tempObject = $factory();
            $className = get_class($tempObject);
            unset($tempObject);

            try {
                $lazyProxy = new LazyProxyObject($className);
                $lazyObjects[$key] = $lazyProxy->lazyProxy($factory);
            } catch (\ReflectionException $e) {
                // Fallback: usa o factory diretamente
                $lazyObjects[$key] = $factory();
            }
        }

        return new self($lazyObjects);
    }

    /**
     * @return float|null
     */
    public function avg(): ?float
    {
        $count = $this->count();
        return $count > 0 ? $this->sum() / $count : null;
    }

    /**
     * Materializa a coleção lazy em array
     * Útil quando você precisa garantir que todas operações lazy foram executadas
     * 
     * @return Collection<TKey, TValue>
     */
    public function materialize(): Collection
    {
        // Se já é array e está cacheado, retorna uma nova instância
        if ($this->cachedArray !== null) {
            return new self($this->cachedArray);
        }

        // Força a materialização
        $array = $this->toArray();
        return new self($array);
    }

    /**
     * Verifica se a coleção está usando avaliação lazy
     * 
     * @return bool
     */
    public function isLazy(): bool
    {
        return $this->collection instanceof Generator;
    }

    /**
     * @return int|float
     */
    public function sum(): int|float
    {
        return $this->reduce(static fn($carry, $item) => $carry + (is_numeric($item) ? $item : 0), 0);
    }

    /**
     * @template TReduce
     *
     * @param callable(TReduce, TValue, TKey): TReduce $callback
     * @param TReduce $initial
     *
     * @return TReduce
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($this->getIterator() as $key => $item) {
            $carry = $callback($carry, $item, $key);
        }

        return $carry;
    }

    /**
     * @return mixed
     */
    public function min(): mixed
    {
        $min = null;
        foreach ($this->getIterator() as $item) {
            if ($min === null || $item < $min) {
                $min = $item;
            }
        }

        return $min;
    }

    /**
     * @return mixed
     */
    public function max(): mixed
    {
        $max = null;
        foreach ($this->getIterator() as $item) {
            if ($max === null || $item > $max) {
                $max = $item;
            }
        }

        return $max;
    }

    /**
     * @param callable(TValue, TValue): int $callback
     *
     * @return Collection<TKey, TValue>
     */
    public function sort(callable $callback): Collection
    {
        $array = $this->toArray();
        uasort($array, $callback);

        return new self($array);
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
        return $this->cachedArray = iterator_to_array($this, true);
    }

    /**
     * @return Collection<TKey, TValue>
     */
    public function sortKeys(): Collection
    {
        $array = $this->toArray();
        ksort($array);

        return new self($array);
    }

    /**
     * @param int $offset
     * @param int|null $limit
     *
     * @return Collection<TKey, TValue>
     */
    public function take(int $limit): Collection
    {
        if ($limit < 0) {
            return $this->slice($limit);
        }

        return $this->slice(0, $limit);
    }

    /**
     * Take lazy - pega N elementos sem materializar toda a coleção
     * 
     * @param int $limit
     *
     * @return Collection<TKey, TValue>
     */
    public function lazyTake(int $limit): Collection
    {
        if ($limit < 0) {
            // Para limites negativos, precisamos materializar
            return $this->slice($limit);
        }

        $generator = function() use ($limit): Generator {
            $count = 0;
            foreach ($this->getIterator() as $key => $item) {
                if ($count >= $limit) {
                    break;
                }
                yield $key => $item;
                $count++;
            }
        };

        return new self($generator());
    }

    /**
     * @param int $offset
     * @param int|null $length
     *
     * @return Collection<TKey, TValue>
     */
    public function slice(int $offset, ?int $length = null): Collection
    {
        return new self(array_slice($this->toArray(), $offset, $length, true));
    }

    /**
     * @param TValue $value
     *
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        foreach ($this->getIterator() as $item) {
            if ($item === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TKey $offset
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        if (is_array($this->collection)) {
            return array_key_exists($offset, $this->collection);
        }

        $array = iterator_to_array($this->getIterator(), true);
        return array_key_exists($offset, $array);
    }

    /**
     * @param TKey $offset
     *
     * @return TValue|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }

    /**
     * @param TKey $key
     *
     * @return TValue|null
     */
    public function getAttribute(mixed $key): mixed
    {
        if (is_array($this->collection)) {
            return $this->collection[$key] ?? null;
        }

        return null;
    }

    /**
     * ArrayAccess implementation
     */

    /**
     * @param TKey|null $offset
     * @param TValue $value
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->add($value);
        } else {
            $this->setAttribute($offset, $value);
        }
    }

    /**
     * @param TValue $item
     *
     * @return void
     */
    public function add(mixed $item): void
    {
        if ($this->collection instanceof Iterator) {
            $this->collection = iterator_to_array($this->collection, true);
        }

        $this->collection[] = $item;
        $this->invalidateCache();
    }

    /**
     * @param TKey $key
     * @param TValue $value
     *
     * @return void
     */
    public function setAttribute(mixed $key, mixed $value): void
    {
        if (is_array($this->collection)) {
            $this->collection[$key] = $value;
            $this->invalidateCache();
        }
    }

    /**
     * @param TKey $offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        if (is_array($this->collection) && array_key_exists($offset, $this->collection)) {
            unset($this->collection[$offset]);
            $this->invalidateCache();
        }
    }
}
