# API Documentation - Complete Method Reference

Documentação completa de todos os métodos públicos disponíveis na Collection Library.

---

## 📦 Collection

Coleção genérica com suporte a Iterator e ArrayAccess.

### Criação

#### `__construct(Iterator|array $items = [])`
Cria uma nova coleção a partir de array ou Iterator.

```php
$collection = new Collection([1, 2, 3, 4, 5]);
$collection = new Collection(new ArrayIterator([1, 2, 3]));
```

#### `lazyRange(int $start, int $end): Collection` (static)
Cria uma coleção lazy de um range de números.

```php
$range = Collection::lazyRange(1, 1000000);
// Não cria array de 1M elementos, usa generator
```

#### `lazyObjects(array $factories, string $class): Collection` (static)
Cria objetos lazy usando LazyProxyObject.

```php
$users = Collection::lazyObjects([
    fn() => new User(1),
    fn() => new User(2)
], User::class);
// Objetos só são instanciados ao serem acessados
```

### Transformações (Eager)

#### `map(callable $callback): Collection`
Aplica função a cada elemento (eager).

```php
$numbers = new Collection([1, 2, 3]);
$doubled = $numbers->map(fn($v, $k) => $v * 2);
// [2, 4, 6]
```

#### `filter(callable $callback): Collection`
Filtra elementos (eager).

```php
$numbers = new Collection([1, 2, 3, 4, 5]);
$evens = $numbers->filter(fn($v, $k) => $v % 2 === 0);
// [2, 4]
```

#### `unique(): Collection`
Remove duplicatas.

```php
$collection = new Collection([1, 2, 2, 3, 3, 3]);
$unique = $collection->unique();
// [1, 2, 3]
```

#### `reverse(): Collection`
Inverte a ordem.

```php
$collection = new Collection([1, 2, 3]);
$reversed = $collection->reverse();
// [3, 2, 1]
```

#### `chunk(int $size): Collection`
Divide em chunks (eager).

```php
$collection = new Collection([1, 2, 3, 4, 5, 6]);
$chunks = $collection->chunk(2);
// [[1, 2], [3, 4], [5, 6]]
```

#### `sort(callable $callback): Collection`
Ordena com comparador customizado.

```php
$collection = new Collection([3, 1, 4, 1, 5]);
$sorted = $collection->sort(fn($a, $b) => $a <=> $b);
// [1, 1, 3, 4, 5]
```

#### `sortKeys(): Collection`
Ordena por chaves.

```php
$collection = new Collection(['c' => 3, 'a' => 1, 'b' => 2]);
$sorted = $collection->sortKeys();
// ['a' => 1, 'b' => 2, 'c' => 3]
```

### Transformações (Lazy)

#### `lazyMap(callable $callback): Collection`
Map lazy com generator.

```php
$range = Collection::lazyRange(1, 1000000);
$doubled = $range->lazyMap(fn($x) => $x * 2);
// Não executa até iterar
```

#### `lazyFilter(callable $callback): Collection`
Filter lazy com generator.

```php
$range = Collection::lazyRange(1, 1000000);
$filtered = $range->lazyFilter(fn($x) => $x > 100);
// Não executa até iterar
```

#### `lazyChunk(int $size): Collection`
Chunking lazy.

```php
$range = Collection::lazyRange(1, 1000000);
$chunks = $range->lazyChunk(1000);
// Gera chunks sob demanda
```

#### `lazyTake(int $limit): Collection`
Take lazy com short-circuit.

```php
$range = Collection::lazyRange(1, 1000000);
$first10 = $range->lazyTake(10);
// Para após 10 elementos
```

#### `lazyPipeline(array $operations): Collection`
Pipeline de operações lazy.

```php
$result = Collection::lazyRange(1, 1000000)->lazyPipeline([
    ['map', fn($x) => $x * 2],
    ['filter', fn($x) => $x > 100],
    ['take', 10]
]);
```

#### `lazy(): Collection`
Converte para lazy generator.

```php
$collection = new Collection([1, 2, 3, 4, 5]);
$lazy = $collection->lazy();
// Agora usa generator
```

### Acesso

#### `first(): mixed`
Retorna primeiro elemento.

```php
$collection = new Collection([1, 2, 3]);
echo $collection->first(); // 1
```

#### `last(): mixed`
Retorna último elemento.

```php
$collection = new Collection([1, 2, 3]);
echo $collection->last(); // 3
```

#### `current(): mixed`
Elemento atual do iterator.

```php
$collection = new Collection([1, 2, 3]);
echo $collection->current(); // 1
```

