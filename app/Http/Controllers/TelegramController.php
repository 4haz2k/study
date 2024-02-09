<?php


namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Participants;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Traits\Telegram;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;

class TelegramController extends Controller
{
    use Telegram;

    /**
     * @var Update
     */
    protected $update;

    public function handle(): JsonResponse
    {
        $this->setTelegram(new Api(env('TOKEN')));
        $this->update = $this->getTelegram()->commandsHandler(true);

        Log::create(['chat_id' => $this->update->message->chat->id, 'message' => $this->update->message->text]);

        return $this->base();
    }

    private function base(): JsonResponse
    {
        switch ($this->update->message->text) {
            case 'Подписаться':
                $this->subscribe();
                break;
            case 'Отписаться':
                $this->unsubscribe();
                break;
            case 'Расписание на неделю':
                $this->schedule();
                break;
            case '/start':
            case 'start':
                return $this->newMessage();
            default:
                return $this->baseMessage();
        }

        return response()->json(['success' => true]);
    }

    private function unsubscribe()
    {
        $keyboard = Keyboard::make()
            ->row(
                Keyboard::button(['text' => 'Подписаться'])
            )
            ->row(
                Keyboard::button(['text' => 'Расписание на неделю'])
            )
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(false);

        Participants::updateOrCreate(['chat_id' => $this->update->message->chat->id], ['subscribed' => false]);
        TelegramFacade::sendMessage([
            'chat_id' => $this->update->message->chat->id,
            'text' => "<b>Вы отписались от уведомлений.</b>",
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    private function subscribe()
    {
        $keyboard = Keyboard::make()
            ->row(
                Keyboard::button(['text' => 'Отписаться'])
            )
            ->row(
                Keyboard::button(['text' => 'Расписание на неделю'])
            )
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(false);

        Participants::updateOrCreate(['chat_id' => $this->update->message->chat->id], ['subscribed' => true]);
        TelegramFacade::sendMessage([
            'chat_id' => $this->update->message->chat->id,
            'text' => "<b>Вы подписались на уведомления.</b> Уведомление о начале занятий будет приходить за 2 часа.",
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    private function schedule()
    {
        $startDate = Carbon::now()->addHours(3);
        $endDate = Carbon::now()->addHours(3)->addWeek()->endOfDay();

        $schedule = Schedule::whereBetween('created_at', [$startDate, $endDate])->get();
        if ($schedule->isEmpty()) {
            TelegramFacade::sendMessage([
                'chat_id' => $this->update->message->chat->id,
                'text' => "Расписание на ближайшую неделю отсутствует",
            ]);

            return;
        }

        $scheduleString = "";
        foreach ($schedule as $item) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $item->created_at)->format('d.m.y H:i');
            if ($item->building) {
                $scheduleString .= "<b>Предмет:</b> {$item->subject}</b>\nТип занятия:</b> {$item->theme}\n<b>Дата и время:</b> {$date}\n<b>Преподаватель:</b> {$item->teacher}\n<b>Здание КАИ:</b> {$item->building}\n<b>Аудитория:</b> {$item->link}\n\n";
            } else {
                $scheduleString .= "<b>Предмет:</b> {$item->subject}\n<b>Тип занятия:</b> {$item->theme}\n<b>Дата и время:</b> {$date}\n<b>Преподаватель:</b> {$item->teacher}\n<b>Ссылка на занятие:</b> {$item->link}\n\n";
            }
        }

        TelegramFacade::sendMessage([
            'chat_id' => $this->update->message->chat->id,
            'text' => "Расписание на неделю:\n\n{$scheduleString}",
            'parse_mode' => 'html'
        ]);
    }

    private function newMessage(): JsonResponse
    {
        $keyboard = Keyboard::make()
            ->row(
                Keyboard::button(['text' => 'Подписаться']),
                Keyboard::button(['text' => 'Отписаться']),
            )
            ->row(
                Keyboard::button(['text' => 'Расписание на неделю'])
            )
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(false);

        TelegramFacade::sendMessage([
            'chat_id' => $this->update->message->chat->id,
            'text' => "Привет.\n\n<b>У этого бота 3 функции:</b>\n1. Подписаться на уведомления о начале занятия. Такое уведомление придёт за 2 часа до начала занятий.\n2. Отписаться от уведомлений. Уведомления приходить не будут (это мой любимый вариант).\n3. Получить расписание на неделю.\n\nВ общем то и всё. <b>Выбирай одну из трёх кнопок.</b>",
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);

        return response()->json(['success' => true]);
    }

    private function baseMessage(): JsonResponse
    {
        $joke = mb_convert_encoding(file_get_contents('http://rzhunemogu.ru/RandJSON.aspx?CType=1'), "utf-8", "windows-1251");

        $joke = str_replace(['{"content":"', '"}'], '', $joke);

        TelegramFacade::sendMessage([
            'chat_id' => $this->update->message->chat->id,
            'text' => "<b>Не знаю, что тебе ответить, поэтому, вот тебе анекдот для людей за 40:</b>\n\n{$joke}",
            'parse_mode' => 'html'
        ]);

        return response()->json(['success' => true]);
    }
}
