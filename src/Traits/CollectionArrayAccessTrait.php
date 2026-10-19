<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Traits;

/**
 * Trait para implementação de ArrayAccess na Collection
 *
 * @template TKey of array-key
 * @template TValue
 */
trait CollectionArrayAccessTrait
{
    /**
     * @param TKey $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        if (is_array($this->collection)) {
            return array_key_exists($offset, $this->collection);
        }

        foreach ($this->getIterator() as $key => $value) {
            if ($key === $offset) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TKey $offset
     * @return TValue|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }

    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function getAttribute(mixed $key): mixed
    {
        if (is_array($this->collection)) {
            return $this->collection[$key] ?? null;
        }

        foreach ($this->getIterator() as $k => $value) {
            if ($k === $key) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param TKey|null $offset
     * @param TValue $value
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
     * @return void
     */
    public function add(mixed $item): void
    {
        $this->realizeCollection();

        $this->collection[] = $item;
        $this->invalidateCache();
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @return void
     */
    public function setAttribute(mixed $key, mixed $value): void
    {
        $this->realizeCollection();

        $this->collection[$key] = $value;
        $this->invalidateCache();
    }

    /**
     * @param TKey $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->realizeCollection();

        if (array_key_exists($offset, $this->collection)) {
            unset($this->collection[$offset]);
            $this->invalidateCache();
        }
    }
}
