# Collection Library

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A powerful, type-safe PHP collection library with support for **eager** and **lazy** evaluation, implementing proper data structures following modern best practices.

## 🚀 Features

- ✨ **Type-Safe** - Full PHPDoc generics support (`Sequence<T>`, `Map<K,V>`)
- ⚡ **Lazy Evaluation** - Memory-efficient processing with generators
- 🔒 **Immutable** - `Sequence` and `Map` are readonly classes
- 🎯 **Rich API** - 100+ methods across all classes
- 🔄 **Flexible** - Generic `Collection` for Iterator support
- 📦 **Modern PHP** - PHP 8.1+ with strict types
- 🧪 **Well Tested** - Comprehensive test coverage

## 📦 Installation

```bash
composer require omegaalfa/collection
```

## 📋 Requirements

- PHP 8.1 or higher
- PHP 8.4+ recommended (for `LazyProxyObject` features)

---

## 🎯 Core Concepts

This library provides **7 specialized classes** for different use cases:

| Class | Type | Purpose | When to Use |
|-------|------|---------|-------------|
| **Collection** | Generic | Iterator wrapper with transformations | Mixed data, legacy code, Iterator support |
| **Sequence** | Eager | Ordered immutable list | Small lists, type safety, immutability |
| **Map** | Eager | Immutable key-value dictionary | Small maps, type safety, immutability |
| **LazySequence** | Lazy | Generator-based pipeline | Large datasets, streaming |
| **LazyMap** | Lazy | Lazy value computation | Expensive computations, caching |
| **LazyFileIterator** | Lazy | File streaming (JSON lines) | Large files, memory constraints |
| **LazyProxyObject** | Lazy | PHP 8.4+ lazy object instantiation | Expensive objects, dependency injection |

---

## 📚 Quick Start

### 1. Collection - Generic Wrapper

```php
use Omegaalfa\Collection\Collection;

// Create from array or Iterator
$collection = new Collection([1, 2, 3, 4, 5]);

// Transform (eager)
$doubled = $collection->map(fn($x) => $x * 2);
$evens = $collection->filter(fn($x) => $x % 2 === 0);

// Lazy methods (memory efficient!)
$result = Collection::lazyRange(1, 1000000)
    ->lazyMap(fn($x) => $x * 2)
    ->lazyFilter(fn($x) => $x > 100)
    ->lazyTake(10);  // Only processes ~51 elements!

// Array access
$collection['key'] = 'value';
echo $collection['key'];

// Statistics
echo $collection->sum();    // 15
echo $collection->avg();    // 3
echo $collection->count();  // 5
```

### 2. Sequence - Ordered Immutable List

```php
use Omegaalfa\Collection\Sequence;

// Create
$numbers = Sequence::of(1, 2, 3, 4, 5);
$range = Sequence::range(1, 10);

// Immutable transformations
$doubled = $numbers->map(fn($x) => $x * 2);
$evens = $numbers->filter(fn($x) => $x % 2 === 0);

// Fluent chaining
$result = Sequence::range(1, 100)
    ->filter(fn($x) => $x % 3 === 0)
    ->map(fn($x) => $x * $x)
    ->take(5);

// Access
echo $numbers->at(0);      // 1
echo $numbers->first();    // 1
echo $numbers->last();     // 5

// Operations
$appended = $numbers->append(6);
$prepended = $numbers->prepend(0);
$inserted = $numbers->insert(2, 99);
$removed = $numbers->remove(2);

// Conversion
$lazy = $numbers->toLazy();  // Convert to LazySequence
$map = $numbers->toMap(fn($v, $i) => "key$i");
```

### 3. Map - Immutable Key-Value Dictionary

```php
use Omegaalfa\Collection\Map;

// Create
$user = Map::of(
    'name', 'John',
    'age', 30,
    'city', 'NY'
);

// Or from array
$config = Map::from(['debug' => true, 'timeout' => 30]);

// Access
echo $user->get('name');               // John
echo $user->getOrDefault('email', '-'); // -

// Check
if ($user->has('age')) {
    echo $user->get('age');
}

// Transform
$aged = $user->put('age', 31);  // Returns new Map
$removed = $user->remove('city');

// Transformations
$uppercased = $user->mapValues(fn($k, $v) => is_string($v) ? strtoupper($v) : $v);
$prefixed = $user->mapKeys(fn($k) => "user_$k");
$filtered = $user->filter(fn($k, $v) => $k !== 'age');

// Merge
$merged = $user->merge(Map::of('email', 'john@example.com'));

// Conversion
$lazy = $user->toLazy();  // Convert to LazyMap
$sequence = $user->toSequence();  // Sequence of [key, value] pairs
```

