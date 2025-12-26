<?php

/**
 * Examples demonstrating the enhanced Collection features
 */

require_once __DIR__ . '/vendor/autoload.php';

use Omegaalfa\Collection\Collection;
use Omegaalfa\Collection\LazyFileIterator;

// ============================================
// 1. BASIC OPERATIONS
// ============================================

echo "=== BASIC OPERATIONS ===\n\n";

$numbers = new Collection([1, 2, 3, 4, 5]);

echo "Original: " . json_encode($numbers->toArray()) . "\n";
echo "First: " . $numbers->first() . "\n";
echo "Last: " . $numbers->last() . "\n";
echo "Count: " . $numbers->count() . "\n";
echo "IsEmpty: " . ($numbers->isEmpty() ? 'true' : 'false') . "\n\n";

// ============================================
// 2. TRANSFORMATION
// ============================================

echo "=== TRANSFORMATION ===\n\n";

$squared = $numbers->map(fn($n) => $n * $n);
echo "Squared: " . json_encode($squared->toArray()) . "\n";

$even = $numbers->filter(fn($n) => $n % 2 === 0);
echo "Even numbers: " . json_encode($even->toArray()) . "\n\n";

// ============================================
// 3. AGGREGATION
// ============================================

echo "=== AGGREGATION ===\n\n";

echo "Sum: " . $numbers->sum() . "\n";
echo "Average: " . $numbers->avg() . "\n";
echo "Min: " . $numbers->min() . "\n";
echo "Max: " . $numbers->max() . "\n\n";

// ============================================
// 4. COMPLEX EXAMPLE WITH OBJECTS
// ============================================

echo "=== WORKING WITH OBJECTS ===\n\n";

class Product
{
	public function __construct(
		public string $name,
		public float $price,
		public int $quantity,
		public string $category
	) {}
}

$products = new Collection([
	new Product('Laptop', 1200.00, 5, 'Electronics'),
	new Product('Mouse', 25.00, 50, 'Electronics'),
	new Product('Desk', 300.00, 10, 'Furniture'),
	new Product('Chair', 150.00, 20, 'Furniture'),
	new Product('Monitor', 400.00, 15, 'Electronics'),
]);

// Get all product names
$names = $products->map(fn(Product $p) => $p->name);
echo "Product names: " . json_encode($names->toArray()) . "\n";

// Filter expensive products
$expensive = $products->filter(fn(Product $p) => $p->price > 200);
echo "Expensive products: " . $expensive->count() . "\n";

// Calculate total inventory value
$totalValue = $products->reduce(
	fn($carry, Product $p) => $carry + ($p->price * $p->quantity),
	0
);
echo "Total inventory value: $" . number_format($totalValue, 2) . "\n";

// Get electronics only
$electronics = $products
	->filter(fn(Product $p) => $p->category === 'Electronics')
	->map(fn(Product $p) => $p->name);
echo "Electronics: " . json_encode($electronics->toArray()) . "\n\n";

// ============================================
// 5. ARRAY ACCESS
// ============================================

echo "=== ARRAY ACCESS ===\n\n";

$collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);

echo "Access like array: \$collection['a'] = " . $collection['a'] . "\n";

$collection['d'] = 4;
echo "After adding 'd': " . json_encode($collection->toArray()) . "\n";

echo "Isset 'a': " . (isset($collection['a']) ? 'true' : 'false') . "\n";

unset($collection['a']);
echo "After unsetting 'a': " . json_encode($collection->toArray()) . "\n\n";

// ============================================
// 6. SLICING AND CHUNKING
// ============================================

echo "=== SLICING AND CHUNKING ===\n\n";

$range = new Collection(range(1, 10));

$firstThree = $range->take(3);
echo "First 3: " . json_encode($firstThree->toArray()) . "\n";

$lastTwo = $range->take(-2);
echo "Last 2: " . json_encode($lastTwo->toArray()) . "\n";

$middle = $range->slice(3, 4);
echo "Middle slice: " . json_encode($middle->toArray()) . "\n";

$chunks = $range->chunk(3);
echo "Chunks of 3: " . $chunks->count() . " chunks\n";
$chunks->each(function($chunk, $i) {
	echo "  Chunk " . ($i + 1) . ": " . json_encode($chunk->toArray()) . "\n";
});
echo "\n";

