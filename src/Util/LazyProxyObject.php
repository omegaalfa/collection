<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Util;

use Closure;
use ReflectionClass;
use ReflectionException;

/**
 * Lazy object proxy using PHP 8.4+ lazy initialization features
 * 
 * @template T of object
 */
class LazyProxyObject
{
	/**
	 * @var ReflectionClass<T>
	 */
	protected ReflectionClass $class;

	/**
	 * @param class-string<T> $class
	 * @throws ReflectionException
	 */
	public function __construct(string $class)
	{
		$this->class = new ReflectionClass($class);
	}


	/**
	 * Create a lazy proxy that initializes on first method call
	 * 
	 * @param Closure(): T $factory
	 * @return T
	 */
	public function lazyProxy(Closure $factory): object
	{
		return $this->class->newLazyProxy($factory);
	}

	/**
	 * Create a lazy ghost that initializes on first property access
	 * 
	 * @param Closure(T): void $factory
	 * @return T
	 */
	public function lazyGhost(Closure $factory): object
	{
		return $this->class->newLazyGhost($factory);
	}
}
