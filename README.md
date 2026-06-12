# YakNet Lighthouse 🕯️ (Lightweight PHP Performance Profiler)

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Aesthetics](https://img.shields.io/badge/design-premium-purple.svg)](https://yakhub.com.tr)

**YakNet Lighthouse** is a zero-dependency, ultra-lightweight, and premium performance profiler library for PHP applications. It enables developers to trace execution times and memory deltas of code blocks, build nested call trees, register checkpoints, and render gorgeous colorized profiles in the CLI or export them as premium dark-themed HTML dashboards.

---

## 🌟 Features

- ⏱️ **Call Tree Profiling**: Nest execution blocks to automatically trace time and memory hierarchy.
- ⚡ **Zero Dependencies**: Lightweight overhead written entirely in pure, highly-optimized PHP.
- 🩺 **Self-Healing Nested Stack**: Automatically handles and gracefully stops unclosed child spans when a parent is stopped.
- 📊 **Beautiful CLI Flame-Tree**: Renders colorized ASCII trees directly in the terminal, scaling units from microseconds to megabytes.
- 💎 **Premium HTML Dashboards**: Exports standalone, responsive, glassmorphic dark-themed reports with nested interactive trees, memory charts, and checkpoints.
- ⏳ **Checkpoint Deltas**: Track memory allocation changes relative to initialization benchmarks.

---

## 📦 Installation

Install the package via Composer:

```bash
composer require yaknet/lighthouse
```

---

## 🚀 Quick Start

### 1. Basic Usage

Wrap your code execution blocks with `start` and `stop` calls:

```php
use YakNet\Lighthouse\Profiler;

// Start profiling the main flow with metadata tags
Profiler::start('Main Operation', ['controller' => 'UserController']);

// Start a sub-span (automatically becomes a child of 'Main Operation')
Profiler::start('DB Query', ['sql' => 'SELECT * FROM users']);
usleep(25000); // 25ms delay
Profiler::stop('DB Query');

// Stop the parent operation
Profiler::stop('Main Operation');
```

### 2. Measuring Callbacks

Quickly measure the execution of closures using `measure`:

```php
$result = Profiler::measure('API Call', function () {
    return file_get_contents('https://api.example.com/data');
}, ['endpoint' => 'get_data']);
```

### 3. Registering Checkpoints

Trace specific code milestones and memory delta changes:

```php
Profiler::checkpoint('Application Started');
// ... run code ...
Profiler::checkpoint('Cache Loaded');
```

---

## 📈 Generating Reports

### 1. Colorized CLI Flame-Tree

Print a beautiful call tree directly to your terminal or logs:

```php
use YakNet\Lighthouse\Renderer\CliRenderer;

$totalDuration = Profiler::getInstance()->getTotalDuration();
echo CliRenderer::render(Profiler::getRootSpans(), $totalDuration);
```

**Terminal Output Example:**
```
==================================================
 🕯️  YAKNET LIGHTHOUSE - PROFILE REPORT
==================================================
Total Wall Time: 190.3ms | Peak Memory: 2 MB

 └── [100.0%] Main Request (190.3ms, 2.4 KB) {route=/api/users, method=GET}
      ├── [ 24.8%] Database Query (47.2ms, 216 B) {sql=SELECT * FROM users WHERE status = ?}
      ├── [ 41.7%] External API Fetch (79.4ms, 408 B) {url=https://api.github.com/users}
      │    └── [  8.2%] JSON Decode (15.5ms, 216 B) {size=2.4MB}
      └── [ 16.2%] Template Rendering (30.8ms, 0 B) {template=dashboard.html}
==================================================
```

### 2. Standalone HTML Dashboard

Generate a stunning dark-themed HTML report:

```php
use YakNet\Lighthouse\Renderer\HtmlRenderer;

$htmlReport = HtmlRenderer::render(
    Profiler::getRootSpans(),
    Profiler::getCheckpoints(),
    Profiler::getInstance()->getTotalDuration(),
    Profiler::getInstance()->getPeakMemory(),
    Profiler::getInstance()->getInitMemory(),
    Profiler::getInstance()->getInitTime()
);

file_put_contents('lighthouse_report.html', $htmlReport);
```

---

## 🧪 Running Tests

Ensure all features work in your local environment by running the test suite:

```bash
composer test
```

Perform static analysis check at level 9:

```bash
composer analyze
```

---

## 📜 License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.
"Illuminate your code bottlenecks." 🕯️
