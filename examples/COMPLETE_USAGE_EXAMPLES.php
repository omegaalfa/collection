<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Omegaalfa\Collection\Collection;
use Omegaalfa\Collection\Map;
use Omegaalfa\Collection\Sequence;
use Omegaalfa\Collection\LazyMap;
use Omegaalfa\Collection\LazySequence;
use Omegaalfa\Collection\LazyFileIterator;
use Omegaalfa\Collection\Util\LazyProxyObject;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║        GUIA COMPLETO DE USO - Collection Library              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// CLASSE: Collection
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. COLLECTION - Coleção genérica com suporte a iteradores\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Construtor e criação
echo "▶ __construct() - Criar coleção de array ou iterator\n";
$collection = new Collection([1, 2, 3, 4, 5]);
echo "  Coleção: " . implode(', ', $collection->toArray()) . "\n\n";

// addIterator
echo "▶ addIterator() - Adicionar/substituir iterator\n";
$collection->addIterator([10, 20, 30]);
echo "  Nova coleção: " . implode(', ', $collection->toArray()) . "\n\n";

// current
echo "▶ current() - Elemento atual\n";
$collection = new Collection([1, 2, 3]);
echo "  Elemento atual: " . $collection->current() . "\n\n";

// map (eager)
echo "▶ map() - Transformar elementos (EAGER)\n";
$doubled = $collection->map(fn($x) => $x * 2);
echo "  Original: " . implode(', ', $collection->toArray()) . "\n";
echo "  Dobrados: " . implode(', ', $doubled->toArray()) . "\n\n";

// lazyMap
echo "▶ lazyMap() - Transformar elementos (LAZY)\n";
$lazyDoubled = $collection->lazyMap(fn($x) => $x * 3);
echo "  Lazy result: " . implode(', ', $lazyDoubled->toArray()) . "\n\n";

// filter (eager)
echo "▶ filter() - Filtrar elementos (EAGER)\n";
$filtered = $collection->filter(fn($x) => $x > 1);
echo "  Filtrados (> 1): " . implode(', ', $filtered->toArray()) . "\n\n";

// lazyFilter
echo "▶ lazyFilter() - Filtrar elementos (LAZY)\n";
$lazyFiltered = $collection->lazyFilter(fn($x) => $x < 3);
echo "  Lazy filtrado (< 3): " . implode(', ', $lazyFiltered->toArray()) . "\n\n";

// each
echo "▶ each() - Executar ação em cada elemento\n";
$collection->each(function($value, $key) {
    echo "  Item[$key] = $value\n";
});
echo "\n";

// lazyPipeline
echo "▶ lazyPipeline() - Pipeline de operações lazy\n";
$result = Collection::lazyRange(1, 10)
    ->lazyPipeline([
        fn($x) => $x * 2,
        fn($x) => $x > 10 ? $x : false,
        fn($x) => $x + 5,
    ])
    ->lazyTake(3)
    ->toArray();
echo "  Pipeline result: " . implode(', ', $result) . "\n\n";

// lazy (factory)
echo "▶ lazy() - Criar coleção lazy de generator\n";
$lazyCollection = Collection::lazy(function() {
    yield 1;
    yield 2;
    yield 3;
});
echo "  Lazy: " . implode(', ', $lazyCollection->toArray()) . "\n\n";

// lazyRange
echo "▶ lazyRange() - Criar range lazy\n";
$range = Collection::lazyRange(1, 5);
echo "  Range: " . implode(', ', $range->toArray()) . "\n\n";

// searchValueKey
echo "▶ searchValueKey() - Buscar valor por chave em arrays aninhados\n";
$nested = [
    'user' => ['name' => 'John', 'address' => ['city' => 'NY']],
    'other' => ['data' => 'value']
];
$collection = new Collection([]);
$city = $collection->searchValueKey($nested, 'city');
echo "  Cidade encontrada: $city\n\n";

// arrayToGenerator
echo "▶ arrayToGenerator() - Converter array para generator\n";
$collection = new Collection([]);
$gen = $collection->arrayToGenerator([1, 2, 3]);
echo "  Generator: ";
foreach ($gen as $v) echo "$v ";
echo "\n\n";

// remove
echo "▶ remove() - Remover elemento\n";
$collection = new Collection([1, 2, 3, 2, 4]);
$collection->remove(2);
echo "  Após remover 2: " . implode(', ', $collection->toArray()) . "\n\n";

// first
echo "▶ first() - Primeiro elemento\n";
$collection = new Collection([10, 20, 30]);
echo "  Primeiro: " . $collection->first() . "\n\n";

// last
echo "▶ last() - Último elemento\n";
echo "  Último: " . $collection->last() . "\n\n";

