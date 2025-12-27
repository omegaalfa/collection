<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Omegaalfa\Collection\LazyFileIterator;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  LazyFileIterator - COMPLETE USAGE EXAMPLES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ============================================================================
// SETUP: Create sample files for demonstration
// ============================================================================
echo "📁 Creating sample files...\n";

// Sample JSON Lines file
$jsonlFile = __DIR__ . '/sample_data.jsonl';
file_put_contents($jsonlFile, implode("\n", [
    '{"id": 1, "name": "Alice", "age": 30, "city": "New York"}',
    '{"id": 2, "name": "Bob", "age": 25, "city": "Los Angeles"}',
    '{"id": 3, "name": "Charlie", "age": 35, "city": "Chicago"}',
    '{"id": 4, "name": "Diana", "age": 28, "city": "Houston"}',
    '{"id": 5, "name": "Eve", "age": 32, "city": "Phoenix"}',
]));

// Sample CSV file
$csvFile = __DIR__ . '/sample_data.csv';
file_put_contents($csvFile, implode("\n", [
    'id,name,age,city',
    '1,Alice,30,New York',
    '2,Bob,25,Los Angeles',
    '3,Charlie,35,Chicago',
    '4,Diana,28,Houston',
    '5,Eve,32,Phoenix',
]));

// Sample TSV file
$tsvFile = __DIR__ . '/sample_data.tsv';
file_put_contents($tsvFile, implode("\n", [
    "id\tname\tage\tcity",
    "1\tAlice\t30\tNew York",
    "2\tBob\t25\tLos Angeles",
    "3\tCharlie\t35\tChicago",
    "4\tDiana\t28\tHouston",
    "5\tEve\t32\tPhoenix",
]));

// Sample plain text file
$txtFile = __DIR__ . '/sample_data.txt';
file_put_contents($txtFile, implode("\n", [
    'First line of text',
    'Second line of text',
    'Third line of text',
    'Fourth line of text',
    'Fifth line of text',
]));

// Large file for performance testing
$largeFile = __DIR__ . '/large_data.jsonl';
$handle = fopen($largeFile, 'w');
for ($i = 1; $i <= 100000; $i++) {
    fwrite($handle, json_encode(['id' => $i, 'value' => $i * 10, 'category' => chr(65 + ($i % 26))]) . "\n");
}
fclose($handle);

echo "✅ Sample files created!\n\n";


// ============================================================================
// Example 1: JSON Lines (JSONL/NDJSON) - Most Common Use Case
// ============================================================================
echo "1️⃣  JSON LINES (JSONL/NDJSON) PARSING\n";
echo "───────────────────────────────────────────────────────────────\n";

// Auto-detect based on .jsonl extension
$iterator = new LazyFileIterator($jsonlFile);

echo "Processing users from JSON Lines file:\n";
foreach ($iterator as $lineNum => $user) {
    echo "  Line {$lineNum}: {$user->name} ({$user->age} years) from {$user->city}\n";
}

// Or use factory method explicitly
$iterator2 = LazyFileIterator::jsonLines($jsonlFile);
echo "\nFiltering users over 30:\n";
foreach ($iterator2 as $user) {
    if ($user->age > 30) {
        echo "  - {$user->name}: {$user->age} years\n";
    }
}
echo "\n";


// ============================================================================
// Example 2: CSV Parsing with Headers
// ============================================================================
echo "2️⃣  CSV PARSING (with headers)\n";
echo "───────────────────────────────────────────────────────────────\n";

$csvIterator = LazyFileIterator::csv($csvFile);

echo "Reading CSV with associative arrays:\n";
foreach ($csvIterator as $lineNum => $row) {
    if ($row === null) continue; // Skip header line
    echo "  Line {$lineNum}: {$row['name']} - Age: {$row['age']}, City: {$row['city']}\n";
}
echo "\n";


// ============================================================================
// Example 3: CSV Parsing with Custom Delimiter
// ============================================================================
echo "3️⃣  CSV PARSING (custom delimiter - semicolon)\n";
echo "───────────────────────────────────────────────────────────────\n";

// Create a semicolon-separated file
$csvSemicolon = __DIR__ . '/sample_semicolon.csv';
file_put_contents($csvSemicolon, implode("\n", [
    'product;price;stock',
    'Laptop;999.99;50',
    'Mouse;29.99;200',
    'Keyboard;79.99;150',
]));

$semiIterator = LazyFileIterator::csv($csvSemicolon, delimiter: ';');

echo "Products with semicolon delimiter:\n";
foreach ($semiIterator as $product) {
    if ($product === null) continue; // Skip header line
    echo "  - {$product['product']}: \${$product['price']} (Stock: {$product['stock']})\n";
}
echo "\n";


// ============================================================================
// Example 4: CSV Without Headers
// ============================================================================
echo "4️⃣  CSV PARSING (without headers - indexed arrays)\n";
echo "───────────────────────────────────────────────────────────────\n";