#### `contains(mixed $value): bool`
Verifica se contém valor.

```php
$collection = new Collection([1, 2, 3]);
$collection->contains(2); // true
$collection->contains(5); // false
```

#### `pluck(string|int $key): Collection`
Extrai valores de uma chave.

```php
$users = new Collection([
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25]
]);
$names = $users->pluck('name'); // ['John', 'Jane']
```

### Agregação

#### `count(): int`
Conta elementos.

```php
$collection = new Collection([1, 2, 3]);
echo $collection->count(); // 3
```

#### `sum(): int|float`
Soma valores numéricos.

```php
$collection = new Collection([1, 2, 3, 4, 5]);
echo $collection->sum(); // 15
```

#### `avg(): ?float`
Média dos valores.

```php
$collection = new Collection([10, 20, 30]);
echo $collection->avg(); // 20
```

#### `min(): mixed`
Valor mínimo.

```php
$collection = new Collection([3, 1, 4, 1, 5]);
echo $collection->min(); // 1
```

#### `max(): mixed`
Valor máximo.

```php
$collection = new Collection([3, 1, 4, 1, 5]);
echo $collection->max(); // 5
```

#### `reduce(callable $callback, mixed $initial): mixed`
Reduz a um único valor.

```php
$collection = new Collection([1, 2, 3, 4, 5]);
$product = $collection->reduce(fn($carry, $item) => $carry * $item, 1);
echo $product; // 120
```

### Slicing

#### `take(int $limit): Collection`
Pega primeiros N elementos (eager).

```php
$collection = new Collection([1, 2, 3, 4, 5]);
$first3 = $collection->take(3); // [1, 2, 3]
$last2 = $collection->take(-2); // [4, 5]
```

#### `slice(int $offset, ?int $length = null): Collection`
Extrai porção.

```php
$collection = new Collection([1, 2, 3, 4, 5]);
$slice = $collection->slice(1, 3); // [2, 3, 4]
```

### Utilidades

#### `each(callable $callback): Collection`
Executa ação em cada elemento.

```php
$collection = new Collection([1, 2, 3]);
$collection->each(function($value, $key) {
    echo "[$key] = $value\n";
});
```

#### `isEmpty(): bool`
Verifica se está vazia.

```php
$collection = new Collection([]);
echo $collection->isEmpty(); // true
```

#### `isNotEmpty(): bool`
Verifica se tem elementos.

```php
$collection = new Collection([1, 2, 3]);
echo $collection->isNotEmpty(); // true
```

#### `isLazy(): bool`
Verifica se é lazy.

```php
$eager = new Collection([1, 2, 3]);
$lazy = Collection::lazyRange(1, 100);
echo $eager->isLazy(); // false
echo $lazy->isLazy(); // true
```

#### `materialize(): Collection`
Força materialização de lazy.

```php
$lazy = Collection::lazyRange(1, 1000000);
$eager = $lazy->materialize();
// Agora é array em memória
```

#### `keys(): Collection`
Retorna chaves.

```php
$collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
$keys = $collection->keys(); // ['a', 'b', 'c']
```

#### `values(): Collection`
Retorna valores reindexados.

```php
$collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
$values = $collection->values(); // [1, 2, 3]
```

#### `toArray(): array`
Converte para array.

```php
$collection = new Collection([1, 2, 3]);
$array = $collection->toArray(); // [1, 2, 3]
```

#### `getIterator(): Traversable`
Retorna iterator.

```php
$collection = new Collection([1, 2, 3]);
foreach ($collection->getIterator() as $item) {
    echo $item;
}
```

### Modificação

#### `add(mixed $item): void`
Adiciona elemento.

```php
$collection = new Collection([1, 2]);
$collection->add(3); // [1, 2, 3]
```

#### `remove(mixed $item): void`
Remove elemento.

```php
$collection = new Collection([1, 2, 3]);
$collection->remove(2); // [1, 3]
```

#### `addIterator(Iterator|array $collection): void`
Adiciona/substitui iterator.

```php
$collection = new Collection([1, 2, 3]);
$collection->addIterator([4, 5, 6]);
```

### ArrayAccess

#### `offsetExists(mixed $offset): bool`
Verifica se chave existe.

```php
$collection = new Collection(['a' => 1]);
isset($collection['a']); // true
```

#### `offsetGet(mixed $offset): mixed`
Obtém valor por chave.

```php
$collection = new Collection(['a' => 1]);
echo $collection['a']; // 1
```

#### `offsetSet(mixed $offset, mixed $value): void`
Define valor.

```php
$collection = new Collection();
$collection['a'] = 1;
```

