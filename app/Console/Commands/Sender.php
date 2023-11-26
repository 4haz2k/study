<?php

namespace App\Console\Commands;

use App\Models\Participants;
use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Telegram\Bot\Traits\Telegram;

class Sender extends Command
{
    use Telegram;

    protected $signature = 'schedule:send';

    public function handle(): int
    {
        Log::info('working');

        $startDate = Carbon::now('Europe/Moscow');
        $endDate = Carbon::now('Europe/Moscow')->addHours(2);

        $schedule = Schedule::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('notified', false)
            ->first();

        if ($schedule) {
            $participants = Participants::query()
                ->where('subscribed', true)
                ->get();

            if ($participants->isEmpty()) {
                return self::SUCCESS;
            }

            $date = Carbon::createFromFormat('Y-m-d H:i:s', $schedule->created_at)->format('d.m.y H:i');
            $scheduleString = "*Предмет:* {$schedule->subject}\n*Тип занятия:* {$schedule->theme}\n*Дата и время:* {$date}\n*Ссылка на занятие:* {$schedule->link}\n\n";

            foreach ($participants as $participant) {
                TelegramFacade::sendMessage([
                    'chat_id' => $participant->chat_id,
                    'text' => "Скоро будет занятие:\n\n{$scheduleString}",
                    'parse_mode' => 'markdown'
                ]);
            }

            Schedule::query()->where('id', $schedule->id)->update(['notified', true]);
        }

        return self::SUCCESS;
    }
}
