<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Omegaalfa\Collection\Sequence;
use Omegaalfa\Collection\Map;
use Omegaalfa\Collection\LazySequence;
use Omegaalfa\Collection\LazyMap;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  LAZY EVALUATION: EAGER VS LAZY COMPARISON\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ============================================================================
// Example 1: Pipeline with take() - Massive performance gain!
// ============================================================================
echo "1️⃣  PIPELINE WITH TAKE - Short-Circuit Evaluation\n";
echo "───────────────────────────────────────────────────────────────\n";

$callCount = 0;

// EAGER - processes ALL 1 million items!
$start = microtime(true);
$eagerResult = Sequence::range(1, 1000000)
	->map(function ($x) use (&$callCount) {
		$callCount++;
		return $x * 2;
	})
	->take(5)
	->toArray();
$eagerTime = microtime(true) - $start;
$eagerCalls = $callCount;

// LAZY - stops after 5 items!
$callCount = 0;
$start = microtime(true);
$lazyResult = LazySequence::range(1, 1000000)
	->map(function ($x) use (&$callCount) {
		$callCount++;
		return $x * 2;
	})
	->take(5)
	->toArray();
$lazyTime = microtime(true) - $start;
$lazyCalls = $callCount;

echo "EAGER: {$eagerTime}s, {$eagerCalls} iterations\n";
echo "LAZY:  {$lazyTime}s, {$lazyCalls} iterations\n";
echo "🚀 Speedup: " . round($eagerTime / $lazyTime, 1) . "x faster!\n";
echo "💾 Efficiency: " . round(($eagerCalls - $lazyCalls) / $eagerCalls * 100, 1) . "% less work!\n\n";


// ============================================================================
// Example 2: first() - Stops immediately
// ============================================================================
echo "2️⃣  FIRST() - Immediate Short-Circuit\n";
echo "───────────────────────────────────────────────────────────────\n";

$callCount = 0;

// EAGER - iterates all items
$start = microtime(true);
$eagerFirst = Sequence::range(1, 100000)
	->map(function ($x) use (&$callCount) {
		$callCount++;
		return $x * 2;
	})
	->first();
$eagerTime = microtime(true) - $start;
$eagerCalls = $callCount;

// LAZY - stops after 1st item!
$callCount = 0;
$start = microtime(true);
$lazyFirst = LazySequence::range(1, 100000)
	->map(function ($x) use (&$callCount) {
		$callCount++;
		return $x * 2;
	})
	->first();
$lazyTime = microtime(true) - $start;
$lazyCalls = $callCount;

echo "EAGER: {$eagerTime}s, {$eagerCalls} iterations\n";
echo "LAZY:  {$lazyTime}s, {$lazyCalls} iterations\n";
echo "🚀 Speedup: " . round($eagerTime / $lazyTime, 1) . "x faster!\n";
echo "Result: {$lazyFirst}\n\n";


// ============================================================================
// Example 3: contains() - Stops when found
// ============================================================================
echo "3️⃣  CONTAINS() - Search Short-Circuit\n";
echo "───────────────────────────────────────────────────────────────\n";

$callCount = 0;

// EAGER - processes everything
$start = microtime(true);
$eagerContains = Sequence::range(1, 100000)
	->map(function ($x) use (&$callCount) {
		$callCount++;
		return $x;
	})
	->contains(100);
$eagerTime = microtime(true) - $start;
$eagerCalls = $callCount;

// LAZY - stops at 100!
$callCount = 0;
$start = microtime(true);
$lazyContains = LazySequence::range(1, 100000)
	->map(function ($x) use (&$callCount) {
		$callCount++;
		return $x;
	})
	->contains(100);
$lazyTime = microtime(true) - $start;
$lazyCalls = $callCount;

echo "EAGER: {$eagerTime}s, {$eagerCalls} iterations\n";
echo "LAZY:  {$lazyTime}s, {$lazyCalls} iterations\n";
echo "🚀 Speedup: " . round($eagerTime / $lazyTime, 1) . "x faster!\n";
echo "Found at position: {$lazyCalls}\n\n";


