<?php

declare(strict_types=1);

namespace YakNet\Lighthouse\Renderer;

use YakNet\Lighthouse\Span;

/**
 * Renders a premium, standalone HTML performance dashboard.
 */
class HtmlRenderer
{
    /**
     * Renders the report data into a complete HTML page.
     *
     * @param array<int, Span> $spans
     * @param array<int, array{message: string, time: float, memory: int}> $checkpoints
     */
    public static function render(
        array $spans,
        array $checkpoints,
        float $totalDuration,
        int $peakMemory,
        int $initMemory,
        float $initTime
    ): string {
        $totalSpans = self::countSpans($spans);
        $spansHtml = '';
        foreach ($spans as $span) {
            $spansHtml .= self::renderSpan($span, $totalDuration, 0);
        }

        $checkpointsHtml = '';
        if (empty($checkpoints)) {
            $checkpointsHtml = '<tr><td colspan="4" class="empty-state">No checkpoints registered.</td></tr>';
        } else {
            foreach ($checkpoints as $index => $cp) {
                $timeFromStart = $cp['time'] - $initTime;
                $memDelta = $cp['memory'] - $initMemory;
                $checkpointsHtml .= sprintf(
                    '<tr>
                        <td>#%d</td>
                        <td class="cp-message">%s</td>
                        <td>+%s</td>
                        <td class="%s">%s</td>
                     </tr>',
                    $index + 1,
                    htmlspecialchars($cp['message']),
                    CliRenderer::formatDuration($timeFromStart),
                    $memDelta >= 0 ? 'mem-positive' : 'mem-negative',
                    CliRenderer::formatBytes($memDelta)
                );
            }
        }

        $timeFormatted = CliRenderer::formatDuration($totalDuration);
        $peakMemFormatted = CliRenderer::formatBytes($peakMemory);
        $initMemFormatted = CliRenderer::formatBytes($initMemory);

        return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YakNet Lighthouse | Profiler Raporu 📊</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #09060f;
            --surface: rgba(20, 15, 30, 0.6);
            --surface-hover: rgba(255, 255, 255, 0.04);
            --border: rgba(168, 85, 247, 0.2);
            --border-hover: rgba(168, 85, 247, 0.4);
            --accent: #a855f7;
            --accent-glow: rgba(168, 85, 247, 0.3);
            --text: #f3e8ff;
            --text-muted: #c084fc;
            --green: #22c55e;
            --rose: #f43f5e;
            --gold: #eab308;
        }

        * {
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        body {
            background: radial-gradient(circle at 50% 0%, #1e1136 0%, var(--bg) 60%);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 2rem 1.5rem;
            min-height: 100vh;
        }

        h1, h2, h3, .brand-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.5rem;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            font-size: 2rem;
            animation: pulse 2s infinite alternate;
        }

        @keyframes pulse {
            0% { transform: scale(1); filter: drop-shadow(0 0 2px var(--accent-glow)); }
            100% { transform: scale(1.05); filter: drop-shadow(0 0 10px var(--accent)); }
        }

        .brand-title {
            font-size: 1.8rem;
            margin: 0;
            background: linear-gradient(135deg, #fff 30%, var(--text-muted) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 2px 0 0 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .badge {
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* KPI Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: var(--accent);
        }

        .kpi-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-2px);
        }

        .kpi-title {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
        }