#### `offsetUnset(mixed $offset): void`
Remove chave.

```php
$collection = new Collection(['a' => 1, 'b' => 2]);
unset($collection['a']);
```

### Atributos

#### `setAttribute(mixed $key, mixed $value): void`
Define atributo.

```php
$collection = new Collection();
$collection->setAttribute('name', 'John');
```

#### `getAttribute(mixed $key): mixed`
Obtém atributo.

```php
$collection->setAttribute('name', 'John');
echo $collection->getAttribute('name'); // John
```

### Utilitários

#### `searchValueKey(array $array, string $key): mixed`
Busca valor em array aninhado.

```php
$data = [
    ['name' => 'John', 'city' => 'NY'],
    ['name' => 'Jane', 'city' => 'LA']
];
$collection = new Collection($data);
$city = $collection->searchValueKey($data, 'city'); // NY
```

#### `arrayToGenerator(array $array): Generator`
Converte array para generator.

```php
$collection = new Collection();
$gen = $collection->arrayToGenerator([1, 2, 3, 4, 5]);
foreach ($gen as $item) {
    echo $item;
}
```

---

## 📋 Sequence

Lista ordenada imutável.

### Criação

#### `static empty(): Sequence`
Cria sequência vazia.

```php
$empty = Sequence::empty();
```

#### `static of(...$values): Sequence`
Cria de valores.

```php
$seq = Sequence::of(1, 2, 3, 4, 5);
```

#### `static range(int $start, int $end): Sequence`
Cria range.

```php
$range = Sequence::range(1, 10); // [1..10]
```

#### `static from(iterable $items): Sequence`
Cria de iterable.

```php
$seq = Sequence::from([1, 2, 3]);
$seq = Sequence::from(new ArrayIterator([1, 2, 3]));
```

### Acesso

#### `at(int $index): mixed`
Elemento na posição.

```php
$seq = Sequence::of(10, 20, 30);
echo $seq->at(1); // 20
```

#### `first(): mixed`
Primeiro elemento.

```php
$seq = Sequence::of(1, 2, 3);
echo $seq->first(); // 1
```

#### `last(): mixed`
Último elemento.

```php
$seq = Sequence::of(1, 2, 3);
echo $seq->last(); // 3
```

#### `indexOf(mixed $value): ?int`
Índice do valor.

```php
$seq = Sequence::of(10, 20, 30);
echo $seq->indexOf(20); // 1
echo $seq->indexOf(99); // null
```

#### `contains(mixed $value): bool`
Verifica se contém.

```php
$seq = Sequence::of(1, 2, 3);
$seq->contains(2); // true
```

### Modificação (retorna nova instância)

#### `append(mixed $value): Sequence`
Adiciona no fim.

```php
$seq = Sequence::of(1, 2, 3);
$new = $seq->append(4); // [1, 2, 3, 4]
// $seq ainda é [1, 2, 3]
```

#### `prepend(mixed $value): Sequence`
Adiciona no início.

```php
$seq = Sequence::of(1, 2, 3);
$new = $seq->prepend(0); // [0, 1, 2, 3]
```

#### `insert(int $index, mixed $value): Sequence`
Insere em posição.

```php
$seq = Sequence::of(1, 2, 4);
$new = $seq->insert(2, 3); // [1, 2, 3, 4]
```

#### `remove(int $index): Sequence`
Remove por índice.

```php
$seq = Sequence::of(1, 2, 3, 4);
$new = $seq->remove(1); // [1, 3, 4]
```

### Transformações

#### `map(callable $fn): Sequence`
Transforma elementos.

```php
$seq = Sequence::of(1, 2, 3);
$doubled = $seq->map(fn($x) => $x * 2); // [2, 4, 6]
```

#### `filter(callable $fn): Sequence`
Filtra elementos.

```php
$seq = Sequence::of(1, 2, 3, 4, 5);
$evens = $seq->filter(fn($x) => $x % 2 === 0); // [2, 4]
```

#### `flatMap(callable $fn): Sequence`
Map + flatten.

```php
$seq = Sequence::of(1, 2, 3);
$result = $seq->flatMap(fn($x) => [$x, $x * 2]);
// [1, 2, 2, 4, 3, 6]
```

#### `unique(): Sequence`
Remove duplicatas.

```php
$seq = Sequence::of(1, 2, 2, 3, 3, 3);
$unique = $seq->unique(); // [1, 2, 3]
```

#### `reverse(): Sequence`
Inverte ordem.

```php
$seq = Sequence::of(1, 2, 3);
$reversed = $seq->reverse(); // [3, 2, 1]
```