// ============================================================================
// Example 4: any() - Predicate short-circuit
// ============================================================================
echo "4️⃣  ANY() - Predicate Short-Circuit\n";
echo "───────────────────────────────────────────────────────────────\n";

$callCount = 0;

// Find first even number > 1000
$start = microtime(true);
$eagerAny = Sequence::range(1, 100000)
	->map(function ($x) use (&$callCount) {
		$callCount++;
		return $x;
	})
	->toArray();
$hasLarge = false;
foreach ($eagerAny as $x) {
	if ($x > 1000) {
		$hasLarge = true;
		break;
	}
}
$eagerTime = microtime(true) - $start;
$eagerCalls = $callCount;

$callCount = 0;
$start = microtime(true);
$lazyAny = LazySequence::range(1, 100000)
	->map(function ($x) use (&$callCount) {
		$callCount++;
		return $x;
	})
	->any(fn($x) => $x > 1000);
$lazyTime = microtime(true) - $start;
$lazyCalls = $callCount;

echo "EAGER: {$eagerTime}s, {$eagerCalls} iterations\n";
echo "LAZY:  {$lazyTime}s, {$lazyCalls} iterations\n";
echo "🚀 Speedup: " . round($eagerTime / $lazyTime, 1) . "x faster!\n";
echo "Stopped at: {$lazyCalls}\n\n";


// ============================================================================
// Example 5: LazyMap - Database lazy loading simulation
// ============================================================================
echo "5️⃣  LAZY MAP - Database Lazy Loading\n";
echo "───────────────────────────────────────────────────────────────\n";

$queryCount = 0;

// Simulate expensive DB queries
$loadUser = function ($id) use (&$queryCount) {
	$queryCount++;
	usleep(10000); // 10ms simulated query
	return ['id' => $id, 'name' => "User$id"];
};

// EAGER - loads everything immediately
$queryCount = 0;
$start = microtime(true);
$eagerUsers = Map::from([
	'john' => $loadUser(1),
	'jane' => $loadUser(2),
	'bob' => $loadUser(3),
	'alice' => $loadUser(4),
	'charlie' => $loadUser(5)
]);
$john = $eagerUsers->get('john');
$eagerTime = microtime(true) - $start;
$eagerQueries = $queryCount;

// LAZY - loads on demand
$queryCount = 0;
$start = microtime(true);
$lazyUsers = LazyMap::from([
	'john' => fn() => $loadUser(1),
	'jane' => fn() => $loadUser(2),
	'bob' => fn() => $loadUser(3),
	'alice' => fn() => $loadUser(4),
	'charlie' => fn() => $loadUser(5)
]);
$john = $lazyUsers->get('john'); // Only john loaded!
$lazyTime = microtime(true) - $start;
$lazyQueries = $queryCount;

echo "EAGER: {$eagerTime}s, {$eagerQueries} DB queries\n";
echo "LAZY:  {$lazyTime}s, {$lazyQueries} DB queries\n";
echo "🚀 Speedup: " . round($eagerTime / $lazyTime, 1) . "x faster!\n";
echo "💾 Saved queries: " . ($eagerQueries - $lazyQueries) . " (" . round(($eagerQueries - $lazyQueries) / $eagerQueries * 100) . "%)\n";
echo "Materialized: {$lazyUsers->getMaterializedCount()} / {$lazyUsers->count()}\n\n";


// ============================================================================
// Example 6: Chunk processing - Memory efficiency
// ============================================================================
echo "6️⃣  CHUNK PROCESSING - Memory Efficiency\n";
echo "───────────────────────────────────────────────────────────────\n";

// EAGER - creates all chunks in memory
$start = microtime(true);
$eagerChunks = Sequence::range(1, 10000)->chunk(100);
$eagerMemory = memory_get_usage();
$eagerCount = $eagerChunks->count();
$eagerTime = microtime(true) - $start;

// LAZY - creates chunks on demand
$start = microtime(true);
$lazyChunks = LazySequence::range(1, 10000)->chunk(100);
$lazyMemory = memory_get_usage();
$lazyCount = $lazyChunks->count();
$lazyTime = microtime(true) - $start;

$memoryDiff = $eagerMemory - $lazyMemory;

