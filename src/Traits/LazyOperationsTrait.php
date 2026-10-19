<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Traits;

use Closure;
use Generator;
use Omegaalfa\Collection\Util\RegenerableIterator;

/**
 * Trait para operações lazy (avaliação preguiçosa)
 *
 * @template TKey of array-key
 * @template TValue
 */
trait LazyOperationsTrait
{
    /**
     * Lazy map - adia execução até materialização
     *
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $callback
     * @return self<TKey, TNewValue>
     */
    public function lazyMap(callable $callback): self
    {
        $generator = function () use ($callback): Generator {
            foreach ($this->getIterator() as $key => $item) {
                yield $key => $callback($item, $key);
            }
        };

        return new self(new RegenerableIterator($generator));
    }

    /**
     * Lazy filter - adia execução até materialização
     *
     * @param callable(TValue, TKey): bool $callback
     * @return self<TKey, TValue>
     */
    public function lazyFilter(callable $callback): self
    {
        $generator = function () use ($callback): Generator {
            foreach ($this->getIterator() as $key => $item) {
                if ($callback($item, $key)) {
                    yield $key => $item;
                }
            }
        };

        return new self(new RegenerableIterator($generator));
    }

    /**
     * Pipeline de operações lazy - combina múltiplas transformações em uma única passagem
     *
     * @param array<array{string, callable|int}|callable> $operations Array de operações [método, callback|valor] ou callbacks
     * @return self<TKey, TValue>
     */
    public function lazyPipeline(array $operations): self
    {
        foreach ($operations as $operation) {
            if (is_array($operation) && count($operation) === 2) {
                [$method, $param] = $operation;
                if (($method === 'take' || $method === 'skip') && !is_int($param)) {
                    throw new \InvalidArgumentException("Parameter for '{$method}' must be an integer");
                }
                if ($method !== 'take' && $method !== 'skip' && !is_callable($param)) {
                    throw new \InvalidArgumentException("Second array member must be callable for method '{$method}'");
                }
                if (!in_array($method, ['map', 'filter', 'take', 'skip'], true)) {
                    throw new \InvalidArgumentException("Unknown operation: {$method}");
                }
            } elseif (!is_callable($operation)) {
                throw new \InvalidArgumentException('Invalid operation format');
            }
        }

        $factory = function () use ($operations): Generator {
            $iterator = $this->getIterator();

            foreach ($operations as $operation) {
            if (is_array($operation) && count($operation) === 2) {
                [$method, $param] = $operation;

                // Valida o parâmetro conforme o método
                if ($method === 'take' || $method === 'skip') {
                    if (!is_int($param)) {
                        throw new \InvalidArgumentException("Parameter for '{$method}' must be an integer");
                    }
                } elseif (!is_callable($param)) {
                    throw new \InvalidArgumentException("Second array member must be callable for method '{$method}'");
                }

                // Aplica a operação correspondente
                $generator = match ($method) {
                    'map' => static function () use ($iterator, $param): Generator {
                        foreach ($iterator as $key => $item) {
                            yield $key => $param($item, $key);
                        }
                    },
                    'filter' => static function () use ($iterator, $param): Generator {
                        foreach ($iterator as $key => $item) {
                            if ($param($item, $key)) {
                                yield $key => $item;
                            }
                        }
                    },
                    'take' => static function () use ($iterator, $param): Generator {
                        if ($param <= 0) {
                            return;
                        }
                        $count = 0;
                        foreach ($iterator as $key => $item) {
                            yield $key => $item;
                            if (++$count >= $param) {
                                return;
                            }
                        }
                    },
                    'skip' => static function () use ($iterator, $param): Generator {
                        $count = 0;
                        foreach ($iterator as $key => $item) {
                            if ($count >= $param) {
                                yield $key => $item;
                            }
                            $count++;
                        }
                    },
                    default => throw new \InvalidArgumentException("Unknown operation: {$method}")
                };

                $iterator = $generator();
            } elseif (is_callable($operation)) {
                // Fallback para callbacks diretos
                $generator = function () use ($iterator, $operation): Generator {
                    foreach ($iterator as $key => $item) {
                        $result = $operation($item, $key);
                        if ($result === false) {
                            continue;
                        }
                        yield $key => ($result !== null && $result !== true) ? $result : $item;
                    }
                };
                $iterator = $generator();
            } else {
                throw new \InvalidArgumentException("Invalid operation format");
            }
            }

            yield from $iterator;
        };

        return new self(new RegenerableIterator($factory));
    }

    /**
     * Slice lazy para offsets não negativos. Offsets negativos mantêm a
     * semântica de slice() e exigem materialização da fonte.
     *
     * @param int $offset
     * @param int|null $length
     * @return self<TKey, TValue>
     */
    public function lazySlice(int $offset, ?int $length = null): self
    {
        if ($offset < 0 || ($length !== null && $length < 0)) {
            return $this->slice($offset, $length);
        }

        $generator = function () use ($offset, $length): Generator {
            $index = 0;
            $yielded = 0;
            foreach ($this->getIterator() as $key => $item) {
                if ($index++ < $offset) {
                    continue;
                }
                if ($length !== null && $yielded >= $length) {
                    break;
                }
                yield $key => $item;
                if (++$yielded >= $length && $length !== null) {
                    return;
                }
            }
        };

        return new self(new RegenerableIterator($generator));
    }

