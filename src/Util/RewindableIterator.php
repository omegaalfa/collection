<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Util;

use Iterator;

/**
 * Iterator que memoriza os itens conforme são consumidos e permite
 * reiteração (rewind) sobre o que já foi lido — inclusive depois de o
 * iterator subjacente (ex.: Generator) ter sido parcialmente consumido.
 *
 * O buffer é um array flat [key, value, key, value, ...] para manter o
 * overhead baixo em passagens únicas sobre fontes lazy.
 *
 * @template TKey of array-key
 * @template TValue
 * @implements Iterator<TKey, TValue>
 */
final class RewindableIterator implements Iterator
{
    /**
     * @var list<TKey|TValue> buffer flat: [key, value, key, value, ...]
     */
    private array $buffer = [];

    /**
     * Quantidade de pares (key, value) no buffer
     */
    private int $buffered = 0;

    /**
     * Posição do par atual durante replay do buffer
     */
    private int $replayPos = 0;

    private bool $replaying = false;

    private bool $sourceExhausted = false;

    private bool $started = false;

    /**
     * @var TKey|null
     */
    private mixed $currentKey = null;

    /**
     * @var TValue|null
     */
    private mixed $currentValue = null;

    private bool $valid = false;

    /**
     * @param Iterator<TKey, TValue> $source
     */
    public function __construct(private readonly Iterator $source)
    {
    }

    public function rewind(): void
    {
        if (!$this->started && !$this->sourceExhausted) {
            $this->started = true;
            $this->source->rewind();
            $this->replaying = false;
            $this->captureCurrentSource();
            return;
        }

        // Fonte já consumida (total ou parcialmente): replay do buffer
        $this->replaying = true;
        $this->replayPos = 0;

        if ($this->buffered > 0) {
            $this->currentKey = $this->buffer[0];
            $this->currentValue = $this->buffer[1];
            $this->valid = true;
        } else {
            $this->valid = false;
        }
    }

    public function valid(): bool
    {
        return $this->valid;
    }

    public function key(): mixed
    {
        return $this->currentKey;
    }

    public function current(): mixed
    {
        return $this->currentValue;
    }

    public function next(): void
    {
        $advancedSource = false;
        if ($this->replaying) {
            $this->replayPos++;

            if ($this->replayPos < $this->buffered) {
                $offset = $this->replayPos * 2;
                $this->currentKey = $this->buffer[$offset];
                $this->currentValue = $this->buffer[$offset + 1];
                $this->valid = true;
                return;
            }

            // Buffer esgotado no meio do replay: continua da fonte, se houver.
            $this->replaying = false;
            if ($this->sourceExhausted) {
                $this->valid = false;
                return;
            }

            $this->source->next();
            $advancedSource = true;
        }

        if (!$this->replaying && !$advancedSource) {
            $this->source->next();
        }
        $this->pullFromSource();
    }

    private function pullFromSource(): void
    {
        if ($this->sourceExhausted) {
            $this->valid = false;
            return;
        }

        if (!$this->source->valid()) {
            $this->sourceExhausted = true;
            $this->valid = false;
            return;
        }

        $this->captureCurrentSource();
    }

    private function captureCurrentSource(): void
    {
        if (!$this->source->valid()) {
            $this->sourceExhausted = true;
            $this->valid = false;
            return;
        }

        $this->currentKey = $this->source->key();
        $this->currentValue = $this->source->current();
        $this->buffer[] = $this->currentKey;
        $this->buffer[] = $this->currentValue;
        $this->buffered++;
        $this->valid = true;
    }
}
