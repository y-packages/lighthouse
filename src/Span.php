<?php

declare(strict_types=1);

namespace YakNet\Lighthouse;

/**
 * Represents a single measured execution block.
 */
class Span
{
    private string $name;
    private float $startTime;
    private ?float $endTime = null;
    private int $startMemory;
    private ?int $endMemory = null;
    /** @var array<string, mixed> */
    private array $tags;
    /** @var array<int, Span> */
    private array $children = [];
    private ?Span $parent = null;

    /**
     * @param array<string, mixed> $tags
     */
    public function __construct(string $name, float $startTime, int $startMemory, array $tags = [], ?Span $parent = null)
    {
        $this->name = $name;
        $this->startTime = $startTime;
        $this->startMemory = $startMemory;
        $this->tags = $tags;
        $this->parent = $parent;
    }

    /**
     * Stops the span recording time and memory.
     */
    public function stop(?float $endTime = null, ?int $endMemory = null): void
    {
        $this->endTime = $endTime ?? microtime(true);
        $this->endMemory = $endMemory ?? memory_get_usage();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function getEndTime(): ?float
    {
        return $this->endTime;
    }

    public function getStartMemory(): int
    {
        return $this->startMemory;
    }

    public function getEndMemory(): ?int
    {
        return $this->endMemory;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getTag(string $key, mixed $default = null): mixed
    {
        return $this->tags[$key] ?? $default;
    }

    public function hasTag(string $key): bool
    {
        return array_key_exists($key, $this->tags);
    }

    /**
     * @return array<int, Span>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(Span $span): void
    {
        $this->children[] = $span;
    }

    public function getParent(): ?Span
    {
        return $this->parent;
    }

    /**
     * Returns the execution duration in seconds.
     */
    public function getDuration(): float
    {
        $end = $this->endTime ?? microtime(true);
        return max(0.0, $end - $this->startTime);
    }

    /**
     * Returns the difference in memory usage in bytes.
     */
    public function getMemoryDelta(): int
    {
        $end = $this->endMemory ?? memory_get_usage();
        return $end - $this->startMemory;
    }
}
