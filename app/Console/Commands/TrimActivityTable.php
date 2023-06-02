<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;

class TrimActivityTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tier4:trim-activity-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trim the activity table based on config tier4.activity_table_trim_days';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $days = config('tier4.activity_table_trim_days', 6*30);
        $this->info("Trimming activity table to $days days");

        Activity::where('created_at', '<', now()->subDays($days))->delete();
    }
}
