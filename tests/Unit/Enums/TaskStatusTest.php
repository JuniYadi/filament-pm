<?php

use App\Enums\TaskStatus;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    public function test_default_statuses_exist()
    {
        $this->assertEquals('backlog', TaskStatus::BACKLOG->value);
        $this->assertEquals('todo', TaskStatus::TODO->value);
        $this->assertEquals('in_progress', TaskStatus::IN_PROGRESS->value);
        $this->assertEquals('review', TaskStatus::REVIEW->value);
        $this->assertEquals('done', TaskStatus::DONE->value);
    }

    public function test_get_label_returns_translated_label()
    {
        $this->assertEquals('Backlog', TaskStatus::BACKLOG->getLabel());
        $this->assertEquals('To Do', TaskStatus::TODO->getLabel());
        $this->assertEquals('In Progress', TaskStatus::IN_PROGRESS->getLabel());
        $this->assertEquals('Review', TaskStatus::REVIEW->getLabel());
        $this->assertEquals('Done', TaskStatus::DONE->getLabel());
    }

    public function test_get_color_returns_color_for_status()
    {
        $this->assertEquals('gray', TaskStatus::BACKLOG->getColor());
        $this->assertEquals('warning', TaskStatus::TODO->getColor());
        $this->assertEquals('primary', TaskStatus::IN_PROGRESS->getColor());
        $this->assertEquals('info', TaskStatus::REVIEW->getColor());
        $this->assertEquals('success', TaskStatus::DONE->getColor());
    }

    public function test_get_all_statuses_ordered()
    {
        $ordered = TaskStatus::ordered();
        $this->assertEquals([
            TaskStatus::BACKLOG,
            TaskStatus::TODO,
            TaskStatus::IN_PROGRESS,
            TaskStatus::REVIEW,
            TaskStatus::DONE,
        ], $ordered);
    }

    public function test_from_value_creates_enum()
    {
        $status = TaskStatus::from('todo');
        $this->assertEquals(TaskStatus::TODO, $status);
    }

    public function test_try_from_value_returns_enum_or_null()
    {
        $status = TaskStatus::tryFrom('todo');
        $this->assertEquals(TaskStatus::TODO, $status);

        $invalid = TaskStatus::tryFrom('invalid_status');
        $this->assertNull($invalid);
    }
}
