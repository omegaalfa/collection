<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Traits;

use Closure;
use Generator;

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

        return new self($generator());
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

        return new self($generator());
    }

    /**
     * Pipeline de operações lazy - combina múltiplas transformações em uma única passagem
     *
     * @param array<array{string, callable|int}|callable> $operations Array de operações [método, callback|valor] ou callbacks
     * @return self<TKey, TValue>
     */
    public function lazyPipeline(array $operations): self
    {
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
                        $count = 0;
                        foreach ($iterator as $key => $item) {
                            if ($count >= $param) {
                                break;
                            }
                            yield $key => $item;
                            $count++;
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

        return new self($iterator);
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

        return new self($generator());
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
     * Cria uma coleção de objetos lazy usando LazyProxyObject
     * Os objetos só são instanciados quando acessados
     *
     * @template TObject of object
     * @param array<TKey, Closure(): TObject> $factories Array de factories para criar objetos
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

            // Determina a classe do objeto a partir do primeiro objeto criado
            $tempObject = $factory();
            $className = get_class($tempObject);
            unset($tempObject);

            try {
                $lazyProxy = new \Omegaalfa\Collection\Util\LazyProxyObject($className);
                $lazyObjects[$key] = $lazyProxy->lazyProxy($factory);
            } catch (\ReflectionException $e) {
                // Fallback: usa o factory diretamente
                $lazyObjects[$key] = $factory();
            }
        }

        return new self($lazyObjects);
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
