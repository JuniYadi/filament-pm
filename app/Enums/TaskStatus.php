<?php

namespace App\Enums;

enum TaskStatus: string
{
    case BACKLOG = 'backlog';
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case DONE = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::BACKLOG => 'Backlog',
            self::TODO => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::REVIEW => 'Review',
            self::DONE => 'Done',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BACKLOG => 'gray',
            self::TODO => 'warning',
            self::IN_PROGRESS => 'primary',
            self::REVIEW => 'info',
            self::DONE => 'success',
        };
    }

    /**
     * @return array<int, TaskStatus>
     */
    public static function ordered(): array
    {
        return [
            self::BACKLOG,
            self::TODO,
            self::IN_PROGRESS,
            self::REVIEW,
            self::DONE,
        ];
    }
}