echo "EAGER: {$eagerTime}s, {$eagerCount} chunks created\n";
echo "LAZY:  {$lazyTime}s, {$lazyCount} chunks (on-demand)\n";
echo "🚀 Speedup: " . round($eagerTime / $lazyTime, 1) . "x faster!\n";
echo "💾 Memory saved: " . round($memoryDiff / 1024, 1) . " KB\n\n";


// ============================================================================
// Example 7: Complex pipeline - Real-world scenario
// ============================================================================
echo "7️⃣  COMPLEX PIPELINE - Real-World Scenario\n";
echo "───────────────────────────────────────────────────────────────\n";

$callCount = 0;

// Find first 20 numbers divisible by 3 or 5, squared, under 10000
$start = microtime(true);
$eagerPipeline = Sequence::range(1, 1000000)
	->filter(function ($x) use (&$callCount) {
		$callCount++;
		return $x % 3 === 0 || $x % 5 === 0;
	})
	->map(fn($x) => $x * $x)
	->filter(fn($x) => $x < 10000)
	->take(20)
	->toArray();
$eagerTime = microtime(true) - $start;
$eagerOps = $callCount;

$callCount = 0;
$start = microtime(true);
$lazyPipeline = LazySequence::range(1, 1000000)
	->filter(function ($x) use (&$callCount) {
		$callCount++;
		return $x % 3 === 0 || $x % 5 === 0;
	})
	->map(fn($x) => $x * $x)
	->filter(fn($x) => $x < 10000)
	->take(20)
	->toArray();
$lazyTime = microtime(true) - $start;
$lazyOps = $callCount;

echo "EAGER: {$eagerTime}s, {$eagerOps} filter operations\n";
echo "LAZY:  {$lazyTime}s, {$lazyOps} filter operations\n";
echo "🚀 Speedup: " . round($eagerTime / $lazyTime, 1) . "x faster!\n";
echo "💾 Efficiency: " . round(($eagerOps - $lazyOps) / $eagerOps * 100, 1) . "% less work!\n";
echo "Result: " . implode(', ', array_slice($lazyPipeline, 0, 5)) . "...\n\n";


// ============================================================================
// Example 8: isEmpty() check - Minimal evaluation
// ============================================================================
echo "8️⃣  ISEMPTY() - Minimal Evaluation\n";
echo "───────────────────────────────────────────────────────────────\n";

$callCount = 0;

// EAGER - processes all
$start = microtime(true);
$eagerIsEmpty = Sequence::range(1, 100000)
	->filter(function ($x) use (&$callCount) {
		$callCount++;
		return $x > 50000;
	})
	->isEmpty();
$eagerTime = microtime(true) - $start;
$eagerOps = $callCount;

$callCount = 0;
$start = microtime(true);
$lazyIsEmpty = LazySequence::range(1, 100000)
	->filter(function ($x) use (&$callCount) {
		$callCount++;
		return $x > 50000;
	})
	->isEmpty();
$lazyTime = microtime(true) - $start;
$lazyOps = $callCount;

echo "EAGER: {$eagerTime}s, {$eagerOps} operations\n";
echo "LAZY:  {$lazyTime}s, {$lazyOps} operations\n";
echo "🚀 Speedup: " . round($eagerTime / $lazyTime, 1) . "x faster!\n";
echo "Empty: " . ($lazyIsEmpty ? 'true' : 'false') . "\n\n";


// ============================================================================
// Summary
// ============================================================================
echo "═══════════════════════════════════════════════════════════════\n";
echo "  🎯 WHEN TO USE EACH APPROACH\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✅ USE EAGER (Sequence/Map):\n";
echo "   • Small collections (< 1,000 items)\n";
echo "   • Need all elements processed\n";
echo "   • Random access patterns\n";
echo "   • Easier debugging\n\n";

echo "⚡ USE LAZY (LazySequence/LazyMap):\n";
echo "   • Large collections (> 10,000 items)\n";
echo "   • Pipelines with take/first/contains\n";
echo "   • Expensive computations\n";
echo "   • Database lazy loading\n";
echo "   • Memory constrained\n\n";

echo "═══════════════════════════════════════════════════════════════\n\n";