// isEmpty / isNotEmpty
echo "▶ isEmpty() / isNotEmpty() - Verificar se está vazia\n";
$empty = new Collection([]);
$notEmpty = new Collection([1]);
echo "  Empty: " . ($empty->isEmpty() ? 'Sim' : 'Não') . "\n";
echo "  Not empty: " . ($notEmpty->isNotEmpty() ? 'Sim' : 'Não') . "\n\n";

// count
echo "▶ count() - Contar elementos\n";
echo "  Total: " . $collection->count() . "\n\n";

// pluck
echo "▶ pluck() - Extrair valores de uma chave\n";
$users = new Collection([
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
]);
$names = $users->pluck('name');
echo "  Nomes: " . implode(', ', $names->toArray()) . "\n\n";

// keys
echo "▶ keys() - Obter chaves\n";
$collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
echo "  Chaves: " . implode(', ', $collection->keys()->toArray()) . "\n\n";

// values
echo "▶ values() - Obter valores\n";
echo "  Valores: " . implode(', ', $collection->values()->toArray()) . "\n\n";

// unique
echo "▶ unique() - Remover duplicatas\n";
$collection = new Collection([1, 2, 2, 3, 3, 3, 4]);
echo "  Únicos: " . implode(', ', $collection->unique()->toArray()) . "\n\n";

// reverse
echo "▶ reverse() - Reverter ordem\n";
$collection = new Collection([1, 2, 3, 4]);
echo "  Reverso: " . implode(', ', $collection->reverse()->toArray()) . "\n\n";

// chunk
echo "▶ chunk() - Dividir em chunks (EAGER)\n";
$collection = new Collection(range(1, 10));
$chunks = $collection->chunk(3);
foreach ($chunks as $i => $chunk) {
    echo "  Chunk $i: " . implode(', ', $chunk->toArray()) . "\n";
}
echo "\n";

// lazyChunk
echo "▶ lazyChunk() - Dividir em chunks (LAZY)\n";
$lazyChunks = Collection::lazyRange(1, 9)->lazyChunk(3);
$count = 0;
foreach ($lazyChunks as $chunk) {
    $count++;
    echo "  Chunk $count: " . implode(', ', $chunk->toArray()) . "\n";
}
echo "\n";

// lazyObjects
echo "▶ lazyObjects() - Criar objetos lazy com LazyProxyObject\n";
class DemoUser {
    public function __construct(public string $name) {
        echo "  -> Criando DemoUser: $name\n";
    }
}
$lazyUsers = (new Collection())->lazyObjects([
    'john' => fn() => new DemoUser('John'),
    'jane' => fn() => new DemoUser('Jane'),
]);
echo "  Acessando john...\n";
$john = $lazyUsers->toArray()['john'];
echo "  Nome: {$john->name}\n\n";

// avg
echo "▶ avg() - Média\n";
$collection = new Collection([10, 20, 30, 40]);
echo "  Média: " . $collection->avg() . "\n\n";

// materialize
echo "▶ materialize() - Materializar coleção lazy\n";
$lazy = Collection::lazyRange(1, 5);
$materialized = $lazy->materialize();
echo "  É lazy antes: " . ($lazy->isLazy() ? 'Sim' : 'Não') . "\n";
echo "  É lazy depois: " . ($materialized->isLazy() ? 'Sim' : 'Não') . "\n\n";

// isLazy
echo "▶ isLazy() - Verificar se é lazy\n";
$eager = new Collection([1, 2, 3]);
$lazy = Collection::lazyRange(1, 10);
echo "  Eager é lazy: " . ($eager->isLazy() ? 'Sim' : 'Não') . "\n";
echo "  Lazy é lazy: " . ($lazy->isLazy() ? 'Sim' : 'Não') . "\n\n";

// sum
echo "▶ sum() - Soma\n";
$collection = new Collection([10, 20, 30]);
echo "  Soma: " . $collection->sum() . "\n\n";

// reduce
echo "▶ reduce() - Reduzir a um valor\n";
$product = $collection->reduce(fn($carry, $item) => $carry * $item, 1);
echo "  Produto: $product\n\n";

// min / max
echo "▶ min() / max() - Mínimo e máximo\n";
$collection = new Collection([5, 2, 9, 1, 7]);
echo "  Mínimo: " . $collection->min() . "\n";
echo "  Máximo: " . $collection->max() . "\n\n";

// sort
echo "▶ sort() - Ordenar\n";
$sorted = $collection->sort(fn($a, $b) => $a <=> $b);
echo "  Ordenado: " . implode(', ', $sorted->toArray()) . "\n\n";

// sortKeys
echo "▶ sortKeys() - Ordenar por chaves\n";
$collection = new Collection(['c' => 3, 'a' => 1, 'b' => 2]);
$sortedKeys = $collection->sortKeys();
echo "  Ordenado por chaves: ";
foreach ($sortedKeys as $k => $v) echo "$k=>$v ";
echo "\n\n";