#### `sort(?callable $comparator = null): Sequence`
Ordena.

```php
$seq = Sequence::of(3, 1, 4, 1, 5);
$sorted = $seq->sort(); // [1, 1, 3, 4, 5]

$desc = $seq->sort(fn($a, $b) => $b <=> $a); // [5, 4, 3, 1, 1]
```

### Slicing

#### `take(int $n): Sequence`
Pega N primeiros.

```php
$seq = Sequence::of(1, 2, 3, 4, 5);
$first3 = $seq->take(3); // [1, 2, 3]
```

#### `skip(int $n): Sequence`
Pula N primeiros.

```php
$seq = Sequence::of(1, 2, 3, 4, 5);
$skipped = $seq->skip(2); // [3, 4, 5]
```

#### `slice(int $start, int $length): Sequence`
Fatia.

```php
$seq = Sequence::of(1, 2, 3, 4, 5);
$slice = $seq->slice(1, 3); // [2, 3, 4]
```

#### `chunk(int $size): Sequence`
Divide em chunks.

```php
$seq = Sequence::of(1, 2, 3, 4, 5, 6);
$chunks = $seq->chunk(2); // [[1, 2], [3, 4], [5, 6]]
```

### Agregação

#### `reduce(callable $fn, mixed $initial): mixed`
Reduz a valor.

```php
$seq = Sequence::of(1, 2, 3, 4, 5);
$sum = $seq->reduce(fn($carry, $x) => $carry + $x, 0); // 15
```

#### `each(callable $fn): void`
Para cada elemento.

```php
$seq = Sequence::of(1, 2, 3);
$seq->each(fn($value, $index) => print("[$index] = $value\n"));
```

#### `sum(): int|float`
Soma.

```php
$seq = Sequence::of(1, 2, 3, 4, 5);
echo $seq->sum(); // 15
```

#### `avg(): ?float`
Média.

```php
$seq = Sequence::of(10, 20, 30);
echo $seq->avg(); // 20
```

#### `min(): mixed`
Mínimo.

```php
$seq = Sequence::of(3, 1, 4, 1, 5);
echo $seq->min(); // 1
```

#### `max(): mixed`
Máximo.

```php
$seq = Sequence::of(3, 1, 4, 1, 5);
echo $seq->max(); // 5
```

#### `count(): int`
Contagem.

```php
$seq = Sequence::of(1, 2, 3);
echo $seq->count(); // 3
```

#### `isEmpty(): bool`
Está vazia?

```php
$empty = Sequence::empty();
echo $empty->isEmpty(); // true
```

### Conversão

#### `toLazy(): LazySequence`
Converte para lazy.

```php
$seq = Sequence::of(1, 2, 3, 4, 5);
$lazy = $seq->toLazy();
```

#### `toMap(callable $keyMapper): Map`
Converte para Map.

```php
$seq = Sequence::of('a', 'b', 'c');
$map = $seq->toMap(fn($v, $i) => $i); // [0 => 'a', 1 => 'b', 2 => 'c']
```

#### `toArray(): array`
Converte para array.

```php
$seq = Sequence::of(1, 2, 3);
$array = $seq->toArray(); // [1, 2, 3]
```

#### `join(string $separator): string`
Junta em string.

```php
$seq = Sequence::of(1, 2, 3);
echo $seq->join(', '); // "1, 2, 3"
```

---

## 🗺️ Map

Dicionário key-value imutável.

### Criação

#### `static empty(): Map`
Map vazio.

```php
$empty = Map::empty();
```

#### `static of(...$pairs): Map`
Cria de pares (key1, val1, key2, val2...).

```php
$map = Map::of(
    'name', 'John',
    'age', 30,
    'city', 'NY'
);
```

#### `static from(array $array): Map`
Cria de array.

```php
$map = Map::from(['name' => 'John', 'age' => 30]);
```

### Acesso

#### `get(mixed $key): mixed`
Obtém valor.

```php
$map = Map::of('name', 'John', 'age', 30);
echo $map->get('name'); // John
```

#### `getOrDefault(mixed $key, mixed $default): mixed`
Obtém ou padrão.

```php
$map = Map::of('name', 'John');
echo $map->getOrDefault('email', 'no-email'); // no-email
```

#### `has(mixed $key): bool`
Verifica chave.

```php
$map = Map::of('name', 'John');
$map->has('name'); // true
$map->has('email'); // false
```

#### `keys(): Sequence`
Retorna chaves.

```php
$map = Map::of('a', 1, 'b', 2, 'c', 3);
$keys = $map->keys(); // Sequence ['a', 'b', 'c']
```