// ============================================
// 7. SORTING
// ============================================

echo "=== SORTING ===\n\n";

$unsorted = new Collection([3, 1, 4, 1, 5, 9, 2, 6]);

$ascending = $unsorted->sort(fn($a, $b) => $a <=> $b);
echo "Ascending: " . json_encode(array_values($ascending->toArray())) . "\n";

$descending = $unsorted->sort(fn($a, $b) => $b <=> $a);
echo "Descending: " . json_encode(array_values($descending->toArray())) . "\n";

$keysSorted = new Collection(['z' => 1, 'a' => 2, 'm' => 3]);
$sortedByKey = $keysSorted->sortKeys();
echo "Sorted by keys: " . json_encode($sortedByKey->toArray()) . "\n\n";

// ============================================
// 8. METHOD CHAINING
// ============================================

echo "=== METHOD CHAINING ===\n\n";

$result = (new Collection(range(1, 20)))
	->filter(fn($n) => $n % 2 === 0)           // Get even numbers
	->map(fn($n) => $n * 3)                     // Triple them
	->filter(fn($n) => $n > 20)                 // Keep only > 20
	->take(5)                                   // Take first 5
	->toArray();                                // Convert to array

echo "Pipeline result: " . json_encode(array_values($result)) . "\n\n";

// ============================================
// 9. UNIQUE AND REVERSE
// ============================================

echo "=== UNIQUE AND REVERSE ===\n\n";

$withDuplicates = new Collection([1, 2, 2, 3, 3, 3, 4, 4, 4, 4]);
$unique = $withDuplicates->unique();
echo "Unique: " . json_encode(array_values($unique->toArray())) . "\n";

$reversed = $numbers->reverse();
echo "Reversed: " . json_encode(array_values($reversed->toArray())) . "\n\n";

// ============================================
// 10. NESTED DATA
// ============================================

echo "=== NESTED DATA ===\n\n";

$users = new Collection([
	['name' => 'John', 'age' => 30, 'city' => 'New York'],
	['name' => 'Jane', 'age' => 25, 'city' => 'Los Angeles'],
	['name' => 'Bob', 'age' => 35, 'city' => 'Chicago'],
]);

$names = $users->pluck('name');
echo "User names: " . json_encode($names->toArray()) . "\n";

$cities = $users->pluck('city');
echo "Cities: " . json_encode($cities->toArray()) . "\n";

$collection = new Collection();
$nested = [
	'user' => [
		'profile' => [
			'address' => [
				'city' => 'São Paulo',
				'country' => 'Brazil'
			]
		]
	]
];
$city = $collection->searchValueKey($nested, 'city');
echo "Found city: " . $city . "\n\n";

// ============================================
// 11. CONTAINS AND VALIDATION
// ============================================

echo "=== CONTAINS ===\n\n";

$values = new Collection(['apple', 'banana', 'orange']);

echo "Contains 'banana': " . ($values->contains('banana') ? 'true' : 'false') . "\n";
echo "Contains 'grape': " . ($values->contains('grape') ? 'true' : 'false') . "\n\n";

// ============================================
// 12. KEY PRESERVATION
// ============================================

echo "=== KEY PRESERVATION ===\n\n";

$associative = new Collection([
	'first' => 10,
	'second' => 20,
	'third' => 30
]);

$doubled = $associative->map(fn($value, $key) => [
	'key' => $key,
	'original' => $value,
	'doubled' => $value * 2
]);

echo "With key access in map:\n";
foreach ($doubled as $key => $data) {
	echo "  {$key}: original={$data['original']}, doubled={$data['doubled']}\n";
}
echo "\n";

// ============================================
// 13. COUNTABLE INTERFACE
// ============================================

echo "=== COUNTABLE INTERFACE ===\n\n";

$countable = new Collection([1, 2, 3, 4, 5]);

echo "Using method: " . $countable->count() . "\n";
echo "Using count(): " . count($countable) . "\n";
echo "Using sizeof(): " . sizeof($countable) . "\n\n";

echo "All examples completed successfully!\n";
