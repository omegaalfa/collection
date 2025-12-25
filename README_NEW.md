# Collection - PHP Collection Library

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A powerful and efficient PHP library for working with collections of data. Provides a comprehensive set of methods for manipulating, filtering, and transforming collections with support for lazy loading.

## 🚀 Features

- **Type-safe** - Full PHPDoc generics support
- **Lazy Loading** - Efficient memory usage with iterators
- **Immutable Operations** - Methods like `map()`, `filter()` return new instances
- **Array Access** - Implements `ArrayAccess` interface
- **Countable** - Implements `Countable` interface
- **Chainable** - Fluent interface for method chaining
- **Rich API** - 30+ methods for data manipulation

## 📦 Installation

```bash
composer require omegaalfa/collection
```

## 📋 Requirements

- PHP 8.1 or higher

## 📖 Usage

### Basic Usage

```php
use Omegaalfa\Collection\Collection;

// Create from array
$collection = new Collection([1, 2, 3, 4, 5]);

// Iterate
foreach ($collection as $item) {
    echo $item . PHP_EOL;
}

// Count
echo $collection->count(); // 5

// Array access
echo $collection[0]; // 1
$collection[5] = 6;
```

### Transformation Methods

#### map()
Apply a callback to each item and return a new collection:

```php
$collection = new Collection([1, 2, 3]);
$squared = $collection->map(fn($item) => $item * $item);
// Result: [1, 4, 9]

// With keys
$collection = new Collection(['a' => 1, 'b' => 2]);
$doubled = $collection->map(fn($value, $key) => $value * 2);
// Result: ['a' => 2, 'b' => 4]
```

#### filter()
Filter items based on a callback:

```php
$collection = new Collection([1, 2, 3, 4, 5]);
$even = $collection->filter(fn($item) => $item % 2 === 0);
// Result: [2, 4]
```

