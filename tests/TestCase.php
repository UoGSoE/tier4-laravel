<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Asserts that a command is registered with the console kernel schedular.
     *
     * @param  string  $command  The artisan-format command (eg 'myapp:do-a-thing') or the class string name of the command (eg \App\Console\Commands\DoAThing::class)
     * @param  string|null  $schedule  The schedule cron expression (eg '0 0 * * *')
     * @return void
     */
    protected function assertCommandIsScheduled(string $command, ?string $schedule = null)
    {
        $schedular = app(\Illuminate\Console\Scheduling\Schedule::class);

        $originalCommand = $command;

        if (class_exists($originalCommand)) {
            $command = \Illuminate\Container\Container::getInstance()->make($originalCommand)->getName();
        }

        $matchingCommands = collect($schedular->events())->filter(
            fn ($task) => str_ends_with($task->command, " 'artisan' {$command}")
        );

        if ($matchingCommands->isEmpty()) {
            $this->fail("Command {$originalCommand} is not registered with the schedular");
        }

        if (! $schedule) {
            $this->assertTrue(true);

            return;
        }

        $this->assertNotEmpty($matchingCommands->filter(
            fn ($task) => $task->expression === $schedule
        ), "Command {$originalCommand} is registered with the schedular but with ".($matchingCommands->count() === 1 ? 'a different schedule' : 'different schedules')." {$matchingCommands->pluck('expression')->implode(', ')}");
    }
}
