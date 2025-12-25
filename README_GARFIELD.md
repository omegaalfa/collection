# Collection Library - Following "Never Use Arrays" Philosophy

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A modern PHP collection library implementing **Sequence** (ordered list) and **Map** (key-value dictionary) as proper data structures, following Larry Garfield's ["Never Use Arrays"](https://presentations.garfieldtech.com/slides-never-use-arrays/) philosophy.

## 🎯 Philosophy

### Why Not Just Use Arrays?

PHP arrays try to be everything: lists, dictionaries, sets, queues, stacks. This leads to:
- ❌ Unclear intent in code
- ❌ Type confusion
- ❌ Accidental bugs
- ❌ Poor IDE support

### The Better Way

Use **specific data structures** for specific purposes:

| Type | Purpose | Example |
|------|---------|---------|
| **Sequence** | Ordered list of values | `[1, 2, 3, 4]` |
| **Map** | Key-value pairs | `['name' => 'John', 'age' => 30]` |
| ~~Array~~ | Don't use! | ❌ |

## 📦 Installation

```bash
composer require omegaalfa/collection
```

## 🚀 Quick Start

### Sequence - Ordered List

```php
use Omegaalfa\Collection\Sequence;

// Create sequence
$numbers = Sequence::of(1, 2, 3, 4, 5);

// Immutable transformations
$doubled = $numbers->map(fn($x) => $x * 2);
$evens = $numbers->filter(fn($x) => $x % 2 === 0);

// Fluent chaining
$result = Sequence::range(1, 100)
    ->filter(fn($x) => $x % 3 === 0)
    ->map(fn($x) => $x * $x)
    ->take(10);

echo $result->join(', ');  // 9, 36, 81, 144, 225, 324, 441, 576, 729, 900
```

### Map - Key-Value Dictionary

```php
use Omegaalfa\Collection\Map;

// Create map
$user = Map::from([
    'name' => 'John',
    'email' => 'john@example.com',
    'age' => 30
]);

// Immutable operations
$updated = $user->put('age', 31);
$without = $user->remove('email');

// Transform values
$uppercased = $user->mapValues(fn($key, $value) => 
    is_string($value) ? strtoupper($value) : $value
);
```

## 📚 Complete API Reference

### Sequence API

#### Creation

```php
Sequence::empty()                    // Empty sequence
Sequence::of(1, 2, 3)               // From values
Sequence::from([1, 2, 3])           // From array/iterable
Sequence::range(1, 10)              // Range of numbers
```

#### Access

```php
$seq->at(0)                         // Get element at index (throws OutOfBoundsException)
$seq->first()                       // First element (null if empty)
$seq->last()                        // Last element (null if empty)
$seq->contains(5)                   // Check if contains value
$seq->indexOf(3)                    // Find index of value (null if not found)
```

#### Immutable Transformations

```php
$seq->append(6)                     // Add to end
$seq->prepend(0)                    // Add to beginning
$seq->insert(2, 99)                 // Insert at index
$seq->remove(1)                     // Remove at index
$seq->slice(1, 3)                   // Extract portion
$seq->reverse()                     // Reverse order
$seq->sort(fn($a, $b) => $a <=> $b) // Sort with comparator
```

#### Functional Operations

```php
$seq->map(fn($x, $i) => $x * 2)     // Transform each element
$seq->filter(fn($x, $i) => $x > 0)  // Keep matching elements
$seq->flatMap(fn($x) => [$x, $x*2]) // Map and flatten
$seq->reduce(fn($c, $x) => $c + $x, 0) // Reduce to single value
$seq->each(fn($x) => echo $x)       // Side effects
```

#### Utilities

```php
$seq->take(5)                       // First N elements
$seq->skip(3)                       // Skip N elements
$seq->unique()                      // Remove duplicates
$seq->chunk(3)                      // Split into chunks
$seq->join(', ')                    // Join into string
```

#### Aggregation

```php
$seq->count()                       // Number of elements
$seq->isEmpty()                     // Check if empty
$seq->sum()                         // Sum of numeric values
$seq->avg()                         // Average
$seq->min()                         // Minimum value
$seq->max()                         // Maximum value
```

#### Conversion

```php
$seq->toArray()                     // Convert to array
$seq->toMap(fn($x, $i) => [$i, $x]) // Convert to Map
```

---

### Map API

#### Creation

```php
Map::empty()                        // Empty map
Map::from(['a' => 1, 'b' => 2])    // From array/iterable
Map::of('a', 1, 'b', 2)            // From key-value pairs
```

#### Access

```php
$map->get('key')                    // Get value (throws OutOfBoundsException)
$map->getOrDefault('key', 'default') // Get or return default
$map->has('key')                    // Check if key exists
$map->keys()                        // Sequence of keys
$map->values()                      // Sequence of values
```

#### Immutable Transformations

```php
$map->put('key', 'value')           // Add/update entry
$map->putAll(['a' => 1, 'b' => 2])  // Add multiple entries
$map->remove('key')                 // Remove entry
$map->merge($otherMap)              // Merge two maps
```

#### Functional Operations

```php
$map->map(fn($k, $v) => [$k, $v*2]) // Transform entries
$map->mapValues(fn($k, $v) => $v*2) // Transform values only
$map->mapKeys(fn($k) => strtoupper($k)) // Transform keys only
$map->filter(fn($k, $v) => $v > 0)  // Keep matching entries
$map->filterKeys(fn($k) => $k !== 'x') // Filter by keys
$map->filterValues(fn($v) => $v > 0) // Filter by values
$map->reduce(fn($c, $k, $v) => ..., 0) // Reduce to single value
$map->each(fn($k, $v) => ...)       // Side effects
```

#### Sorting

