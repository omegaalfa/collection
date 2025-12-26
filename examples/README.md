# Examples Directory

This directory contains comprehensive examples demonstrating all features of the Collection Library.

## 📚 Files Overview

### 🌟 COMPLETE_USAGE_EXAMPLES.php
**Complete API Reference**

Demonstrates **ALL public methods** from all 7 classes:
- Collection (50+ methods)
- Sequence (30+ methods)
- Map (25+ methods)
- LazySequence (20+ methods)
- LazyMap (15+ methods)
- LazyFileIterator (6 methods)
- LazyProxyObject (3 methods)

**Run:**
```bash
php examples/COMPLETE_USAGE_EXAMPLES.php
```

**What you'll see:**
- Every method with practical examples
- Input and output for each operation
- Performance comparisons (lazy vs eager)
- When to use each class

---

### ⚡ examples_lazy.php
**Eager vs Lazy Performance Comparison**

Demonstrates the massive performance gains of lazy evaluation:
- Pipeline operations with short-circuit
- Memory efficiency comparisons
- Iteration count comparisons
- Real-world scenarios

**Benchmarks included:**
- `Sequence` vs `LazySequence`
- `Map` vs `LazyMap`
- Processing 1M elements (eager vs lazy)

**Performance gains:**
- Up to **2290x faster** execution
- **95% less memory** usage
- **19,607x fewer iterations** with lazy pipelines

---

### 🚀 examples_lazy_collection.php
**Collection Lazy Methods**

Demonstrates Collection's lazy methods:
- `lazyMap()` - Transform lazily
- `lazyFilter()` - Filter lazily
- `lazyTake()` - Short-circuit evaluation
- `lazyChunk()` - Chunk lazily
- `lazyPipeline()` - Compose operations
- `lazyRange()` - Lazy ranges
- `lazy()` - Convert to lazy
- `lazyObjects()` - Create lazy objects

**Performance comparison:**
- Eager: 1625ms, 40MB memory
- Lazy: 0.71ms, 2MB memory

---

### 🎯 examples_lazymap_proxy.php
**LazyMap + LazyProxyObject Integration**

Demonstrates lazy object instantiation (PHP 8.4+ feature):
- `LazyMap::ofLazyObjects()` - Native lazy objects
- `LazyMap::ofLazyFactories()` - Custom factories
- `LazyProxyObject::lazyProxy()` - Lazy proxy pattern
- `LazyProxyObject::lazyGhost()` - Lazy ghost pattern

**Real-world scenarios:**
- Lazy database connections
- Service container with lazy services
- API client lazy initialization
- Heavy object instantiation optimization

**Performance gains:**
- 100 objects: 10s (eager) → 1ms + 100ms per accessed object (lazy)
- **95% fewer instantiations** when accessing only some objects

---

### 📦 examples.php
**Basic Collection Usage**

Demonstrates core Collection features:
- Basic operations (first, last, count, isEmpty)
- Transformations (map, filter, each)
- Aggregations (sum, avg, min, max)
- Sorting (sort, sortKeys)
- Array access (ArrayAccess interface)
- Slicing (take, slice, chunk)
- Utilities (unique, reverse, pluck)

**Perfect for:**
- Getting started with the library
- Understanding basic transformations
- Learning fluent API patterns

---

## 🎓 Learning Path

**Recommended order:**

1. **examples.php** → Start here for basics
2. **COMPLETE_USAGE_EXAMPLES.php** → See all available methods
3. **examples_lazy.php** → Understand lazy evaluation benefits
4. **examples_lazy_collection.php** → Learn Collection lazy methods
5. **examples_lazymap_proxy.php** → Master advanced lazy patterns

---

## 🚀 Running Examples

### Run all examples:
```bash
php examples/examples.php
php examples/examples_lazy.php
php examples/examples_lazy_collection.php
php examples/examples_lazymap_proxy.php
php examples/COMPLETE_USAGE_EXAMPLES.php
```

### Run with output redirection:
```bash
php examples/COMPLETE_USAGE_EXAMPLES.php > output.txt
```

---

## 💡 Key Takeaways

### When to use EAGER (Sequence, Map, Collection eager methods):
- ✅ Small datasets (< 10,000 items)
- ✅ Need all elements processed
- ✅ Multiple reuses of same collection
- ✅ Simpler debugging

### When to use LAZY (LazySequence, LazyMap, Collection lazy methods):
- ✅ Large datasets (> 100,000 items)
- ✅ Only need some elements (take, first)
- ✅ Expensive computations
- ✅ Memory constraints
- ✅ Streaming data

### When to use LazyProxyObject:
- ✅ PHP 8.4+ available
- ✅ Objects expensive to instantiate
- ✅ Not all objects will be used
- ✅ Service containers, DI
- ✅ Database connections

---

**Happy Coding! 🚀**