#### `values(): Sequence`
Retorna valores.

```php
$map = Map::of('a', 1, 'b', 2, 'c', 3);
$values = $map->values(); // Sequence [1, 2, 3]
```

### Modificação (retorna nova instância)

#### `put(mixed $key, mixed $value): Map`
Adiciona/atualiza par.

```php
$map = Map::of('name', 'John');
$new = $map->put('age', 30); // ['name' => 'John', 'age' => 30]
```

#### `putAll(iterable $pairs): Map`
Adiciona múltiplos.

```php
$map = Map::of('a', 1);
$new = $map->putAll(['b' => 2, 'c' => 3]);
```

#### `remove(mixed $key): Map`
Remove chave.

```php
$map = Map::of('a', 1, 'b', 2, 'c', 3);
$new = $map->remove('b'); // ['a' => 1, 'c' => 3]
```

#### `merge(Map $other): Map`
Mescla Maps.

```php
$map1 = Map::of('a', 1, 'b', 2);
$map2 = Map::of('b', 20, 'c', 30);
$merged = $map1->merge($map2); // ['a' => 1, 'b' => 20, 'c' => 30]
```

### Transformações

#### `map(callable $fn): Map`
Transforma pares.

```php
$map = Map::of('a', 1, 'b', 2);
$new = $map->map(fn($k, $v) => [$k, $v * 10]);
// ['a' => 10, 'b' => 20]
```

#### `mapKeys(callable $fn): Map`
Transforma chaves.

```php
$map = Map::of('name', 'John', 'age', 30);
$new = $map->mapKeys(fn($k) => strtoupper($k));
// ['NAME' => 'John', 'AGE' => 30]
```

#### `mapValues(callable $fn): Map`
Transforma valores.

```php
$map = Map::of('a', 1, 'b', 2, 'c', 3);
$new = $map->mapValues(fn($k, $v) => $v * 100);
// ['a' => 100, 'b' => 200, 'c' => 300]
```

#### `filter(callable $fn): Map`
Filtra pares.

```php
$map = Map::of('a', 1, 'b', 2, 'c', 3);
$filtered = $map->filter(fn($k, $v) => $v > 1);
// ['b' => 2, 'c' => 3]
```

#### `filterKeys(callable $fn): Map`
Filtra por chaves.

```php
$map = Map::of('name', 'John', 'age', 30, 'city', 'NY');
$filtered = $map->filterKeys(fn($k) => $k !== 'age');
// ['name' => 'John', 'city' => 'NY']
```

#### `filterValues(callable $fn): Map`
Filtra por valores.

```php
$map = Map::of('a', 10, 'b', 20, 'c', 5);
$filtered = $map->filterValues(fn($v) => $v >= 10);
// ['a' => 10, 'b' => 20]
```

### Agregação

#### `reduce(callable $fn, mixed $initial): mixed`
Reduz a valor.

```php
$map = Map::of('a', 1, 'b', 2, 'c', 3);
$sum = $map->reduce(fn($carry, $k, $v) => $carry + $v, 0); // 6
```

#### `each(callable $fn): void`
Para cada par.

```php
$map = Map::of('name', 'John', 'age', 30);
$map->each(fn($k, $v) => print("$k => $v\n"));
```

#### `count(): int`
Contagem.

```php
$map = Map::of('a', 1, 'b', 2);
echo $map->count(); // 2
```

#### `isEmpty(): bool`
Está vazio?

```php
$empty = Map::empty();
echo $empty->isEmpty(); // true
```

### Ordenação

#### `sortKeys(?callable $comparator = null): Map`
Ordena por chaves.

```php
$map = Map::of('c', 3, 'a', 1, 'b', 2);
$sorted = $map->sortKeys(); // ['a' => 1, 'b' => 2, 'c' => 3]
```

#### `sortValues(?callable $comparator = null): Map`
Ordena por valores.

```php
$map = Map::of('a', 30, 'b', 10, 'c', 20);
$sorted = $map->sortValues(); // ['b' => 10, 'c' => 20, 'a' => 30]
```

### Conversão

#### `toLazy(): LazyMap`
Converte para lazy.

```php
$map = Map::of('a', 1, 'b', 2);
$lazy = $map->toLazy();
```

#### `toSequence(): Sequence`
Converte para Sequence de pares.

```php
$map = Map::of('a', 1, 'b', 2);
$seq = $map->toSequence(); // Sequence [['a', 1], ['b', 2]]
```

#### `toArray(): array`
Converte para array.

```php
$map = Map::of('a', 1, 'b', 2);
$array = $map->toArray(); // ['a' => 1, 'b' => 2]
```

