<?php

namespace App\Console\Commands;

use App\Models\Participants;
use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Telegram\Bot\Traits\Telegram;

class Sender extends Command
{
    use Telegram;

    protected $signature = 'schedule:send';

    public function handle(): int
    {
        $startDate = Carbon::now()->addHours(2);
        $endDate = Carbon::now()->addHours(5);

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
            if ($schedule->building) {
                $scheduleString = "<b>Предмет:</b> {$schedule->subject}</b>\nТип занятия:</b> {$schedule->theme}\n<b>Дата и время:</b> {$date}\n<b>Преподаватель:</b> {$schedule->teacher}\n<b>Здание КАИ:</b> {$schedule->building}\n<b>Аудитория:</b> {$schedule->link}\n\n";
            } else {
                $scheduleString = "<b>Предмет:</b> {$schedule->subject}\n<b>Тип занятия:</b> {$schedule->theme}\n<b>Дата и время:</b> {$date}\n<b>Преподаватель:</b> {$schedule->teacher}\n<b>Ссылка на занятие:</b> {$schedule->link}\n\n";
            }

            foreach ($participants as $participant) {
                try {
                    TelegramFacade::sendMessage([
                        'chat_id' => $participant->chat_id,
                        'text' => "Скоро будет занятие:\n\n{$scheduleString}",
                        'parse_mode' => 'html'
                    ]);
                } catch (\Throwable $exception) {}
            }

            Schedule::query()->where('id', $schedule->id)->update(['notified' => true]);
        }

        return self::SUCCESS;
    }
}
