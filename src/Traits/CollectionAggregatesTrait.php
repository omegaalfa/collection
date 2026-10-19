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
     * Média dos valores numéricos (elementos não numéricos são ignorados)
     *
     * @return float|null
     */
    public function avg(): ?float
    {
        $sum = 0;
        $count = 0;

        foreach ($this->getIterator() as $item) {
            if (is_numeric($item)) {
                $sum += $item;
                $count++;
            }
        }

        return $count > 0 ? $sum / $count : null;
    }

    /**
     * Soma dos valores numéricos (elementos não numéricos contam como 0)
     *
     * @return int|float
     */
    public function sum(): int|float
    {
        if (is_array($this->collection)) {
            $sum = 0;
            foreach ($this->collection as $item) {
                if (is_numeric($item)) {
                    $sum += $item;
                }
            }

            return $sum;
        }

        return $this->reduce(static fn($carry, $item) => $carry + (is_numeric($item) ? $item : 0), 0);
    }

    /**
     * @return mixed
     */
    public function min(): mixed
    {
        $array = $this->toArray();
        return empty($array) ? null : min($array);
    }

    /**
     * @return mixed
     */
    public function max(): mixed
    {
        $array = $this->toArray();
        return empty($array) ? null : max($array);
    }
}