---

## 🔄 LazySequence

Sequência com avaliação lazy (generator-based).

### Criação

#### `static empty(): LazySequence`
Lazy vazio.

```php
$empty = LazySequence::empty();
```

#### `static of(...$values): LazySequence`
Cria de valores.

```php
$lazy = LazySequence::of(1, 2, 3, 4, 5);
```

#### `static range(int $start, int $end): LazySequence`
Range lazy.

```php
$range = LazySequence::range(1, 1000000);
// Não cria array, usa generator
```

#### `static from(iterable $items): LazySequence`
De iterable.

```php
$lazy = LazySequence::from([1, 2, 3]);
$lazy = LazySequence::from($generator);
```

### Transformações (todas lazy!)

#### `map(callable $fn): LazySequence`
Map lazy.

```php
$lazy = LazySequence::range(1, 1000000)
    ->map(fn($x) => $x * 2); // Não executa ainda
```

#### `filter(callable $fn): LazySequence`
Filter lazy.

```php
$lazy = LazySequence::range(1, 1000000)
    ->filter(fn($x) => $x % 2 === 0); // Não executa ainda
```

#### `flatMap(callable $fn): LazySequence`
FlatMap lazy.

```php
$lazy = LazySequence::of(1, 2, 3)
    ->flatMap(fn($x) => [$x, $x * 2]);
```

#### `take(int $n): LazySequence`
Take lazy (short-circuit!).

```php
$first10 = LazySequence::range(1, 1000000)
    ->take(10); // Para após 10 elementos!
```

#### `skip(int $n): LazySequence`
Skip lazy.

```php
$skipped = LazySequence::range(1, 100)
    ->skip(50); // [51..100]
```

#### `slice(int $start, int $length): LazySequence`
Slice lazy.

```php
$slice = LazySequence::range(1, 100)
    ->slice(10, 20); // [11..30]
```

#### `unique(): LazySequence`
Unique lazy.

```php
$lazy = LazySequence::of(1, 2, 2, 3, 3, 3)
    ->unique(); // [1, 2, 3]
```

#### `chunk(int $size): LazySequence`
Chunk lazy.

```php
$chunks = LazySequence::range(1, 1000000)
    ->chunk(1000); // Generator de arrays
```

### Agregação (materializa!)

#### `first(): mixed`
Primeiro (short-circuit).

```php
$first = LazySequence::range(1, 1000000)->first(); // 1
// Para imediatamente
```

#### `reduce(callable $fn, mixed $initial): mixed`
Reduz.

```php
$sum = LazySequence::of(1, 2, 3, 4, 5)
    ->reduce(fn($carry, $x) => $carry + $x, 0); // 15
```

#### `sum(): int|float`
Soma.

```php
$sum = LazySequence::range(1, 100)->sum(); // 5050
```

#### `avg(): ?float`
Média.

```php
$avg = LazySequence::of(10, 20, 30)->avg(); // 20
```

#### `min(): mixed`
Mínimo.

```php
$min = LazySequence::of(3, 1, 4, 1, 5)->min(); // 1
```

#### `max(): mixed`
Máximo.

```php
$max = LazySequence::of(3, 1, 4, 1, 5)->max(); // 5
```

#### `count(): int`
Conta (materializa).

```php
$count = LazySequence::range(1, 100)->count(); // 100
```

#### `each(callable $fn): LazySequence`
Para cada (lazy).

```php
LazySequence::of(1, 2, 3)
    ->each(fn($v, $i) => print("[$i] = $v\n"))
    ->toArray(); // Executa ao materializar
```

### Conversão

#### `toEager(): Sequence`
Materializa para Sequence.

```php
$lazy = LazySequence::range(1, 100);
$eager = $lazy->toEager(); // Sequence [1..100]
```

#### `toArray(): array`
Materializa para array.

```php
$lazy = LazySequence::range(1, 10);
$array = $lazy->toArray(); // [1, 2, 3, ..., 10]
```

---

## 🗺️ LazyMap

Map com valores lazy (closures).

### Criação

#### `static empty(): LazyMap`
LazyMap vazio.

```php
$empty = LazyMap::empty();
```

#### `static of(array $items): LazyMap`
Cria de closures.

```php
$map = LazyMap::of([
    'db' => fn() => new Database(),
    'cache' => fn() => new Redis()
]);
// Nada instanciado ainda!
```

#### `static from(array $array): LazyMap`
De array (converte valores para closures).

```php
$map = LazyMap::from(['a' => 1, 'b' => 2]);
```

