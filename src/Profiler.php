<?php

declare(strict_types=1);

namespace YakNet\Lighthouse;

/**
 * Main performance profiler orchestrator.
 */
class Profiler
{
    private static ?Profiler $instance = null;

    /** @var array<int, Span> */
    private array $rootSpans = [];

    /** @var array<int, Span> */
    private array $activeStack = [];

    /** @var array<int, array{message: string, time: float, memory: int}> */
    private array $checkpoints = [];

    private float $initTime;
    private int $initMemory;

    private function __construct()
    {
        $this->initTime = microtime(true);
        $this->initMemory = memory_get_usage();
    }

    public static function getInstance(): Profiler
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Resets the profiler state.
     */
    public static function reset(): void
    {
        $instance = self::getInstance();
        $instance->rootSpans = [];
        $instance->activeStack = [];
        $instance->checkpoints = [];
        $instance->initTime = microtime(true);
        $instance->initMemory = memory_get_usage();
    }

    /**
     * Starts a new span.
     *
     * @param array<string, mixed> $tags
     */
    public static function start(string $name, array $tags = []): Span
    {
        return self::getInstance()->startSpan($name, $tags);
    }

    /**
     * Stops the active span by name.
     */
    public static function stop(string $name): void
    {
        self::getInstance()->stopSpan($name);
    }

    /**
     * Measures the execution of a callback.
     *
     * @param array<string, mixed> $tags
     */
    public static function measure(string $name, callable $callback, array $tags = []): mixed
    {
        return self::getInstance()->measureSpan($name, $callback, $tags);
    }

    /**
     * Records a manual checkpoint of time and memory.
     */
    public static function checkpoint(string $message): void
    {
        self::getInstance()->recordCheckpoint($message);
    }

    /**
     * @return array<int, Span>
     */
    public static function getRootSpans(): array
    {
        return self::getInstance()->rootSpans;
    }

    /**
     * @return array<int, array{message: string, time: float, memory: int}>
     */
    public static function getCheckpoints(): array
    {
        return self::getInstance()->checkpoints;
    }

    // Instance level methods

    /**
     * @param array<string, mixed> $tags
     */
    public function startSpan(string $name, array $tags = []): Span
    {
        $parent = count($this->activeStack) > 0 ? end($this->activeStack) : null;
        $span = new Span($name, microtime(true), memory_get_usage(), $tags, $parent);

        if ($parent !== null) {
            $parent->addChild($span);
        } else {
            $this->rootSpans[] = $span;
        }

        $this->activeStack[] = $span;
        return $span;
    }

    public function stopSpan(string $name): void
    {
        $index = null;
        for ($i = count($this->activeStack) - 1; $i >= 0; $i--) {
            if ($this->activeStack[$i]->getName() === $name) {
                $index = $i;
                break;
            }
        }

        if ($index !== null) {
            // Stop and pop all nested spans up to the matching index (self-healing for unstopped children)
            while (count($this->activeStack) > $index) {
                $span = array_pop($this->activeStack);
                $span->stop();
            }
        }
    }

    /**
     * @param array<string, mixed> $tags
     */
    public function measureSpan(string $name, callable $callback, array $tags = []): mixed
    {
        $this->startSpan($name, $tags);
        try {
            return $callback();
        } finally {
            $this->stopSpan($name);
        }
    }

    public function recordCheckpoint(string $message): void
    {
        $this->checkpoints[] = [
            'message' => $message,
            'time' => microtime(true),
            'memory' => memory_get_usage(),
        ];
    }

    /**
     * Returns total duration in seconds.
     */
    public function getTotalDuration(): float
    {
        if (empty($this->rootSpans)) {
            return 0.0;
        }

        $minStart = $this->initTime;
        $maxEnd = microtime(true);

        // Find the absolute bounds of all root spans
        $first = reset($this->rootSpans);
        $last = end($this->rootSpans);

        if ($first !== false) {
            $minStart = $first->getStartTime();
        }

        $maxEnd = 0.0;
        foreach ($this->rootSpans as $span) {
            $end = $span->getEndTime() ?? microtime(true);
            if ($end > $maxEnd) {
                $maxEnd = $end;
            }
        }

        return max(0.0, $maxEnd - $minStart);
    }

    /**
     * Returns the peak memory usage during this request/run in bytes.
     */
    public function getPeakMemory(): int
    {
        return memory_get_peak_usage(true);
    }

    /**
     * Returns the initial memory usage at profiler initialization.
     */
    public function getInitMemory(): int
    {
        return $this->initMemory;
    }

    /**
     * Returns the initial time at profiler initialization.
     */
    public function getInitTime(): float
    {
        return $this->initTime;
    }
}
