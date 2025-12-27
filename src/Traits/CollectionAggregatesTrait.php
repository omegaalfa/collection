<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Traits;

/**
 * Trait para operações de agregação (sum, avg, min, max)
 * 
 * @template TKey of array-key
 * @template TValue
 */
trait CollectionAggregatesTrait
{
    /**
     * @return float|null
     */
    public function avg(): ?float
    {
        $count = $this->count();
        return $count > 0 ? $this->sum() / $count : null;
    }

    /**
     * @return int|float
     */
    public function sum(): int|float
    {
        // Fast path: use native array_sum for arrays
        if (is_array($this->collection)) {
            return array_sum($this->collection);
        }

        // Fallback for iterators
        return $this->reduce(static fn($carry, $item) => $carry + (is_numeric($item) ? $item : 0), 0);
    }

    /**
     * @return mixed
     */
    public function min(): mixed
    {
        // Fast path: use native min() for arrays
        $array = $this->toArray();
        return empty($array) ? null : min($array);
    }

    /**
     * @return mixed
     */
    public function max(): mixed
    {
        // Fast path: use native max() for arrays
        $array = $this->toArray();
        return empty($array) ? null : max($array);
    }
}