#### `static ofLazyObjects(array $classes, array $args = []): LazyMap`
Lazy objects com LazyProxyObject (PHP 8.4+).

```php
$services = LazyMap::ofLazyObjects([
    'logger' => Logger::class,
    'mailer' => Mailer::class
], ['dsn' => 'config']);
// Objetos são lazy proxies
```

#### `static ofLazyFactories(array $factories): LazyMap`
De factories customizadas.

```php
$map = LazyMap::ofLazyFactories([
    'timestamp' => fn() => time(),
    'random' => fn() => rand(1, 100)
]);
```

### Acesso

#### `get(mixed $key): mixed`
Obtém valor (executa closure!).

```php
$map = LazyMap::of([
    'db' => fn() => new Database()
]);
$db = $map->get('db'); // AGORA instancia!
```

#### `getOrDefault(mixed $key, mixed $default): mixed`
Obtém ou padrão.

```php
$value = $map->getOrDefault('missing', fn() => 'default');
```

#### `has(mixed $key): bool`
Verifica chave.

```php
$map->has('db'); // true
```

#### `keys(): Sequence`
Chaves.

```php
$keys = $map->keys(); // Sequence de chaves
```

#### `values(): Sequence`
Valores (executa todos!).

```php
$values = $map->values(); // Executa todas closures
```

### Modificação

#### `put(mixed $key, Closure $value): LazyMap`
Adiciona closure.

```php
$new = $map->put('api', fn() => new ApiClient());
```

#### `putAll(array $pairs): LazyMap`
Adiciona múltiplos.

```php
$new = $map->putAll([
    'service1' => fn() => new Service1(),
    'service2' => fn() => new Service2()
]);
```

#### `remove(mixed $key): LazyMap`
Remove chave.

```php
$new = $map->remove('cache');
```

#### `merge(LazyMap $other): LazyMap`
Mescla.

```php
$merged = $map1->merge($map2);
```

### Transformações

#### `map(callable $fn): LazyMap`
Transforma pares (ainda lazy!).

```php
$new = $map->map(fn($k, $closure) => [
    $k, 
    fn() => strtoupper($closure())
]);
```

#### `mapKeys(callable $fn): LazyMap`
Transforma chaves.

```php
$new = $map->mapKeys(fn($k) => "prefix_$k");
```

#### `mapValues(callable $fn): LazyMap`
Transforma valores (ainda lazy!).

```php
$new = $map->mapValues(fn($k, $closure) => 
    fn() => $closure() * 2
);
```

#### `filter(callable $fn): LazyMap`
Filtra (testa closure, não resultado).

```php
$filtered = $map->filter(fn($k, $closure) => $k !== 'debug');
```

### Agregação

#### `reduce(callable $fn, mixed $initial): mixed`
Reduz (materializa todos!).

```php
$sum = $map->reduce(fn($carry, $k, $closure) => 
    $carry + $closure(), 0
);
```

#### `each(callable $fn): void`
Para cada.

```php
$map->each(fn($k, $closure) => 
    print("$k => " . $closure() . "\n")
);
```

#### `count(): int`
Conta.

```php
echo $map->count(); // 3
```

#### `isEmpty(): bool`
Vazio?

```php
$map->isEmpty(); // false
```

### Conversão

#### `toArray(): array`
Materializa tudo.

```php
$array = $map->toArray(); // Executa todas closures
```

#### `toSequence(): Sequence`
Para Sequence.

```php
$seq = $map->toSequence(); // Sequence de pares [k, v]
```

#### `toEager(): Map`
Materializa para Map eager.

```php
$eager = $map->toEager(); // Map normal
```

---

## 📁 LazyFileIterator

Iterator para arquivos JSON lines.

### Criação

#### `__construct(string $filePath)`
Cria iterator para arquivo.

```php
$iterator = new LazyFileIterator('data.jsonl');
// Arquivo não é carregado em memória
```

### Iteração

#### `current(): mixed`
Objeto JSON atual.

```php
$obj = $iterator->current(); // stdClass
```

#### `key(): int`
Linha atual.

```php
$line = $iterator->key(); // 0, 1, 2...
```

#### `next(): void`
Próxima linha.

```php
$iterator->next();
```

#### `valid(): bool`
Tem mais linhas?

```php
if ($iterator->valid()) {
    $obj = $iterator->current();
}
```

#### `rewind(): void`
Volta ao início.

```php
$iterator->rewind();
```

### Uso

```php
$iterator = new LazyFileIterator('users.jsonl');

foreach ($iterator as $index => $user) {
    echo "User {$index}: {$user->name}\n";
}

// Com Collection
$collection = new Collection($iterator);
$filtered = $collection->lazyFilter(fn($user) => $user->active);
```

