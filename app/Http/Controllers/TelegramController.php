<?php


namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Participants;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Telegram\Bot\Api;
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

        Log::query()->create(['chat_id' => $this->update->message->chat->id, 'message' => $this->update->message->text]);

        return $this->update->isType('callback_query') ? $this->command() : $this->base();
    }

    private function command(): JsonResponse
    {
        switch ($this->update->callbackQuery->data) {
            case 'subscribe':
                $this->subscribe();
                break;
            case 'unsubscribe':
                $this->unsubscribe();
                break;
            case 'schedule':
                $this->schedule();
                break;
            case 'start' || '/start':
                $this->newMessage();
                break;
        }

        return response()->json(['success' => true]);
    }

    private function base(): JsonResponse
    {
        $participant = Participants::query()->where('chat_id', $this->update->message->chat->id)->first();
        if ($participant) {
            return $this->baseMessage();
        }

        return $this->newMessage();
    }

    private function unsubscribe()
    {
        $keyboard = [
            ['text' => 'Подписаться', 'callback_data' => 'subscribe'],
            ['text' => 'Расписание на ближайшую неделю', 'callback_data' => 'schedule'],
        ];

        $replyMarkup = [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        Participants::query()->updateOrCreate(['chat_id' => $this->update->message->chat->id], ['subscribed' => false]);
        TelegramFacade::sendMessage([
            'chat_id' => $this->update->message->chat->id,
            'text' => "*Вы отписались от уведомлений.*",
            'reply_markup' => json_encode($replyMarkup),
            'parse_mode' => 'markdown'
        ]);
    }

    private function subscribe()
    {
        $keyboard = [
            ['text' => 'Отписаться', 'callback_data' => 'unsubscribe'],
            ['text' => 'Расписание на ближайшую неделю', 'callback_data' => 'schedule'],
        ];

        $replyMarkup = [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        Participants::query()->updateOrCreate(['chat_id' => $this->update->message->chat->id], ['subscribed' => true]);
        TelegramFacade::sendMessage([
            'chat_id' => $this->update->message->chat->id,
            'text' => "*Вы подписались на уведомления.* Уведомление о начале занятий будет приходить за 2 часа.",
            'reply_markup' => json_encode($replyMarkup),
            'parse_mode' => 'markdown'
        ]);
    }

    private function schedule()
    {
        $startDate = Carbon::now('Europe/Moscow');
        $endDate = Carbon::now('Europe/Moscow')->addWeek()->endOfDay();

        $schedule = Schedule::query()->whereBetween('created_at', [$startDate, $endDate])->get();
        if ($schedule->isEmpty()) {
            TelegramFacade::sendMessage([
                'chat_id' => $this->update->message->chat->id,
                'text' => "Расписание на ближайшую неделю отсутствует",
            ]);

            return;
        }

        $scheduleString = "";
        foreach ($schedule as $item) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $item->created_at)->format('d.m.y H:i:s');
            $scheduleString .= "*Предмет:* {$item->subject}\n*Тип занятия:* {$item->theme}\n*Дата и время:* {$date}\n*Ссылка на занятие:* {$item->link}\n\n";
        }

        TelegramFacade::sendMessage([
            'chat_id' => $this->update->message->chat->id,
            'text' => "Расписание на неделю:\n\n{$scheduleString}",
            'parse_mode' => 'markdown'
        ]);
    }

    private function newMessage(): JsonResponse
    {
        $keyboard = [
            ['text' => 'Подписаться', 'callback_data' => 'subscribe'],
            ['text' => 'Отписаться', 'callback_data' => 'unsubscribe'],
            ['text' => 'Расписание на ближайшую неделю', 'callback_data' => 'schedule'],
        ];

        $replyMarkup = [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        TelegramFacade::sendMessage([
            'chat_id' => $this->update->message->chat->id,
            'text' => "Здрасьте.\n\n
            *У этого бота 3 функции:*\n
            1. Подписаться на уведомления о начале занятия. Такое уведомление придёт за 2 часа до начала занятий.
            2. Отписаться от уведомлений. Уведомления приходить не будут (это мой любимый вариант).\n
            3. Получить расписание на неделю.\n\n
            В общем то и всё. *Выбирай одну из трёх кнопок.*
            ",
            'reply_markup' => json_encode($replyMarkup),
            'parse_mode' => 'markdown'
        ]);

        return response()->json(['success' => true]);
    }

    private function baseMessage(): JsonResponse
    {
        $joke = json_decode(file_get_contents('http://rzhunemogu.ru/RandJSON.aspx?CType=1'));

        TelegramFacade::sendMessage([
            'chat_id' => $this->update->message->chat->id,
            'text' => "*Не знаю, что тебе ответить, поэтому, вот тебе анекдот для людей за 40:*\n\n{$joke->content}",
            'parse_mode' => 'markdown'
        ]);

        return response()->json(['success' => true]);
    }
}