$csvNoHeaders = __DIR__ . '/sample_no_headers.csv';
file_put_contents($csvNoHeaders, implode("\n", [
    '1,Product A,100',
    '2,Product B,200',
    '3,Product C,300',
]));

$noHeadersIterator = LazyFileIterator::csv($csvNoHeaders, hasHeaders: false);

echo "CSV without headers (indexed arrays):\n";
foreach ($noHeadersIterator as $row) {
    echo "  - ID: {$row[0]}, Name: {$row[1]}, Quantity: {$row[2]}\n";
}
echo "\n";


// ============================================================================
// Example 5: TSV (Tab-Separated Values) Parsing
// ============================================================================
echo "5️⃣  TSV PARSING (tab-separated values)\n";
echo "───────────────────────────────────────────────────────────────\n";

$tsvIterator = LazyFileIterator::tsv($tsvFile);

echo "Reading TSV file:\n";
foreach ($tsvIterator as $row) {
    if ($row === null) continue; // Skip header line
    echo "  - {$row['name']} from {$row['city']}\n";
}
echo "\n";


// ============================================================================
// Example 6: Plain Text File (Line by Line)
// ============================================================================
echo "6️⃣  PLAIN TEXT FILE PARSING\n";
echo "───────────────────────────────────────────────────────────────\n";

$txtIterator = LazyFileIterator::text($txtFile);

echo "Reading plain text file:\n";
foreach ($txtIterator as $lineNum => $line) {
    echo "  Line {$lineNum}: {$line}\n";
}
echo "\n";


// ============================================================================
// Example 7: Custom Parser (Closure)
// ============================================================================
echo "7️⃣  CUSTOM PARSER (Closure)\n";
echo "───────────────────────────────────────────────────────────────\n";

// Create a log file
$logFile = __DIR__ . '/app.log';
file_put_contents($logFile, implode("\n", [
    '[2024-01-15 10:30:45] ERROR: Database connection failed',
    '[2024-01-15 10:31:12] INFO: Service started successfully',
    '[2024-01-15 10:32:08] WARNING: High memory usage detected',
    '[2024-01-15 10:33:22] ERROR: API timeout',
]));

// Custom parser to extract log components
$logParser = function(string $line, int $lineNumber): array {
    preg_match('/\[([\d\-\s:]+)\]\s+(\w+):\s+(.+)/', $line, $matches);
    return [
        'timestamp' => $matches[1] ?? '',
        'level' => $matches[2] ?? '',
        'message' => $matches[3] ?? '',
        'line' => $lineNumber
    ];
};

$logIterator = LazyFileIterator::custom($logFile, $logParser);

echo "Parsing log file with custom parser:\n";
foreach ($logIterator as $log) {
    $emoji = match($log['level']) {
        'ERROR' => '❌',
        'WARNING' => '⚠️',
        'INFO' => 'ℹ️',
        default => '📋'
    };
    echo "  {$emoji} [{$log['timestamp']}] {$log['level']}: {$log['message']}\n";
}
echo "\n";


// ============================================================================
// Example 8: Memory Efficiency - Large Files
// ============================================================================
echo "8️⃣  MEMORY EFFICIENCY - Processing Large Files\n";
echo "───────────────────────────────────────────────────────────────\n";

echo "Processing 100,000 records with minimal memory...\n";

$startMemory = memory_get_usage(true);
$startTime = microtime(true);

$count = 0;
$sum = 0;
$iterator = LazyFileIterator::jsonLines($largeFile);

foreach ($iterator as $record) {
    $count++;
    $sum += $record->value;
    
    // Only process first 10,000 for demo
    if ($count >= 10000) {
        break;
    }
}

$endTime = microtime(true);
$endMemory = memory_get_usage(true);

$memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
$timeElapsed = ($endTime - $startTime) * 1000;

echo "✅ Processed {$count} records\n";
echo "   Total sum: " . number_format($sum) . "\n";
echo "   Memory used: " . number_format($memoryUsed, 2) . " MB\n";
echo "   Time: " . number_format($timeElapsed, 2) . " ms\n";
echo "   Average: " . number_format($sum / $count, 2) . "\n\n";


// ============================================================================
// Example 9: Rewind and Re-iterate
// ============================================================================
echo "9️⃣  REWIND AND RE-ITERATE\n";
echo "───────────────────────────────────────────────────────────────\n";

$iterator = LazyFileIterator::jsonLines($jsonlFile);

echo "First iteration:\n";
$firstPass = [];
foreach ($iterator as $user) {
    $firstPass[] = $user->name;
}
echo "  Names: " . implode(', ', $firstPass) . "\n";

// Rewind to start again
$iterator->rewind();

echo "\nSecond iteration (after rewind):\n";
$secondPass = [];
foreach ($iterator as $user) {
    $secondPass[] = $user->name;
}
echo "  Names: " . implode(', ', $secondPass) . "\n\n";