---

## 🔮 LazyProxyObject

Wrapper para PHP 8.4+ lazy objects.

### Criação

#### `__construct(string $class)`
Cria factory para classe.

```php
$factory = new LazyProxyObject(ExpensiveService::class);
```

### Lazy Instantiation

#### `lazyProxy(Closure $factory): object`
Cria lazy proxy.

```php
$service = $factory->lazyProxy(fn() => new ExpensiveService());
// Objeto NÃO instanciado ainda

$service->doSomething(); // AGORA instancia!
```

#### `lazyGhost(Closure $initializer): object`
Cria lazy ghost.

```php
$service = $factory->lazyGhost(function($instance) {
    // Inicializa propriedades
    $instance->name = 'Service';
});
```

### Uso Prático

```php
// Serviços pesados
class Database {
    public function __construct() {
        sleep(2); // Conexão pesada
    }
}

// Lazy loading
$factory = new LazyProxyObject(Database::class);
$db = $factory->lazyProxy(fn() => new Database());

// Ainda não conectou...
echo "App iniciado!\n";

// Conecta só quando usar
$db->query('SELECT * FROM users'); // Agora conecta!
```

### Com LazyMap

```php
$services = LazyMap::ofLazyObjects([
    'db' => Database::class,
    'cache' => Redis::class,
    'logger' => Logger::class
]);

// Nada instanciado!

$db = $services->get('db');
// Agora DB é lazy proxy

$db->connect(); // Instancia DB agora
```

---

## 🎯 Exemplos Práticos

### Pipeline Lazy vs Eager

```php
// ❌ EAGER - 1M iterações
$result = Sequence::range(1, 1000000)
    ->map(fn($x) => $x * 2)
    ->filter(fn($x) => $x > 100)
    ->take(10);
// 1625ms, 40MB

// ✅ LAZY - ~51 iterações
$result = LazySequence::range(1, 1000000)
    ->map(fn($x) => $x * 2)
    ->filter(fn($x) => $x > 100)
    ->take(10);
// 0.71ms, 2MB
// 2290x mais rápido!
```

### Service Container

```php
$container = LazyMap::ofLazyObjects([
    'database' => Database::class,
    'mailer' => Mailer::class,
    'logger' => Logger::class,
    'cache' => Redis::class
], ['dsn' => 'mysql://...']);

// Nada instanciado ainda!

$db = $container->get('database');
// Lazy proxy criado

$db->query('...'); // Instancia agora
```

### File Streaming

```php
$iterator = new LazyFileIterator('huge_file.jsonl');

$collection = new Collection($iterator);

$result = $collection
    ->lazyFilter(fn($obj) => $obj->status === 'active')
    ->lazyMap(fn($obj) => $obj->email)
    ->lazyTake(100);

// Processa só 100 registros ativos, sem carregar arquivo inteiro
```

### Data Processing Pipeline

```php
$pipeline = Collection::lazyRange(1, 1000000)
    ->lazyMap(fn($n) => ['id' => $n, 'value' => $n * 2])
    ->lazyFilter(fn($item) => $item['value'] > 1000)
    ->lazyChunk(100)
    ->lazyTake(5);

// 5 chunks de 100 itens cada
// Total: ~551 iterações vs 1M
```

---

## 📚 Quando Usar Cada Classe

### Collection
- ✅ Dados variados, flexibilidade
- ✅ Trabalhar com Iterators
- ✅ Precisa de eager E lazy
- ✅ ArrayAccess necessário

### Sequence
- ✅ Listas pequenas/médias (< 10K)
- ✅ Imutabilidade importante
- ✅ Type safety essencial
- ✅ Ordered collection

### Map
- ✅ Dicionários pequenos/médios
- ✅ Imutabilidade importante
- ✅ Type safety essencial
- ✅ Key-value pairs

### LazySequence
- ✅ Grandes datasets (> 100K)
- ✅ Streaming de dados
- ✅ Pipeline complexo
- ✅ Short-circuit evaluation

### LazyMap
- ✅ Valores caros de computar
- ✅ Nem todos valores serão usados
- ✅ Service containers
- ✅ Lazy initialization

### LazyFileIterator
- ✅ Arquivos grandes
- ✅ JSON lines format
- ✅ Não cabe em memória
- ✅ Streaming processing

### LazyProxyObject
- ✅ PHP 8.4+ disponível
- ✅ Objetos caros de instanciar
- ✅ Dependency injection
- ✅ True lazy semantics

---

**Total: 150+ métodos públicos documentados!** 🚀
