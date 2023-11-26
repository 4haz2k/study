<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class Test extends Command
{
    protected $signature = 'test';

    public function handle()
    {
        $startDate = Carbon::now()->addHours(2);
        $endDate = Carbon::now()->addHours(5);

        $schedule = Schedule::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('notified', false)
            ->first();

        dd($schedule);
    }
}