### 4. LazySequence - Generator-Based Pipeline

```php
use Omegaalfa\Collection\LazySequence;

// Create
$lazy = LazySequence::of(1, 2, 3, 4, 5);
$range = LazySequence::range(1, 1000000);

// Pipeline - NOTHING executes yet!
$pipeline = LazySequence::range(1, 1000000)
    ->map(fn($x) => $x * 2)
    ->filter(fn($x) => $x > 100)
    ->take(10);

// Now it executes - only ~51 iterations!
foreach ($pipeline as $value) {
    echo $value;  // 102, 104, 106...
}

// Short-circuit operations
$first = LazySequence::range(1, 1000000)->first();  // Stops at 1

// Materialize to eager
$eager = $lazy->toEager();  // Returns Sequence
```

### 5. LazyMap - Lazy Value Computation

```php
use Omegaalfa\Collection\LazyMap;

// Values are closures - computed on-demand!
$config = LazyMap::of([
    'database' => fn() => new Database(),  // Not created yet
    'cache' => fn() => new Redis(),        // Not created yet
    'api' => fn() => new ApiClient()       // Not created yet
]);

// Only creates Database when accessed
$db = $config->get('database');

// With LazyProxyObject (PHP 8.4+)
$services = LazyMap::ofLazyObjects([
    'logger' => Logger::class,
    'mailer' => Mailer::class
], ['dsn' => '...']);

// Creates lazy proxy - object instantiated on first method call
$logger = $services->get('logger');
$logger->info('message');  // NOW Logger is instantiated

// Transform (still lazy!)
$mapped = $config->mapValues(fn($k, $closure) => fn() => strtoupper($closure()));
```

### 6. LazyFileIterator - Stream Large Files

```php
use Omegaalfa\Collection\LazyFileIterator;

// Stream JSON lines file
$iterator = new LazyFileIterator('data.jsonl');

foreach ($iterator as $index => $object) {
    echo "Line {$index}: {$object->name}\n";
}

// Use with Collection for transformations
$collection = new Collection($iterator);
$filtered = $collection->lazyFilter(fn($obj) => $obj->active);
```

### 7. LazyProxyObject - PHP 8.4+ Lazy Objects

```php
use Omegaalfa\Collection\Util\LazyProxyObject;

class ExpensiveService {
    public function __construct() {
        // Heavy initialization
        sleep(2);
    }
    
    public function execute(): string {
        return "Service executed!";
    }
}

// Create lazy proxy
$factory = new LazyProxyObject(ExpensiveService::class);

// Object NOT instantiated yet!
$service = $factory->lazyProxy(fn() => new ExpensiveService());

// NOW it instantiates (on first method call)
echo $service->execute();  // Waits 2s, then "Service executed!"
```

---

## 🎯 Choosing the Right Class

### Use **Collection** when:
- ✅ Working with `Iterator` instances
- ✅ Need array-like access (`ArrayAccess`)
- ✅ Want both eager and lazy methods
- ✅ Migrating legacy code

### Use **Sequence** when:
- ✅ Need ordered list (0-indexed)
- ✅ Want immutability
- ✅ Working with small-to-medium datasets
- ✅ Type safety is important

### Use **Map** when:
- ✅ Need key-value pairs
- ✅ Want immutability
- ✅ Working with configuration, dictionaries
- ✅ Type safety is important

### Use **LazySequence** when:
- ✅ Large datasets (millions of items)
- ✅ Memory is constrained
- ✅ Need pipeline composition
- ✅ Can benefit from short-circuit evaluation

### Use **LazyMap** when:
- ✅ Values are expensive to compute
- ✅ Not all values will be accessed
- ✅ Need lazy initialization
- ✅ Dependency injection containers

### Use **LazyFileIterator** when:
- ✅ Processing large JSON line files
- ✅ Cannot load entire file in memory
- ✅ Streaming data processing

### Use **LazyProxyObject** when:
- ✅ Objects are expensive to instantiate
- ✅ PHP 8.4+ available
- ✅ Need true lazy object semantics
- ✅ Dependency injection, service containers

---

## 📖 API Reference

### Collection Methods (50+)

#### Creation
- `__construct(Iterator|array $items = [])`
- `lazyRange(int $start, int $end): Collection`
- `lazyObjects(array $factories, string $class): Collection`

#### Transformation (Eager)
- `map(callable $callback): Collection`
- `filter(callable $callback): Collection`
- `unique(): Collection`
- `reverse(): Collection`
- `chunk(int $size): Collection`
- `sort(callable $callback): Collection`
- `sortKeys(): Collection`

