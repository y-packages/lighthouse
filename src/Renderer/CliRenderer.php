<?php

declare(strict_types=1);

namespace YakNet\Lighthouse\Renderer;

use YakNet\Lighthouse\Span;

/**
 * Renders the profiling call tree directly to CLI.
 */
class CliRenderer
{
    /**
     * Renders an array of spans as a call tree string.
     *
     * @param array<int, Span> $spans
     */
    public static function render(array $spans, float $totalDuration, bool $useColors = true): string
    {
        $output = '';
        $output .= self::colorize("==================================================\n", "\e[90m", $useColors);
        $output .= self::colorize(" 🕯️  YAKNET LIGHTHOUSE - PROFILE REPORT\n", "\e[1;36m", $useColors);
        $output .= self::colorize("==================================================\n", "\e[90m", $useColors);
        $output .= sprintf(
            "Total Wall Time: %s | Peak Memory: %s\n\n",
            self::formatDuration($totalDuration),
            self::formatBytes(memory_get_peak_usage(true))
        );

        $count = count($spans);
        foreach ($spans as $index => $span) {
            $isLast = ($index === $count - 1);
            $output .= self::renderSpan($span, '', $isLast, $totalDuration, $useColors);
        }

        $output .= self::colorize("==================================================\n", "\e[90m", $useColors);
        return $output;
    }

    private static function renderSpan(
        Span $span,
        string $indent,
        bool $isLast,
        float $totalDuration,
        bool $useColors
    ): string {
        $duration = $span->getDuration();
        $percent = $totalDuration > 0 ? ($duration / $totalDuration) * 100 : 0.0;
        $memory = $span->getMemoryDelta();

        $prefix = $isLast ? ' └── ' : ' ├── ';
        $childIndent = $isLast ? '     ' : ' │   ';

        // Format label details
        $percentStr = sprintf('[%5.1f%%] ', $percent);
        $percentColor = $percent > 50 ? "\e[1;31m" : ($percent > 20 ? "\e[1;33m" : "\e[1;35m");

        $infoStr = sprintf(
            "%s (%s, %s)",
            $span->getName(),
            self::formatDuration($duration),
            self::formatBytes($memory)
        );

        // Build CLI line
        $line = self::colorize($indent, "\e[90m", $useColors);
        $line .= self::colorize($prefix, "\e[90m", $useColors);
        $line .= self::colorize($percentStr, $percentColor, $useColors);
        $line .= self::colorize($infoStr, "\e[32m", $useColors);

        // Append custom tags if present
        $tags = $span->getTags();
        if (!empty($tags)) {
            $tagParts = [];
            foreach ($tags as $key => $val) {
                $tagParts[] = "{$key}=" . (is_scalar($val) ? (string)$val : json_encode($val));
            }
            $line .= self::colorize(" {" . implode(', ', $tagParts) . "}", "\e[36m", $useColors);
        }
        $line .= "\n";

        // Render children recursively
        $children = $span->getChildren();
        $childCount = count($children);
        foreach ($children as $childIndex => $child) {
            $childIsLast = ($childIndex === $childCount - 1);
            $line .= self::renderSpan(
                $child,
                $indent . $childIndent,
                $childIsLast,
                $totalDuration,
                $useColors
            );
        }

        return $line;
    }

    private static function colorize(string $text, string $ansiColor, bool $useColors): string
    {
        return $useColors ? $ansiColor . $text . "\e[0m" : $text;
    }

    public static function formatDuration(float $seconds): string
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000, 1) . 'µs';
        }
        if ($seconds < 1.0) {
            return round($seconds * 1000, 1) . 'ms';
        }
        return round($seconds, 3) . 's';
    }

    public static function formatBytes(int $bytes): string
    {
        $absBytes = abs($bytes);
        $sign = $bytes < 0 ? '-' : '';

        if ($absBytes < 1024) {
            return $sign . $absBytes . ' B';
        }
        if ($absBytes < 1048576) {
            return $sign . round($absBytes / 1024, 1) . ' KB';
        }
        return $sign . round($absBytes / 1048576, 2) . ' MB';
    }
}
