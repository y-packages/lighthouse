<?php

require_once __DIR__ . '/vendor/autoload.php';

use YakNet\Lighthouse\Profiler;
use YakNet\Lighthouse\Renderer\CliRenderer;
use YakNet\Lighthouse\Renderer\HtmlRenderer;

echo "🕯️ Running YakNet Lighthouse Demo...\n\n";

Profiler::reset();

Profiler::checkpoint('Application Bootstrap');

// Simulate Main Application Flow
Profiler::start('Main Request', ['route' => '/api/users', 'method' => 'GET']);
usleep(20000); // 20ms startup

// Database query simulation
Profiler::start('Database Query', ['sql' => 'SELECT * FROM users WHERE status = ?']);
usleep(45000); // 45ms query
Profiler::stop('Database Query');

Profiler::checkpoint('Database Loaded');

// API fetch simulation
Profiler::start('External API Fetch', ['url' => 'https://api.github.com/users']);
usleep(60000); // 60ms call

// Nested JSON decoding
Profiler::start('JSON Decode', ['size' => '2.4MB']);
usleep(15000); // 15ms decode
Profiler::stop('JSON Decode');

Profiler::stop('External API Fetch');

Profiler::checkpoint('API Processing Completed');

// Rendering simulation
Profiler::start('Template Rendering', ['template' => 'dashboard.html']);
usleep(25000); // 25ms render
Profiler::stop('Template Rendering');

Profiler::stop('Main Request');

Profiler::checkpoint('Response Sent');

// 1. Output the colorized CLI report
$totalDuration = Profiler::getInstance()->getTotalDuration();
$cliReport = CliRenderer::render(Profiler::getRootSpans(), $totalDuration, true);
echo $cliReport;

// 2. Export HTML dashboard report
$htmlReport = HtmlRenderer::render(
    Profiler::getRootSpans(),
    Profiler::getCheckpoints(),
    $totalDuration,
    Profiler::getInstance()->getPeakMemory(),
    Profiler::getInstance()->getInitMemory(),
    Profiler::getInstance()->getInitTime()
);

$htmlFile = __DIR__ . '/lighthouse_report.html';
file_put_contents($htmlFile, $htmlReport);
echo "\n✨ Premium HTML report exported to: " . $htmlFile . "\n\n";