#### Transformation (Lazy)
- `lazyMap(callable $callback): Collection`
- `lazyFilter(callable $callback): Collection`
- `lazyChunk(int $size): Collection`
- `lazyTake(int $limit): Collection`
- `lazyPipeline(array $operations): Collection`
- `lazy(): Collection`

#### Access
- `first(): mixed`
- `last(): mixed`
- `at(int $index): mixed` (ArrayAccess)
- `contains(mixed $value): bool`
- `pluck(string|int $key): Collection`

#### Aggregation
- `count(): int`
- `sum(): int|float`
- `avg(): ?float`
- `min(): mixed`
- `max(): mixed`
- `reduce(callable $callback, mixed $initial): mixed`

#### Slicing
- `take(int $limit): Collection`
- `slice(int $offset, ?int $length): Collection`

#### Utilities
- `each(callable $callback): Collection`
- `isEmpty(): bool`
- `isNotEmpty(): bool`
- `isLazy(): bool`
- `materialize(): Collection`
- `toArray(): array`
- `keys(): Collection`
- `values(): Collection`

### Sequence Methods (30+)

#### Creation
- `static empty(): Sequence`
- `static of(...$values): Sequence`
- `static range(int $start, int $end): Sequence`
- `static from(iterable $items): Sequence`

#### Access
- `at(int $index): mixed`
- `first(): mixed`
- `last(): mixed`
- `indexOf(mixed $value): ?int`
- `contains(mixed $value): bool`

#### Modification (returns new instance)
- `append(mixed $value): Sequence`
- `prepend(mixed $value): Sequence`
- `insert(int $index, mixed $value): Sequence`
- `remove(int $index): Sequence`

#### Transformation
- `map(callable $fn): Sequence`
- `filter(callable $fn): Sequence`
- `flatMap(callable $fn): Sequence`
- `unique(): Sequence`
- `reverse(): Sequence`
- `sort(?callable $comparator = null): Sequence`

#### Slicing
- `take(int $n): Sequence`
- `skip(int $n): Sequence`
- `slice(int $start, int $length): Sequence`
- `chunk(int $size): Sequence`

#### Aggregation
- `reduce(callable $fn, mixed $initial): mixed`
- `sum(): int|float`
- `avg(): ?float`
- `min(): mixed`
- `max(): mixed`
- `count(): int`
- `isEmpty(): bool`

#### Conversion
- `toLazy(): LazySequence`
- `toMap(callable $keyMapper): Map`
- `toArray(): array`
- `join(string $separator): string`

### Map Methods (25+)

#### Creation
- `static empty(): Map`
- `static of(...$pairs): Map` (key1, val1, key2, val2, ...)
- `static from(array $array): Map`

#### Access
- `get(mixed $key): mixed`
- `getOrDefault(mixed $key, mixed $default): mixed`
- `has(mixed $key): bool`
- `keys(): Sequence`
- `values(): Sequence`

#### Modification (returns new instance)
- `put(mixed $key, mixed $value): Map`
- `putAll(iterable $pairs): Map`
- `remove(mixed $key): Map`
- `merge(Map $other): Map`

#### Transformation
- `map(callable $fn): Map` - `fn(key, value) => [newKey, newValue]`
- `mapKeys(callable $fn): Map` - `fn(key) => newKey`
- `mapValues(callable $fn): Map` - `fn(key, value) => newValue`
- `filter(callable $fn): Map` - `fn(key, value) => bool`
- `filterKeys(callable $fn): Map`
- `filterValues(callable $fn): Map`

#### Aggregation
- `reduce(callable $fn, mixed $initial): mixed`
- `each(callable $fn): void`
- `count(): int`
- `isEmpty(): bool`

#### Sorting
- `sortKeys(?callable $comparator = null): Map`
- `sortValues(?callable $comparator = null): Map`

#### Conversion
- `toLazy(): LazyMap`
- `toSequence(): Sequence` (of [key, value] pairs)
- `toArray(): array`

### LazySequence Methods (20+)

Same as `Sequence`, but all operations are lazy (generator-based).

**Key differences:**
- `toArray()`: Returns PHP `array` (materializes)
- `toEager()`: Returns `Sequence` (materializes)
- Operations chain without executing until iteration

### LazyMap Methods (15+)

Same as `Map`, but values are `Closure` instances.

**Additional methods:**
- `ofLazyObjects(array $classes, array $args = []): LazyMap` - PHP 8.4+ LazyProxyObject
- `ofLazyFactories(array $factories): LazyMap` - Custom factory closures

**Key differences:**
- Values must be `Closure` instances
- `get()`: Executes closure and returns result
- `toArray()`: Materializes all closures