    /**
     * Remove duplicados sem materializar a coleção resultante.
     * A tabela de chaves vistas continua sendo O(n), como em unique().
     *
     * @return self<TKey, TValue>
     */
    public function lazyUnique(): self
    {
        $generator = function () : Generator {
            $seen = [];
            foreach ($this->getIterator() as $key => $item) {
                $hash = $this->lazyValueHash($item);
                if (isset($seen[$hash])) {
                    continue;
                }
                $seen[$hash] = true;
                yield $key => $item;
            }
        };

        return new self(new RegenerableIterator($generator));
    }

    /**
     * Extrai uma chave de arrays/objetos sob demanda.
     *
     * @param string|int $key
     * @return self<int, mixed>
     */
    public function lazyPluck(string|int $key): self
    {
        $generator = function () use ($key): Generator {
            foreach ($this->getIterator() as $item) {
                if (is_array($item) && array_key_exists($key, $item)) {
                    yield $item[$key];
                    continue;
                }
                if (is_object($item)) {
                    $vars = get_object_vars($item);
                    if (array_key_exists($key, $vars)) {
                        yield $vars[$key];
                    }
                }
            }
        };

        return new self(new RegenerableIterator($generator));
    }

    private function lazyValueHash(mixed $value): string
    {
        try {
            return serialize($value);
        } catch (\Throwable) {
            return is_object($value)
                ? 'object:' . get_class($value) . ':' . spl_object_id($value)
                : get_debug_type($value) . ':' . var_export($value, true);
        }
    }

    /**
     * Lazy chunk - cria chunks sob demanda
     *
     * @param int $size
     * @return self<int, self<TKey, TValue>>
     */
    public function lazyChunk(int $size): self
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('Chunk size must be greater than 0');
        }

        $generator = function () use ($size): Generator {
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

        return new self(new RegenerableIterator($generator));
    }

    /**
     * Take lazy - pega N elementos sem materializar toda a coleção
     *
     * @param int $limit
     * @return self<TKey, TValue>
     */
    public function lazyTake(int $limit): self
    {
        if ($limit < 0) {
            // Para limites negativos, precisamos materializar
            return $this->slice($limit);
        }

        $generator = function () use ($limit): Generator {
            if ($limit <= 0) {
                return;
            }
            $count = 0;
            foreach ($this->getIterator() as $key => $item) {
                yield $key => $item;
                if (++$count >= $limit) {
                    return;
                }
            }
        };

        return new self(new RegenerableIterator($generator));
    }

    /**
     * Cria uma coleção de objetos lazy usando LazyProxyObject
     * Os objetos só são instanciados quando acessados
     *
     * @template TObject of object
     * @param array<TKey, callable(): TObject> $factories Array de factories para criar objetos
     * @return self<TKey, TObject>
     */
    public function lazyObjects(array $factories): self
    {
        $lazyObjects = [];

        foreach ($factories as $key => $factory) {
            // Garante que é Closure
            if (!$factory instanceof Closure) {
                $factory = Closure::fromCallable($factory);
            }

            $className = $this->resolveFactoryClass($factory);

            try {
                $lazyProxy = new \Omegaalfa\Collection\Util\LazyProxyObject($className);
                $lazyObjects[$key] = $lazyProxy->lazyProxy($factory);
            } catch (\ReflectionException) {
                // Fallback: classe não suporta lazy objects — instancia direto
                $lazyObjects[$key] = $factory();
            }
        }

        /** @var self<TKey, TObject> */
        return new self($lazyObjects);
    }

    /**
     * Determina a classe de retorno do factory sem instanciar o objeto.
     * Só recorre à instanciação temporária quando o tipo de retorno é desconhecido.
     *
     * @param Closure $factory
     * @return class-string
     */
    private function resolveFactoryClass(Closure $factory): string
    {
        $returnType = (new \ReflectionFunction($factory))->getReturnType();

        if ($returnType instanceof \ReflectionNamedType && !$returnType->isBuiltin()) {
            return $returnType->getName();
        }

        // Fallback: instancia temporariamente para descobrir a classe
        $tempObject = $factory();
        $className = get_class($tempObject);
        unset($tempObject);

        return $className;
    }

    /**
     * Verifica se a coleção está usando avaliação lazy
     *
     * @return bool
     */
    public function isLazy(): bool
    {
        return $this->collection instanceof Generator || $this->collection instanceof RegenerableIterator;
    }

    /**
     * Materializa a coleção lazy em array
     * Útil quando você precisa garantir que todas operações lazy foram executadas
     *
     * @return self<TKey, TValue>
     */
    public function materialize(): self
    {
        // Se já é array e está cacheado, retorna uma nova instância
        if ($this->cachedArray !== null) {
            return new self($this->cachedArray);
        }

        // Força a materialização
        $array = $this->toArray();
        return new self($array);
    }
}