        /* Main Sections */
        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            margin-bottom: 2.5rem;
        }

        .section-title {
            font-size: 1.3rem;
            margin: 0 0 1.5rem 0;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Call Tree nodes */
        .span-node {
            margin-bottom: 4px;
            border-radius: 8px;
            overflow: hidden;
        }

        .span-header {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 10px 14px;
            cursor: pointer;
            position: relative;
        }

        .span-header:hover {
            background: var(--surface-hover);
            border-color: var(--border);
        }

        .toggle-icon {
            font-size: 0.8rem;
            color: var(--text-muted);
            width: 20px;
            user-select: none;
        }

        .toggle-icon.expanded {
            transform: rotate(90deg);
        }

        .span-name {
            font-weight: 600;
            font-size: 0.95rem;
            flex: 2;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .span-meta {
            font-family: monospace;
            font-size: 0.9rem;
            color: var(--text-muted);
            width: 140px;
            text-align: right;
            padding-right: 15px;
        }

        .span-memory {
            font-family: monospace;
            font-size: 0.9rem;
            color: var(--gold);
            width: 100px;
            text-align: right;
            padding-right: 15px;
        }

        .span-bar-container {
            width: 150px;
            height: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }

        .span-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--accent) 0%, #d8b4fe 100%);
            border-radius: 5px;
        }

        .span-bar-percent {
            position: absolute;
            right: 4px;
            top: -1px;
            font-size: 0.65rem;
            font-weight: 700;
            color: #fff;
        }

        .span-tags {
            font-size: 0.75rem;
            color: var(--text-muted);
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid var(--border);
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 8px;
        }

        .span-children {
            margin-left: 20px;
            border-left: 1px dashed rgba(168, 85, 247, 0.15);
            padding-left: 10px;
            display: block;
        }

        /* Checkpoint Table */
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        th {
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 1px solid var(--border);
            padding: 12px;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            color: #d1d5db;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }

        .cp-message {
            font-weight: 600;
            color: #fff;
        }

        .mem-positive {
            color: var(--rose);
            font-weight: 600;
        }

        .mem-negative {
            color: var(--green);
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            color: var(--text-muted);
            font-style: italic;
            padding: 2rem 0;
        }

        footer {
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 3rem;
            opacity: 0.7;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Header -->
        <header>
            <div class="brand">
                <span class="brand-logo">🕯️</span>
                <div>
                    <h1 class="brand-title">LIGHTHOUSE REPORT</h1>
                    <p class="brand-subtitle">YakNet Performance Profiler</p>
                </div>
            </div>
            <span class="badge">PHP 8.2+ Active</span>
        </header>

        <!-- KPI Grid -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-title">Wall Time</div>
                <div class="kpi-value">{$timeFormatted}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Peak Memory</div>
                <div class="kpi-value">{$peakMemFormatted}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Startup Memory</div>
                <div class="kpi-value">{$initMemFormatted}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Total Spans</div>
                <div class="kpi-value">{$totalSpans}</div>
            </div>
        </div>

        <!-- Call Tree Section -->
        <div class="section-card">
            <h2 class="section-title">📊 Execution Call Tree</h2>
            <div class="call-tree">
                {$spansHtml}
            </div>
        </div>

        <!-- Checkpoints Section -->
        <div class="section-card">
            <h2 class="section-title">⏳ Checkpoints & Memory Deltas</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Index</th>
                        <th>Checkpoint Message</th>
                        <th style="width: 150px;">Time from Start</th>
                        <th style="width: 180px;">Memory Allocation Delta</th>
                    </tr>
                </thead>
                <tbody>
                    {$checkpointsHtml}
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <footer>
            YakNet Lighthouse &copy; 2026 | Generated dynamically.
        </footer>
    </div>

    <script>
        function toggleNode(header) {
            const childrenContainer = header.nextElementSibling;
            const icon = header.querySelector('.toggle-icon');
            if (childrenContainer && childrenContainer.classList.contains('span-children')) {
                if (childrenContainer.style.display === 'none') {
                    childrenContainer.style.display = 'block';
                    if (icon) icon.classList.add('expanded');
                } else {
                    childrenContainer.style.display = 'none';
                    if (icon) icon.classList.remove('expanded');
                }
            }
        }
    </script>
</body>
</html>
HTML;
    }

    private static function renderSpan(Span $span, float $totalDuration, int $depth): string
    {
        $duration = $span->getDuration();
        $percent = $totalDuration > 0 ? ($duration / $totalDuration) * 100 : 0.0;
        $memory = $span->getMemoryDelta();

        $children = $span->getChildren();
        $hasChildren = count($children) > 0;
        $toggleChar = $hasChildren ? '▶' : '•';
        $iconClass = $hasChildren ? 'toggle-icon expanded' : 'toggle-icon';

        // Tag serialization
        $tagsHtml = '';
        $tags = $span->getTags();
        if (!empty($tags)) {
            $tagParts = [];
            foreach ($tags as $key => $val) {
                $tagParts[] = "{$key}=" . (is_scalar($val) ? (string)$val : json_encode($val));
            }
            $tagsHtml = '<span class="span-tags">' . htmlspecialchars(implode(', ', $tagParts)) . '</span>';
        }

        $durationFormatted = htmlspecialchars(CliRenderer::formatDuration($duration));
        $memoryFormatted = htmlspecialchars(CliRenderer::formatBytes($memory));
        $nameFormatted = htmlspecialchars($span->getName());

        $html = sprintf(
            '<div class="span-node">
                <div class="span-header" onclick="toggleNode(this)">
                    <div class="%s">%s</div>
                    <div class="span-name">%s %s</div>
                    <div class="span-meta">%s</div>
                    <div class="span-memory">%s</div>
                    <div class="span-bar-container">
                        <div class="span-bar" style="width: %.1f%%;"></div>
                        <div class="span-bar-percent">%.1f%%</div>
                    </div>
                </div>',
            $iconClass,
            $toggleChar,
            $nameFormatted,
            $tagsHtml,
            $durationFormatted,
            $memoryFormatted,
            $percent,
            $percent
        );

        if ($hasChildren) {
            $html .= '<div class="span-children" style="display: block;">';
            foreach ($children as $child) {
                $html .= self::renderSpan($child, $totalDuration, $depth + 1);
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * @param array<int, Span> $spans
     */
    private static function countSpans(array $spans): int
    {
        $count = count($spans);
        foreach ($spans as $span) {
            $count += self::countSpans($span->getChildren());
        }
        return $count;
    }
}