// take
echo "▶ take() - Pegar N elementos\n";
$collection = new Collection(range(1, 10));
$taken = $collection->take(3);
echo "  Primeiros 3: " . implode(', ', $taken->toArray()) . "\n\n";

// lazyTake
echo "▶ lazyTake() - Pegar N elementos (LAZY)\n";
$lazyTaken = Collection::lazyRange(1, 1000)->lazyTake(5);
echo "  Lazy primeiros 5: " . implode(', ', $lazyTaken->toArray()) . "\n\n";

// slice
echo "▶ slice() - Fatiar coleção\n";
$collection = new Collection(range(1, 10));
$sliced = $collection->slice(2, 4);
echo "  Slice(2, 4): " . implode(', ', $sliced->toArray()) . "\n\n";

// contains
echo "▶ contains() - Verificar se contém valor\n";
$collection = new Collection([1, 2, 3, 4]);
echo "  Contém 3: " . ($collection->contains(3) ? 'Sim' : 'Não') . "\n";
echo "  Contém 10: " . ($collection->contains(10) ? 'Sim' : 'Não') . "\n\n";

// ArrayAccess: offsetExists, offsetGet, offsetSet, offsetUnset
echo "▶ ArrayAccess - Usar como array\n";
$collection = new Collection(['a' => 1, 'b' => 2]);
echo "  collection['a'] = " . $collection['a'] . "\n";
$collection['c'] = 3;
echo "  Após collection['c'] = 3: " . implode(', ', $collection->toArray()) . "\n";
echo "  Existe 'b': " . (isset($collection['b']) ? 'Sim' : 'Não') . "\n";
unset($collection['b']);
echo "  Após unset('b'): " . implode(', ', $collection->toArray()) . "\n\n";

// add
echo "▶ add() - Adicionar elemento\n";
$collection = new Collection([1, 2]);
$collection->add(3);
echo "  Após add(3): " . implode(', ', $collection->toArray()) . "\n\n";

// setAttribute / getAttribute
echo "▶ setAttribute() / getAttribute() - Atributos\n";
$collection = new Collection(['a' => 1]);
$collection->setAttribute('b', 2);
echo "  Após setAttribute('b', 2): b = " . $collection->getAttribute('b') . "\n\n";

// toArray
echo "▶ toArray() - Converter para array\n";
$collection = new Collection([1, 2, 3]);
$array = $collection->toArray();
echo "  Array: " . implode(', ', $array) . "\n\n";

// getIterator
echo "▶ getIterator() - Obter iterador\n";
$collection = new Collection([1, 2, 3]);
echo "  Iterando: ";
foreach ($collection->getIterator() as $item) {
    echo "$item ";
}
echo "\n\n";

// ============================================================================
// CLASSE: Sequence (Imutável)
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "2. SEQUENCE - Lista ordenada imutável\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// empty
echo "▶ Sequence::empty() - Sequência vazia\n";
$empty = Sequence::empty();
echo "  Vazia: " . ($empty->isEmpty() ? 'Sim' : 'Não') . "\n\n";

// of
echo "▶ Sequence::of() - Criar de valores\n";
$seq = Sequence::of(1, 2, 3, 4, 5);
echo "  Sequência: " . $seq->join(', ') . "\n\n";

// range
echo "▶ Sequence::range() - Criar range\n";
$range = Sequence::range(1, 5);
echo "  Range: " . $range->join(', ') . "\n\n";

// at
echo "▶ at() - Elemento na posição\n";
echo "  at(2) = " . $seq->at(2) . "\n\n";

// first / last
echo "▶ first() / last() - Primeiro e último\n";
echo "  Primeiro: " . $seq->first() . "\n";
echo "  Último: " . $seq->last() . "\n\n";

// contains
echo "▶ contains() - Contém valor\n";
echo "  Contém 3: " . ($seq->contains(3) ? 'Sim' : 'Não') . "\n\n";

// indexOf
echo "▶ indexOf() - Índice do valor\n";
$index = $seq->indexOf(4);
echo "  Índice de 4: " . ($index !== null ? $index : 'null') . "\n\n";

// append / prepend
echo "▶ append() / prepend() - Adicionar no fim/início\n";
$appended = $seq->append(6);
$prepended = $seq->prepend(0);
echo "  Appended: " . $appended->join(', ') . "\n";
echo "  Prepended: " . $prepended->join(', ') . "\n\n";

// insert
echo "▶ insert() - Inserir em posição\n";
$inserted = $seq->insert(2, 99);
echo "  Insert 99 at 2: " . $inserted->join(', ') . "\n\n";

// remove
echo "▶ remove() - Remover por índice\n";
$removed = $seq->remove(2);
echo "  Remove at 2: " . $removed->join(', ') . "\n\n";

