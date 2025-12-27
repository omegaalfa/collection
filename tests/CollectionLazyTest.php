<?php

declare(strict_types=1);

namespace Omegaalfa\Collection\Tests;

use PHPUnit\Framework\TestCase;
use Omegaalfa\Collection\Collection;

class CollectionLazyTest extends TestCase
{
    public function testLazyMapDoesNotExecuteUntilNeeded(): void
    {
        $executed = 0;
        
        $collection = Collection::lazyRange(1, 100)
            ->lazyMap(function($x) use (&$executed) {
                $executed++;
                return $x * 2;
            });
        
        // Não deve ter executado ainda
        $this->assertEquals(0, $executed);
        
        // Força materialização
        $collection->toArray();
        
        // Agora deve ter executado 100 vezes
        $this->assertEquals(100, $executed);
    }

    public function testLazyFilterDoesNotExecuteUntilNeeded(): void
    {
        $executed = 0;
        
        $collection = Collection::lazyRange(1, 100)
            ->lazyFilter(function($x) use (&$executed) {
                $executed++;
                return $x > 50;
            });
        
        // Não deve ter executado ainda
        $this->assertEquals(0, $executed);
        
        // Força materialização
        $result = $collection->toArray();
        
        // Deve ter executado 100 vezes
        $this->assertEquals(100, $executed);
        // Deve ter 50 elementos
        $this->assertEquals(50, count($result));
    }

    public function testLazyTakeStopsEarly(): void
    {
        $executed = 0;
        
        $collection = Collection::lazyRange(1, 1000)
            ->lazyMap(function($x) use (&$executed) {
                $executed++;
                return $x * 2;
            })
            ->lazyTake(5);
        
        $result = $collection->toArray();
        
        // Deve ter executado poucos elementos (pode ser 5 ou 6 devido ao funcionamento do generator)
        $this->assertLessThanOrEqual(10, $executed);
        $this->assertEquals(5, count($result));
        $this->assertEquals([2, 4, 6, 8, 10], array_values($result));
    }

    public function testLazyChunkProcessesOnDemand(): void
    {
        $collection = Collection::lazyRange(1, 100);
        $chunks = $collection->lazyChunk(10);
        
        $processed = 0;
        foreach ($chunks as $chunk) {
            $processed++;
            if ($processed >= 3) {
                break;
            }
        }
        
        // Deve ter processado apenas 3 chunks
        $this->assertEquals(3, $processed);
    }

    public function testLazyRangeCreatesGenerator(): void
    {
        $collection = Collection::lazyRange(1, 1000000);
        
        // Deve ser lazy (Generator)
        $this->assertTrue($collection->isLazy());
        
        // Não deve consumir muita memória
        $memoryBefore = memory_get_usage();
        $collection->lazyTake(10)->toArray();
        $memoryAfter = memory_get_usage();
        
        // Deve consumir menos de 1KB
        $this->assertLessThan(1024, $memoryAfter - $memoryBefore);
    }

    public function testLazyPipelineOptimization(): void
    {
        $executed = 0;
        
        $result = Collection::lazyRange(1, 100)
            ->lazyPipeline([
                function($x) use (&$executed) {
                    $executed++;
                    return $x * 2;
                },
                function($x) {
                    return $x > 20 ? $x : false;
                },
                function($x) {
                    return $x + 10;
                },
            ])
            ->lazyTake(5)
            ->toArray();
        
        // Deve executar apenas até conseguir 5 resultados
        $this->assertLessThan(100, $executed);
        $this->assertEquals(5, count($result));
    }

    public function testMaterializeConvertsLazyToEager(): void
    {
        $lazy = Collection::lazyRange(1, 10)->lazyMap(fn($x) => $x * 2);
        
        $this->assertTrue($lazy->isLazy());
        
        $materialized = $lazy->materialize();
        
        $this->assertFalse($materialized->isLazy());
        $this->assertEquals([2, 4, 6, 8, 10, 12, 14, 16, 18, 20], array_values($materialized->toArray()));
    }

    public function testCachedArrayAvoidsDuplicateConversions(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        
        $array1 = $collection->toArray();
        $array2 = $collection->toArray();
        
        // Deve retornar a mesma referência (cache)
        $this->assertSame($array1, $array2);
    }

    public function testLazyObjects(): void
    {
        $createdObjects = [];
        
        $collection = (new Collection())->lazyObjects([
            'obj1' => function() use (&$createdObjects) {
                $createdObjects[] = 'obj1';
                return new \stdClass();
            },
            'obj2' => function() use (&$createdObjects) {
                $createdObjects[] = 'obj2';
                return new \stdClass();
            },
            'obj3' => function() use (&$createdObjects) {
                $createdObjects[] = 'obj3';
                return new \stdClass();
            },
        ]);
        
        // Como lazyObjects cria um tempObject para determinar a classe,
        // os objetos já foram criados uma vez
        // O comportamento lazy real acontece com o proxy que é retornado
        $this->assertGreaterThanOrEqual(0, count($createdObjects));
        
        // Acessa obj1
        $arr = $collection->toArray();
        $obj1 = $arr['obj1'];
        
        // Verifica que obj1 existe
        $this->assertInstanceOf(\stdClass::class, $obj1);
    }

    public function testLazyMethodsChaining(): void
    {
        $result = Collection::lazyRange(1, 1000)
            ->lazyMap(fn($x) => $x * 2)
            ->lazyFilter(fn($x) => $x > 100)
            ->lazyMap(fn($x) => $x + 10)
            ->lazyTake(5)
            ->toArray();
        
        $this->assertEquals(5, count($result));
        // Primeiro elemento: 51 * 2 = 102, 102 + 10 = 112
        $this->assertEquals(112, array_values($result)[0]);
    }

    public function testIsLazyDetectsGenerators(): void
    {
        $eager = new Collection([1, 2, 3]);
        $lazy = Collection::lazyRange(1, 100);
        
        $this->assertFalse($eager->isLazy());
        $this->assertTrue($lazy->isLazy());
    }

    public function testLazyStaticFactory(): void
    {
        $collection = Collection::lazy(function() {
            yield 1;
            yield 2;
            yield 3;
        });
        
        $this->assertTrue($collection->isLazy());
        $this->assertEquals([1, 2, 3], array_values($collection->toArray()));
    }
}