```php
$map->sortValues(fn($a, $b) => $a <=> $b) // Sort by values
$map->sortKeys()                    // Sort by keys
```

#### Utilities

```php
$map->count()                       // Number of entries
$map->isEmpty()                     // Check if empty
```

#### Conversion

```php
$map->toArray()                     // Convert to array
$map->toSequence()                  // Convert to Sequence of [key, value] pairs
```

---

## 💡 Real World Examples

### Processing Products

```php
use Omegaalfa\Collection\Sequence;

class Product {
    public function __construct(
        public string $name,
        public float $price,
        public string $category
    ) {}
}

$products = Sequence::of(
    new Product('Laptop', 1200, 'Electronics'),
    new Product('Mouse', 25, 'Electronics'),
    new Product('Desk', 300, 'Furniture')
);

// Get affordable electronics
$affordable = $products
    ->filter(fn($p) => $p->category === 'Electronics')
    ->filter(fn($p) => $p->price < 500)
    ->map(fn($p) => $p->name);

echo $affordable->join(', ');  // Mouse

// Calculate total value
$total = $products
    ->map(fn($p) => $p->price)
    ->sum();  // 1525
```

### Configuration Management

```php
use Omegaalfa\Collection\Map;

$defaults = Map::from([
    'theme' => 'light',
    'fontSize' => 14,
    'showLineNumbers' => true
]);

$userPrefs = Map::from([
    'theme' => 'dark',
    'fontSize' => 16
]);

// Merge with user preferences
$config = $defaults->merge($userPrefs);
// Result: {theme: 'dark', fontSize: 16, showLineNumbers: true}
```

### Data Processing Pipeline

```php
use Omegaalfa\Collection\Sequence;

$result = Sequence::range(1, 100)
    ->filter(fn($n) => $n % 3 === 0 || $n % 5 === 0)  // Multiples of 3 or 5
    ->map(fn($n) => $n * $n)                          // Square them
    ->filter(fn($n) => $n < 1000)                     // Under 1000
    ->take(10);                                       // First 10

echo $result->join(', ');
// 9, 25, 36, 100, 144, 225, 324, 400, 441, 625
```

### Working with JSON APIs

```php
use Omegaalfa\Collection\Sequence;
use Omegaalfa\Collection\Map;

$json = '[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]';
$users = Sequence::from(json_decode($json));

// Extract names
$names = $users
    ->map(fn($user) => $user->name)
    ->join(', ');  // Alice, Bob

// Create lookup map
$userMap = $users->toMap(fn($user) => [$user->id, $user->name]);
echo $userMap->get(1);  // Alice
```

## 🔄 Migration from Generic Arrays

### Before (Generic Arrays)

```php
// ❌ Unclear: is this a list or a dictionary?
$data = [1, 2, 3, 4];
$data[] = 5;  // Mutation

// ❌ What type of values? What keys?
$config = ['name' => 'John', 'age' => 30];
unset($config['age']);  // Mutation
```

### After (Sequence/Map)

```php
// ✅ Clear: it's an ordered list
$data = Sequence::of(1, 2, 3, 4);
$newData = $data->append(5);  // Immutable

// ✅ Clear: it's a key-value dictionary
$config = Map::from(['name' => 'John', 'age' => 30]);
$updated = $config->remove('age');  // Immutable
```

## 🎨 Type Safety with Generics

Full PHPDoc generic support for static analysis:

```php
/** @var Sequence<int> */
$numbers = Sequence::of(1, 2, 3);

/** @var Map<string, User> */
$users = Map::from(['john' => new User('John')]);

/** @var Sequence<string> */
$names = $numbers->map(fn(int $n): string => "Number $n");
```

## ✨ Key Features

- ✅ **Immutable** - All operations return new instances
- ✅ **Type Safe** - Full generic support for IDEs and static analyzers
- ✅ **Fluent** - Chainable method calls
- ✅ **Performant** - Optimized internal array operations
- ✅ **Well Tested** - Comprehensive test suite
- ✅ **Zero Dependencies** - Pure PHP 8.1+

## 🔄 Backward Compatibility

**Collection class still available!** We maintain the original `Collection` class for backward compatibility:

```php
use Omegaalfa\Collection\Collection;

// Old API still works
$collection = new Collection([1, 2, 3]);
$collection->add(4);  // Mutable
```

**When to use what:**

| Use Case | Recommended |
|----------|-------------|
| New code | **Sequence** or **Map** |
| Ordered list | **Sequence** |
| Key-value pairs | **Map** |
| Legacy code | **Collection** |
| Need mutability | **Collection** |

## 🧪 Testing

```bash
composer test
```

Run specific tests:
```bash
vendor/bin/phpunit tests/SequenceTest.php
vendor/bin/phpunit tests/MapTest.php
```

## 📖 Further Reading

- [Larry Garfield - "Never Use Arrays"](https://presentations.garfieldtech.com/slides-never-use-arrays/)
- [PHP RFC: Enumerations](https://wiki.php.net/rfc/enumerations)
- [Functional Programming in PHP](https://www.php.net/manual/en/language.types.callable.php)

## 🤝 Contributing

Contributions welcome! Please follow these principles:

1. **Maintain Immutability** - Never modify internal state
2. **Type Safety** - Use proper PHPDoc generics
3. **Clear Intent** - Method names should be self-documenting
4. **Test Coverage** - Add tests for all new features

## 📄 License

MIT License - see [LICENSE](LICENSE) file.

## 🙏 Acknowledgments

- Larry Garfield for the "Never Use Arrays" philosophy
- Laravel Collections for API inspiration
- Doctrine Collections for type safety patterns

---

**Stop using arrays. Start using Sequence and Map.** 🚀