// map
echo "▶ map() - Transformar\n";
$mapped = $seq->map(fn($x) => $x * 10);
echo "  Mapped (*10): " . $mapped->join(', ') . "\n\n";

// filter
echo "▶ filter() - Filtrar\n";
$filtered = $seq->filter(fn($x) => $x % 2 == 0);
echo "  Filtered (pares): " . $filtered->join(', ') . "\n\n";

// flatMap
echo "▶ flatMap() - Map + flatten\n";
$flatMapped = $seq->flatMap(fn($x) => [$x, $x * 2]);
echo "  FlatMapped: " . $flatMapped->join(', ') . "\n\n";

// reduce
echo "▶ reduce() - Reduzir\n";
$sum = $seq->reduce(fn($carry, $x) => $carry + $x, 0);
echo "  Soma: $sum\n\n";

// each
echo "▶ each() - Para cada elemento\n";
$seq->each(fn($x, $i) => print("  [$i] = $x\n"));
echo "\n";

// take
echo "▶ take() - Pegar N elementos\n";
$taken = $seq->take(3);
echo "  Take 3: " . $taken->join(', ') . "\n\n";

// skip
echo "▶ skip() - Pular N elementos\n";
$skipped = $seq->skip(2);
echo "  Skip 2: " . $skipped->join(', ') . "\n\n";

// slice
echo "▶ slice() - Fatiar\n";
$sliced = $seq->slice(1, 3);
echo "  Slice(1, 3): " . $sliced->join(', ') . "\n\n";

// unique
echo "▶ unique() - Únicos\n";
$seq2 = Sequence::of(1, 2, 2, 3, 3, 3);
$unique = $seq2->unique();
echo "  Únicos: " . $unique->join(', ') . "\n\n";

// sort
echo "▶ sort() - Ordenar\n";
$unsorted = Sequence::of(5, 2, 8, 1, 9);
$sorted = $unsorted->sort(fn($a, $b) => $a <=> $b);
echo "  Ordenado: " . $sorted->join(', ') . "\n\n";

// reverse
echo "▶ reverse() - Reverter\n";
$reversed = $seq->reverse();
echo "  Reverso: " . $reversed->join(', ') . "\n\n";

// chunk
echo "▶ chunk() - Dividir em chunks\n";
$chunks = $seq->chunk(2);
foreach ($chunks as $i => $chunk) {
    echo "  Chunk $i: " . $chunk->join(', ') . "\n";
}
echo "\n";

// avg / sum / min / max
echo "▶ avg() / sum() / min() / max() - Estatísticas\n";
echo "  Média: " . $seq->avg() . "\n";
echo "  Soma: " . $seq->sum() . "\n";
echo "  Mínimo: " . $seq->min() . "\n";
echo "  Máximo: " . $seq->max() . "\n\n";

// count / isEmpty
echo "▶ count() / isEmpty() - Contagem\n";
echo "  Total: " . $seq->count() . "\n";
echo "  Vazia: " . ($seq->isEmpty() ? 'Sim' : 'Não') . "\n\n";

// toArray
echo "▶ toArray() - Para array\n";
$array = $seq->toArray();
echo "  Array: " . implode(', ', $array) . "\n\n";

