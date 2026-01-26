<?php

namespace Tests\Unit;

use App\Enums\TaskPriority;
use PHPUnit\Framework\TestCase;

class TaskPriorityTest extends TestCase
{
    public function test_default_priorities_exist()
    {
        $this->assertEquals('low', TaskPriority::LOW->value);
        $this->assertEquals('medium', TaskPriority::MEDIUM->value);
        $this->assertEquals('high', TaskPriority::HIGH->value);
        $this->assertEquals('critical', TaskPriority::CRITICAL->value);
    }

    public function test_get_label_returns_translated_label()
    {
        $this->assertEquals('Low', TaskPriority::LOW->getLabel());
        $this->assertEquals('Medium', TaskPriority::MEDIUM->getLabel());
        $this->assertEquals('High', TaskPriority::HIGH->getLabel());
        $this->assertEquals('Critical', TaskPriority::CRITICAL->getLabel());
    }

    public function test_get_color_returns_color_for_priority()
    {
        $this->assertEquals('gray', TaskPriority::LOW->getColor());
        $this->assertEquals('warning', TaskPriority::MEDIUM->getColor());
        $this->assertEquals('orange', TaskPriority::HIGH->getColor());
        $this->assertEquals('danger', TaskPriority::CRITICAL->getColor());
    }

    public function test_get_all_priorities_ordered()
    {
        $ordered = TaskPriority::ordered();
        $this->assertEquals([
            TaskPriority::LOW,
            TaskPriority::MEDIUM,
            TaskPriority::HIGH,
            TaskPriority::CRITICAL,
        ], $ordered);
    }

    public function test_from_value_creates_enum()
    {
        $priority = TaskPriority::from('low');
        $this->assertEquals(TaskPriority::LOW, $priority);
    }

    public function test_try_from_value_returns_enum_or_null()
    {
        $priority = TaskPriority::tryFrom('low');
        $this->assertEquals(TaskPriority::LOW, $priority);

        $invalid = TaskPriority::tryFrom('invalid_priority');
        $this->assertNull($invalid);
    }
}