### LazyFileIterator Methods

- `__construct(string $filePath)`
- `current(): mixed` - Current JSON object
- `key(): int` - Current line number
- `next(): void` - Move to next line
- `valid(): bool` - Has more lines
- `rewind(): void` - Reset to start

### LazyProxyObject Methods

- `__construct(string $class)`
- `lazyProxy(Closure $factory): object` - Creates lazy proxy
- `lazyGhost(Closure $initializer): object` - Creates lazy ghost

---

## ⚡ Performance

### Lazy vs Eager - Collection

```php
// ❌ EAGER - processes 1M elements
Collection::new([1...1000000])
    ->map(fn($x) => $x * 2)     // 1M iterations
    ->filter(fn($x) => $x > 100) // 1M iterations
    ->take(10);                  // Returns 10
// Time: ~1625ms, Memory: ~40MB

// ✅ LAZY - processes ~51 elements
Collection::lazyRange(1, 1000000)
    ->lazyMap(fn($x) => $x * 2)
    ->lazyFilter(fn($x) => $x > 100)
    ->lazyTake(10);
// Time: ~0.71ms, Memory: ~1KB
// 🚀 2290x FASTER!
```

### LazyMap with LazyProxyObject

```php
// ❌ EAGER - instantiates 100 objects
$users = [];
for ($i = 0; $i < 100; $i++) {
    $users[] = new User($i);  // Heavy constructor
}
// Time: ~10s for 100 objects

// ✅ LAZY - instantiates on access
$users = LazyMap::ofLazyObjects(
    array_fill(0, 100, User::class),
    ['connection' => $db]
);
$user1 = $users->get(0);  // Only 1 object created
// Time: ~1ms + ~100ms (for 1 object)
// 🚀 100x FASTER for partial access!
```

---

## 🧪 Testing

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test
./vendor/bin/phpunit tests/SequenceTest.php

# With coverage
./vendor/bin/phpunit --coverage-html coverage
```

---

## 📝 Examples

See complete usage examples in the `examples/` directory:
- [COMPLETE_USAGE_EXAMPLES.php](examples/COMPLETE_USAGE_EXAMPLES.php) - All methods demonstrated
- [examples_lazy_collection.php](examples/examples_lazy_collection.php) - Collection lazy methods
- [examples_lazymap_proxy.php](examples/examples_lazymap_proxy.php) - LazyMap + LazyProxyObject
- [examples_lazy.php](examples/examples_lazy.php) - Eager vs Lazy performance comparisons
- [examples.php](examples/examples.php) - Basic Collection usage

Run examples:
```bash
php examples/COMPLETE_USAGE_EXAMPLES.php
```

---

## 📖 Documentation

- **[API.md](docs/API.md)** - Complete API reference with all 150+ methods documented
- **[BENCHMARK.md](BENCHMARK.md)** - Performance comparison vs Doctrine Collections
- **[CHANGELOG.md](CHANGELOG.md)** - Version history and changes
- **[examples/README.md](examples/README.md)** - Examples directory index

---

## ⚡ Benchmark

Performance comparison with Doctrine Collections:

```bash
php benchmark.php
```

**Key Results**:
- 🚀 **Lazy Evaluation**: OmegaAlfa is **579x faster** than Doctrine
- 💾 **Memory Efficiency**: 95% less memory with lazy operations
- ⚡ **Eager Operations**: Doctrine ~1.5x faster on small datasets
- 🎯 **Best Use Case**: Large datasets with lazy evaluation

See [BENCHMARK.md](BENCHMARK.md) for detailed analysis.

---

## 🏗️ Architecture

### Design Principles

1. **Separation of Concerns**
   - `Sequence` → Ordered lists
   - `Map` → Key-value pairs
   - Never use plain arrays in public APIs

2. **Immutability**
   - `Sequence` and `Map` are `readonly` classes
   - All transformations return new instances
   - No side effects

3. **Lazy Evaluation**
   - `LazySequence` uses generators
   - `LazyMap` uses closures
   - `LazyProxyObject` uses PHP 8.4+ native lazy objects
   - Operations deferred until materialization

4. **Type Safety**
   - Full PHPDoc generics: `@template T`, `@implements SequenceInterface<T>`
   - Strict types enabled
   - No mixed returns unless necessary

### Inspired By

- [Larry Garfield - Never Use Arrays](https://www.garfieldtech.com/blog/never-use-arrays)
- Scala/Kotlin Collections
- Java Streams API
- Rust Iterators

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## 📧 Support

For issues, questions, or suggestions, please [open an issue](https://github.com/omegaalfa/collection/issues).

---

**Happy Coding! 🚀**