// ============================================================================
// Example 10: Error Handling
// ============================================================================
echo "🔟 ERROR HANDLING\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    $iterator = new LazyFileIterator('/non/existent/file.jsonl');
} catch (RuntimeException $e) {
    echo "✅ Caught expected error: {$e->getMessage()}\n";
}

// Invalid JSON
$badJsonFile = __DIR__ . '/bad.jsonl';
file_put_contents($badJsonFile, implode("\n", [
    '{"valid": "json"}',
    '{invalid json}',
    '{"another": "valid"}',
]));

echo "\nHandling parse errors (manual iteration):\n";
$iterator = LazyFileIterator::jsonLines($badJsonFile);
$iterator->rewind(); // Garante que começamos do início

while ($iterator->valid()) {
    $lineNum = $iterator->key();
    try {
        $data = $iterator->current();
        echo "  Line {$lineNum}: ✅ Valid JSON\n";
    } catch (RuntimeException $e) {
        $errorMsg = explode(". Content:", $e->getMessage())[0];
        echo "  Line {$lineNum}: ❌ {$errorMsg}\n";
    }
    $iterator->next();
}
echo "\n";


// ============================================================================
// Example 11: Integration with LazySequence
// ============================================================================
echo "1️⃣1️⃣  INTEGRATION WITH LazySequence\n";
echo "───────────────────────────────────────────────────────────────\n";

use Omegaalfa\Collection\LazySequence;

$iterator = LazyFileIterator::jsonLines($jsonlFile);
$sequence = LazySequence::from($iterator);

echo "Using LazySequence methods:\n";
$result = $sequence
    ->filter(fn($user) => $user->age > 28)
    ->map(fn($user) => strtoupper($user->name))
    ->take(3)
    ->toArray();

echo "  Filtered & transformed names: " . implode(', ', $result) . "\n\n";


// ============================================================================
// Example 12: Batch Processing
// ============================================================================
echo "1️⃣2️⃣  BATCH PROCESSING\n";
echo "───────────────────────────────────────────────────────────────\n";

$iterator = LazyFileIterator::jsonLines($largeFile);
$batchSize = 1000;
$batch = [];
$batchNumber = 1;
$processed = 0;

echo "Processing in batches of {$batchSize}:\n";

foreach ($iterator as $record) {
    $batch[] = $record;
    
    if (count($batch) >= $batchSize) {
        // Process batch
        $avg = array_sum(array_column($batch, 'value')) / count($batch);
        echo "  Batch {$batchNumber}: {$batchSize} records, Average value: " . number_format($avg, 2) . "\n";
        
        $batch = [];
        $batchNumber++;
        $processed += $batchSize;
        
        if ($batchNumber > 5) break; // Process only 5 batches for demo
    }
}

echo "  Total processed: " . number_format($processed) . " records\n\n";


// ============================================================================
// Example 13: Filtering While Reading
// ============================================================================
echo "1️⃣3️⃣  FILTERING WHILE READING (Memory Efficient)\n";
echo "───────────────────────────────────────────────────────────────\n";

$iterator = LazyFileIterator::jsonLines($jsonlFile);

echo "Only users from specific cities:\n";
$targetCities = ['New York', 'Chicago'];

foreach ($iterator as $user) {
    if (in_array($user->city, $targetCities)) {
        echo "  ✓ {$user->name} from {$user->city}\n";
    }
}
echo "\n";


// ============================================================================
// Example 14: Aggregation (Sum, Count, Average)
// ============================================================================
echo "1️⃣4️⃣  AGGREGATION OPERATIONS\n";
echo "───────────────────────────────────────────────────────────────\n";

$iterator = LazyFileIterator::jsonLines($jsonlFile);

$totalAge = 0;
$count = 0;
$minAge = PHP_INT_MAX;
$maxAge = 0;

echo "Calculating statistics:\n";

foreach ($iterator as $user) {
    $totalAge += $user->age;
    $count++;
    $minAge = min($minAge, $user->age);
    $maxAge = max($maxAge, $user->age);
}

echo "  Total users: {$count}\n";
echo "  Average age: " . number_format($totalAge / $count, 2) . " years\n";
echo "  Min age: {$minAge} years\n";
echo "  Max age: {$maxAge} years\n\n";


// ============================================================================
// Cleanup
// ============================================================================
echo "🧹 Cleaning up temporary files...\n";
@unlink($jsonlFile);
@unlink($csvFile);
@unlink($tsvFile);
@unlink($txtFile);
@unlink($largeFile);
@unlink($csvSemicolon);
@unlink($csvNoHeaders);
@unlink($logFile);
@unlink($badJsonFile);
echo "✅ Done!\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "  End of LazyFileIterator Examples\n";
echo "═══════════════════════════════════════════════════════════════\n";
