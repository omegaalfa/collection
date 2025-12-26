<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Omegaalfa\Collection\Collection;

echo "=== Demonstração de Lazy Evaluation na Collection ===\n\n";

// Exemplo 1: Lazy Map + Lazy Filter + Lazy Take
echo "1. Lazy Pipeline (Map + Filter + Take):\n";
echo "   Processa apenas o necessário!\n\n";

$start = microtime(true);
$memStart = memory_get_usage();

// Usando métodos lazy - processa apenas até conseguir 5 resultados
$result = Collection::lazyRange(1, 1000000)
    ->lazyMap(fn($x) => $x * 2)
    ->lazyFilter(fn($x) => $x > 100)
    ->lazyTake(5)
    ->toArray();

$memEnd = memory_get_usage();
$end = microtime(true);

echo "   Resultado: " . implode(', ', $result) . "\n";
echo "   Tempo: " . round(($end - $start) * 1000, 2) . "ms\n";
echo "   Memória: " . round(($memEnd - $memStart) / 1024, 2) . "KB\n\n";

// Comparação com métodos eager
echo "2. Comparação com métodos Eager:\n\n";

$start = microtime(true);
$memStart = memory_get_usage();

// Sem lazy - processa TODOS os 1M elementos
$result = Collection::lazyRange(1, 1000000)
    ->map(fn($x) => $x * 2)
    ->filter(fn($x) => $x > 100)
    ->take(5)
    ->toArray();

$memEnd = memory_get_usage();
$end = microtime(true);

echo "   Resultado: " . implode(', ', $result) . "\n";
echo "   Tempo: " . round(($end - $start) * 1000, 2) . "ms\n";
echo "   Memória: " . round(($memEnd - $memStart) / 1024, 2) . "KB\n\n";

// Exemplo 3: Lazy Chunks
echo "3. Lazy Chunks (processamento sob demanda):\n\n";

$collection = Collection::lazyRange(1, 100);
$chunks = $collection->lazyChunk(10);

echo "   Processando chunks sob demanda...\n";
$processed = 0;
foreach ($chunks as $chunk) {
    $processed++;
    echo "   Chunk $processed: " . implode(', ', $chunk->toArray()) . "\n";
    if ($processed >= 3) {
        echo "   (parando após 3 chunks - os outros não foram criados!)\n";
        break;
    }
}
echo "\n";

// Exemplo 4: Lazy Pipeline (múltiplas operações em uma passagem)
echo "4. Lazy Pipeline (otimização máxima):\n\n";

$start = microtime(true);

$result = Collection::lazyRange(1, 100)
    ->lazyPipeline([
        fn($x) => $x * 2,              // map
        fn($x) => $x > 20 ? $x : false, // filter
        fn($x) => $x + 10,             // map
    ])
    ->lazyTake(5)
    ->toArray();

$end = microtime(true);

echo "   Resultado: " . implode(', ', $result) . "\n";
echo "   Tempo: " . round(($end - $start) * 1000, 2) . "ms\n";
echo "   (Uma única passagem pelos dados!)\n\n";

// Exemplo 5: Lazy Objects usando LazyProxyObject
echo "5. Lazy Objects (objetos instanciados sob demanda):\n\n";

class User {
    public function __construct(
        public string $name,
        public int $age
    ) {
        echo "   -> Criando usuário: $name\n";
    }
    
    public function getName(): string {
        return $this->name;
    }
}

echo "   Criando coleção lazy de usuários...\n";

$users = (new Collection())->lazyObjects([
    'john' => fn() => new User('John Doe', 30),
    'jane' => fn() => new User('Jane Smith', 25),
    'bob' => fn() => new User('Bob Johnson', 35),
]);

echo "\n   Acessando apenas 'john'...\n";
$john = $users->toArray()['john'];
echo "   Nome: " . $john->getName() . "\n";
echo "   (jane e bob não foram criados - economia de memória!)\n\n";

// Exemplo 6: Verificando se é lazy
echo "6. Verificando tipo de avaliação:\n\n";

$eager = new Collection([1, 2, 3]);
$lazy = Collection::lazyRange(1, 100);

echo "   Coleção eager é lazy? " . ($eager->isLazy() ? 'Sim' : 'Não') . "\n";
echo "   Coleção lazy é lazy? " . ($lazy->isLazy() ? 'Sim' : 'Não') . "\n\n";

// Exemplo 7: Materializando coleção lazy
echo "7. Materializando coleção lazy:\n\n";

$lazy = Collection::lazyRange(1, 10)->lazyMap(fn($x) => $x * 2);
echo "   Antes da materialização - é lazy? " . ($lazy->isLazy() ? 'Sim' : 'Não') . "\n";

$materialized = $lazy->materialize();
echo "   Após materialização - é lazy? " . ($materialized->isLazy() ? 'Sim' : 'Não') . "\n";
echo "   Valores: " . implode(', ', $materialized->toArray()) . "\n\n";

echo "=== Benefícios dos Métodos Lazy ===\n";
echo "✅ Menor consumo de memória\n";
echo "✅ Processamento apenas do necessário\n";
echo "✅ Performance superior em grandes datasets\n";
echo "✅ Objetos instanciados sob demanda\n";
echo "✅ Pipeline otimizado de operações\n";