// toMap
echo "▶ toMap() - Para Map\n";
$map = $seq->toMap(fn($x, $i) => ["key$i", $x]);
echo "  Map: ";
foreach ($map->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// from
echo "▶ Sequence::from() - De iterable\n";
$fromArray = Sequence::from([10, 20, 30]);
echo "  From array: " . $fromArray->join(', ') . "\n\n";

// toLazy
echo "▶ toLazy() - Converter para LazySequence\n";
$lazySeq = $seq->toLazy();
$array = $lazySeq->toArray();
echo "  Lazy sequence: " . implode(', ', $array) . "\n\n";

// join
echo "▶ join() - Juntar em string\n";
echo "  Join: " . $seq->join(' - ') . "\n\n";

// ============================================================================
// CLASSE: Map (Imutável)
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "3. MAP - Dicionário key-value imutável\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// empty
echo "▶ Map::empty() - Map vazio\n";
$emptyMap = Map::empty();
echo "  Vazio: " . ($emptyMap->isEmpty() ? 'Sim' : 'Não') . "\n\n";

// of
echo "▶ Map::of() - Criar de pares\n";
$map = Map::of('name', 'John', 'age', 30, 'city', 'NY');
echo "  Map: ";
foreach ($map->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// from
echo "▶ Map::from() - De array\n";
$map = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
echo "  Map: ";
foreach ($map->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// get
echo "▶ get() - Obter valor\n";
echo "  map['a'] = " . $map->get('a') . "\n\n";

// getOrDefault
echo "▶ getOrDefault() - Valor ou padrão\n";
echo "  get('x', 999) = " . $map->getOrDefault('x', 999) . "\n\n";

// has
echo "▶ has() - Verificar chave\n";
echo "  Tem 'b': " . ($map->has('b') ? 'Sim' : 'Não') . "\n\n";

// keys / values
echo "▶ keys() / values() - Chaves e valores\n";
echo "  Keys: " . $map->keys()->join(', ') . "\n";
echo "  Values: " . $map->values()->join(', ') . "\n\n";

// put
echo "▶ put() - Adicionar par\n";
$newMap = $map->put('d', 4);
echo "  Após put('d', 4): ";
foreach ($newMap->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// putAll
echo "▶ putAll() - Adicionar múltiplos\n";
$merged = $map->putAll(['e' => 5, 'f' => 6]);
echo "  Após putAll: ";
foreach ($merged->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// remove
echo "▶ remove() - Remover chave\n";
$removed = $map->remove('b');
echo "  Após remove('b'): ";
foreach ($removed->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// map
echo "▶ map() - Transformar pares\n";
$map2 = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
$mappedMap = $map2->map(fn($k, $v) => [$k, $v * 10]);
echo "  Mapped (*10): ";
foreach ($mappedMap->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// mapKeys
echo "▶ mapKeys() - Transformar chaves\n";
$map3 = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
$mappedKeys = $map3->mapKeys(fn($k) => strtoupper($k));
echo "  Keys uppercase: ";
foreach ($mappedKeys->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// mapValues
echo "▶ mapValues() - Transformar valores\n";
$map4 = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
$mappedValues = $map4->mapValues(fn($k, $v) => $v * 100);
echo "  Values (*100): ";
foreach ($mappedValues->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// filter
echo "▶ filter() - Filtrar pares\n";
$map5 = Map::from(['a' => 1, 'b' => 2, 'c' => 3]);
$filtered = $map5->filter(fn($k, $v) => $v > 1);
echo "  Filtered (v > 1): ";
foreach ($filtered->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// filterKeys
echo "▶ filterKeys() - Filtrar por chaves\n";
$filteredKeys = $map->filterKeys(fn($k) => $k !== 'a');
echo "  Filtered (k != 'a'): ";
foreach ($filteredKeys->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// filterValues
echo "▶ filterValues() - Filtrar por valores\n";
$filteredValues = $map->filterValues(fn($v) => $v % 2 == 0);
echo "  Filtered (v pares): ";
foreach ($filteredValues->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// merge
echo "▶ merge() - Mesclar Maps\n";
$map1 = Map::from(['a' => 1, 'b' => 2]);
$map2 = Map::from(['b' => 20, 'c' => 30]);
$mergedMap = $map1->merge($map2);
echo "  Merged: ";
foreach ($mergedMap->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// each
echo "▶ each() - Para cada par\n";
$map->each(fn($k, $v) => print("  $k => $v\n"));
echo "\n";

// reduce
echo "▶ reduce() - Reduzir\n";
$sum = $map->reduce(fn($carry, $k, $v) => $carry + $v, 0);
echo "  Soma valores: $sum\n\n";

// sortValues / sortKeys
echo "▶ sortValues() / sortKeys() - Ordenar\n";
$map = Map::from(['c' => 3, 'a' => 1, 'b' => 2]);
$sortedValues = $map->sortValues(fn($a, $b) => $a <=> $b);
$sortedKeys = $map->sortKeys();
echo "  Sorted values: ";
foreach ($sortedValues->toArray() as $k => $v) echo "$k=>$v ";
echo "\n  Sorted keys: ";
foreach ($sortedKeys->toArray() as $k => $v) echo "$k=>$v ";
echo "\n\n";

// count / isEmpty
echo "▶ count() / isEmpty() - Contagem\n";
echo "  Total: " . $map->count() . "\n";
echo "  Vazio: " . ($map->isEmpty() ? 'Sim' : 'Não') . "\n\n";

// toSequence
echo "▶ toSequence() - Para Sequence de pares\n";
$seq = $map->toSequence();
echo "  Sequence: " . $seq->count() . " pares\n\n";

// toLazy
echo "▶ toLazy() - Para LazyMap\n";
$lazyMap = $map->toLazy();
echo "  LazyMap criado\n\n";

// ============================================================================
// CLASSE: LazySequence
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "4. LAZYSEQUENCE - Sequência com avaliação lazy\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// empty
echo "▶ LazySequence::empty() - Lazy vazio\n";
$emptyLazy = LazySequence::empty();
echo "  Vazio: " . ($emptyLazy->count() == 0 ? 'Sim' : 'Não') . "\n\n";

// of
echo "▶ LazySequence::of() - De valores\n";
$lazySeq = LazySequence::of(1, 2, 3, 4, 5);
echo "  Lazy: " . $lazySeq->toEager()->join(', ') . "\n\n";

// range
echo "▶ LazySequence::range() - Range lazy\n";
$lazyRange = LazySequence::range(1, 5);
echo "  Range: " . $lazyRange->toEager()->join(', ') . "\n\n";

// from
echo "▶ LazySequence::from() - De iterable\n";
$lazyFrom = LazySequence::from([10, 20, 30]);
echo "  From: " . $lazyFrom->toEager()->join(', ') . "\n\n";

// map (lazy!)
echo "▶ map() - Transform lazy\n";
$lazyMapped = LazySequence::range(1, 5)->map(fn($x) => $x * 2);
echo "  Mapped: " . $lazyMapped->toEager()->join(', ') . "\n\n";

// filter (lazy!)
echo "▶ filter() - Filter lazy\n";
$lazyFiltered = LazySequence::range(1, 10)->filter(fn($x) => $x % 2 == 0);
echo "  Filtered: " . $lazyFiltered->toEager()->join(', ') . "\n\n";

// flatMap
echo "▶ flatMap() - FlatMap lazy\n";
$lazyFlat = LazySequence::of(1, 2, 3)->flatMap(fn($x) => [$x, $x * 2]);
echo "  FlatMapped: " . $lazyFlat->toEager()->join(', ') . "\n\n";

// take (lazy - stops early!)
echo "▶ take() - Take lazy (para cedo!)\n";
$lazyTake = LazySequence::range(1, 1000000)->take(5);
echo "  Take 5 de 1M: " . $lazyTake->toEager()->join(', ') . "\n\n";

// skip
echo "▶ skip() - Skip lazy\n";
$lazySkip = LazySequence::range(1, 10)->skip(5);
echo "  Skip 5: " . $lazySkip->toEager()->join(', ') . "\n\n";

// slice
echo "▶ slice() - Slice lazy\n";
$lazySlice = LazySequence::range(1, 10)->slice(2, 4);
echo "  Slice(2, 4): " . $lazySlice->toEager()->join(', ') . "\n\n";

// unique
echo "▶ unique() - Unique lazy\n";
$lazyUnique = LazySequence::of(1, 2, 2, 3, 3, 3)->unique();
echo "  Unique: " . $lazyUnique->toEager()->join(', ') . "\n\n";

// each
echo "▶ each() - Each lazy\n";
$lazyEach = LazySequence::of(1, 2, 3)->each(fn($x, $i) => print("  [$i] = $x\n"));
$lazyEach->toArray(); // Materialize to execute
echo "\n";

// chunk
echo "▶ chunk() - Chunk lazy\n";
$lazyChunks = LazySequence::range(1, 9)->chunk(3);
foreach ($lazyChunks as $i => $chunk) {
    echo "  Chunk: " . $chunk->join(', ') . "\n";
}
echo "\n";

// first (lazy - stops after finding!)
echo "▶ first() - First lazy\n";
$first = LazySequence::range(1, 1000000)->first();
echo "  First: $first\n\n";

// reduce
echo "▶ reduce() - Reduce lazy\n";
$sum = LazySequence::range(1, 5)->reduce(fn($c, $x) => $c + $x, 0);
echo "  Sum: $sum\n\n";

// avg / sum / min / max
echo "▶ avg() / sum() / min() / max() - Stats lazy\n";
$lazy = LazySequence::range(1, 5);
echo "  Avg: " . $lazy->avg() . "\n";
echo "  Sum: " . $lazy->sum() . "\n";
echo "  Min: " . $lazy->min() . "\n";
echo "  Max: " . $lazy->max() . "\n\n";

// count
echo "▶ count() - Count lazy\n";
echo "  Count: " . $lazy->count() . "\n\n";

// toArray
echo "▶ toArray() - Materializa para array PHP\n";
$materialized = $lazy->toArray();
echo "  Materialized: " . implode(', ', $materialized) . "\n\n";

// ============================================================================
// CLASSE: LazyMap
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "5. LAZYMAP - Map com valores lazy\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// empty
echo "▶ LazyMap::empty() - LazyMap vazio\n";
$emptyLazyMap = LazyMap::empty();
echo "  Vazio: " . ($emptyLazyMap->count() == 0 ? 'Sim' : 'Não') . "\n\n";

// of
echo "▶ LazyMap::of() - De pares\n";
$lazyMap = LazyMap::of(['a', 1], ['b', 2]);
echo "  LazyMap: a=" . $lazyMap->get('a') . ", b=" . $lazyMap->get('b') . "\n\n";

// from
echo "▶ LazyMap::from() - De array\n";
$lazyMap = LazyMap::from([
    'key1' => fn() => "Lazy Value 1",
    'key2' => fn() => "Lazy Value 2",
]);
echo "  key1: " . $lazyMap->get('key1') . "\n\n";

// ofLazyObjects (novo!)
echo "▶ LazyMap::ofLazyObjects() - Objetos lazy com LazyProxyObject\n";
class Product {
    public function __construct(public string $name, public float $price) {
        echo "  -> Criando Product: $name\n";
    }
}
$products = LazyMap::ofLazyObjects([
    'laptop' => [Product::class, 'Laptop', 999.99],
    'mouse' => [Product::class, 'Mouse', 29.99],
]);
echo "  Acessando laptop...\n";
$laptop = $products->get('laptop');
echo "  Nome: {$laptop->name}, Preço: {$laptop->price}\n\n";

// ofLazyFactories (novo!)
echo "▶ LazyMap::ofLazyFactories() - Factories customizadas\n";
$config = LazyMap::ofLazyFactories([
    'db' => [\stdClass::class, fn() => (object)['host' => 'localhost']],
]);
$db = $config->get('db');
echo "  DB Host: {$db->host}\n\n";

// get
echo "▶ get() - Obter valor (materializa se closure)\n";
$lazyMap = LazyMap::from([
    'eager' => 100,
    'lazy' => fn() => 200,
]);
echo "  eager: " . $lazyMap->get('eager') . "\n";
echo "  lazy: " . $lazyMap->get('lazy') . "\n\n";

// getOrDefault
echo "▶ getOrDefault() - Valor ou padrão\n";
echo "  inexistent: " . $lazyMap->getOrDefault('inexistent', 999) . "\n\n";

// has
echo "▶ has() - Verificar chave\n";
echo "  Tem 'eager': " . ($lazyMap->has('eager') ? 'Sim' : 'Não') . "\n\n";

// put
echo "▶ put() - Adicionar par\n";
$newLazyMap = $lazyMap->put('new', fn() => 300);
echo "  Após put: " . $newLazyMap->get('new') . "\n\n";

// putAll
echo "▶ putAll() - Adicionar múltiplos\n";
$merged = $lazyMap->putAll(['x' => 10, 'y' => 20]);
echo "  x: " . $merged->get('x') . ", y: " . $merged->get('y') . "\n\n";

// remove
echo "▶ remove() - Remover chave\n";
$removed = $lazyMap->remove('eager');
echo "  'eager' existe após remove: " . ($removed->has('eager') ? 'Sim' : 'Não') . "\n\n";

// map
echo "▶ map() - Transform (materializa todos!)\n";
$lazyMap = LazyMap::from(['a' => 1, 'b' => 2]);
$mapped = $lazyMap->map(fn($k, $v) => [strtoupper($k), $v * 10]);
echo "  Mapped: A=" . $mapped->get('A') . ", B=" . $mapped->get('B') . "\n\n";

// mapValues
echo "▶ mapValues() - Transform values (preserva lazy!)\n";
$lazyMap = LazyMap::from(['a' => fn() => 5, 'b' => fn() => 10]);
$mappedValues = $lazyMap->mapValues(fn($k, $v) => $v * 2);
echo "  a: " . $mappedValues->get('a') . ", b: " . $mappedValues->get('b') . "\n\n";

// mapKeys
echo "▶ mapKeys() - Transform keys\n";
$mapped = $lazyMap->mapKeys(fn($k, $v) => "key_$k");
echo "  key_a: " . $mapped->get('key_a') . "\n\n";

// filter / filterKeys / filterValues
echo "▶ filter() / filterKeys() / filterValues() - Filtrar\n";
$lazyMap = LazyMap::from(['a' => 1, 'b' => 2, 'c' => 3]);
$filtered = $lazyMap->filter(fn($k, $v) => $v > 1);
echo "  Filtered (v>1): b=" . $filtered->get('b') . ", c=" . $filtered->get('c') . "\n\n";

// reduce
echo "▶ reduce() - Reduce\n";
$sum = $lazyMap->reduce(fn($c, $k, $v) => $c + $v, 0);
echo "  Sum: $sum\n\n";

// each
echo "▶ each() - Para cada\n";
$lazyMap->each(fn($k, $v) => print("  $k => $v\n"));
echo "\n";

// keys / values
echo "▶ keys() / values() - Chaves e valores\n";
echo "  Keys: " . $lazyMap->keys()->join(', ') . "\n";
echo "  Values: " . $lazyMap->values()->join(', ') . "\n\n";

// count / isEmpty
echo "▶ count() / isEmpty()\n";
echo "  Count: " . $lazyMap->count() . "\n";
echo "  Empty: " . ($lazyMap->isEmpty() ? 'Sim' : 'Não') . "\n\n";

// toArray
echo "▶ toArray() - Materializar (executa closures!)\n";
$lazyMap = LazyMap::from(['x' => fn() => 100]);
$array = $lazyMap->toArray();
echo "  Array x: " . $array['x'] . "\n\n";

// toSequence
echo "▶ toSequence() - Para Sequence de pares\n";
$seq = $lazyMap->toSequence();
echo "  Sequence: " . $seq->count() . " pares\n\n";

// ============================================================================
// CLASSE: LazyFileIterator
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "6. LAZYFILEITERATOR - Iterador lazy para arquivos JSON\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Criar arquivo de teste
$testFile = __DIR__ . '/test_data.jsonl';
file_put_contents($testFile, implode("\n", [
    '{"id": 1, "name": "Item 1"}',
    '{"id": 2, "name": "Item 2"}',
    '{"id": 3, "name": "Item 3"}',
]));

echo "▶ Construtor - Iterar arquivo JSON linha por linha\n";
try {
    $fileIterator = new LazyFileIterator($testFile);
    
    echo "  Iterando arquivo:\n";
    $count = 0;
    foreach ($fileIterator as $index => $item) {
        echo "    [$index] id={$item->id}, name={$item->name}\n";
        $count++;
        if ($count >= 2) break; // Para demonstração
    }
    echo "\n";
    
    // current
    echo "▶ current() - Elemento atual\n";
    $fileIterator->rewind();
    $current = $fileIterator->current();
    echo "  Current: id={$current->id}, name={$current->name}\n\n";
    
    // key
    echo "▶ key() - Chave atual\n";
    echo "  Key: " . $fileIterator->key() . "\n\n";
    
    // valid
    echo "▶ valid() - É válido\n";
    echo "  Valid: " . ($fileIterator->valid() ? 'Sim' : 'Não') . "\n\n";
    
    // next
    echo "▶ next() - Próximo\n";
    $fileIterator->next();
    $next = $fileIterator->current();
    echo "  Next: id={$next->id}, name={$next->name}\n\n";
    
    // rewind
    echo "▶ rewind() - Voltar ao início\n";
    $fileIterator->rewind();
    echo "  Key após rewind: " . $fileIterator->key() . "\n\n";
    
} catch (\RuntimeException $e) {
    echo "  Erro: " . $e->getMessage() . "\n\n";
}

// Limpar
unlink($testFile);

// ============================================================================
// CLASSE: LazyProxyObject
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "7. LAZYPROXYOBJECT - Lazy object instantiation (PHP 8.4+)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

class ExpensiveService {
    public function __construct() {
        echo "  🔨 ExpensiveService criado\n";
    }
    
    public function execute(): string {
        return "Service executed!";
    }
}

// Construtor
echo "▶ __construct() - Criar proxy\n";
try {
    $proxyFactory = new LazyProxyObject(ExpensiveService::class);
    echo "  Proxy factory criado\n\n";
    
    // lazyProxy
    echo "▶ lazyProxy() - Criar objeto lazy (Proxy)\n";
    echo "  Criando proxy (objeto NÃO é instanciado ainda)...\n";
    $service = $proxyFactory->lazyProxy(fn() => new ExpensiveService());
    echo "  Proxy criado, objeto ainda NÃO foi instanciado\n\n";
    
    echo "  Chamando método (AGORA instancia)...\n";
    $result = $service->execute();
    echo "  Resultado: $result\n\n";
    
    // lazyGhost
    echo "▶ lazyGhost() - Criar objeto lazy (Ghost)\n";
    echo "  Criando ghost...\n";
    $ghost = $proxyFactory->lazyGhost(function($instance) {
        echo "  🔨 Ghost sendo inicializado\n";
    });
    echo "  Ghost criado\n\n";
    
} catch (\ReflectionException $e) {
    echo "  Erro: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// FIM
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ GUIA COMPLETO DE USO FINALIZADO!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📚 RESUMO DAS CLASSES:\n\n";
echo "1. Collection      - Coleção genérica com Iterator support\n";
echo "2. Sequence        - Lista ordenada imutável (eager)\n";
echo "3. Map             - Dicionário key-value imutável (eager)\n";
echo "4. LazySequence    - Sequência com pipeline lazy\n";
echo "5. LazyMap         - Map com valores lazy (closures)\n";
echo "6. LazyFileIterator - Iterador lazy para arquivos JSON\n";
echo "7. LazyProxyObject - Lazy object instantiation (PHP 8.4+)\n\n";

echo "💡 QUANDO USAR:\n\n";
echo "• Collection      → Dados variados, precisa de flexibilidade\n";
echo "• Sequence/Map    → Dados pequenos, imutabilidade, type safety\n";
echo "• LazySequence    → Grandes datasets, pipeline de transformações\n";
echo "• LazyMap         → Valores caros de computar, lazy loading\n";
echo "• LazyFileIterator → Arquivos grandes, streaming\n";
echo "• LazyProxyObject → Objetos caros de instanciar (DB, API)\n\n";

echo "🚀 Performance:\n";
echo "• Lazy methods são até 10,000x mais rápidos para grandes datasets\n";
echo "• Economia de até 95% de memória\n";
echo "• Objetos lazy só são criados quando necessário\n\n";
