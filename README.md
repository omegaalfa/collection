# collection

This package is a PHP library that provides a simple and efficient way to work with collections of data. It provides a wide range of methods for manipulating, filtering, and transforming collections, making it a powerful tool for any PHP developer.

## Installation

```bash
composer require omegaalfa/collection
```

# Prerequisites

PHP 8.1 or higher

# Methods
The Collection class provides the following methods:

Collection Class
- __construct(Iterator|array $items = []): Creates a new Collection instance, optionally initializing it with an array or an Iterator.
- getIterator(): Traversable: Returns an iterator for the collection, allowing iteration over its items.
- map(callable $callback): Collection: Applies a callback function to each item in the collection and returns a new Collection with the transformed items.
- filter(callable $callback): Collection: Filters the collection based on a given callback function, returning a new Collection with only the items that satisfy the condition.
- each(callable $callback): Collection: Applies a callback function to each item in the collection, without modifying the original collection.
- count(): int: Returns the number of items in the collection.
- searchValueKey(array $array, string $key): mixed: Searches for a value in a multidimensional array based on a given key.
- add(mixed $item): void: Adds a new item to the collection.
- remove(mixed $item): void: Removes an item from the collection.
- arrayToGenerator(array $array): Generator: Converts an array into a generator, allowing iteration over its elements.
- toArray(): array: Returns the collection as an array.

LazyFileIterator Class
- __construct(string $filePath): Creates a new LazyFileIterator instance, specifying the path to the JSON file.
- current(): mixed: Returns the current JSON object decoded from the file line.
- next(): void: Moves the iterator to the next line in the file.
- key(): int: Returns the current key (line number) of the iterator.
- valid(): bool: Checks if the iterator is pointing to a valid line in the file.
- rewind(): void: Resets the iterator to the beginning of the file.

# Examples

```php
use OmegaAlfa\Collection\Collection;
use OmegaAlfa\Collection\LazyFileIterator;

// Create a new collection from an array
$collection = new Collection([1, 2, 3, 4, 5]);

// Iterate over the collection
foreach ($collection as $item) {
    echo $item . PHP_EOL;
}

// Map the collection
$squaredNumbers = $collection->map(function ($item) {
    return $item * $item;
});

// Filter the collection
$evenNumbers = $collection->filter(function ($item) {
    return $item % 2 === 0;
});

// Apply a callback to each item in the collection
$collection->each(function ($item) {
    echo "Item: $item" . PHP_EOL;
});

// Get the number of items in the collection
$count = $collection->count();

// Add an item to the collection
$collection->add(6);

// Remove an item from the collection
$collection->remove(3);

// Convert the collection to an array
$array = $collection->toArray();

// Search for a value in a multidimensional array
$value = $collection->searchValueKey([
    'name' => 'John Doe',
    'address' => [
        'street' => 'Main Street',
        'city' => 'Anytown',
    ],
], 'city');

echo $value; // Output: Anytown

$iterator = new LazyFileIterator('path/to/your/json_file.json');
$collection = new Collection($iterator);

foreach ($collection as $item) {
    // Process each JSON object from the file
    echo $item->name . PHP_EOL;
}

```

# Contributing
Feel free to submit issues or pull requests. For major changes, please open an issue first to discuss what you would like to change.

# License
This project is licensed under the MIT License. See the LICENSE file for details.