#### each()
Execute a callback for each item (doesn't modify collection):

```php
$collection = new Collection([1, 2, 3]);
$collection->each(fn($item) => echo $item . PHP_EOL);
```

### Aggregation Methods

#### reduce()
Reduce collection to a single value:

```php
$collection = new Collection([1, 2, 3, 4]);
$sum = $collection->reduce(fn($carry, $item) => $carry + $item, 0);
// Result: 10
```

#### sum()
Calculate sum of numeric values:

```php
$collection = new Collection([1, 2, 3, 4]);
echo $collection->sum(); // 10
```

#### avg()
Calculate average:

```php
$collection = new Collection([1, 2, 3, 4]);
echo $collection->avg(); // 2.5
```

#### min() / max()
Find minimum or maximum value:

```php
$collection = new Collection([3, 1, 4, 2]);
echo $collection->min(); // 1
echo $collection->max(); // 4
```

### Array Methods

#### pluck()
Extract a column from arrays/objects:

```php
$collection = new Collection([
    ['id' => 1, 'name' => 'John'],
    ['id' => 2, 'name' => 'Jane'],
]);
$names = $collection->pluck('name');
// Result: ['John', 'Jane']
```

#### keys() / values()
Get all keys or values:

```php
$collection = new Collection(['a' => 1, 'b' => 2]);
$keys = $collection->keys();     // ['a', 'b']
$values = $collection->values(); // [1, 2]
```

#### unique()
Remove duplicate values:

```php
$collection = new Collection([1, 2, 2, 3, 3, 3]);
$unique = $collection->unique();
// Result: [1, 2, 3]
```

#### reverse()
Reverse the collection:

```php
$collection = new Collection([1, 2, 3]);
$reversed = $collection->reverse();
// Result: [3, 2, 1]
```

#### chunk()
Split into smaller collections:

```php
$collection = new Collection([1, 2, 3, 4, 5]);
$chunks = $collection->chunk(2);
// Result: [[1, 2], [3, 4], [5]]
```

### Sorting Methods

#### sort()
Sort with custom callback:

```php
$collection = new Collection([3, 1, 4, 2]);
$sorted = $collection->sort(fn($a, $b) => $a <=> $b);
// Result: [1, 2, 3, 4]
```

#### sortKeys()
Sort by keys:

```php
$collection = new Collection(['c' => 3, 'a' => 1, 'b' => 2]);
$sorted = $collection->sortKeys();
// Result: ['a' => 1, 'b' => 2, 'c' => 3]
```

### Slicing Methods

#### slice()
Extract a portion of the collection:

```php
$collection = new Collection([1, 2, 3, 4, 5]);
$slice = $collection->slice(1, 3);
// Result: [2, 3, 4]
```

#### take()
Take first N items (or last N if negative):

```php
$collection = new Collection([1, 2, 3, 4, 5]);
$first3 = $collection->take(3);    // [1, 2, 3]
$last2 = $collection->take(-2);    // [4, 5]
```

### Inspection Methods

#### first() / last()
Get first or last item:

```php
$collection = new Collection([1, 2, 3]);
echo $collection->first(); // 1
echo $collection->last();  // 3
```

#### isEmpty() / isNotEmpty()
Check if collection is empty:

```php
$collection = new Collection([]);
$collection->isEmpty();    // true
$collection->isNotEmpty(); // false
```

#### contains()
Check if value exists:

```php
$collection = new Collection([1, 2, 3]);
$collection->contains(2); // true
$collection->contains(5); // false
```

#### count()
Get number of items (Countable interface):

```php
$collection = new Collection([1, 2, 3]);
echo $collection->count(); // 3
echo count($collection);   // 3 (works with count() function)
```

### Mutation Methods

> ⚠️ These methods modify the collection in place

#### add()
Add item to collection:

```php
$collection = new Collection([1, 2, 3]);
$collection->add(4);
// Result: [1, 2, 3, 4]
```

#### remove()
Remove item from collection:

```php
$collection = new Collection([1, 2, 3]);
$collection->remove(2);
// Result: [1, 3]
```

#### setAttribute() / getAttribute()
Set/get by key:

```php
$collection = new Collection(['a' => 1]);
$collection->setAttribute('b', 2);
echo $collection->getAttribute('b'); // 2
```

### Conversion Methods

#### toArray()
Convert to array:

```php
$collection = new Collection([1, 2, 3]);
$array = $collection->toArray();
// Result: [1, 2, 3]
```

### Utility Methods

#### searchValueKey()
Recursively search for a key in nested arrays:

```php
$data = [
    'user' => [
        'address' => [
            'city' => 'New York'
        ]
    ]
];
$collection = new Collection();
$city = $collection->searchValueKey($data, 'city');
// Result: 'New York'
```

## 🔄 Lazy Loading with LazyFileIterator

Process large JSON files line by line without loading into memory:

```php
use Omegaalfa\Collection\LazyFileIterator;
use Omegaalfa\Collection\Collection;

// Read large JSON file (one JSON object per line)
$iterator = new LazyFileIterator('large-file.jsonl');
$collection = new Collection($iterator);

// Process items lazily
$filtered = $collection
    ->filter(fn($item) => $item->active === true)
    ->map(fn($item) => $item->name);

foreach ($filtered as $name) {
    echo $name . PHP_EOL;
}
```

### LazyFileIterator Methods

- `__construct(string $filePath)` - Create iterator for file
- `current(): mixed` - Get current decoded JSON object
- `next(): void` - Move to next line
- `key(): int` - Get current line number
- `valid(): bool` - Check if current position is valid
- `rewind(): void` - Reset to beginning

## 🎯 Method Chaining

Combine multiple operations:

```php
$result = (new Collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]))
    ->filter(fn($n) => $n % 2 === 0)  // [2, 4, 6, 8, 10]
    ->map(fn($n) => $n * 2)            // [4, 8, 12, 16, 20]
    ->take(3)                          // [4, 8, 12]
    ->toArray();                       // Convert to array
```

## 🔍 Type Safety

Full PHPDoc generic support for type checking with static analysis tools:

```php
/** @var Collection<string, User> $users */
$users = new Collection(['john' => new User('John')]);

/** @var Collection<int, string> $names */
$names = $users->map(fn(User $user) => $user->getName());
```

## 🧪 Testing

```bash
composer test
```

## 📝 API Reference

### Transformation
- `map(callable $callback): Collection` - Transform each item
- `filter(callable $callback): Collection` - Filter items
- `each(callable $callback): static` - Iterate without modification
- `reverse(): Collection` - Reverse order
- `unique(): Collection` - Remove duplicates
- `chunk(int $size): Collection` - Split into chunks

### Aggregation
- `reduce(callable $callback, mixed $initial): mixed` - Reduce to single value
- `sum(): int|float` - Sum numeric values
- `avg(): ?float` - Calculate average
- `min(): mixed` - Find minimum
- `max(): mixed` - Find maximum

### Sorting
- `sort(callable $callback): Collection` - Sort with callback
- `sortKeys(): Collection` - Sort by keys

### Slicing
- `slice(int $offset, ?int $length): Collection` - Extract portion
- `take(int $limit): Collection` - Take first/last N items
- `first(): mixed` - Get first item
- `last(): mixed` - Get last item

### Inspection
- `count(): int` - Count items
- `isEmpty(): bool` - Check if empty
- `isNotEmpty(): bool` - Check if not empty
- `contains(mixed $value): bool` - Check if value exists

### Array Operations
- `pluck(string|int $key): Collection` - Extract column
- `keys(): Collection` - Get all keys
- `values(): Collection` - Get all values

### Mutation
- `add(mixed $item): void` - Add item
- `remove(mixed $item): void` - Remove item
- `setAttribute(mixed $key, mixed $value): void` - Set by key
- `getAttribute(mixed $key): mixed` - Get by key

### Conversion
- `toArray(): array` - Convert to array
- `getIterator(): Traversable` - Get iterator

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

Inspired by Laravel Collections and modern functional programming practices.

## 📧 Support

For issues, questions, or suggestions, please open an issue on GitHub.
