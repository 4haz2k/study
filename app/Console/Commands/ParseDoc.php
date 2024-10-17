<?php


namespace App\Console\Commands;


use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ParseDoc extends Command
{
    protected $signature = 'parse:schedule';

    protected $description = 'Сохранить документ с расписанием';

    public function handle()
    {
        $csvFile = resource_path('ras4.csv');

        $result = $this->readCSV($csvFile, ['delimiter' => ';']);

        unset($result[74]);

        $toInsert = [];
        foreach ($result as $value) {
            $date = $value[3] . ' ' . $value[2];
            $toInsert[] = [
                'subject' => $value[4],
                'theme' => $value[5],
                'link' => $value[7],
                'created_at' => Carbon::createFromFormat('d.m.Y H:i', $date)->toDateTimeString(),
                'teacher' => $value[9],
//                    'building' => empty($value[10]) ? null : $value[10]
                'building' => null
            ];
        }

        Schedule::query()->truncate();
        Schedule::query()->insert($toInsert);
    }

    private function readCSV($csvFile, $options): array
    {
        $file_handle = fopen($csvFile, 'r');

        $line_of_text = [];

        while (!feof($file_handle)) {
            $line_of_text[] = fgetcsv($file_handle, 0, $options['delimiter']);
        }

        fclose($file_handle);

        unset($line_of_text[16]);

        return $line_of_text;
    }
}
