<?php

namespace YakNet\Lighthouse\Tests;

use PHPUnit\Framework\TestCase;
use YakNet\Lighthouse\Profiler;
use YakNet\Lighthouse\Span;

class ProfilerTest extends TestCase
{
    protected function setUp(): void
    {
        Profiler::reset();
    }

    public function testProfilerStartAndStop(): void
    {
        $span = Profiler::start('test_op', ['tag1' => 'val1']);

        $this->assertInstanceOf(Span::class, $span);
        $this->assertSame('test_op', $span->getName());
        $this->assertSame('val1', $span->getTag('tag1'));

        usleep(50000); // 50ms

        Profiler::stop('test_op');

        $this->assertNotNull($span->getEndTime());
        $this->assertGreaterThanOrEqual(0.045, $span->getDuration());
    }

    public function testNestedSpansCallTree(): void
    {
        $parent = Profiler::start('parent');
        usleep(10000);
        
        $child1 = Profiler::start('child_1');
        usleep(10000);
        Profiler::stop('child_1');

        $child2 = Profiler::start('child_2');
        usleep(10000);
        Profiler::stop('child_2');

        Profiler::stop('parent');

        $rootSpans = Profiler::getRootSpans();
        $this->assertCount(1, $rootSpans);
        $this->assertSame('parent', $rootSpans[0]->getName());

        $children = $rootSpans[0]->getChildren();
        $this->assertCount(2, $children);
        $this->assertSame('child_1', $children[0]->getName());
        $this->assertSame('child_2', $children[1]->getName());
        
        $this->assertSame($rootSpans[0], $children[0]->getParent());
    }

    public function testMeasureCallback(): void
    {
        $result = Profiler::measure('measurement', function () {
            usleep(20000);
            return 'callback_result';
        }, ['op' => 'run']);

        $this->assertSame('callback_result', $result);

        $rootSpans = Profiler::getRootSpans();
        $this->assertCount(1, $rootSpans);
        $this->assertSame('measurement', $rootSpans[0]->getName());
        $this->assertSame('run', $rootSpans[0]->getTag('op'));
        $this->assertGreaterThanOrEqual(0.018, $rootSpans[0]->getDuration());
    }

    public function testCheckpoints(): void
    {
        Profiler::checkpoint('start_checkpoint');
        usleep(10000);
        Profiler::checkpoint('end_checkpoint');

        $checkpoints = Profiler::getCheckpoints();
        $this->assertCount(2, $checkpoints);
        $this->assertSame('start_checkpoint', $checkpoints[0]['message']);
        $this->assertSame('end_checkpoint', $checkpoints[1]['message']);
        $this->assertGreaterThan($checkpoints[0]['time'], $checkpoints[1]['time']);
    }
}
